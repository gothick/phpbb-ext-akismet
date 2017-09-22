<?php
/**
 * Akismet Admin Controller
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2015 Matt Gibson Creative Ltd.
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace gothick\akismet\controller;

/**
* Admin controller
*/
class admin_controller
{
	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\log\log_interface */
	protected $log;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var string Custom form action */
	protected $u_action;

	/** @var \phpbb\language\language */
	protected $language;

	const FORM_KEY = 'gothick/akismet';

	/**
	* Constructor
	*
	* @param \phpbb\request\request $request Request object
	* @param \phpbb\template\template $template Template object
	* @param \phpbb\user $user User object
	* @param \phpbb\log\log_interface $log Log object
	* @param \phpbb\config\config $config Config object
	* @param \phpbb\language\language $language Language object
	*/
	public function __construct(
			\phpbb\request\request $request,
			\phpbb\template\template $template,
			\phpbb\user $user,
			\phpbb\log\log_interface $log,
			\phpbb\config\config $config,
			\phpbb\language\language $language
		)
	{
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->log = $log;
		$this->config = $config;
		$this->language = $language;
	}

	/**
	* Akismet settings
	*
	*/
	public function display_settings()
	{
		add_form_key(self::FORM_KEY);

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key(self::FORM_KEY))
			{
				trigger_error('FORM_INVALID');
			}

			$this->save_settings();

			$this->log->add(
					'admin',
					$this->user->data['user_id'],
					$this->user->ip,
					'AKISMET_LOG_SETTING_CHANGED'
			);

			trigger_error(
					$this->language->lang('ACP_AKISMET_SETTING_SAVED') .
					adm_back_link($this->u_action)
			);

		}

		$this->template->assign_vars(
				array(
						'U_ACTION' => $this->u_action,
						'API_KEY' => $this->config['gothick_akismet_api_key'],
						'S_CHECK_REGISTRATIONS' => $this->config['gothick_akismet_check_registrations'],
						'S_GROUP_LIST' => $this->group_select_options($this->config['gothick_akismet_add_registering_spammers_to_group'])
				));

	}

	protected function group_select_options($selected_group_id = 0)
	{
		// Adapted from global function group_select_options in core file functions_admin.php and adapted.
		global $db, $config, $phpbb_container;

		/** @var \phpbb\group\helper $group_helper */
		$group_helper = $phpbb_container->get('group_helper');

		$sql_coppa = (!$config['coppa_enable']) ? " WHERE group_name <> 'REGISTERED_COPPA'" : '';

		$sql = 'SELECT group_id, group_name, group_type
		FROM ' . GROUPS_TABLE . "
		$sql_coppa
		ORDER BY group_type DESC, group_name ASC";

		$result = $db->sql_query($sql);

		$s_group_options = '';

		while ($row = $db->sql_fetchrow($result))
		{
			$selected = ($row['group_id'] == $selected_group_id) ? ' selected="selected"' : '';
			$s_group_options .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' class="sep"' : '') . ' value="' . $row['group_id'] . '"' . $selected . '>' . $group_helper->get_name($row['group_name']) . '</option>';
		}
		$db->sql_freeresult($result);

		return $s_group_options;
	}

	/**
	* Save settings back to the DB
	*/
	protected function save_settings()
	{
		$this->config->set('gothick_akismet_api_key', $this->request->variable('api_key', ''));
		$this->config->set('gothick_akismet_check_registrations', $this->request->variable('check_registrations', 0));
		$this->config->set('gothick_akismet_add_registering_spammers_to_group', $this->request->variable('add_registering_spammers_to_group', 0));
	}
	/**
	* Set action
	*
	* @param string $u_action Action
	*/
	public function set_action($u_action)
	{
		$this->u_action = $u_action;
	}
}
