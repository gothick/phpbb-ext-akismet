<?php
/**
 * Akismet factory test
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2017 Matt Gibson Creative Ltd.
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */


// Fake this method for the factory.
namespace gothick\akismet\utility {
	function generate_board_url() {
		return "http://fake.board.url";
	}
}

namespace gothick\akismet\tests\factory {

	class main_controller_test extends \phpbb_test_case
	{
		/** @var \phpbb\log\log_interface|\PHPUnit_Framework_MockObject_MockObject */
		protected $log;
		/** @var \phpbb\config\config|\PHPUnit_Framework_MockObject_MockObject */
		protected $config;
		/** @var \phpbb\language\language|\PHPUnit_Framework_MockObject_MockObject */
		protected $language;
		/** @var \phpbb\user|\PHPUnit_Framework_MockObject_MockObject */
		protected $user;
		/** @var \phpbb\group\helper|\PHPUnit_Framework_MockObject_MockObject */

		public function setUp()
		{
			global $phpbb_root_path, $phpEx;

			$this->log = $this->getMockBuilder(\phpbb\log\dummy::class)->getMock();
			$this->config = new \phpbb\config\config([]);
			$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
			$this->language = new \phpbb\language\language($lang_loader);
			$this->user = new \phpbb\user($this->language, '\phpbb\datetime');
		}

		/**
		 * Basic test to exercise the constructor
		 */
		public function test_factory_without_key()
		{
			$this->log
				->expects($this->once())
				->method('add')
				->with($this->equalTo('critical'));
			$factory = new \gothick\akismet\utility\akismet_factory($this->config, $this->log, $this->user);
			$this->assertFalse($factory->createAkismet());
		}
		/**
		 * Basic test to exercise the constructor
		 */
		public function test_factory_with_key()
		{
			// If we've set the key up properly, we shouldn't get an error message.
			$this->log
				->expects($this->never())
				->method('add');
			$this->config['gothick_akismet_api_key'] = 'abcdef';
			$this->config['version'] = '1.2.3';
			$factory = new \gothick\akismet\utility\akismet_factory($this->config, $this->log, $this->user);
			$akismet_client = $factory->createAkismet();
			$this->assertInstanceOf(\Gothick\AkismetClient\Client::class, $akismet_client);
		}
	}
}
