<?php
/**
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2015 Matt Gibson Creative Ltd.
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */
namespace gothick\akismet\acp;

/**
 * ACP page for configuring Gothick Akismet: API key, Akismet, etc.
 *
 * @author matt
 *
 */
class akismet_module
{

	var $u_action;

	function main ($id, $mode)
	{
		global $db, $user, $auth, $template, $cache, $request, $phpbb_log;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		// Add our ACP language file.
		$user->add_lang_ext('gothick/akismet', 'akismet_acp');

		$this->tpl_name = 'akismet_body';
		$this->page_title = $user->lang('ACP_AKISMET_TITLE');
		add_form_key('gothick/akismet');

		if ($request->is_set_post('submit'))
		{
			if (! check_form_key('gothick/akismet'))
			{
				trigger_error('FORM_INVALID');
			}

			// TODO: Verify API key using Akismet library's "verifyKey" method
			$config->set('gothick_akismet_api_key',
					$request->variable('gothick_akismet_api_key', ''));

			$phpbb_log->add(
					'admin',
					$user->data['user_id'],
					$user->ip,
					'AKISMET_LOG_SETTING_CHANGED'
			);

			trigger_error(
					$user->lang('ACP_AKISMET_SETTING_SAVED') .
					adm_back_link($this->u_action)
			);

		}

		$template->assign_vars(
				array(
						'U_ACTION' => $this->u_action,
						'GOTHICK_AKISMET_API_KEY' => $config['gothick_akismet_api_key'],
				));
	}
}
