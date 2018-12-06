<?php

class PadLib_Crontab_Task {
	private $_crontab = '1 * * * * *';
	private $_executeCallback = null;

	public function __construct($options = array()) {
		$this->options = array_merge(array(
			'time' => time(),
		), $options);
	}

	public function setCrontab ($crontab) {
		$this->_crontab = $crontab;
		return $this;
	}

	public function setExecute ($callback) {
		$this->_executeCallback = $callback;
		return $this;
	}

	public function isCrontabHit () {
		$time = $this->options['time'];
		$timeList = array(
			date('s', $time),
			date('i', $time),
			date('H', $time),
			date('d', $time),
			date('m', $time),
			date('w', $time)
		);

		$isHit = true;
		$crontabList = explode(' ', $this->_crontab);
		foreach ($timeList as $i => $value) {
			$crontabVal = $crontabList[$i];
			if ($crontabVal == '*') {
			} else if (strpos($crontabVal, '/') !== false) {
				list($null, $cValue) = explode('/', $crontabVal);
				$isHit = ($value % $cValue == 0);
			} else {
				$isHit = ($value == $crontabVal);
			}
		}
		return $isHit;
	}

	public function execute () {
		if ($this->isCrontabHit()) {
			call_user_func_array($this->_executeCallback, array());
		}
	}
}



