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
	* GeoModerate settings
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
					$this->lang('ACP_AKISMET_SETTING_SAVED') .
					adm_back_link($this->u_action)
			);

		}

		$this->template->assign_vars(
				array(
						'U_ACTION' => $this->u_action,
						'GOTHICK_AKISMET_API_KEY' => $this->config['gothick_akismet_api_key'],
				));

	}

	/**
	* Save settings back to the DB
	*/
	protected function save_settings()
	{
		$this->config->set('gothick_akismet_api_key', $this->request->variable('gothick_akismet_api_key', ''));
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
