<?php

class PadLib_Pworker_Client_JobSerial {
	public $jobList = array();
	public $options = array();
	
	public function options($options){
		$this->options = $options;
	}
	
	public function addJob($name, $parents, $function, $params = array(), $options = array()){
		$jobOneClass = new PadLib_Pworker_Client_JobOne();
		$jobOneClass->func($function);
		$jobOneClass->params($params);
		$jobOneClass->options($options);
		$this->addJobClass($name, $parents, $jobOneClass);
		return $this;
	}
	
	public function addJobClass($name, $parents, $jobOneClass){
		$this->jobList[] = array(
			'name' => $name,
			'parents' => $parents,
			'class' => $jobOneClass,
		);
		return $this;
	}
	
	public function getData(){
		$list = array();
		foreach ($this->jobList as $job) {
			$item = $job['class']->getData();
			$item['options']['serial'] = array(
				'name' => $job['name'],
				'parents' => $job['parents']
			);
			$list[] = $item;
		}
		
		return array(
			'type' => 'serial',
			'options' => $this->options,
			'list' => $list,
		);
	}
}


