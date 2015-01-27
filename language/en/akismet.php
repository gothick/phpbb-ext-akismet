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
				'AKISMET_DISAPPROVED' => 'Disapproved by Akismet check.',
				// Email subject
				'FORUM_SPAM_DETECTED_FROM_USER' => 'Forum spam detected from user'
		)
		);
