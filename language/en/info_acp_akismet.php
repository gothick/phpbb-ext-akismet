<?php
/**
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2013 phpBB Group
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */
if (! defined('IN_PHPBB'))
{
	exit();
}

if (empty($lang) || ! is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang,
		array(
				// ACP modules
				'ACP_AKISMET_TITLE' => 'Gothick Akismet',
				'ACP_AKISMET_SETTINGS' => 'Settings',
				'ACP_AKISMET_SETTING_SAVED' => 'Settings have been saved successfully!',

				// Log operations
				'AKISMET_LOG_SETTING_CHANGED' => '<strong>Akismet settings updated.</strong>',
				'AKISMET_LOG_CALL_FAILED' => '<strong>Call to Akismet API failed</strong><br />» API returned: "%1$s"',
				'AKISMET_LOG_USING_ANONYMOUS_USER' => '<strong>Using anonymous user for Gothick Akismet</strong><br />» Check the Extension’s settings',
				'AKISMET_LOG_NO_KEY_CONFIGURED' => '<strong>No API key configured for Gothick Akismet</strong><br />» Check the Extension’s settings'
		));
