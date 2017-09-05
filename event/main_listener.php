<?php
/**
 * Akismet Event Listener
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
				'core.posting_modify_submit_post_before' => 'check_submitted_post',
				'core.notification_manager_add_notifications' => 'add_akismet_details_to_notification'
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
			if ($this->is_spam($event['data']))
			{
				// Whatever the post status was before, this will override it
				// and mark it as unapproved.
				$data['force_approved_state'] = ITEM_UNAPPROVED;
				// This will be used by our notification event listener to
				// figure out that the post was moderated by Akismet.
				$data['gothick_akismet_unapproved'] = true;
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

	/**
	 * Check for spam using our handy client. I hear it was written by
	 * a talented and ruggedly-handsome programmer.
	 * 
	 * @param array $data Data array from event that triggered us.
	 */
	private function is_spam($data)
	{
		$is_spam = false;

		// Akismet fields
		$params = array();
		$params['comment_content'] = $data['message'];
		$params['comment_author_email'] = $this->user->data['user_email'];
		$params['comment_author'] = $this->user->data['username_clean'];

		// URL of poster, i.e. poster's "website" profile field.
		$this->user->get_profile_fields($this->user->data['user_id']);
		$url = isset($this->user->profile_fields['pf_phpbb_website']) ? $this->user->profile_fields['pf_phpbb_website'] : '';

		$params['comment_author_url'] = $url;

		// URL of topic
		$params['permalink'] = generate_board_url() . '/' . append_sid(
				"viewtopic.{$this->php_ext}",
				"t={$data['topic_id']}",
				true,
				''
		);
		// 'forum-post' recommended for type:
		// http://blog.akismet.com/2012/06/19/pro-tip-tell-us-your-comment_type/
		$params['comment_type'] = 'forum-post';

		/* @var $akismet \Gothick\AkismetClient\Client */
		$akismet = $this->phpbb_container->get(
			'gothick.akismet.client',
			ContainerInterface::NULL_ON_INVALID_REFERENCE
		);

		// We can't just pass $_SERVER in to our Akismet client as phpBB turns off super globals (which is,
		// of course, fair enough. Interrogate our request object instead, grabbing as many relevant
		// things as we can, excluding anything that might leak anything sensitive to Akismet (bear in
		// mind we're already throwing all the user details and the entire contents of their comment
		// at Akismet, so it's more our server details I'm worrying about.)

		// "This data is highly useful to Akismet. How the submitted content interacts with the server can 
		// be very telling, so please include as much of it as possible."
		// https://akismet.com/development/api/#comment-check
		$server_vars = array(
			// TODO: vet these and consider adding more after looking at what's actually in the $_SERVER
			// variable for a typical request to our fairly typical server.
			'GATEWAY_INTERFACE',
			'SERVER_ADDR',
			'SERVER_NAME',
			'SERVER_PROTOCOL',
			'REQUEST_METHOD',
			'REQUEST_TIME',
			'REQUEST_TIME_FLOAT',
			'QUERY_STRING',
			'HTTP_ACCEPT',
			'HTTP_ACCEPT_CHARSET',
			'HTTP_ACCEPT_ENCODING',
			'HTTP_ACCEPT_LANGUAGE',
			'HTTP_CONNECTION',
			'HTTP_HOST',
			'HTTP_REFERER',
			'HTTP_USER_AGENT',
			'HTTPS',
			'REMOTE_ADDR',
			'REMOTE_HOST',
			'REMOTE_PORT',
			'REMOTE_USER',
			'REDIRECT_REMOTE_USER',
			'SCRIPT_FILENAME',
			'SERVER_PORT',
			'SERVER_SIGNATURE',
			'PATH_TRANSLATED',
			'SCRIPT_NAME',
			'REQUEST_URI',
			'PHP_AUTH_DIGEST',
			'PHP_AUTH_USER',
			'PHP_AUTH_PW',
			'AUTH_TYPE',
			'PATH_INFO',
			'ORIG_PATH_INFO'
		);

		// Try to recreate $_SERVER. 
		$server = array();
		foreach ($server_vars as $var)
		{
			$value = $this->request->server($var, null);
			if ($value != null)
			{
				$server[$var] = $value;
			}
		}

		// Our factory may not have returned an Akismet object if there
		// was no API key set in the configuration, so we still need to
		// check if one was passed back to us. It's okay if one wasn't;
		// the factory method will log an error, and we'll silently not
		// check for spam. We don't want every post to the board to
		// be marked as spam in between installing the extension and the
		// administrator configuring the API key!
		if (isset($akismet))
		{
			try
			{
				$is_spam = $akismet->commentCheck(
						$this->user->ip,
						// TODO: Check this is sending the right thing; we need the user's full User-Agent string
						$this->user->browser,
						$params,
						$server);
				// TODO: Also available from our result object is isBlatantSpam, indicating something
				// so obviously spammy that it can be silently discarded without human intervention. 
				// Might want to do something more extreme with those.
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
		}
		return $is_spam;
	}

	/**
	 * We send out customised versions of the standard post_in_queue and
	 * topic_in_queue notifications so that people can tell that the reason
	 * for queueing was an Akismet spam detection rather than any other
	 * reason.
	 *
	 * @param unknown $event
	 */
	public function add_akismet_details_to_notification ($event)
	{
		if ($event['notification_type_name'] == 'notification.type.post_in_queue' ||
			$event['notification_type_name'] == 'notification.type.topic_in_queue')
		{
			$data = $event['data'];
			if (isset($data['gothick_akismet_unapproved']))
			{
				$event['notification_type_name'] = 'gothick.akismet.' . $event['notification_type_name'];
			}
			$event['data'] = $data;
		}
	}
}
