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

class main_test extends \phpbb_test_case
{

	public function handle_data ()
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

	// TODO: Test what happens if we don't put an Akismet object in the container. It
	// should fail quietly and just not mark anything as spam.

	/**
	 * @dataProvider handle_data
	 */
	public function test_post_check ($username, $message, $should_pass)
	{
		$phpbb_container = new \phpbb_mock_container_builder();
		$akismet_mock = new \gothick\akismet\tests\mock\akismet_mock();
		$phpbb_container->set('gothick.akismet.client',
				$akismet_mock);

		$request = $this->getMock('\phpbb\request\request');

		$listener = new \gothick\akismet\event\main_listener(
				new \gothick\akismet\tests\mock\user($username),
				$request,
				new \phpbb\config\config(array('gothick_akismet_user_id' => 1)),
				new \phpbb\log\dummy(),
				$this->getMock('\phpbb\auth\auth'),
				$phpbb_container,
				'.php', // $php_ext,
				'./' // $root_path;
			);

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
}
