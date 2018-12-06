<?php

class PadLib_Dataer_DataType {
	//public $TypeString = 'TypeString';
	//public $TypeDate = 'TypeDate';
	//public $TypeNumber = 'TypeNumber';

	private $_parentName;

	static public function format($value, $type) {
		if ($type == '@ddt|TypeString') {
			return strval($value);
		} else if ($type == '@ddt|TypeNumber') {
			return intval($value);
		} else if ($type == '@ddt|TypeDate') {
			return date('Y-m-d', $value);
		} else if ($type == '@ddt|TypeFsUrl') {
			return cpt('filestore')->getUrl($value);
		} else {
			return 'UNKNOWN TYPE';
		}
	}

	public function __construct($parentName = null) {
		$this->_parentName = $parentName;
	}

	public function __get($name) {
		return $this->_parentName . '.@ddt|' . $name;
	}

	public function __toString() {
		if (strpos($this->_parentName, '()') === false) {
			return $this->_parentName . '.@ddt|TypeString';
		} else {
			return $this->_parentName;
		}
	}
}

