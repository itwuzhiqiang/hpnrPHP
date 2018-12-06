<?php

class PadApiPadinx {
	private $_server;

	public function setServer ($server) {
		$this->_server = $server;
	}

	public function task ($op, $paramsMix = array()) {
		$params = array();
		if (is_string($paramsMix)) {
			parse_str(str_replace(',', '&', $paramsMix), $params);
		} else {
			$params = $paramsMix;
		}

		$this->_server->sendIpcMessage(100, array(
			'op' => $op,
			'params' => $params,
		));
	}
}
