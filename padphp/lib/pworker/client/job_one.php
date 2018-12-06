<?php

class PadLib_Pworker_Client_JobOne {
	public $function;
	public $params = array();
	public $options = array();
	
	public function func($function){
		if (is_string($function)) {
			if (strpos($function, '@@') === false) {
				$this->function = $function;
			} else {
				$functionCode = PadLib_Pworker_Client::getFunctionCode(substr($function, 2));
				$functionCode = preg_replace('/^function(.*?)\(/', 'function (', $functionCode);
				$this->function = 'function::'.$functionCode;
			}
		} else if (is_array($function)) {
			$functionCode = PadLib_Pworker_Client::getFunctionCode($function);
			$functionCode = preg_replace('/^function(.*?)\(/', 'function (', $functionCode);
			$this->function = 'function::'.$functionCode;
		} else if (is_object($function)) {
			$functionCode = PadLib_Pworker_Client::getFunctionCode($function);
			$this->function = 'function::'.$functionCode;
		}
		return $this;
	}
	
	public function params($params){
		$this->params = $params;
		return $this;
	}
	
	public function options($options){
		$this->options = $options;
		return $this;
	}
	
	public function getData(){
		return array(
			'type' => 'one',
			'options' => $this->options,
			'function' => $this->function,
			'params' => $this->params,
		);
	}
}
