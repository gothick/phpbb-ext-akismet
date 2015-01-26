<?php
/**
 *
* @package phpBB Extension - Gothick Akismet
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

$lang = array_merge($lang, array(
		'ACP_AKISMET_SETTINGS_CHANGED'	=> 'Akismet settings updated.',		
		'AKISMET_DISAPPROVED'	=> 'Disapproved by Akismet check.',
		'AKISMET_LOG_CALL_FAILED'	=> 'Call to Akismet API failed: %1$s' 
));
