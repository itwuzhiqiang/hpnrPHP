<?php

class PadLib_Pworker_WorkerProcess_Worker {
	public $_jobData;
	public $_returnPosString;
	
	public function __construct() {
		$jobData = stream_get_contents(STDIN);
		$this->_jobData = json_decode($jobData, true);
		$this->_returnPosString = $_SERVER['PwReturnPos'];
	}
	
	public function setReturn($data){
		echo $this->_returnPosString.json_encode($data);
	}
	
	public function work(){
		$jobParams = (object) array(
			'id' => $this->_jobData['options']['id'],
		);
		$params = $this->_jobData['params'];
		array_unshift($params, $jobParams);
		
		$func = $this->_jobData['function'];
		$return = null;
		if (strpos($func, 'function::') !== 0) {
			if (strpos($func, '.') !== false) {
				list($className, $method) = explode('.', $func);
				$class = new $className();
				$func = array($class, $method);
			}
		} else {
			$func = eval('return '.substr($func, strlen('function::')).';');
		}
		$return = call_user_func_array($func, $params);
		return $this->setReturn($return);
	}
}

