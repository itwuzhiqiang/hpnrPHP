<?php

class PadLib_Crontab_Worker {

	public function getTasks () {
		$tasks = array();
		foreach (get_class_methods($this) as $method) {
			if (strpos($method, 'task') === 0) {
				$task = new PadLib_Crontab_Task();
				$this->$method($task);
				$isHit = $task->isCrontabHit();

				$tasks[] = array(
					'class' => str_replace('PadCrontab_', '', get_class($this)),
					'method' => $method,
					'isHit' => $isHit,
				);
			}
		}
		return $tasks;
	}

	public function execteTask ($method) {
		$task = new PadLib_Crontab_Task();
		$this->$method($task);
		$task->execute();
	}
}


