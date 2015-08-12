<?php
/**
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2015 Matt Gibson Creative Ltd.
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Translated By : Bassel Taha Alhitary - www.alhitary.net
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
				'ACP_AKISMET_TITLE' => 'خدمة الحماية أكسميت',
				'ACP_AKISMET_SETTINGS' => 'الإعدادات',
				'ACP_AKISMET_SETTING_SAVED' => 'تم حفظ الإعدادات بنجاح !',

				// Log operations
				'AKISMET_LOG_SETTING_CHANGED' => '<strong>تحديث إعدادات خدمة الحماية أكسميت.</strong>',
				'AKISMET_LOG_CALL_FAILED' => '<strong>فشل في الإتصال بمفتاح الأكسميت API</strong><br />» الرد : "%1$s"',
				'AKISMET_LOG_NO_KEY_CONFIGURED' => '<strong>لا يوجد أي مفتاح API لخدمة الحماية أكسميت</strong><br />» تحقق من إعدادات الإضافة',
				'AKISMET_LOG_POST_DISAPPROVED' => '<strong>رفض المُشاركة “%1$s” بواسطة الكاتب “%2$s” بسبب </strong><br />» تم تحديد هذه المُشاركة بأنها مُزعجة بواسطة خدمة الحماية أكسميت',
				'AKISMET_LOG_TOPIC_DISAPPROVED' => '<strong>رفض الموضوع “%1$s” بواسطة الكاتب “%2$s” بسبب </strong><br />» تم تحديد هذا الموضوع بأنه مُزعج بواسطة خدمة الحماية أكسميت',
		));
