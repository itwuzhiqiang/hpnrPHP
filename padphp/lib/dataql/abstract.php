<?php

abstract class PadLib_Dataql_Abstract {
	public $dataql;
	abstract public function config();

	public function getContext ($key) {
		return isset($this->dataql->context[$key]) ? $this->dataql->context[$key] : null;
	}

	public function getData () {
		$fieldsList = array();
		$methodList = array();

		foreach (get_class_methods($this) as $methodName) {
			if (strpos($methodName, '_method_') === 0) {
				$method = new PadLib_Dataql_Method();
				$this->$methodName($method);
				$methodList[substr($methodName, 8)] = $method->getData();
			} else if (strpos($methodName, '_fields_') === 0) {
				$fields = new PadLib_Dataql_Fields();
				$this->$methodName($fields);
				$fieldsList[substr($methodName, 8)] = $fields->getData();
			}
		}

		$configRaw = $this->config();
		return array(
			'config' => array(
				'source' => $configRaw['source'],
			),
			'fetchField' => $fieldsList,
			'fetchMethod' => $methodList,
		);
	}

	public function methodExecute ($methodName) {

	}
}



