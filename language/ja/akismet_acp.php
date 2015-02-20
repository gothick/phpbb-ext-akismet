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
				'ACP_AKISMET_WELCOME' => 'Akismet 拡張機能へようこそ',
				'ACP_AKISMET_INTRO' => 'この拡張機能はSPAMからあなたの掲示板を保護するために<a href="http://akismet.com">Automatic Akismet</a>サービスを使用し、疑わしい新規投稿を直接自動的に承認待ちにします。',
				'ACP_AKISMET_ADMINS_AND_MODS_OKAY' => '掲示板の管理者及びモデレーターからの全ての投稿は完全にチェックをバイパスします。',
				'ACP_AKISMET_SIGN_UP' => 'この拡張機能を使用するには、まず最初に<a href="http://akismet.com">APIキーのためにサインアップをし</a>、それから以下にそのキーを入力します。',
				'ACP_AKISMET_UNENCRYPTED_WARNING' => '新規トピック及び投稿はチェックするためにAkismetサーバーへ暗号化されていない、つまり標準のHTTPを介して渡されます。',

				'ACP_AKISMET_SETTING_CHANGED' => 'Akismet設定を更新しました', // For log
				'ACP_AKISMET_SETTING_SAVED' => '設定を保存しました',

				'ACP_AKISMET_API_KEY' => 'Akismet API キー'
		));
