<?php

class PadMvcRequest {
	public $fullUrl;
	public $mvc;
	public $controller;
	public $action;
	public $params = array();

	public $mode = 'run';
	public $documentParams = array();

	/**
	 * 初始化
	 *
	 * @param unknown_type $controller
	 * @param unknown_type $action
	 * @param unknown_type $params
	 */
	public function __construct($mvc, $controller, $action, $params) {
		$this->mvc = $mvc;
		$this->controller = $controller;
		$this->action = $action;
		$this->params = $params;
		$this->fullUrl = $mvc->fullUrl;

		// 替换JSON类型
		foreach ($this->params as $key => $value) {
			if (is_string($value) && strpos($value, '__JSON__') === 0) {
				$value = json_decode(substr($value, 8), true);
				$this->params[$key] = $value;
			}
		}
		
		// 自动trim参数
		self::trimParams($this->params);

		// 模式
		$this->mode = $this->param('__pad_mode', 'run');
	}

	/**
	 * 设置请求参数的默认值
	 *
	 * @param unknown_type $params
	 */
	public function setParamsDefaultValue($params) {
		foreach ($params as $k => $v) {
			if (! isset($this->params[$k])) {
				$this->params[$k] = $v;
			}
		}
	}

	/**
	 * 设置当前的请求参数
	 * @param $key
	 * @param $value
	 */
	public function setParam ($key, $value) {
		$this->params[$key] = $value;
	}

	/**
	 * 获得一个请求参数
	 *
	 * @param string $name
	 * @param mix $default
	 * @return mixed
	 */
	public function param($name, $default = null) {
		return isset($this->params[$name]) ? $this->params[$name] : $default;
	}
	
	public function paramHas($name){
		return isset($this->params[$name]) ? true : false;
	}

	/**
	 * 获取一个环境参数
	 *
	 * @param $key
	 * @param null $default
	 * @return mix
	 */
	public function env ($key, $default = null) {
		return isset($this->mvc->gServer[$key]) ? $this->mvc->gServer[$key] : $default;
	}
	
	/**
	 * 获得一个参数，没有则设置默认值
	 * @param unknown $name
	 * @param string $default
	 */
	public function paramWithDefault($name, $default = null){
		if (! isset($this->params[$name])) {
			$this->params[$name] = $default;
		}
		return $this->param($name);
	}

	/**
	 * 获得文件的原始数据
	 *
	 * @param string $key
	 * @return Ambigous <NULL, unknown>
	 */
	public function fileRaw($key) {
		$raw = isset($this->mvc->gFiles[$key]) ? $this->mvc->gFiles[$key] : null;
		if (!$raw) {
			return null;
		}
		
		if (!is_array($raw['name']))  {
			if (!$raw || !isset($raw['tmp_name']) || !file_exists($raw['tmp_name'])) {
				$raw = null;
			}
			return $raw;
		} else {
			$return = array();
			foreach ($raw['name'] as $idx => $null) {
				$return[$idx] = array(
					'name' => $raw['name'][$idx],
					'type' => $raw['type'][$idx],
					'tmp_name' => $raw['tmp_name'][$idx],
					'size' => $raw['size'][$idx],
					'error' => $raw['error'][$idx],
				);
				if (!file_exists($return[$idx]['tmp_name'])) {
					$return[$idx] = null;
				}
			}
			return $return;
		}
	}

	/**
	 * 获得POST的原始数据
	 *
	 * @return string
	 */
	public function postRawData() {
		return file_get_contents('php:/'.'/input');
	}

	public function defineParam ($key, $default, $vcheck, $title = '', $desc = '') {
		if ($this->mode == 'document') {
			$this->documentParams[$key] = array(
				'key' => $key,
				'default' => $default,
				'vcheck' => $vcheck,
				'title' => $title,
				'desc' => $desc,
			);
		} else if ($this->mode == 'run') {
			$value = $this->param($key, $default);

			// JSON格式的参数
			if (is_string($value) && strpos($value, '__JSON__') === 0) {
				$value = json_decode(substr($value, 8), true);
				$this->params[$key] = $value;
			}

			// 检测
			if ($vcheck) {
				vcheck($value, $vcheck, '参数['.$key.']错误');
			}

			return $value;
		}
	}

	public function defineParamFinish () {
		if ($this->mode == 'document') {
			throw new PadBizException('DOC');
		}
	}

	/**
	 * 检查输入参数是否有错误
	 *
	 * @param string $name 参数
	 * @param string $format 规定的格式
	 * @param string $errorKey
	 * @param string $errorMessage
	 */
	public function checkParam($name, $format, $errorKey = null, $errorMessage = null) {
		$names = explode(',', $name);
		$activeResponse = $GLOBALS['pad_core']->mvc->activeResponse;
		foreach ($names as $name) {
			$value = reqval($name);
			
			if (gettype($format) == 'object') {
				$res = $format($value);
				if (is_array($res)) {
					list($errorKey, $errorMessage) = $res;
					$activeResponse->error($errorKey, $errorMessage);
				}
			} elseif ($format == '@not_empty' && ($value === NULL || $value === '')) {
				$activeResponse->error($errorKey, $errorMessage);
			} elseif ($format == '@number_greater_0' && $value <= 0) {
				$activeResponse->error($errorKey, $errorMessage);
			} elseif (strpos($format, '/') !== false && ! preg_match($format, $value)) {
				$activeResponse->error($errorKey, $errorMessage);
			}
		}
	}

	/**
	 * 和 checkParam 一样，只是可以传入一个数组
	 *
	 * @param array $list
	 */
	public function checkParams($list) {
		foreach ($list as $item) {
			list ($name, $format, $errorKey, $errorMessage) = $item;
			$this->checkParam($name, $format, $errorKey, $errorMessage);
		}
	}
	
	/**
	 * 通过实体检测输入
	 * @param unknown $entityName
	 * @param unknown $id
	 * @param unknown $config
	 */
	public function checkByEntity ($entityName, $id, $config) {
		$activeResponse = $GLOBALS['pad_core']->mvc->activeResponse;
		$datas = array();
		$messageKeys = array();
		foreach ($config as $line) {
			list($field, $paramKey, $messageKey) = explode(',', $line);
			$datas[$field] = reqval($paramKey);
			$messageKeys[$field] = $messageKey;
		}
		
		$checkRes = ety($entityName)->checkDatas($datas, $id);
		foreach ($checkRes as $k => $message) {
			$activeResponse->error($messageKeys[$k], $message);
		}
	}

	/**
	 * 获得来源的URL
	 * @return Ambigous <mix, multitype:>|unknown|string
	 */
	public function getReferUrl($default = ''){
		if ($this->param('referUrl')) {
			return $this->param('referUrl');
		} else if (isset($_SERVER['HTTP_REFERER'])) {
			return $_SERVER['HTTP_REFERER'];
		} else {
			return $default;
		}
	}

	public function __call($name, $arguments) {
		$activeResponse = $GLOBALS['pad_core']->mvc->activeResponse;
		if (strpos($name, 'do') === 0) {
			list($actionGet, $callback) = $arguments;
			$action = substr($name, 2);
			$actionKey = PadBaseString::padStrtolower($action);
			$actionValue = $this->param($actionKey, 'default');

			if ($actionGet == $actionValue) {
				$callback($this, $activeResponse);
			}
		}
	}

	/**
	 * 自动trim传入参数
	 *
	 * @param mix $item
	 * @return mix
	 */
	static public function trimParams($item) {
		return is_array($item) ? array_map('PadMvcRequest::trimParams', $item) : trim($item);
	}
}


