<?php

class PadBaseValidation {
	static $errorLastMessage;
	static $errorLastCode = 0;

	public $stringPregs = array();
	private $query = array();

	/**
	 * 快速通过表达式验证
	 * @param $value
	 * @param $query
	 * @return bool
	 */
	static public function quickCheck ($value, $query, $callback = null) {
		$query = str_replace(' ', '', $query);

		if (strpos($query, '=>') !== false) {
			list($valueKey, $query) = explode('=>', $query);
			$valueKeyList = explode('.', $valueKey);

			$loopValue = $value;
			if (!is_object($loopValue) && !is_array($loopValue)) {
				self::quickCheckCallback($callback);
				return false;
			}

			foreach ($valueKeyList as $k) {
				if (is_array($loopValue) && isset($loopValue[$k])) {
					$loopValue = $loopValue[$k];
				} else if (is_object($loopValue) && isset($loopValue->$k)) {
					$loopValue = $loopValue->$k;
				} else {
					$loopValue = null;
					break;
				}
			}
			$value = $loopValue;
		}

		$va = new PadBaseValidation();
		if (is_string($query)) {
			$va->queryExp($query);
		} else if (is_object($query) && $query instanceof PadBaseValidationQueryOr) {
			$va->queryOr($query);
		} else if (is_array($query)) {
			$va->query($query);
		}
		$ret = $va->validation($value);

		if (!$ret) {
			self::quickCheckCallback($callback);
		}
		return $ret;
	}

	static public function quickCheckCallback ($callback) {
		if (!$callback) {
			return;
		}

		if (is_object($callback) && $callback instanceof PadException) {
			throw $callback;
		} else if (is_callable($callback)) {
			$callback('error', 100);
		} else if (is_string($callback)) {
			throw new PadBizException($callback);
		}
	}

	static public function stringPregs () {
		$stringPregs = array();

		// 基本数据类型
		$stringPregs['null'] = function ($value) {
			return $value === null || $value === '';
		};
		$stringPregs['empty'] = function ($value) {
			return $value === '' || $value === array() || $value === null || $value === new stdClass();
		};
		$stringPregs['json'] = function ($value) {
			@json_decode($value);
			return json_last_error() == JSON_ERROR_NONE;
		};

		// 字符串相关的验证
		$stringPregs['string'] = '/^.+$/';
		$stringPregs['/^string\((\d+)-(\d+)\)$/'] = '/^.{$$1,$$2}$/';
		$stringPregs['/^string\((\d+)\)$/'] = '/^.{$$1}$/';
		$stringPregs['/^in_array\(([a-zA-Z0-9_\-\.,]+)\)$/'] = function ($value, $list) {
			return strpos($list.',', $value.',') !== false;
		};

		// 特定类型的验证
		$stringPregs['date'] = '/^(\d{2}|\d{4})-\d{1,2}-\d{1,2}$/';
		$stringPregs['time'] = '/^\d{1,2}:\d{1,2}(:\d{1,2})*$/';
		$stringPregs['datetime'] = '/^(\d{2}|\d{4})-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}(:\d{1,2})*$/';
		$stringPregs['email'] = '/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9_\-\.]+$/';
		$stringPregs['mobile'] = '/^\d{11}$/';

		// 数字相关的验证
		$stringPregs['number'] = '/^[0-9\.]+$/';
		$stringPregs['float'] = '/^[0-9]+\.[0-9]+$/';
		$stringPregs['int'] = '/^[0-9]+$/';
		$stringPregs['/^number\(([\d\.]+)\)$/'] = '/^[0-9\.]{$$1}$/';
		$stringPregs['/^number\(([\d\.]+)-([\d\.]+)\)$/'] = '/^[0-9\.]{$$1,$$2}$/';
		$stringPregs['/^range\(([\d\.]+)-([\d\.]+)\)$/'] = function ($value, $min, $max) {
			if (!is_numeric($value)) {
				return false;
			}
			return $value >= $min && $value <= $max;
		};
		$stringPregs['/^number\(([>=]+)([0-9\.]+)\)$/'] = function ($value, $eq, $valueTo) {
			if (!is_numeric($value)) {
				return false;
			}
			if ($eq == '>') {
				return $value > $valueTo;
			} else if ($eq == '>=') {
				return $value >= $valueTo;
			} else if ($eq == '<') {
				return $value < $valueTo;
			} else if ($eq == '<=') {
				return $value <= $valueTo;
			} else {
				return false;
			}
		};

		// 数组相关的验证
		$stringPregs['array'] = function ($value) {
			return is_array($value);
		};

		$stringPregs['/^array\((\d+)\)$/'] = function ($value, $length) {
			if (!is_array($value)) {
				return false;
			}
			return count($value) == $length;
		};

		$stringPregs['/^array\((\d+)-(\d+)\)$/'] = function ($value, $min, $max) {
			if (!is_array($value)) {
				return false;
			}
			return count($value) >= $min && count($value) <= $max;
		};

		// 对象相关的验证
		$stringPregs['object'] = function ($value) {
			return is_object($value);
		};

		// 与实体相关的验证
		$stringPregs['ety_null'] = function ($value) {
			return is_object($value) && $value instanceof PadOrmEntityNull;
		};

		$stringPregs['ety'] = function ($value) {
			return is_object($value) && $value instanceof PadOrmEntity;
		};

		return $stringPregs;
	}

	public function __construct() {}

	/**
	 * 通过表达式验证
	 * @param $exp
	 */
	public function queryExp ($exp) {
		if (strpos($exp, '[') === 0 || strpos($exp, '{') === 0) {
			$this->query($exp);
		} else if (strpos($exp, '||') !== false) {
			$this->queryOr(explode('||', $exp));
		} else {
			foreach (explode('&&', $exp) as $item) {
				$this->query($item);
			}
		}
		return $this;
	}

