<?php
/**
*
* @package phpBB Extension - Gothick Akismet
* @copyright (c) 2015 Matt Gibson
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace gothick\akismet\tests\listener;

class main_test extends \phpbb_test_case
{
	public function handle_data()
	{
		return array(
			array('viagra-test-123', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', true),
			array('matt', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', false)
		);
	}

	/**
	 * @dataProvider handle_data
	 */
	public function test_post_check($username, $message, $should_pass)
	{
		$listener = new \gothick\akismet\event\main_listener(
				new \gothick\akismet\tests\mock\user($username),
				new \gothick\akismet\tests\mock\request(),
				new \phpbb\config\config(array('gothick_akismet_user_id' => 1)),
				new \phpbb\log\null(),
				new \gothick\akismet\tests\mock\user_loader(),
				$this->getMock('\phpbb\auth\auth'),
				$this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface'),
				'.php' ,	// $php_ext,
				'./'		// $root_path
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
			$this->assertTrue(isset($event['data']['force_approved_state']));
			$this->assertEquals($event['data']['force_approved_state'], ITEM_UNAPPROVED);
		}
		else
		{
			$this->assertFalse(isset($event['data']['force_approved_state']));
		}
	}
}
