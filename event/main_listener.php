<?php
/**
 *
 * @package phpBB Extension - Akismet
 * @copyright (c) 2015 Matt Gibson gothick@gothick.org.uk
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */
namespace gothick\akismet\event;

/**
 *
 * @ignore
 *
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

	/* @var \phpbb\auth\auth */
	protected $auth;

	/* @var \Symfony\Component\DependencyInjection\ContainerInterface */
	protected $phpbb_container;

	/* @var \TijsVerkoyen\Akismet */
	protected $akismet;

	/* @var \messenger */
	protected $messenger;

	protected $php_ext;

	protected $root_path;

	// Nominated Akismet user's data, so we can, e.g. email them with notifications
	protected $akismet_user_data;

	protected $akismet_user_id;

	/**
	 * Constructor
	 *
	 * Lightweight initialisation of the API key and user ID.
	 * Heavy lifting is done only if the user actually tries
	 * to post a message.
	 *
	 * @param \phpbb\user $user
	 * @param \phpbb\request\request $request
	 * @param \phpbb\config\config $request
	 * @param \phpbb\log\log $log
	 * @param \phpbb\user_loader $user_loader
	 * @param \phpbb\auth\auth $auth
	 * @param string $php_ext
	 * @param string $root_path
	 */
	public function __construct (\phpbb\user $user,
			\phpbb\request\request $request, \phpbb\config\config $config,
			\phpbb\log\log $log, \phpbb\user_loader $user_loader,
			\phpbb\auth\auth $auth,
			\Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container,
			$php_ext, $root_path)
	{
		$this->user = $user;
		$this->config = $config;
		$this->log = $log;
		$this->user_loader = $user_loader;
		$this->auth = $auth;
		$this->phpbb_container = $phpbb_container;
		$this->php_ext = $php_ext;
		$this->root_path = $root_path;
		// To allow super-globals when we call our third-party Akismet library.
		$this->request = $request;

		if (! empty($config['gothick_akismet_user_id']))
		{
			$this->akismet_user_id = $config['gothick_akismet_user_id'];
		}
	}

	/**
	 * Prepares our Akismet library and other items (messenger, etc.)
	 *
	 * We only need these objects if someone's actually going to
	 * post to the board, so we set them up on demand rather than
	 * in the constructor. (Also, it means that if there's no API
	 * key configured, we only log an error on every attempted
	 * post, not on every page view!)
	 *
	 * @return bool true if Akismet is now ready to use.
	 */
	protected function prepare_for_akismet ()
	{
		if (isset($this->akismet))
		{
			// Already done.
			return true;
		}

		// Load our third-party library. We use a factory method that
		// reads the configured API key from this extension's settings,
		// as the third-party library takes the API key as a constructor
		// parameter. (The factory method means we can also create
		// a mock Akismet client library for testing.)
		$this->akismet = $this->phpbb_container->get(
				'gothick.akismet.tijsverkoyen.akismet',
				ContainerInterface::NULL_ON_INVALID_REFERENCE);

		// Our factory may not have returned an Akismet object if there
		// was no API key set in the configuration, so we still need to
		// check if one was passed back to us. It's okay if one wasn't;
		// the factory method will log an error, and we'll silently not
		// check for spam. We don't want every post to the board to
		// be marked as spam in between installing the extension and the
		// administrator configuring the API key!
		if (isset($this->akismet))
		{

			// We log, send mail, etc. as our Akismet user.
			$this->akismet_user_data = $this->get_akismet_user_data(
					$this->akismet_user_id);

			// For email sending
			if ($this->config['email_enable'])
			{
				if (! class_exists('messenger'))
				{
					include ($this->root_path . 'includes/functions_messenger.' .
							$this->php_ext);
					$this->messenger = new \messenger(false);
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Loads the standard user data array for a specified user.
	 * We use this to grab
	 * information about our nominated Akismet user; mail will be sent to their
	 * email address, etc.
	 *
	 * @param string $user_id
	 *        	User id to fetch data for
	 * @return array Standard $user->data[] of the user, or of ANONYMOUS if not
	 *         found.
	 *
	 */
	protected function get_akismet_user_data ($user_id)
	{
		// We default to the anonymous user as a fallback
		$akismet_user_id = ANONYMOUS;

		// But if there's a user configured in the Extension settings, we try
		// to use it instead.
		if (! empty($user_id))
		{
			$better_akismet_user_id = filter_var($user_id, FILTER_VALIDATE_INT);
			if ($better_akismet_user_id !== false)
			{
				$akismet_user_id = $better_akismet_user_id;
			}
		}

		// get_user() will fall back to ANONYMOUS if the user doesn't exist...
		$akismet_user_data = $this->user_loader->get_user($akismet_user_id,
				true);
		// ...so we may end up with a different user_id from the one we asked for
		$akismet_user_id = $akismet_user_data['user_id'];

		// If, after all that, we're still using the anonymous user, log it as an issue.
		if ($akismet_user_id == ANONYMOUS)
		{
			$this->log->add('critical', ANONYMOUS,
					$this->user->data['session_ip'],
					'AKISMET_LOG_USING_ANONYMOUS_USER');
		}
		return $akismet_user_data;
	}

	/**
	 * Loads up our (minimal) language entries.
	 *
	 * @param unknown $event
	 */
	public function load_language_on_setup ($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
				'ext_name' => 'gothick/akismet',
				'lang_set' => 'akismet'
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * If a post is detected as spam, we send a notification to our nominated
	 * Akismet user.
	 *
	 * @param array $post_data
	 */
	protected function send_moderator_notification ($post_data)
	{
		// TODO: Issue #3 We should *really* use something like a phpBB 3.1 version
		// of a mod like Board Watch. Then someone else would do the heavy lifting.
		// However, it looks like if we want that, we'll have to do it ourselves:
		// https://www.phpbb.com/customise/db/mod/board_watch/support/topic/131696

		// We may not have messenger, if, for example, the board has email
		// disabled.
		if (isset($this->messenger))
		{
			// There's perhaps a chance we don't have a nominated
			// Akismet user set up. Quietly fail if we don't.
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
				// to the language of the *recipient* of the email, i.e. the Akismet
				// user, *not* $this->user.
				// https://github.com/gothick/phpbb-ext-akismet/issues/2
				$this->messenger->subject(
						$this->user->lang['FORUM_SPAM_DETECTED_FROM_USER'] . ' ' .
								$this->user->data['username_clean']);
				$this->messenger->headers(
						'X-AntiAbuse: User IP - ' . $this->user->ip);

				$this->messenger->send(
						$this->akismet_user_data['user_notify_type']);
				$this->messenger->save_queue();
			}
		}
	}

	/**
	 * The main event.
	 * When a post is submitted, we do several checks: is the
	 * user an admin or moderator (instant approval), does it fail the Akismet
	 * isSpam check, etc. On a failure we mark the post as not approved and
	 * log and notify.
	 *
	 * @param unknown $event
	 */
	public function check_submitted_post ($event)
	{
		// Skip the Akismet check for anyone who's a moderator or an administrator. If your
		// admins and moderators are posting spam, you've got bigger problems...
		if (! ($this->auth->acl_getf_global('m_') ||
				$this->auth->acl_getf_global('a_')))
		{
			if ($this->prepare_for_akismet())
			{

				$data = $event['data'];

				// Akismet fields
				$content = $data['message'];
				$email = $this->user->data['user_email'];
				$author = $this->user->data['username_clean'];

				// URL of poster, i.e. poster's "website" profile field.
				$this->user->get_profile_fields($this->user->data['user_id']);
				$url = isset($this->user->profile_fields['pf_phpbb_website']) ? $this->user->profile_fields['pf_phpbb_website'] : '';

				// URL of topic
				$permalink = generate_board_url() . '/' . append_sid(
						"viewtopic.{$this->php_ext}", "t={$data['topic_id']}",
						true, '');

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
				} catch (\Exception $e)
				{
					// If Akismet's down, or there's some other problem like that,
					// we'll give the post the benefit of the doubt, but log a
					// warning.
					$this->log->add('critical',
							$this->akismet_user_data['user_id'],
							$this->user->data['session_ip'],
							'AKISMET_LOG_CALL_FAILED', false,
							array(
									$e->getMessage()
							));
				}

				$this->request->disable_super_globals();

				if ($is_spam)
				{
					// Whatever the post status was before, this will override it
					// and mark it as unapproved.
					$data['force_approved_state'] = ITEM_UNAPPROVED;
					$event['data'] = $data;

					// Note our action in the moderation log
					if ($event['mode'] == 'post' || ($event['mode'] == 'edit' &&
							$data['topic_first_post_id'] == $data['post_id']))
					{
						$log_message = 'LOG_TOPIC_DISAPPROVED';
					}
					else
					{
						$log_message = 'LOG_POST_DISAPPROVED';
					}

					$akismet_user_id = $this->akismet_user_data['user_id'];
					$akismet_username = $this->akismet_user_data['username'];

					$this->log->add('mod', $akismet_user_id,
							$this->user->data['session_ip'], $log_message, false,
							array(
									$data['topic_title'],
									// TODO: Issue #2: This log message ("AKISMET_DISAPPROVED")
									// should be in the language of the nominated Akismet user.
									// This has been a nightmare to figure out, though, and
									// got quite messy, so we're just going to log stuff in the
									// language of the posting user for now. This should be okay
									//  for most boards, as it's only in multilingual boards
									// where the user's language would be different from a
									// board admin's language. Revisit when (if?) phpBB makes
									// this easier.
									$this->user->lang('AKISMET_DISAPPROVED'),
									$this->user->data['username']
							));

					$this->send_moderator_notification($data);
				}
			}
		}
	}
}
