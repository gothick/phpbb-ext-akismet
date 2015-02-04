<?php
/**
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2015 Matt Gibson Creative Ltd.
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
				'core.posting_modify_submit_post_before' => 'check_submitted_post'
		);
	}

	/* @var \phpbb\user */
	protected $user;

	/* @var \phpbb\request\request */
	protected $request;

	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\log\log_interface */
	protected $log;

	/* @var \phpbb\auth\auth */
	protected $auth;

	/* @var \Symfony\Component\DependencyInjection\ContainerInterface */
	protected $phpbb_container;

	/* @var \messenger */
	protected $messenger;

	protected $php_ext;

	protected $root_path;

	/**
	 * Constructor
	 *
	 * Lightweight initialisation of the API key and user ID.
	 * Heavy lifting is done only if the user actually tries
	 * to post a message.
	 *
	 * @param \phpbb\user $user
	 * @param \phpbb\request\request $request
	 * @param \phpbb\config\config $config
	 * @param \phpbb\log\log_interface $log
	 * @param \phpbb\auth\auth $auth
	 * @param string $php_ext
	 * @param string $root_path
	 */
	public function __construct (\phpbb\user $user,
			\phpbb\request\request $request, \phpbb\config\config $config,
			\phpbb\log\log_interface $log,
			\phpbb\auth\auth $auth,
			\Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container,
			$php_ext, $root_path)
	{
		$this->user = $user;
		$this->config = $config;
		$this->log = $log;
		$this->auth = $auth;
		$this->phpbb_container = $phpbb_container;
		$this->php_ext = $php_ext;
		$this->root_path = $root_path;
		// To allow super-globals when we call our third-party Akismet library.
		$this->request = $request;
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

			// Load our third-party library. We use a factory method that
			// reads the configured API key from this extension's settings,
			// as the third-party library takes the API key as a constructor
			// parameter. (The factory method means we can also create
			// a mock Akismet client library for testing.)

			/* @var $akismet \TijsVerkoyen\Akismet */
			$akismet = $this->phpbb_container->get(
					'gothick.akismet.tijsverkoyen.akismet',
					ContainerInterface::NULL_ON_INVALID_REFERENCE);

			// Our factory may not have returned an Akismet object if there
			// was no API key set in the configuration, so we still need to
			// check if one was passed back to us. It's okay if one wasn't;
			// the factory method will log an error, and we'll silently not
			// check for spam. We don't want every post to the board to
			// be marked as spam in between installing the extension and the
			// administrator configuring the API key!
			if (isset($akismet))
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
						"viewtopic.{$this->php_ext}",
						"t={$data['topic_id']}",
						true,
						''
					);

				// The phpBB team have indicated that this use of enable_super_globals()
				// doesn't seem like a terrible thing, given it's a workaround for a
				// third-party library I'm bringing in as-is with Composer.
				// https://www.phpbb.com/community/viewtopic.php?f=461&t=2290231#p13918466
				$this->request->enable_super_globals();

				$is_spam = false;

				try
				{
					// 'forum-post' recommended for type:
					// http://blog.akismet.com/2012/06/19/pro-tip-tell-us-your-comment_type/
					$is_spam = $akismet->isSpam(
							$content,
							$author,
							$email,
							$url,
							$permalink,
							'forum-post'
					);
				}
				catch (\Exception $e)
				{
					// If Akismet's down, or there's some other problem like that,
					// we'll give the post the benefit of the doubt, but log a
					// warning.
					$this->log->add('critical',
							$this->user->data['user_id'],
							$this->user->ip,
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
						$log_message = 'AKISMET_LOG_TOPIC_DISAPPROVED';
					}
					else
					{
						$log_message = 'AKISMET_LOG_POST_DISAPPROVED';
					}

					$this->log->add(
							'mod',
							$this->user->data['user_id'],
							$this->user->ip,
							$log_message,
							false,
							array(
									$data['topic_title'],
									$this->user->data['username']
							));
				}
			}
		}
	}
}
