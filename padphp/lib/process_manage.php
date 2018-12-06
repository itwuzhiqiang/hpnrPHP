<?php

class PadLib_ProcessManage {
	private $_bootFile;
	private $_processList = array();

	public function __construct($bootFile, $processList) {
		$this->_bootFile = $bootFile;
		$this->_processList = $processList;
	}

	public function getNowProcessList() {
		$string = exec('ps -ef|grep php');
		$list = explode("\n", $string);
		$return = array();
		foreach ($list as $line) {
			$return[] = trim($line);
		}
		return $return;
	}

	public function workDaemonLoop() {
		$nowList = $this->getNowProcessList();
		$processList = $this->_processList;
		
		foreach ($nowList as $line) {
			foreach ($processList as $idx => $process) {
				if (strpos($line, $process['command']) !== false) {
					$this->_processList[$idx]['maxNumber'] --;
				}
			}
		}
		
		$allChildPids = array();
		foreach ($processList as $process) {
			$isRun = true;
			if (isset($process['schedule']) && !$this->isSchedule($process['schedule'])) {
				$isRun = false;
			}
			
			while ($isRun && $process['maxNumber'] -- > 0) {
				$childPid = pcntl_fork();
				if ($childPid < 0) {
					// @todo
				} else 
					if ($childPid > 0) {
						$allChildPids[] = $childPid;
					} else {
						system($process['command']);
					}
			}
		}
	}

	public function work() {
		$daemonPid = pcntl_fork();
		if ($daemonPid < 0) {
			// @todo
		} else 
			if ($daemonPid > 0) {
				// @todo
			} else {
				$this->workDaemon();
				exit();
			}
	}

	private function isSchedule($schedule) {
		return true;
	}
}


