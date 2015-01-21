<?php
/**
 *
 * @package phpBB Extension - Akismet
 * @copyright (c) 2015 Matt Gibson gothick@gothick.org.uk
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */
namespace gothick\akismet\event;

//TODO: Remove this when we've got a configuration interface
require_once(__DIR__ . '/api_key.php');

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
				'core.page_header' => 'add_page_header_link',
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
	
	// vendor Akismet client library
	protected $akismet;
	
	/**
	 * Constructor
	 *
	 * @param \phpbb\controller\helper $helper
	 *        	object
	 * @param \phpbb\template $template        	
	 * @param \phpbb\user $user    
	 * @param \phpbb\request\request $request    	
	 */
	public function __construct (\phpbb\controller\helper $helper, 
			\phpbb\template\template $template,
	        \phpbb\user $user,
	        \phpbb\request\request $request)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		//TODO: Remove $request when we don't need it to allow access to $_SERVER any more.
		// https://www.phpbb.com/community/viewtopic.php?f=461&t=2270496
		$this->request = $request;
		
		// TODO: Should this be injected?
		// TODO: Make API key configurable rather than hard-coded
		$this->akismet = new Akismet(AKISMET_KEY, AKISMET_URL);
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

	public function check_submitted_post ($event)
	{
	    $data = $event['data'];

	    // Akismet fields
	    $content = $data['message'];
	    // TODO: Should we be using $data['poster_id'] instead? I think if we only check on
	    // submission, then the current $user should be fine.
	    // TODO: Only check on initial submission. not on edit. :D
	    $email = $this->user->data['user_email'];
	    // TODO: Grab actual name from profile if they've 
	    // set it.
	    $author = $this->user->data['username_clean'];
	   
	    // TODO: Might be useful for user's URL: https://www.phpbb.com/community/viewtopic.php?f=461&t=2267121&p=13760226&hilit=username#p13760226 
	    // $this->profilefields->grab_profile_fields_data($user_id)
	    
	    // TODO: 
	    $url = '';
	    $permalink = '';
	    
	    // TODO: Figure out how we can use this nice Akismet library without having to re-enable $_SERVER access
	    // in this hack.
	    // https://www.phpbb.com/community/viewtopic.php?f=461&t=2270496
	    $this->request->enable_super_globals();
	    
	    // 'forum-post' recommended for type: http://blog.akismet.com/2012/06/19/pro-tip-tell-us-your-comment_type/
		$is_spam = $this->akismet->isSpam($content, $author, $email, $url, $permalink, 'forum-post');
		
		$this->request->disable_super_globals();
		
		if ($is_spam) {
		    // Whatever the post status was before, this will override it and mark
		    // it as unapproved.
			$data['force_approved_state'] = ITEM_UNAPPROVED;
			$event['data'] = $data;
		}
	}
}
