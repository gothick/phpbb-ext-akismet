<?php
/**
 * Akismet notification type migrations.
 *
 * @package phpBB Extension - Gothick Akismet
 * @copyright (c) 2015 Matt Gibson Creative Ltd.
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */
namespace gothick\akismet\migrations;

class release_1_0_1 extends \phpbb\db\migration\migration
{
	protected static $notification_types = array(
			'gothick.akismet.notification.type.post_in_queue',
			'gothick.akismet.notification.type.topic_in_queue',
	);

	static public function depends_on ()
	{
		return array(
				'\gothick\akismet\migrations\release_1_0_0'
		);
	}
	public function update_data ()
	{
		return array(
				array('custom', array(array($this, 'add_notification_types'))),
		);
	}
	public function revert_data ()
	{
		return array(
				array('custom', array(array($this, 'remove_notification_types'))),
		);
	}
	public function add_notification_types()
	{
		foreach (self::$notification_types as $type)
		{
			$sql_arr = array(
					'notification_type_name' => $type,
					'notification_type_enabled' => 1
			);
			$sql = 'INSERT INTO ' . NOTIFICATION_TYPES_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_arr);
			$this->db->sql_query($sql);
		}
	}
	public function remove_notification_types()
	{
		$sql = 'DELETE FROM ' . NOTIFICATION_TYPES_TABLE .
				' WHERE ' . $this->db->sql_in_set('notification_type_name', self::$notification_types);
		$this->db->sql_query($sql);
	}
}
