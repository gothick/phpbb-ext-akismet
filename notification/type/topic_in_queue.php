<?php

namespace gothick\akismet\notification\type;

class topic_in_queue extends \phpbb\notification\type\topic_in_queue
{
	public function get_type()
	{
		return 'gothick.akismet.notification.type.topic_in_queue';
	}

	/**
	 * Get email template
	 *
	 * @return string|bool
	 */
	public function get_email_template()
	{
		return '@gothick_akismet/topic_in_queue';
	}
}