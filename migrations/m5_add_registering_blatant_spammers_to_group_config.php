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

class m5_add_registering_blatant_spammers_to_group_config extends \phpbb\db\migration\migration
{
	static public function depends_on ()
	{
		return array(
				'\gothick\akismet\migrations\m4_add_registering_spammers_to_group_config'
		);
	}
	public function update_data ()
	{
		return array(
				array(
						'config.add',
						array(
								'gothick_akismet_add_registering_blatant_spammers_to_group',
								0
						)
				)
		);
	}
}
