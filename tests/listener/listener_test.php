<?php
/**
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2015 Matt Gibson Creative Ltd.
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

// We use the same namespace as our event so we can "override" some
// global functions.
namespace gothick\akismet\event;

require_once __DIR__ . '/../../../../../../phpBB/includes/functions.php';
require_once __DIR__ . '/../../../../../../phpBB/includes/functions_user.php';

/**
 * Hide the global append_sid method with one that does rather less, so we don't
 * have to mock up a whole bunch of other stuff.
 */
function append_sid ($url, $params = false, $is_amp = true, $session_id = false,
		$is_route = false)
{
	$url_delim = (strpos($url, '?') === false) ? '?' : (($is_amp) ? '&amp;' : '&');
	return $url . ($params !== false ? $url_delim . $params : '');
}

/**
 * Avoid having to provide a global $request object
 */
function generate_board_url ($without_script_path = false)
{
	return 'http://phpbb.test';
}

class listener_test extends \phpbb_test_case
{
	// TODO: Test what happens if we don't put an Akismet object in the container. It
	// should fail quietly and just not mark anything as spam.

	protected $container;

	public function setUp()
	{
		$this->container = new \phpbb_mock_container_builder();
		// $akismet_mock = new \gothick\akismet\tests\mock\akismet_mock();
		// $phpbb_container->set('gothick.akismet.client', $akismet_mock);
	}

	protected function get_listener($user)
	{
		return new \gothick\akismet\event\main_listener(
				$user,
				$this->getMock('\phpbb\request\request'),
				new \phpbb\config\config([]),
				new \phpbb\log\dummy(),
				$this->getMock('\phpbb\auth\auth'),
				$this->container,
				'.php', // $php_ext,
				'./' // $root_path;
		);
	}


	public function post_data ()
	{
		return array(
				array(
						'viagra-test-123',
						'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
						false
				),
				array(
						'matt',
						'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
						true
				)
		);
	}

	/**
	 * @dataProvider post_data
	 */
	public function test_post_check ($username, $message, $should_pass)
	{
		$listener = $this->get_listener(new \gothick\akismet\tests\mock\user($username));
		$akismet_mock = new \gothick\akismet\tests\mock\akismet_mock();
		$this->container->set('gothick.akismet.client', $akismet_mock);
		$request = $this->getMock('\phpbb\request\request');

		$data = array(
				'data' => array(
						'message' => $message,
						'topic_id' => 123
				)
		);
		$event = new \phpbb\event\data($data);
		$listener->check_submitted_post($event);

		if ($should_pass)
		{
			$this->assertFalse(isset($event['data']['force_approved_state']));
		}
		else
		{
			$this->assertTrue(isset($event['data']['force_approved_state']));
			$this->assertEquals($event['data']['force_approved_state'], ITEM_UNAPPROVED);
		}
	}
	public function test_getSubscribedEvents ()
	{
		$function_map = \gothick\akismet\event\main_listener::getSubscribedEvents();
		$this->assertGreaterThan(0, count($function_map), 'No events subscribed');
		$reflection = new \ReflectionClass(\gothick\akismet\event\main_listener::class);
		foreach ($function_map as $function_name)
		{
			$this->assertTrue($reflection->hasMethod($function_name), 'Event mapped to non-existent function: ' . $function_name);
		}
	}
}
