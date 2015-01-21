<?php
/**
*
* @package phpBB Extension - Gothick Akismet
* @copyright (c) 2015 Matt Gibson 
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace gothick\akismet\acp;

class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\gothick\akismet\acp\main_module',
			'title'		=> 'GOTHICK_AKISMET_TITLE',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'settings'	=> array('title' => 'GOTHICK_AKISMET', 'auth' => 'ext_gothick/akismet && acl_a_board', 'cat' => array('GOTHICK_AKISMET_TITLE')),
			),
		);
	}
}
