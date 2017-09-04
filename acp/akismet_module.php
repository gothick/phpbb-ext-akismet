<?php
/**
 * Akismet settings ACP module.
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

	public function main ($id, $mode)
	{
		global $phpbb_container;

		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');
		$language->add_lang('akismet_acp', 'gothick/akismet');

		/* @var $admin_controller \gothick\akismet\controller\admin_controller */
		$admin_controller = $phpbb_container->get('gothick.akismet.admin.controller');
		$admin_controller->set_action($this->u_action);

		$this->tpl_name = 'akismet_body';
		$this->page_title = $language->lang('ACP_AKISMET_TITLE');

		$admin_controller->display_settings();
	}
}
