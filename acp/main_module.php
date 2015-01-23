<?php
/**
*
* @package phpBB Extension - Gothick Akismet
* @copyright (c) 2015 Matt Gibson Creative Ltd.
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace gothick\akismet\acp;

class main_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $cache, $request, $phpbb_log;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		$user->add_lang('acp/common');
		$this->tpl_name = 'akismet_body';
		$this->page_title = $user->lang('GOTHICK_AKISMET_TITLE');
		add_form_key('gothick/akismet');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('gothick/akismet'))
			{
				trigger_error('FORM_INVALID');
			}

			$config->set('gothick_akismet_api_key', $request->variable('gothick_akismet_api_key', ''));
			$config->set('gothick_akismet_url', $request->variable('gothick_akismet_url', ''));

			$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 'ACP_AKISMET_SETTINGS_CHANGED');
			
			trigger_error($user->lang('GOTHICK_AKISMET_SETTING_SAVED') . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'U_ACTION'					=> $this->u_action,
			'GOTHICK_AKISMET_API_KEY'	=> $config['gothick_akismet_api_key'],
			'GOTHICK_AKISMET_URL'		=> $config['gothick_akismet_url'],
		));
	}
}
