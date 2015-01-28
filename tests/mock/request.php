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
class request extends \phpbb\request\request
{
	public function __construct()
	{
	}

	public function enable_super_globals()
	{
	}

	public function disable_super_globals()
	{
	}
}