	/**
	 * 或匹配，一个查询条件是一个数组的话
	 * @param array $query
	 * @throws PadException
	 */
	public function queryOr ($query = array()) {
		$queryList = array();
		foreach ($query as $q) {
			$queryList[] = $this->getQuery($q);
		}
		$this->query[] = new PadBaseValidationQueryOr($queryList);
		return $this;
	}

	public function query ($query) {
		$this->query[] = $this->getQuery($query);
		return $this;
	}

	/**
	 * 匹配结果
	 * @param $value
	 * @return bool
	 */
	public function validation ($value) {
		foreach ($this->query as $query) {
			$subSucc = false;
			if ($query instanceof PadBaseValidationQueryOr) {
				foreach ($query->queryList as $subQuery) {
					$subSucc = $subSucc || $this->validationQuery($value, $subQuery);
				}
			} else {
				$subSucc = $this->validationQuery($value, $query);
			}

			if (!$subSucc) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 获得格式化以后的匹配条件
	 * @param $query
	 * @return mixed
	 * @throws PadException
	 */
	private function getQuery ($query) {
		// 直接使用原生的方法验证，不推荐使用
		if (is_array($query)) {
			return $query;
		}

		// !null 或者 !number 取反
		$returnNot = false;
		if (strpos($query, '!') === 0) {
			$query = substr($query, 1);
			$returnNot = true;
		}

		// 直接使用正则表达式验证
		if (strpos($query, '/') === 0) {
			return array('preg', $query, array(), $returnNot);
		}

		// 验证的是一个对象
		if (strpos($query, '{') === 0) {
			$query = substr($query, 1, -1);
			if (strpos($query, '{') !== false) {
				throw new PadErrorException('Object Error Loop { }');
			}

			$pregItems = array();
			foreach (explode(',', $query) as $line) {
				list($key, $value) = explode(':', $line);
				$va = new self();
				$va->queryExp($value);
				$pregItems[$key] = $va;
			}
			return array('ObjectPreg', $pregItems, array(), $returnNot);
		}

		// 验证的是一个数组
		if (strpos($query, '[') === 0) {
			$query = substr($query, 1, -1);
			$va = new self();
			$va->queryExp($query);
			return array('ArrayPreg', $va, array(), $returnNot);
		}

		// 直接命中内置验证规则的KEY
		foreach (self::stringPregs() as $key => $preg) {
			$pregType = 'Preg';
			$pregCallback = null;
			$pregParams = array();

			if (strpos($key, '/') === 0 && preg_match($key, $query, $match) > 0) {
				if (is_string($preg)) {
					for ($i = 1; $i < count($match); $i++) {
						$preg = str_replace('$$' . $i, $match[$i], $preg);
					}
				} else if (is_callable($preg)) {
					array_shift($match);
					$pregParams = $match;
					$pregType = 'Callback';
				}
				$pregCallback = $preg;
			} else if ($key == $query) {
				if (is_callable($preg)) {
					$pregType = 'Callback';
				}
				$pregCallback = $preg;
			}

			if ($pregCallback) {
				return array($pregType, $pregCallback, $pregParams, $returnNot);
			}
		}

		throw new PadErrorException('Query Error: ' . $query);
	}

	/**
	 * 项目验证
	 * @param $value
	 * @param $query
	 * @return mixed
	 */
	private function validationQuery ($value, $query) {
		$type = array_shift($query);
		$returnNot = $query[2];
		$ret = true;
		if ($type == 'Callback') {
			array_unshift($query[1], $value);
			$ret = call_user_func_array($query[0], $query[1]);
		} else {
			array_unshift($query, $value);
			$ret = call_user_func_array(array($this, 'validationQuery' . $type), $query);
		}
		return $returnNot ? !$ret : $ret;
	}

	/**
	 * 正则表达式的验证
	 * @param $value
	 * @param $preg
	 * @return bool
	 */
	private function validationQueryPreg ($value, $preg) {
		return preg_match($preg, $value) > 0 ? true : false;
	}

	/**
	 * 数组验证
	 * @param $value
	 * @param $preg
	 */
	private function validationQueryArrayPreg ($value, $preg) {
		if (!is_array($value)) {
			return false;
		}

		foreach ($value as $idx => $v) {
			if (is_string($preg) && preg_match($preg, $v) <= 0) {
				// 字符串,则时数组单一元素验证
				return false;
			} else if (is_array($preg) && !$this->validationQueryObjectPreg($v, $preg)) {
				// 数组,则是数组对象元素验证
				return false;
			} else if (is_object($preg) && $preg instanceof PadBaseValidation && !$preg->validation($v)) {
				return false;
			} else if (is_callable($preg) && !call_user_func_array($preg, array($value))) {
				// 回调函数验证
				return false;
			}
		}
		return true;
	}

	/**
	 * 对象验证
	 * @param $values
	 * @param $pregs
	 * @return bool
	 */
	private function validationQueryObjectPreg ($values, $pregs) {
		if (!is_array($values) && !is_object($values)) {
			return false;
		}

		foreach ($pregs as $key => $va) {
			$value = null;
			if (is_array($values) && isset($values[$key])) {
				$value = $values[$key];
			} else if (is_object($values) && isset($values->$key)) {
				$value = $values->$key;
			}

			if (!$va->validation($value)) {
				return false;
			}
		}
		return true;
	}
}

/**
 * 或条件的验证
 * Class PadBaseValidationQueryOr
 */
class PadBaseValidationQueryOr {
	public $queryList;

	public function __construct($queryList) {
		$this->queryList = $queryList;
	}
}



