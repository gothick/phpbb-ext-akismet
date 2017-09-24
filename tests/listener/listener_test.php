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
	}

	protected function get_listener($user, $config = array())
	{
		return $this
			->getMockBuilder(\gothick\akismet\event\main_listener::class)
			->setConstructorArgs(
					[
							$user,
							$this->getMock('\phpbb\request\request'),
							new \phpbb\config\config($config),
							new \phpbb\log\dummy(),
							$this->getMock('\phpbb\auth\auth'),
							$this->container,
							'php', // $php_ext,
							__DIR__ . '/../../../../../' // $root_path;
					]
					)
			->setMethods(['group_user_add'])
			->getMock();
		//.return new \gothick\akismet\event\main_listener(

	}


	public function post_data ()
	{
		return array(
				array(
						'viagra-test-123',
						'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
						'reply',
						false
				),
				array(
						'viagra-test-123',
						'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
						'post', // Post produces a different log message, so it's a different path.
						false
				),
				array(
						'matt',
						'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
						'reply',
						true
				)
		);
	}

	/**
	 * @dataProvider post_data
	 */
	public function test_post_check ($username, $message, $mode, $should_pass)
	{
		$listener = $this->get_listener(new \gothick\akismet\tests\mock\user($username));
		$akismet_mock = new \gothick\akismet\tests\mock\akismet_mock();
		$this->container->set('gothick.akismet.client', $akismet_mock);

		$data = array(
				'mode' => $mode,
				'data' => array(
						'message' => $message,
						'topic_id' => 123,
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


	public function user_registration_data ()
	{
		return array(
				array(
						[
							'gothick_akismet_check_registrations' => true
						], // Config
						'viagra-test-123', // User name being registered
						false, // "Blatant" spammer
						false, // Should it pass the spammy test? (No, they're trying to sell viagra!)
						false  // Should it be added to the spammers group? (No, as we've not set that config option)
				),
				array(
						[
							'gothick_akismet_check_registrations' => true
						], // Config
						'viagra-test-123', // User name being registered
						true,  // "Blatant" spammer
						false, // Should it pass the spammy test? (No, they're *blatantly* trying to sell viagra!)
						false  // Should it be added to the spammers group? (No, as we've not set that config option.)
				),
				array(
						[
							'gothick_akismet_check_registrations' => true,
							'gothick_akismet_add_registering_spammers_to_group' => 234
						], // Config
						'viagra-test-123', // User name being registered
						false, // "Blatant" spammer
						false, // Should it pass the spammy test? (No, they're trying to sell viagra!)
						true   // Should it be added to the spammers group? (Yes, as we've set that config option)
				),
				array(
						[
								'gothick_akismet_check_registrations' => true,
								'gothick_akismet_add_registering_spammers_to_group' => 234
						], // Config
						'viagra-test-123', // User name being registered
						true,  // "Blatant" spammer
						false, // Should it pass the spammy test? (No, they're *blatantly* trying to sell viagra!)
						true   // Should it be added to the spammers group? (Yes, as we've set that config option)
				),
				array(
						[
							'gothick_akismet_check_registrations' => false, // Not configured to check registrations...
							'gothick_akismet_add_registering_spammers_to_group' => 234
						],
						'viagra-test-123',
						true,
						true, // So even a blatant spammer should pass through
						false // And we shouldn't add it to the spammy group even though we're configured to put spammers in there
				),array(
						[
								'gothick_akismet_check_registrations' => true,
								'gothick_akismet_add_registering_spammers_to_group' => 234
						],
						'matt',
						false,
						true, // "Matt" should be fine; he's not trying to sell us viagra
						false // And shouldn't be added to the spammy group, even though we're configured to put spammers in there.
				)
		);
	}

	/**
	 * @dataProvider user_registration_data
	 */
	public function test_registration_check ($config, $username, $blatant, $should_pass, $should_add_to_spammy_group)
	{
		$listener = $this->get_listener(
				new \gothick\akismet\tests\mock\user($username),
				$config
		);
		if ($should_add_to_spammy_group) {
			$listener
				->expects($this->once())
				->method('group_user_add')
				->with($this->equalTo(234), $this->equalTo(123));
		}
		$akismet_mock = new \gothick\akismet\tests\mock\akismet_mock($blatant);
		$this->container->set('gothick.akismet.client', $akismet_mock);
		$data = array(
				'user_id' => 123,
				'user_row' => array(
						'username' => $username,
						'user_email' => 'whoever@example.com',
				)
		);
		$event = new \phpbb\event\data($data);
		$listener->check_new_user($event);

		// TODO: Test log messages & anything else you can think of
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
