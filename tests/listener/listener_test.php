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
function append_sid ($url, $params = false, $is_amp = true, $session_id = false, $is_route = false)
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

	public function setUp ()
	{
		$this->container = new \phpbb_mock_container_builder();
	}

	protected function get_listener ($user, \phpbb\config\config $config = null, \phpbb\log\log_interface $log = null)
	{
		global $phpbb_root_path, $phpEx;
		if (! $log)
		{
			$log = new \phpbb\log\dummy();
		}

		if (! $config)
		{
			$config = new \phpbb\config\config([]);
		}
		return $this->getMockBuilder(\gothick\akismet\event\main_listener::class)
			->setConstructorArgs(
				[
						$user,
						$this->getMock('\phpbb\request\request'),
						$config,
						$log,
						$this->getMock('\phpbb\auth\auth'),
						$this->container,
						$phpEx,
						$phpbb_root_path
				])
			->setMethods([
				'group_user_add'
		])
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
		$log = $this->getMockBuilder(\phpbb\log\dummy::class)->getMock();
		if (! $should_pass)
		{
			$log->expects($this->once())
				->method('add')
				->with($this->equalTo('mod'));
		}

		$listener = $this->get_listener(new \gothick\akismet\tests\mock\user($username), null, $log);
		$akismet_mock = new \gothick\akismet\tests\mock\akismet_mock();
		$this->container->set('gothick.akismet.client', $akismet_mock);

		$data = array(
				'mode' => $mode,
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

	/**
	 * @dataProvider post_data
	 */
	public function test_no_akismet_object_post ($username, $message, $mode, $should_pass)
	{
		// This is the same as test_post_check except because we don't have an Akismet
		// object set up, every check should quietly pass (with no exceptions, but a
		// bit of gentle logging.)
		$log = $this->getMockBuilder(\phpbb\log\dummy::class)->getMock();
		$log->expects($this->once())
			->method('add')
			->with($this->equalTo('critical'));
		$listener = $this->get_listener(new \gothick\akismet\tests\mock\user($username), null, $log);
		// $akismet_mock = new \gothick\akismet\tests\mock\akismet_mock();
		// $this->container->set('gothick.akismet.client', null);

		$data = array(
				'mode' => $mode,
				'data' => array(
						'message' => $message,
						'topic_id' => 123
				)
		);
		$event = new \phpbb\event\data($data);
		$listener->check_submitted_post($event);

		// As we couldn't have gotten an Akismet object, every test should pass.
		$this->assertFalse(isset($event['data']['force_approved_state']));
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
						false, // Should it be added to the spammers group? (No, as we've not set that config option)
						false  // Should it be added to the blatant spammers group? (No, as we've not set that config option)
				),
				array(
						[
								'gothick_akismet_check_registrations' => true
						], // Config
						'viagra-test-123', // User name being registered
						true, // "Blatant" spammer
						false, // Should it pass the spammy test? (No, they're *blatantly* trying to sell viagra!)
						false, // Should it be added to the spammers group? (No, as we've not set that config option.)
						false  // Should it be added to the blatant spammers group? (No, as we've not set that config option)
				),
				array(
						[
								'gothick_akismet_check_registrations' => true,
								'gothick_akismet_add_registering_spammers_to_group' => 234
						], // Config
						'viagra-test-123', // User name being registered
						false, // "Blatant" spammer
						false, // Should it pass the spammy test? (No, they're trying to sell viagra!)
						true, // Should it be added to the spammers group? (Yes, as we've set that config option)
						false  // Should it be added to the blatant spammers group? (No, as we've not set that config option)
				),
				array(
						[
								'gothick_akismet_check_registrations' => true,
								'gothick_akismet_add_registering_spammers_to_group' => 234
						], // Config
						'viagra-test-123', // User name being registered
						true, // "Blatant" spammer
						false, // Should it pass the spammy test? (No, they're *blatantly* trying to sell viagra!)
						true, // Should it be added to the spammers group? (Yes, as we've set that config option)
						false  // Should it be added to the blatant spammers group? (No, as we've not set that config option)
				),
				array(
						[
								'gothick_akismet_check_registrations' => false, // Not configured to check registrations...
								'gothick_akismet_add_registering_spammers_to_group' => 234,
								'gothick_akismet_add_registering_blatant_spammers_to_group' => 345
						],
						'viagra-test-123',
						true,
						true, // So even a blatant spammer should pass through
						false, // And we shouldn't add it to the spammy group even though we're configured to put spammers in there
						false  // ...nor to the blatant spammers group
				),
				array(
						[
								'gothick_akismet_check_registrations' => true,
								'gothick_akismet_add_registering_spammers_to_group' => 234,
								'gothick_akismet_add_registering_blatant_spammers_to_group' => 345
						],
						'matt',
						false,
						true, // "Matt" should be fine; he's not trying to sell us viagra
						false, // And shouldn't be added to the spammy group, even though we're configured to put spammers in there.
						false  // ...nor to the blatant spammers group
				),
				array(
						[
								'gothick_akismet_check_registrations' => true,
								'gothick_akismet_add_registering_spammers_to_group' => 234,
								'gothick_akismet_add_registering_blatant_spammers_to_group' => 345
						],
						'viagra-test-123',
						true, // Blatant spammer
						false, // Shouldn't pass the spammy test
						false, // Should be added to the spammy group
						false  // *and* to the the blatant spammers group
				),
		);
	}

	/**
	 * @dataProvider user_registration_data
	 */
	public function test_registration_check ($config, $username, $blatant, $should_pass, $should_add_to_spammy_group, $should_add_to_blatantly_spammy_group)
	{
		$log = $this->getMockBuilder(\phpbb\log\dummy::class)->getMock();
		if (! $should_pass)
		{
			$log->expects($this->once())
				->method('add')
				->with($this->equalTo('mod'));
		}

		$listener = $this->get_listener(new \gothick\akismet\tests\mock\user($username), new \phpbb\config\config($config), $log);
		if ($should_add_to_spammy_group)
		{
			$listener->expects($this->once())
				->method('group_user_add')
				->with($this->equalTo(234), $this->equalTo(123));
		}
		if ($should_add_to_blatantly_spammy_group)
		{
			$listener->expects($this->once())
			->method('group_user_add')
			->with($this->equalTo(345), $this->equalTo(123));
		}
		$akismet_mock = new \gothick\akismet\tests\mock\akismet_mock($blatant);
		$this->container->set('gothick.akismet.client', $akismet_mock);
		$data = array(
				'user_id' => 123,
				'user_row' => array(
						'username' => $username,
						'user_email' => 'whoever@example.com'
				)
		);
		$event = new \phpbb\event\data($data);
		$listener->check_new_user($event);
	}

	/**
	 * @dataProvider user_registration_data
	 *
	 * Same as test_registration_check, except we'll fake a failure to create the Akismet client. All
	 * registrations should pass through without being marked as spam.
	 */
	public function test_no_akismet_object_registration ($config, $username, $blatant, $should_pass, $should_add_to_spammy_group)
	{
		$log = $this->getMockBuilder(\phpbb\log\dummy::class)->getMock();

		if ($config['gothick_akismet_check_registrations'])
		{
			// If we're configured to check registrations, then I expect us to fail with
			// an exception, as there'll be no Akismet client configured.
			$log->expects($this->once())
				->method('add')
				->with($this->equalTo('critical'));
		}
		else
		{
			// But if we're not configured, none of our code should run, so there should
			// be no logging.
			$log->expects($this->never())
				->method('add');
		}

		$listener = $this->get_listener(new \gothick\akismet\tests\mock\user($username), new \phpbb\config\config($config), $log);

		$listener->expects($this->never())
			->method('group_user_add');

		// $akismet_mock = new \gothick\akismet\tests\mock\akismet_mock($blatant);
		// $this->container->set('gothick.akismet.client', $akismet_mock);
		$data = array(
				'user_id' => 123,
				'user_row' => array(
						'username' => $username,
						'user_email' => 'whoever@example.com'
				)
		);
		$event = new \phpbb\event\data($data);
		$listener->check_new_user($event);
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

	public function notification_template_data ()
	{
		return array(
				array(
						true, // gothick_akismet_unapproved
						'notification.type.some_type_or_other', // Incoming notification type
						'notification.type.some_type_or_other' // Expected outgoing notification type
				),
				array(
						false, // gothick_akismet_unapproved
						'notification.type.some_type_or_other', // Incoming notification type
						'notification.type.some_type_or_other' // Expected outgoing notification type
				),
				array(
						null, // gothick_akismet_unapproved
						'notification.type.some_type_or_other', // Incoming notification type
						'notification.type.some_type_or_other' // Expected outgoing notification type
				),
				array(
						null, // gothick_akismet_unapproved
						'notification.type.post_in_queue', // Incoming notification type
						'notification.type.post_in_queue' // Expected outgoing notification type
				),
				array(
						null, // gothick_akismet_unapproved
						'notification.type.topic_in_queue', // Incoming notification type
						'notification.type.topic_in_queue' // Expected outgoing notification type
				),
				array(
						true, // gothick_akismet_unapproved
						'notification.type.post_in_queue', // Incoming notification type
						'gothick.akismet.notification.type.post_in_queue' // Expected outgoing notification type
				),
				array(
						true, // gothick_akismet_unapproved
						'notification.type.topic_in_queue', // Incoming notification type
						'gothick.akismet.notification.type.topic_in_queue' // Expected outgoing notification type
				)
		);
	}

	/**
	 * @dataProvider notification_template_data
	 */
	public function test_akismet_notification_template_triggered ($gothick_akismet_unapproved, $incoming_type, $expected_outgoing_type)
	{
		$listener = $this->get_listener(new \gothick\akismet\tests\mock\user('dummy'));
		$event = new \phpbb\event\data();
		$data = [
				'gothick_akismet_unapproved' => $gothick_akismet_unapproved
		];
		$event['data'] = $data;
		$event['notification_type_name'] = $incoming_type;
		$listener->add_akismet_details_to_notification($event);
		$this->assertEquals($expected_outgoing_type, $event['notification_type_name']);
	}

	/**
	 * Make sure we unset the configuration to send spammers to a particular group if that
	 * group gets deleted.
	 */
	public function test_normal_spammy_group_deleted ()
	{
		// It should pop something in the moderator log, too.
		$log = $this->getMockBuilder(\phpbb\log\dummy::class)->getMock();
		$log->expects($this->once())
			->method('add')
			->with($this->equalTo('mod'));

		$config = new \phpbb\config\config([
				'gothick_akismet_add_registering_spammers_to_group' => 888,
				'gothick_akismet_add_registering_blatant_spammers_to_group' => 999 // Should remain unchanged
		]);
		$listener = $this->get_listener(new \gothick\akismet\tests\mock\user('dummy'), $config, $log);
		$event = new \phpbb\event\data();
		$event['group_id'] = 123;
		$listener->group_deleted($event);
		$this->assertEquals(888, $config['gothick_akismet_add_registering_spammers_to_group']);
		$event['group_id'] = 888;
		$listener->group_deleted($event);
		$this->assertEquals(0, $config['gothick_akismet_add_registering_spammers_to_group']);

		// The other group should remain unchanged by all this
		$this->assertEquals(999, $config['gothick_akismet_add_registering_blatant_spammers_to_group']);
	}

	/**
	 * Make sure we unset the configuration to send spammers to a particular group if that
	 * group gets deleted.
	 */
	public function test_blatantly_spammy_group_deleted ()
	{
		// It should pop something in the moderator log, too.
		$log = $this->getMockBuilder(\phpbb\log\dummy::class)->getMock();
		$log->expects($this->once())
		->method('add')
		->with($this->equalTo('mod'));

		$config = new \phpbb\config\config([
				'gothick_akismet_add_registering_spammers_to_group' => 888,
				'gothick_akismet_add_registering_blatant_spammers_to_group' => 999 // Should remain unchanged
		]);
		$listener = $this->get_listener(new \gothick\akismet\tests\mock\user('dummy'), $config, $log);
		$event = new \phpbb\event\data();
		$event['group_id'] = 123;
		$listener->group_deleted($event);
		$this->assertEquals(999, $config['gothick_akismet_add_registering_blatant_spammers_to_group']);
		$event['group_id'] = 999;
		$listener->group_deleted($event);
		$this->assertEquals(0, $config['gothick_akismet_add_registering_blatant_spammers_to_group']);

		// The other group should remain unchanged by all this
		$this->assertEquals(888, $config['gothick_akismet_add_registering_spammers_to_group']);
	}
}
