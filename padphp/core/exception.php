<?php

class PadException extends Exception {
	private $errorFields = array();

	/**
	 * 一般用户自己处理异常,需要标志已经catch
	 * 否则会导致数据库等不应该的提交
	 */
	static public function catched () {
		$GLOBALS['pad_core']->hasException = true;
	}

	public function __construct($message, $code = 1700) {
		parent::__construct($message, $code);
	}

	/**
	 * 合并2个异常
	 * @param PadException $e
	 */
	public function merge (PadException $e) {
		$this->code = $e->getCode();
		$this->message = $e->getMessage();
		foreach ($e->getErrorFields() as $k => $item) {
			$this->set($k, $item);
		}
	}

	public function set ($key, $message, $code = 1700) {
		$this->errorFields[$key] = array(
			'message' => $message,
			'code' => $code,
		);
	}

	public function getErrorFields () {
		return $this->errorFields;
	}
}

class PadErrorException extends PadException {

}

class PadBizException extends PadException {

}



