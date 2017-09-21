<?php
/**
 * Akismet notification type migrations.
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2017 Matt Gibson Creative Ltd.
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */
namespace gothick\akismet\migrations;

class release_1_0_2 extends \phpbb\db\migration\migration
{
	static public function depends_on ()
	{
		return array(
				'\gothick\akismet\migrations\release_1_0_1'
		);
	}
	public function update_data ()
	{
		return array(
				array(
						'config.add',
						array(
								'gothick_akismet_check_registrations',
								0
						)
				)
		);
	}
}
