<?php
/**
 * Basic admin controller tests
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2017 Matt Gibson Creative Ltd.
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

/*
 * Many of the ideas in my tests were taken from the excellent set of official extensions
 * at https://github.com/phpbb-extensions, especially ad-management, autogroups and boardrules.
 * Thanks!
 *
 * https://github.com/phpbb-extensions/ad-management/blob/master/tests/controller/admin_input_test.php
 * https://github.com/phpbb-extensions/autogroups/blob/master/tests/controller/submit_autogroup_rule_test.php
 */

/**
 * Override form_key global functions with ones that depend on a simple flag
 * we can set in our test class. Note we have to change namespaces so the functions
 * end up in the controller's namespace.
 */
namespace gothick\akismet\controller {
	function check_form_key($dummy)
	{
		return \gothick\akismet\tests\controller\main_controller_test::$check_form_key_result;
	}
	function add_form_key($dummy)
	{
	}
}

/**
 * Basic tests of the Admin Controller
 */
namespace gothick\akismet\tests\controller {
	use \gothick\akismet\controller\admin_controller;

	class main_controller_test extends \phpbb_test_case
	{
		public static $check_form_key_result = false;

		/** @var \phpbb\request\request|\PHPUnit_Framework_MockObject_MockObject */
		protected $request;
		/** @var \phpbb\template\template|\PHPUnit_Framework_MockObject_MockObject */
		protected $template;
		/** @var \phpbb\log\log_interface|\PHPUnit_Framework_MockObject_MockObject */
		protected $log;
		/** @var \phpbb\config\config|\PHPUnit_Framework_MockObject_MockObject */
		protected $config;
		/** @var \phpbb\language\language|\PHPUnit_Framework_MockObject_MockObject */
		protected $language;
		/** @var \phpbb\user|\PHPUnit_Framework_MockObject_MockObject */
		protected $user;
		/** @var \phpbb\group\helper|\PHPUnit_Framework_MockObject_MockObject */
		protected $group_helper;

		public function setUp()
		{
			global $phpbb_root_path, $phpEx;

			$this->request = $this->getMockBuilder(\phpbb\request\request::class)->getMock();
			$this->template = $this->getMockBuilder(\phpbb\template\template::class)->getMock();
			$this->log = $this->getMockBuilder(\phpbb\log\dummy::class)->getMock();
			$this->config = new \phpbb\config\config([]);
			$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
			$this->language = new \phpbb\language\language($lang_loader);
			$this->user = new \phpbb\user($this->language, '\phpbb\datetime');
			$this->group_helper = $this->getMockBuilder(\phpbb\group\helper::class)->disableOriginalConstructor()->getMock();
			$this->db = $this->getMockBuilder(\phpbb\db\driver\driver_interface::class)->getMock();
		}

		public function get_controller()
		{
			global $phpbb_root_path, $phpEx;

			$controller = new \gothick\akismet\controller\admin_controller
			(
					$this->request,
					$this->template,
					$this->user,
					$this->log,
					$this->config,
					$this->language,
					$this->group_helper,
					$this->db,
					$phpEx,
					$phpbb_root_path
			);
			return $controller;
		}

		/**
		 * Basic test to exercise the constructor
		 */
		public function test_construct()
		{
			$controller = $this->get_controller();
			$this->assertInstanceOf(admin_controller::class, $controller);
		}

		/**
		 * Make sure we log the change of settings to the admin log.
		 */
		public function test_save_settings_logged()
		{
			self::$check_form_key_result = true;
			$this->setExpectedTriggerError(E_USER_NOTICE, 'ACP_AKISMET_SETTING_SAVED');
			$this->request->method('is_set_post')->with('submit')->willReturn('submit');

			$this->log->expects($this->once())
				->method('add')
				->with($this->equalTo('admin'));

			$controller = $this->get_controller();

			$controller->display_settings();
		}

		/**
		 * Make sure we're paying attention to the form key.
		 */
		public function test_invalid_form_key()
		{
			self::$check_form_key_result = false;
			$this->setExpectedTriggerError(E_USER_NOTICE, 'FORM_INVALID');
			$this->request->method('is_set_post')->with('submit')->willReturn('submit');
			$controller = $this->get_controller();
			$controller->display_settings();
		}

		/**
		 * Test that the controller assigns all its variables properly.
		 */
		public function test_assign_vars()
		{
			$this->config['gothick_akismet_api_key'] = 'IM_AN_API_KEY_HONEST_GUV_123';
			$this->config['gothick_akismet_check_registrations'] = 1;
			$this->config['gothick_akismet_add_registering_spammers_to_group'] = 2;
			$this->config['gothick_akismet_add_registering_blatant_spammers_to_group'] = 3;
			$this->db->expects($this->any())
				->method('sql_fetchrow')
				->will(
					$this->onConsecutiveCalls(
							array(
									// Should be ignored, as we ignore all special groups except NEWLY_REGISTERED
									'group_id' => '1',
									'group_type' => GROUP_SPECIAL,
									'group_name' => 'ADMINISTRATORS'
							),
							array(
									// Should be picked up
									'group_id' => '2',
									'group_type' => GROUP_HIDDEN,
									'group_name' => 'Newly-Registered Spammers'
							),
							array(
									// Should be picked up
									'group_id' => '3',
									'group_type' => GROUP_HIDDEN,
									'group_name' => 'Newly-Registered Blatant Spammers'
							),
							false, // End of rows
							array(
									// Should be ignored, as we ignore all special groups except NEWLY_REGISTERED
									'group_id' => '1',
									'group_type' => GROUP_SPECIAL,
									'group_name' => 'ADMINISTRATORS'
							),
							array(
									// Should be picked up
									'group_id' => '2',
									'group_type' => GROUP_HIDDEN,
									'group_name' => 'Newly-Registered Spammers'
							),
							array(
									// Should be picked up
									'group_id' => '3',
									'group_type' => GROUP_HIDDEN,
									'group_name' => 'Newly-Registered Blatant Spammers'
							),
							false // End of rows
					));

			$this->template
				->expects($this->once())
				->method('assign_vars')
				->with(
						$this->callback(function($vars) {

							if ($vars['U_ACTION'] != 'index_test.php')
							{
								return false;
							}
							if ($vars['API_KEY'] != 'IM_AN_API_KEY_HONEST_GUV_123')
							{
								return false;
							}
							if ($vars['S_CHECK_REGISTRATIONS'] != 1)
							{
								return false;
							}
							if (!preg_match('/option value="2" selected="selected"/', $vars['S_GROUP_LIST']))
							{
								return false;
							}
							if (!preg_match('/option value="3" selected="selected"/', $vars['S_GROUP_LIST_BLATANT']))
							{
								return false;
							}

							return true;
						}));

			$this->group_helper->method('get_name')->will($this->returnArgument(0));

			$controller = $this->get_controller();
			$controller->set_action('index_test.php');
			$controller->display_settings();
		}
	}
}
