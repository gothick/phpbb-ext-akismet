<?php

namespace gothick\akismet\notification\type;

class post_in_queue extends \phpbb\notification\type\post_in_queue
{
	public function get_type()
	{
		return 'gothick.akismet.notification.type.post_in_queue';
	}

	/**
	 * Get email template
	 *
	 * @return string|bool
	 */
	public function get_email_template()
	{
		return '@gothick_akismet/post_in_queue';
	}
}
