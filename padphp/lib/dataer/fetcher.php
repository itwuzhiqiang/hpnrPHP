<?php


class PadLib_Dataer_Fetcher {

	private $entityLoader;

	public function __construct($entityLoader) {
		$this->entityLoader = $entityLoader;
	}

	public function makeValue($value, $fields) {
		foreach ($fields as $idx => $fld) {
			if (!is_array($fld)) {
				$fields[$idx] = strval($fld);
			}
		}

		$return = new stdClass();
		foreach ($fields as $idx => $fld) {
			if (is_array($fld)) {
				list($k, $value) = $fld;
				$return->$k = $value;
				continue;
			}

			$fldDeep = explode('.', $fld);
			$fldDeepCount = count($fldDeep);

			$valueTemp = $value;
			$returnTemp = $return;

			$preFd = null;
			foreach ($fldDeep as $ii => $fd) {
				$ii = $ii + 1;

				if ($ii == $fldDeepCount) {
					$rValue = $returnTemp->{$preFd};
					if (strpos($fd, '@ddt|') === 0) {
						$rValue = PadLib_Dataer_DataType::format($rValue, $fd);
					} else if (strpos($fd, '@fetcher|') === 0) {
						list($null, $fetcherName) = explode('|', $fd);
						$rValue = $valueTemp->$fetcherName();
					} else if (strpos($fd, '()') !== false) {
						$fetcherName = str_replace('()', '', $fd);
						$rValue = $valueTemp->$fetcherName();
					}
					$returnTemp->{$preFd} = $rValue;
				} else if ($ii == $fldDeepCount - 1) {
					$valueTemp = $valueTemp->$fd;
					$returnTemp->{$fd} = (is_object($valueTemp) && $valueTemp->isnull ? null : $valueTemp);
				} else {
					$valueTemp = $valueTemp->$fd;
					if (!isset($returnTemp->{$fd})) {
						$returnTemp->{$fd} = new stdClass();
					}
					$returnTemp = $returnTemp->{$fd};
				}
				$preFd = $fd;
			}
		}
		return $return;
	}

	public function __get($name) {
		$obj = new PadLib_Dataer_FetcherObject($this->entityLoader);
		return $obj->$name;
	}

	public function __call($name, $arguments) {
		$obj = new PadLib_Dataer_FetcherObject($this->entityLoader);
		return call_user_func_array(array($obj, $name), $arguments);
	}
}

class PadLib_Dataer_FetcherObject {
	private $_entityLoader;
	private $_parentName;

	public function __construct($entityLoader, $parentName = null) {
		$this->_entityLoader = $entityLoader;
		$this->_parentName = $parentName;

		if ($this->_parentName) {
			$this->_parentName = $this->_parentName.'.';
		}
	}

	public function fetcher ($name) {
		return $this->_parentName . '@fetcher|' . $name;
	}

	public function __get($name) {
		if ($this->_entityLoader->fieldExists($name)) {
			return new PadLib_Dataer_DataType($this->_parentName . $name);
		} else if ($this->_entityLoader->relationExists($name)) {
			return new PadLib_Dataer_FetcherObject($this->_entityLoader->relationLoader($name), $this->_parentName . $name);
		} else {
			return new PadLib_Dataer_DataType($this->_parentName . $name);
		}
	}

	public function __call($name, $arguments) {
		return new PadLib_Dataer_DataType($this->_parentName . $name.'()');
	}
}

