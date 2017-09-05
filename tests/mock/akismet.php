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
 * 
 * Dead simple Akismet mock.
 * 
 * @package Gothick Akismet
 */
class Akismet extends \Gothick\AkismetClient\Client
{
	public function __construct ()
	{
	}

	public function commentCheck($user_ip, $user_agent, $other_params = array(), $server_params = array(), $user_role = 'user', $is_test = false)
	{
		if ($other_params['comment_author'] == 'viagra-test-123')
		{
			return true;
		}
		return false;
	}
}
