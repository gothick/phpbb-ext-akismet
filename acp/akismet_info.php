<?php
/**
 * Akismet settings ACP module info.
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2015 Matt Gibson Creative Ltd.
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */
namespace gothick\akismet\acp;

class akismet_info
{
	public function module ()
	{
		return array(
				'filename' => '\gothick\akismet\acp\akismet_module',
				'title' => 'ACP_AKISMET_TITLE',
				'modes' => array(
						'settings' => array(
								'title' => 'ACP_AKISMET_SETTINGS',
								'auth' => 'ext_gothick/akismet && acl_a_board',
								'cat' => array(
										'ACP_AKISMET_TITLE'
								)
						)
				)
		);
	}
}
