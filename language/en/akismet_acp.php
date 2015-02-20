<?php
/**
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2015 Matt Gibson Creative Ltd.
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
				'ACP_AKISMET_WELCOME' => 'Welcome to Akismet',
				'ACP_AKISMET_INTRO' => 'This extension will use the <a href="http://akismet.com">Automattic Akismet</a> service to protect your board from spam, placing suspcious new posts directly into the moderation queue automatically.',
				'ACP_AKISMET_ADMINS_AND_MODS_OKAY' => 'All posts from board administrators and moderators will bypass the check completely.',
				'ACP_AKISMET_SIGN_UP' => 'To use this extension, you must first <a href="http://akismet.com">sign up for an API key</a>, then enter the key below.',
				'ACP_AKISMET_UNENCRYPTED_WARNING' => 'Please note that new topics and posts will be passed unencrypted—that is, over a standard http connection—to the Akismet servers for checking.',

				'ACP_AKISMET_SETTING_CHANGED' => 'Akismet settings updated.', // For log
				'ACP_AKISMET_SETTING_SAVED' => 'Settings have been saved successfully!',

				'ACP_AKISMET_API_KEY' => 'Akismet API Key',
		));
