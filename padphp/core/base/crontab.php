<?php

class PadBaseCrontab {

	static public function appCliExecute($className, $classMethod, $classMethodParams, $options = array()) {
		$crontabClass = new $className();
		$task = new PadBaseCrontabTask();
		$task->setOptions($options);
		array_unshift($classMethodParams, $task);
		call_user_func_array(array($crontabClass, $classMethod), $classMethodParams);
		$task->execute();
	}
}

class PadBaseCrontabAbstract {

	final public function getTaskList() {
		$executeList = array();
		$batchId = uniqid();
		$className = get_class($this);

		if (method_exists($this, 'setTaskList')) {
			$result = $this->setTaskList();
			foreach ($result as $key => $item) {
				$retItem = array(
					'batchId' => $batchId,
					'class' => $className,
					'method' => $item['method'],
					'taskList' => $item['taskList'],
				);
				$executeList[$className . '_' . $item['method']] = $retItem;
			}
		} else {
			foreach (get_class_methods($this) as $methodName) {
				if (strpos($methodName, 'task') === 0) {
					$executeList[$className . '_' . $methodName] = array(
						'batchId' => $batchId,
						'class' => $className,
						'method' => $methodName,
						'taskList' => array(array()),
					);
				}
			}
		}

		return $executeList;
	}
}

class PadBaseCrontabTask {
	public $executeCallback;
	public $options = array();

	private $_maxProcess = 6;
	private $_crontab = '*/15 * * * *';
	private $_isHitCrontab = false;

	private $_isLoop = false;
	private $_isLoopInterval = 1000;
	private $_isLoopNum = 60;

	public function setOptions ($options) {
		$this->options = array_merge(array(
			'force' => 0,
			'time' => time(),
		), $options);
	}

	public function setMaxProcess ($num) {
		$this->_maxProcess = $num;
		return $this;
	}

	public function setCrontab ($crontab) {
		$this->_crontab = $crontab;
		return $this;
	}

	public function setLoop ($interval = 1000, $num = 1000) {
		$this->_isLoop = true;
		$this->_isLoopInterval = $interval;
		$this->_isLoopNum = $num;
		return $this;
	}

	public function setExecute ($callback) {
		$this->executeCallback = $callback;
		return $this;
	}

	public function checkCrontabHit () {
		$this->_isHitCrontab = true;
	}

	public function isCrontabHit () {
		if ($this->options['force'] > 0) {
			return true;
		}

		$time = $this->options['time'];
		$timeList = array(date('i', $time), date('H', $time), date('d', $time), date('m', $time), date('w', $time));
		$crontabList = explode(' ', $this->_crontab);
		$isHit = true;

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

	public function isProcessLimit () {
		if ($this->options['force'] > 0) {
			return false;
		}
		return $this->options['checkProcess'] >= $this->_maxProcess;
	}

	public function execute() {
		if ($this->_isLoop) {
			$times = 0;
			while ($this->isCrontabHit() && $times < $this->_isLoopNum) {
				call_user_func_array($this->executeCallback, array());
				usleep($this->_isLoopInterval * 1000);
				$times++;
			}
		} else if ($this->isCrontabHit()) {
			call_user_func_array($this->executeCallback, array());
		}
	}
}

