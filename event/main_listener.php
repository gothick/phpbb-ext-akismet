<?php
/**
 *
 * @package phpBB Extension - Akismet
 * @copyright (c) 2015 Matt Gibson gothick@gothick.org.uk
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */
namespace gothick\akismet\event;

// TODO: Remove this when we've got a configuration interface
require_once (__DIR__ . '/api_key.php');

/**
 *
 * @ignore
 *
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 * @ignore
 *
 */
use TijsVerkoyen\Akismet\Akismet;

/**
 * Event listener
 */
class main_listener implements EventSubscriberInterface
{

	static public function getSubscribedEvents ()
	{
		return array(
				'core.user_setup' => 'load_language_on_setup',
				'core.posting_modify_submit_post_before' => 'check_submitted_post'
		);
	}

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @var \phpbb\request\request */
	protected $request;

	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\log\log */
	protected $log;

	/* @var \phpbb\user_loader */
	protected $user_loader;

	/* @var \TijsVerkoyen\Akismet */
	protected $akismet;
	// Third-party client library
	

	/* @var \messenger */
	protected $messenger;

	protected $akismet_user_data;

	/**
	 * Constructor
	 *
	 * @param \phpbb\controller\helper $helper
	 *        	object
	 * @param \phpbb\template $template        	
	 * @param \phpbb\user $user        	
	 * @param \phpbb\request\request $request        	
	 * @param \phpbb\config\config $request        	
	 * @param \phpbb\log\log $log        	
	 * @param \phpbb\user_loader $user_loader        	
	 */
	public function __construct (\phpbb\controller\helper $helper, 
			\phpbb\template\template $template, \phpbb\user $user, 
			\phpbb\request\request $request, \phpbb\config\config $config, 
			\phpbb\log\log $log, \phpbb\user_loader $user_loader)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->config = $config;
		$this->log = $log;
		$this->user_loader = $user_loader;

		// To allow super-globals when we call our third-party Akismet library.
		$this->request = $request; 
		
