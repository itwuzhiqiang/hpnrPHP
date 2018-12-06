<?php

class PadLib_Dataql_Method {

	public $params = array();
	public $paramsDefault = array();

	public $dataType = 'entity';
	public $dataTypeOptions = array();

	public $fieldsAllow = array();

	public $executeMethod;
	public $executeCallback;
	public $dataTrans;

	public function setParam ($key, $default = false) {
		$this->params[] = $key;
		if ($default !== false) {
			$nkey = $key;
			if (strpos($nkey, '*') === 0) {
				$nkey = substr($key, 2);
			}
			$this->paramsDefault[$nkey] = $default;
		}
		return $this;
	}

	/**
	 * @param string $type entity,entityList,entityPageList,entityCall,result
	 *        entity 单个实体
	 *        entityList 实体列表，无分页
	 *        entityPageList 实体列表，有分页
	 *        entityCall 调用实体上的方法
	 *        result 原始的返回值
	 * @param array $typeOptions
	 * @return $this
	 */
	public function setDataType ($type, $typeOptions = array()) {
		$this->dataType = $type;
		$this->dataTypeOptions = $typeOptions;
		return $this;
	}

	public function setFieldsAllow ($nameList) {
		$this->fieldsAllow = $nameList;
		return $this;
	}

	/**
	 * 调用的方法名称
	 * @param $methodName
	 */
	public function setExecuteMethod ($methodName) {
		$this->executeMethod = $methodName;
		return $this;
	}

	public function setDataTrans ($cbk) {
		$this->dataTrans = $cbk;
	}

	/**
	 * 自定义调用方法
	 * @param $callback
	 */
	public function setExecute ($callback) {
		$this->executeCallback = $callback;
	}

	public function getData () {
		$dataType = 'detail';
		$withPage = false;
		$methodType = 'entity';

		if ($this->dataType == 'entity') {
			$dataType = 'detail';
			$methodType = 'loader';
		} else if ($this->dataType == 'entityList') {
			$dataType = 'list';
			$methodType = 'loader';
		} else if ($this->dataType == 'entityPageList') {
			$dataType = 'list';
			$withPage = true;
			$methodType = 'loader';
		} else if ($this->dataType == 'result') {
			$dataType = 'result';
			$methodType = 'loader';
		} else if ($this->dataType == 'entityCall') {
			$methodType = 'entityCall';
		}

		$retMethod = null;
		if ($this->executeCallback) {
			$retMethod = $this->executeCallback;
		} else if ($this->executeMethod) {
			$retMethod = $this->executeMethod;
		}

		return array(
			'allowFields' => $this->fieldsAllow,
			'comment' => '',
			'method' => new stdClass(),
			'methodValue' => $retMethod,
			'dataTrans' => $this->dataTrans,
			'methodType' => $methodType,
			'dataType' => $dataType,
			'withPage' => $withPage,
			'defineParams' => $this->params,
			'defaultParams' => $this->paramsDefault,
		);
	}
}


