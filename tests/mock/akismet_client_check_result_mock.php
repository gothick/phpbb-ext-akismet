<?php
namespace gothick\akismet\tests\mock;

class akismet_client_check_result_mock extends \Gothick\AkismetClient\Result\CommentCheckResult
{
	protected $result;
	protected $blatant;

	public function __construct($result, $blatant = false)
	{
		$this->result = $result;
		$this->blatant = $blatant;
	}
	public function isSpam()
	{
		return $this->result;
	}
	public function isBlatantSpam()
	{
		return $this->blatant;
	}
}