<?php
/**
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

namespace gothick\akismet\controller {
	// Override global CSRF functions using the controller's namespace, so
	// we don't have to worry about them during this test. We use two namespaces
	// in this file so that autoloading still works but the global function still
	// gets overridden.
	function check_form_key($dummy)
	{
		return true;
	}
	function add_form_key($dummy)
	{
	}
}

namespace gothick\akismet\tests\controller {
	use \gothick\akismet\controller\admin_controller;

	class main_controller_test extends \phpbb_test_case
	{
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
					$phpEx,
					$phpbb_root_path
			);
			return $controller;
		}
		public function test_construct()
		{
			$controller = $this->get_controller();
			$this->assertInstanceOf(admin_controller::class, $controller);
		}
		public function test_save_settings_logged()
		{
			$this->setExpectedTriggerError(E_USER_NOTICE, 'ACP_AKISMET_SETTING_SAVED');
			$this->request->method('is_set_post')->with('submit')->willReturn('submit');

			$this->log->expects($this->once())
				->method('add')
				->with($this->equalTo('admin'));

			$controller = $this->get_controller();

			$controller->display_settings();
		}
	}
}
