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

// TODO: Split ACP strings to separate file
$lang = array_merge($lang, array(
		'AKISMET_DISAPPROVED'	=> 'Disapproved by Akismet check.',
		'AKISMET_LOG_CALL_FAILED'	=> '<strong>Call to Akismet API failed</strong><br />» API returned: "%1$s"',
		'AKISMET_LOG_USING_ANONYMOUS_USER' => "<strong>Using anonymous user for Gothick Akismet</strong><br />» Check the Extension's settings",
		'AKISMET_NO_KEY_CONFIGURED' => "<strong>No API key configured for Gothick Akismet</strong><br />» Check the Extension's settings",

		'ACP_AKISMET_TITLE' => 'Gothick Akismet',
		'ACP_AKISMET_SETTINGS' => 'Settings',
		'ACP_AKISMET_SETTING_CHANGED'	=> 'Akismet settings updated.', // For log
		'ACP_AKISMET_SETTING_SAVED' => 'Settings have been saved successfully!',
		
		'ACP_AKISMET_API_KEY' => 'Akismet API Key',
		'ACP_AKISMET_ENTER_USERNAME' => 'Admin/moderator username for Akismet actions'
));
