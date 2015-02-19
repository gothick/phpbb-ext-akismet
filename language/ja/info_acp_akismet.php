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
				// ACP modules
				'ACP_AKISMET_TITLE' => 'Akismet',
				'ACP_AKISMET_SETTINGS' => '設定',
				'ACP_AKISMET_SETTING_SAVED' => '設定を保存しました',

				// Log operations
				'AKISMET_LOG_SETTING_CHANGED' => '<strong>Akismet 設定を更新しました</strong>',
				'AKISMET_LOG_CALL_FAILED' => '<strong>Akismet API の呼び出しに失敗しました</strong><br />» API の返した結果: "%1$s"',
				'AKISMET_LOG_NO_KEY_CONFIGURED' => '<strong>Gothick Akismet の API キーが設定されていません</strong><br />» 拡張機能の設定を確認して下さい',
				'AKISMET_LOG_POST_DISAPPROVED' => '<strong>以下の理由によって“%2$s”さんによって書かれた投稿 “%1$s” は承認されていません</strong><br />» 投稿はAkismetによってSPAMとして検出されました',
				'AKISMET_LOG_TOPIC_DISAPPROVED' => '<strong>以下の理由によって“%2$s”さんによって書かれたトピック “%1$s” は承認されていません</strong><br />» トピックはAkismetによってSPAMとして検出されました',
		));