		// TODO: Should this be injected?
		// TODO: Some kind of (quiet) error logging if the API key isn't set
		if (isset($config['gothick_akismet_api_key']) &&
				 isset($config['gothick_akismet_url']))
		{
			$this->akismet = new Akismet($config['gothick_akismet_api_key'], 
					$config['gothick_akismet_url']);
			
			// We log, send mail, etc. as our Akismet user, and we want the
			// results to be in their language, not the language of the user
			// who's posting.
			if (isset($config['gothick_akismet_user_id']))
			{
				$akismet_user_id = filter_var(
						$config['gothick_akismet_user_id'], FILTER_VALIDATE_INT);
				if ($akismet_user_id !== false)
				{
					// We load the Akismet user's common language file, plus
					// this extension's
					// langauge file. That way we can send emails/log messages
					// in the target
					// user's language, not the current user's language.
					$this->akismet_user_data = $user_loader->get_user(
							$akismet_user_id, true);
				}
			} else
			{
				// TODO: Should this be an error?
			}
			
			// For email sending
			if ($this->config['email_enable'])
			{
				if (! class_exists('messenger'))
				{
					global $phpbb_root_path, $phpEx;
					include ($phpbb_root_path . 'includes/functions_messenger.' .
							 $phpEx);
					$this->messenger = new \messenger(false);
				}
			}
		}
	}

	public function load_language_on_setup ($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
				'ext_name' => 'gothick/akismet',
				'lang_set' => 'common'
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function add_page_header_link ($event)
	{
		$this->template->assign_vars(
				array(
						'U_DEMO_PAGE' => $this->helper->route(
								'gothick_akismet_controller', 
								array(
										'name' => 'world'
								))
				));
	}

	protected function send_mail ($post_data)
	{
		// TODO: What we should *really* do for emails is to use something like
		// a phpBB 3.1 version of a mod like Board Watch. Then someone else would do 
		// the heavy lifting.
		// However, it looks like if we want that, we'll have to do it ourselves:
		// https://www.phpbb.com/customise/db/mod/board_watch/support/topic/131696
		

		// We may not have messenger, if, for example, the board has email
		// disabled.
		if (isset($this->messenger))
		{
			// If we have a nominated Akismet user, we send them an email to let
			// them
			// know the message has been marked as spam:
			if (isset($this->akismet_user_data))
			{
				$this->messenger->template(
						'@gothick_akismet/message_marked_as_spam', 
						$this->akismet_user_data['user_lang']);
				$this->messenger->to($this->akismet_user_data['user_email'], 
						$this->akismet_user_data['username']);
				$this->messenger->im($this->akismet_user_data['user_jabber'], 
						$this->akismet_user_data['username']);
				$this->messenger->assign_vars(
						array(
								'TOPIC_TITLE' => $post_data['topic_title'],
								'POSTING_USER_USERNAME' => $this->user->data['username'],
								'POSTING_USER_URL' => generate_board_url() .
										 '/memberlist.php?mode=viewprofile&u=' .
										 $this->user->data['user_id'],
										'POST_TEXT' => $post_data['message']
						));
				// TODO: Issue #2: Internationalise "Forum spam detected from user". 
				// Bear in mind that this should be internationalised to the 
				// *recipient* of the email, i.e. the Akismet user, *not* 
				// $this->user.
				// https://github.com/gothick/phpbb-ext-akismet/issues/2
				$this->messenger->subject(
						'Forum spam detected from user ' .
								 $this->user->data['username_clean']);
				$this->messenger->headers(
						'X-AntiAbuse: User IP - ' . $this->user->ip);
				
				$this->messenger->send(
						$this->akismet_user_data['user_notify_type']);
				$this->messenger->save_queue();
			}
		}
	}

	public function check_submitted_post ($event)
	{
		// TODO: Some kind of (quiet) error logging if the Akismet object hasn't
		// been created (might happen if API key isn't set.) Admin log?
		// Use
		// https://github.com/ForumHulp/errorpages/blob/master/event/listener.php
		// for logging example
		if (isset($this->akismet))
		{
			
			$data = $event['data'];
			
			// Akismet fields
			$content = $data['message'];
			// TODO: Should we be using $data['poster_id'] instead? I think if
			// we only check on submission, then the current $user should be fine.
			$email = $this->user->data['user_email'];
			$author = $this->user->data['username_clean'];

			// URL of poster, i.e. poster's "website" profile field.
			$this->user->get_profile_fields($this->user->data['user_id']);
			$url = isset($this->user->profile_fields['pf_phpbb_website']) ? $this->user->profile_fields['pf_phpbb_website'] : '' ;
			
			// URL of topic
			global $phpEx;
			$permalink = generate_board_url() . '/' . append_sid("viewtopic.$phpEx", "t={$data['topic_id']}" , true, '');
			
			// TODO: Issue #1: Should we find a way of avoiding enable_super_globals()?
			// https://github.com/gothick/phpbb-ext-akismet/issues/1
			// https://www.phpbb.com/community/viewtopic.php?f=461&t=2270496
			$this->request->enable_super_globals();
			
			$is_spam = false;
			try
			{
				// 'forum-post' recommended for type:
				// http://blog.akismet.com/2012/06/19/pro-tip-tell-us-your-comment_type/
				$is_spam = $this->akismet->isSpam($content, $author, $email, 
						$url, $permalink, 'forum-post');
			} // TODO: The Akismet class actually throws its own
			// TijsVerkoyen\Akismet\Exception. Should
			// we be checking for that/
			catch (\Exception $e)
			{
				// If Akismet's down, or there's some other problem like that,
				// we'll give the post the benefit of the doubt, but log a 
				// warning.
				$this->log->add('mod', 
						$this->akismet_user_data['user_id'], 
						$this->user->data['session_ip'], 
						'AKISMET_LOG_CALL_FAILED', 
						false,
						array (
							$e->getMessage()	
						) );
			}
			
			$this->request->disable_super_globals();
			
			if ($is_spam)
			{
				// Whatever the post status was before, this will override it
				// and mark it as unapproved.
				$data['force_approved_state'] = ITEM_UNAPPROVED;
				$event['data'] = $data;
				
				// Note our action in the moderation log
				if ($event['mode'] == 'post' ||
						($event['mode'] == 'edit' &&
						$data['topic_first_post_id'] == $data['post_id']))
				{
					$log_message = 'LOG_TOPIC_DISAPPROVED';
				}
				else
				{
					$log_message = 'LOG_POST_DISAPPROVED';
				}
				
				$akismet_user_id = isset($this->akismet_user_data) ? $this->akismet_user_data['user_id'] : $this->user->data['user_id'];
				$akismet_username = isset($this->akismet_user_data) ? $this->akismet_user_data['username'] : $this->user->data['username'];
				
				$this->log->add('mod', $this->akismet_user_data['user_id'], 
						$this->user->data['session_ip'], $log_message, false,  // Logger
						// will
						// provide the
						// time
						array(
								$data['topic_title'],
								// TODO: We should log in the language of the
								// nominated Akismet user. This has
								// been a nightmare to figure out, though, and
								// got very messy, so we're just
								// going to log stuff in the language of the
								// posting user for now. This
								// should be okay for most boards, as it's only
								// in multilingual boards
								// where the user's language would be different
								// from a board admin's
								// language. Revisit when (if?) phpBB makes this
								// easier.
								$this->user->lang('AKISMET_DISAPPROVED'),
								$this->user->data['username']
						));
				
				$this->send_mail($data);
			}
		}
	}
}
