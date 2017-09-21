<?php
/**
 * Akismet factory.
 *
 * @package phpBB Extension - Akismet
 * @copyright (c) 2015 Matt Gibson gothick@gothick.org.uk
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */
namespace gothick\akismet\utility;

/**
 * The Akismet Factory class is used to create a new vendor Akismet
 * API wrapper class using our configured API key and board URL. These
 * need to be passed into the constructor, so a factory seemed a good
 * approach that would allow us to unit test easily.
 */
class akismet_factory
{

	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\log\log_interface */
	protected $log;

	/* @var \phpbb\user */
	protected $user;

	protected $akismet_api_key;

	/**
	 * Constructor
	 *
	 * Lightweight initialisation of the API key and user ID.
	 * Heavy lifting is done only if the user actually tries
	 * to post a message.
	 *
	 * @param \phpbb\config\config $config
	 * @param \phpbb\log\log_interface $log
	 * @param \phpbb\user $user
	 */
	public function __construct (\phpbb\config\config $config,
			\phpbb\log\log_interface $log, \phpbb\user $user)
	{
		$this->config = $config;
		$this->log = $log;
		$this->user = $user;

		if (! empty($config['gothick_akismet_api_key']))
		{
			$this->akismet_api_key = $config['gothick_akismet_api_key'];
		}
	}

	public function createAkismet ()
	{
		if (empty($this->akismet_api_key))
		{
			$this->log->add('critical', ANONYMOUS,
					$this->user->data['session_ip'],
					'AKISMET_LOG_NO_KEY_CONFIGURED');
			return false;
		}
		else
		{
			return new \Gothick\AkismetClient\Client(generate_board_url(), 'phpBB',  $this->config['version'], $this->akismet_api_key);
		}
	}
}
