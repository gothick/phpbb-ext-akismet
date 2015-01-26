<?php
/**
*
* @package phpBB Extension - Gothick Akismet
* @copyright (c) 2015 Matt Gibson Creative Ltd.
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace gothick\akismet\migrations;

class release_1_0_1 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['gothick_akismet_user_id']);
	}

	static public function depends_on()
	{
		return array('\gothick\akismet\migrations\release_1_0_0');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('gothick_akismet_user_id', '')),
		);
	}
}
