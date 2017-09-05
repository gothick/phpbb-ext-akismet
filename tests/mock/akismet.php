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
 * TijsVerkoyen Akismet Mock
 *
 * This simple mock object simply returns true if the author of any
 * message tested is 'viagra-test-123', which is also the actual
 * Akismet API's standard "always positive" test user.
 *
 * @package Gothick Akismet
 */
class Akismet extends \TijsVerkoyen\Akismet\Akismet
{
	public function __construct ()
	{
	}

	public function isSpam ($content, $author = null, $email = null, $url = null,
			$permalink = null, $type = null)
	{
		if ($author == 'viagra-test-123')
		{
			return true;
		}
		return false;
	}
}
