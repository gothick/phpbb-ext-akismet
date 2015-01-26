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

	function main ($id, $mode)
	{
		global $db, $user, $auth, $template, $cache, $request, $phpbb_log;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		
		$user->add_lang('acp/common');
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
			
			$username = utf8_normalize_nfc(
					request_var('gothick_akismet_username', '', true));
			$sql = 'SELECT user_id
				FROM ' . USERS_TABLE . "
				WHERE username_clean = '" . $db->sql_escape(utf8_clean_string($username)) .
					 "'";
			$result = $db->sql_query($sql);
			$user_id = (int) $db->sql_fetchfield('user_id');
			$db->sql_freeresult($result);
			
			if (! $user_id)
			{
				trigger_error(
						$user->lang['NO_USER'] . adm_back_link($this->u_action), 
						E_USER_WARNING);
			} else
			{
				$config->set('gothick_akismet_user_id', $user_id);
			}
			
			$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 
					'ACP_AKISMET_SETTING_CHANGED');
			
			trigger_error(
					$user->lang('ACP_AKISMET_SETTING_SAVED') .
							 adm_back_link($this->u_action));
		}
		
		$username = '';
		if (isset($config['gothick_akismet_user_id']))
		{
			$user_id = filter_var($config['gothick_akismet_user_id'], 
					FILTER_VALIDATE_INT);
			if ($user_id !== false)
			{
				$sql = 'SELECT u.username FROM ' . USERS_TABLE .
						 ' u WHERE u.user_id = ' . $user_id;
				$result = $db->sql_query_limit($sql, 1);
				$user_row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
				
				if ($user_row)
				{
					$username = $user_row['username'];
				}
			}
		}
		
		$template->assign_vars(
				array(
						'U_ACTION' => $this->u_action,
						'GOTHICK_AKISMET_API_KEY' => $config['gothick_akismet_api_key'],
						'GOTHICK_AKISMET_USERNAME' => $username
				));
	}
}
