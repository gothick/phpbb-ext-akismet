<?php
/**
 *
 * @package phpBB Extension - Akismet
 * @copyright (c) 2015 Matt Gibson gothick@gothick.org.uk
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace gothick\akismet\tests\mock;

/**
* User Mock
* @package Gothick Akismet
*/
class user_loader extends \phpbb\user_loader
{
	public function __construct()
	{
	}
	public function get_user($user_id, $force = false)
	{
		return array(
				'user_id' => $user_id,
				'user_lang' => 'en',
				'user_email' => 'gothick@gothick.org.uk',
				'user_jabber' => 'jabberjabber',
				'username' => 'mock_user_loader_user',
				'user_notify_type' => 0
		);
	}

}
