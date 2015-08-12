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
				'ACP_AKISMET_WELCOME' => 'مرحباً بك في خدمة الحماية أكسميت',
				'ACP_AKISMET_INTRO' => 'هذه الإضافة سوف تستخدم خدمة <a href="http://akismet.com">الأكسميت التلقائي</a> لحماية منتداك من المُشاركات المُزعجة. وسيتم مباشرة وضع المُشاركات المُزعجة تلقائياً في قائمة المراجعة للمشرفين.',
				'ACP_AKISMET_ADMINS_AND_MODS_OKAY' => 'لن يتم فحص مُشاركات المدراء والمشرفين بواسطة خدمة الحماية أكسميت.',
				'ACP_AKISMET_SIGN_UP' => 'لإستخدام هذه الخدمة , يجب عليك أولاً <a href="http://akismet.com">التسجيل للحصول على مفتاح API</a>, ثم ادخال رقم المفتاح في الخيار أدناه.',
				'ACP_AKISMET_UNENCRYPTED_WARNING' => 'نرجوا الإنتباه إلى أن أنه سيتم إرسال المواضيع والمشاركات الجديدة بدون تشفير إلى سيرفرات خدمة الحماية أكسميت لفحصها ( خلال إتصال http قياسي ).',

				'ACP_AKISMET_SETTING_CHANGED' => 'تحديث إعدادات خدمة الحماية أكسميت.', // For log
				'ACP_AKISMET_SETTING_SAVED' => 'تم حفظ الإعدادات بنجاح !',

				'ACP_AKISMET_API_KEY' => 'مفتاح الأكسميت API ',
		));
