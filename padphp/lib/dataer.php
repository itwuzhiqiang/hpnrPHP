<?php


class PadLib_Dataer {
	private $mapping = array();
	private $request;
	private $response;

	public function __construct($request, $response) {
		$this->request = $request;
		$this->response = $response;
	}

	public function mapping($mapping) {
		foreach ($mapping as $k => $v) {
			$this->mapping[$k] = $v;
		}
	}
	
	public function execute() {
		$fetchObject = $this->request->param('fetch');
		$fetchParams = $this->request->param('params', '[]');
		$fetchParams = json_decode($fetchParams);
		$dataFormat = $this->request->param('format', 'default');

		$mappingCall = null;
		$mappingFormat = null;
		foreach ($this->mapping as $k => $v) {
			if (strpos($k, '/') === 0 && preg_match($k, $fetchObject, $match) > 0) {
				$mappingCall = $v['data'];
				array_shift($match);
				foreach ($match as $k1 => $v1) {
					$mappingCall = str_replace('$' . ($k1 + 1), $v1, $mappingCall);
				}
			} else if ($k == $fetchObject) {
				$mappingCall = $v['data'];
			}

			// 使用的数据结果格式化函数
			if ($mappingCall) {
				if (!isset($v['format'])) {
					$mappingFormat = '@formatDefault';
				} else if (isset($v['format'][$dataFormat])) {
					$mappingFormat = $v['format'][$dataFormat];
				} else if (isset($v['format']['default'])) {
					$mappingFormat = $v['format']['default'];
				} else {
					$mappingFormat = '@formatDefault';
				}
				break;
			}
		}

		if (!$mappingCall) {
			$this->response->json(array(
				'code' => '404',
				'message' => 'not found router',
			));
			return;
		}

		$callObject = null;
		$callMethod = null;
		$callParams = $fetchParams;
		if (is_string($mappingCall)) {
			if (strpos($mappingCall, 'ety::') === 0) {
				$mappingCall = substr($mappingCall, strlen('ety::'));
				list($entity, $callMethod) = explode('.', $mappingCall);
				$callObject = $GLOBALS['pad_core']->orm->getEntityModel($entity);
			}
		} else if (is_array($mappingCall)) {
			list($callObject, $callMethod) = $mappingCall;
		}

		if ($callObject && $callMethod) {
			$return = call_user_func_array(array($callObject, $callMethod), $callParams);
			$return = $this->formatReturn($return, $mappingFormat);
			$this->response->json($return);
		} else {
			$this->response->json(array(
				'code' => '500',
				'message' => 'call (object OR method) not found',
			));
			return;
		}
	}

	/**
	 * 格式化返回结果
	 *
	 * @param $return
	 * @param $format
	 * @return array|mixed
	 */
	public function formatReturn($return, $format) {
		if (is_string($format)) {
			if (strpos($format, '@') === 0) {
				$format = array($this, substr($format, 1));
			} else if (strpos($format, 'ety::') === 0) {
				$format = substr($format, strlen('ety::'));
				list($entity, $callMethod) = explode('.', $format);
				$model = $GLOBALS['pad_core']->orm->getEntityModel($entity);
				$format = array($model, $callMethod);
			}
		}

		if (is_array($return)) {
			foreach ($return as $i => $val) {
				if (strpos(get_class($val), 'Entity_') === 0) {
					$return[$i] = call_user_func_array($format, array($val));
				}
			}
		} else if (is_object($return)) {
			if (strpos(get_class($return), 'Entity_') === 0) {
				$return = call_user_func_array($format, array($return));
			}
		}
		return $return;
	}

	/**
	 * 默认的数据格式化,处理entity对象和其他对象
	 *
	 * @param $value
	 * @return mixed
	 */
	private function formatDefault ($value) {
		if (is_object($value)) {
			if (strpos(get_class($value), 'Entity_') === 0) {
				return $value->__datas();
			} else if (get_class($value) == 'stdclass') {
				return $value;
			} else {
				return $value->__datas();
			}
		} else {
			return $value;
		}

	}
}

