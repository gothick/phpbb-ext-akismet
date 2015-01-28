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
class user extends \phpbb\user
{
	protected $username;
	public function __construct($username)
	{
		$this->data['username'] = $username;
		$this->data['username_clean'] = $username;
	}

	public function lang()
	{
		return implode(' ', func_get_args());
	}

	public function get_profile_fields($user_id)
	{
		$this->profile_fields = array('pf_phpbb_website' => 'http://mock.user.website/');
	}
}
