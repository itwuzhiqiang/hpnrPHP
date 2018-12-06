<?php

class PadModel {
	public $modelList = array();
	
	public function __call($func, $params){
		//uksort($params);
		$reflect  = new ReflectionClass('Model_'.$func);
		$instance = $reflect->newInstanceArgs($params);
		return $instance;
	}

	public function get ($name) {
		$className = PadBaseString::padStrtoupper($name);
		return $this->$className();
	}
}


