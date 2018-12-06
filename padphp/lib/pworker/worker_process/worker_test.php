<?php

class PadLib_Pworker_WorkerProcess_WorkerTest extends PadLib_Pworker_WorkerProcess_Worker {
	
	public function __construct($jobData) {
		$this->_jobData = $jobData;
	}
	
	public function setReturn($data){
		return $data;
	}
}

