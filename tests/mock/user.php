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
	public function __construct()
	{
	}

	public function lang()
	{
		return implode(' ', func_get_args());
	}
}
