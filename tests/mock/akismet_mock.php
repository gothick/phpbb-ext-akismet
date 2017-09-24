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
class akismet_mock extends \Gothick\AkismetClient\Client
{
	protected $blatant;
	public function __construct($blatant = false)
	{
		$this->blatant = $blatant;
	}

	public function commentCheck($params = array(), $server_params = array())
	{
		if ($params['comment_author'] == 'viagra-test-123')
		{
			return new akismet_client_check_result_mock(true, $blatant);
		}
		return new akismet_client_check_result_mock(false, false);
	}
}
