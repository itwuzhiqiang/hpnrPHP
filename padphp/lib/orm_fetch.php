<?php

class PadLib_OrmFetch {
	private $options = array();
	private $contextParams = array();
	private $schemeList = array();

	public $context = array();

	public function __construct($options = array()) {
		$this->options = array_merge(array(
			'ignoreParams' => array(),
		), $options);
		$this->getSchemeList();
	}

	public function setContextParam ($key, $value) {
		$this->contextParams[$key] = $value;
	}

	public function setContext ($key, $val) {
		$this->context[$key] = $val;
	}

	public function batchFetchByRequest (PadMvcRequest $request, PadMvcResponse $response) {
		$batchList = $request->param('json');
		$batchList = json_decode($batchList, true);


		$returnJson = array();
		foreach ($batchList as $k => $batch) {
			list($entity, $method, $fieldGroup) = explode('.', $batch['query']);
			try {
				$json = $this->fetch($entity, $method, $fieldGroup, $batch['params'], $request, $response);
				$returnJson[$k] = $json;
			} catch (PadException $e) {
				PadException::catched();
				$returnJson[$k] = array(
					'code' => $e->getCode(),
					'message' => $e->getMessage(),
					'timestrap' => (isset($batch['params']['_timestrap']) ? $batch['params']['_timestrap'] : 0),
					'fields' => $e->getErrorFields(),
					'res' => null,
				);
			}

		}
		return array(
			'code' => 0,
			'res' => $returnJson,
		);
	}

	public function fetchByRequest (PadMvcRequest $request, PadMvcResponse $response) {
		$entity = $request->param('__entity');
		$method = $request->param('__method');
		$fieldGroup = $request->param('__fields');

		try {
			return $this->fetch($entity, $method, $fieldGroup, $request->params, $request, $response);
		} catch (PadException $e) {
			PadException::catched();
			return array(
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
				'timestrap' => $request->params('_timestrap', 0),
				'fields' => $e->getErrorFields(),
				'res' => null,
			);
		}
	}

	public function fetch($entity, $method, $fieldGroup, $reqParams, PadMvcRequest $request, PadMvcResponse $response) {
		$schemeList = $this->schemeList;
		$schemeConfig = array();
		$fetchConfig = array();

		if (isset($schemeList[$entity])) {
			$schemeConfig = $schemeList[$entity];
			$fetchConfig = $schemeConfig['fetchMethod'][$method];
		}

		$defaultParams = (isset($fetchConfig['defaultParams']) ? $fetchConfig['defaultParams'] : array());
		$defineParams = (isset($fetchConfig['defineParams']) ? $fetchConfig['defineParams'] : array());

		foreach ($defineParams as $k) {
			$realKey = $k;
			if (strpos($k, '*') === 0) {
				$realKey = substr($k, 2);
			}
			if (!isset($reqParams[$realKey]) && isset($defaultParams[$realKey])) {
				$reqParams[$k] = $defaultParams[$realKey];
			}
		}

		// 修正提交的参数
		foreach ($reqParams as $k => $v) {
			if (is_string($v) && isset($this->contextParams[$v])) {
				$reqParams[$k] = $this->contextParams[$v];
			}

			if (is_string($v) && strpos($v, '__JSON__') === 0) {
				$reqParams[$k] = json_decode(substr($v, 8), true);
			}
		}

		$getParam = function ($key, $default = null) use ($reqParams) {
			return isset($reqParams[$key]) ? $reqParams[$key] : $default;
		};

		if ($entity == 'workflow') {
			return $this->fetchWorkflow($method, $fieldGroup, $getParam);
		}

		$methodCall = $method;

		// 忽略的参数
		unset($reqParams['__entity']);
		unset($reqParams['__method']);
		unset($reqParams['__fields']);

		$tParams = array();
		$tParamsOk = true;
		$tParamsMiss = array();
		$xParams = array();
		$reqParamsCopy = $reqParams;

		// 尝试从提交的变量构造params
		foreach ($defineParams as $k) {
			if ($k == '*') {
			} else if (strpos($k, '*') === 0) {
				if (preg_match('/^\*\.(.*?)$/', $k, $match)) {
					$xParam = $match[1];
					if (!isset($reqParams[$xParam])) {
						$tParamsOk = false;
						$tParamsMiss[] = $xParam;
					} else if ($reqParams[$xParam] == '@NULL') {
						$xParams[$xParam] = null;
						unset($reqParamsCopy[$k]);
					} else {
						$xParams[$xParam] = $reqParams[$xParam];
						unset($reqParamsCopy[$k]);
					}
				}
			} else {
				if (!isset($reqParams[$k])) {
					$tParamsOk = false;
					$tParamsMiss[] = $k;
				} else {
					$value = $reqParamsCopy[$k];
					if ($value == '@NULL') {
						$value = null;
					}
					$tParams[] = $value;
					unset($reqParamsCopy[$k]);
				}
			}
		}

		// 如果定义了*,标示允许其他的参数
		if (in_array('*', $defineParams)) {
			// 不允许已经忽略的参数
			foreach ($this->options['ignoreParams'] as $pKey) {
				unset($reqParamsCopy[$pKey]);
			}
			$xParams = array_merge($xParams, $reqParamsCopy);
		}

		// 加入*的参数
		if ($xParams) {
			$tParams[] = $xParams;
		}

		// 参数不全的提示
		if (!$tParamsOk) {
			throw new PadErrorException('参数不全:' . implode('|', $tParamsMiss));
		}
		$params = $tParams;

		// 字段继承
		foreach ($schemeConfig['fetchField'] as $key => $fldList) {
			foreach ($schemeConfig['fetchField'] as $key2 => $fldList2) {
				foreach ($fldList2 as $kidx => $fkey) {
					if ($fkey == '@extends('.$key.')') {
						unset($schemeConfig['fetchField'][$key2][$kidx]);
						foreach ($schemeConfig['fetchField'][$key] as $fld) {
							$schemeConfig['fetchField'][$key2][] = $fld;
						}
					}
				}
			}
		}

		$fields = array();
		if (isset($schemeConfig['fetchField'][$fieldGroup])) {
			$fields = $schemeConfig['fetchField'][$fieldGroup];
		}

		if (isset($fetchConfig['method'])) {
			$methodCall = $fetchConfig['method'];
		}

		// 请求的方法类型
		$methodType = (isset($fetchConfig['methodType']) ? $fetchConfig['methodType'] : 'loader');
		$withPage = (isset($fetchConfig['withPage']) && $fetchConfig['withPage']);

		$methodResult = null;
		$methodResultPageInfo = null;

		// 模型的类型(实体或者controller)
		$source = $schemeList[$entity]['config']['source'];
		$schemeThisType = '';
		$entity = '';
		if (strpos($source, '::') === false) {
			list($schemeThisType) = explode('::', $source);
		} else {
			list($schemeThisType, $entity) = explode('::', $source);
		}

		if (is_string($methodCall)) {
			// 请求方法的类型
			if ($schemeThisType == 'entity') {
				$entityModel = ety($entity);
				if ($methodType == 'loader') {
					$loader = $entityModel;
					if ($withPage) {
						$loader = $entityModel->loader();
						$page = $getParam('page', 1);
						$pageSize = $getParam('page_size', 10);
						$loader->page($page, $pageSize);
					}
					$methodResult = call_user_func_array(array($loader, $methodCall), $params);

					// 一些特殊loader方法的纠错处理
					if ($methodCall == 'get') {
						vcheck($methodResult, '!ety_null', '请求的数据不存在');
					} else if ($methodCall == 'replace') {
						$methodResult->checkToFlush();
					}

					if ($withPage) {
						$methodResultPageInfo = $loader->getPageInfo();
					}
				} else if ($methodType == 'entity') {
					if (!$getParam('id')) {
						throw new PadErrorException('请求类型为实体时必须设置"id"参数');
					}
					$entityDetail = $entityModel->get($getParam('id'));
					$methodResult = call_user_func_array(array($entityDetail, $methodCall), $params);
					if ($methodCall == 'sets') {
						$entityDetail->checkToFlush();
						$methodResult = $entityDetail;
					}
				}
			} else if ($schemeThisType == 'controller') {
				$methodCall = PadBaseString::padStrtolower($methodCall);
				list($req, $res) = $response->callAction($entity, $methodCall, $reqParams);
				if ($res->displayType == PadMvcResponse::TYPE_JSON) {
					$methodResult = $res->displayParams['json_data'];
				}
			} else if ($schemeThisType == 'report') {
				$report = report($entity);
				$methodResult = call_user_func_array(array($report, $methodCall), $params);
			}
		} else {
			$methodCall = $fetchConfig['methodValue'];

			if ($schemeThisType == 'entity') {
				if ($methodType == 'loader') {
					$loader = ety($entity);

					if ($withPage) {
						$page = $getParam('page', 1);
						$pageSize = $getParam('page_size', 10);
						$loader = $loader->loader();
						$loader->page($page, $pageSize);
					}

					if (is_string($methodCall)) {
						$methodResult = call_user_func_array(array($loader, $methodCall), $params);
					} else {
						$cparams = $params;
						array_unshift($cparams, $loader);
						$methodResult = call_user_func_array($methodCall, $cparams);
					}

					if ($withPage) {
						$methodResultPageInfo = $loader->getPageInfo();
					}
				} else if ($methodType == 'entityCall') {
					$entityDetail = ety($entity)->get($getParam('id'));
					if (is_string($methodCall)) {
						$methodResult = call_user_func_array(array($entityDetail, $methodCall), $params);
					} else {
						$methodResult = call_user_func_array($methodCall, array($entityDetail));
					}
				}
			} else if ($schemeThisType == 'report') {
				$report = report($entity);
				$methodResult = call_user_func_array(array($report, $methodCall), $params);
			} else if ($schemeThisType == 'free') {
				$methodResult = call_user_func_array($methodCall, array($params, $request));
			} else if ($schemeThisType == 'controller') {
				$controllerClass = 'Controller_' . PadBaseString::padStrtoupper($entity);
				$controller = new $controllerClass();
				if (is_string($methodCall)) {
					$methodResult = call_user_func_array(array($controller, 'do' . PadBaseString::padStrtoupper($methodCall)), array($request, $response));
				}else {
					$methodResult = call_user_func_array($methodCall, array($request, $response));
				}
			} else if ($schemeThisType == 'rest') {
				list($cptClass, $cptModel) = explode('@', $entity);
				$rest = new PadCptRest_Rest();
				$result = null;
				if (is_string($methodCall)) {
					$result = $rest->runMethod(str_replace('@', '/', $entity), $methodCall, $reqParams);
				} else {
					$result = call_user_func_array($methodCall, array($reqParams));
				}

				if ($fetchConfig['dataType'] == 'detail') {
					$methodResult = $result;
				} else if ($fetchConfig['dataType'] == 'list') {
					$loader = $result;

					if ($withPage) {
						$page = $getParam('page', 1);
						$pageSize = $getParam('page_size', 10);
						$loader->page($page, $pageSize);
					}

					$methodResult = $loader->getList();

					if ($withPage) {
						$methodResultPageInfo = $loader->getPageInfo();
					}
				} else if ($fetchConfig['dataType'] == 'result') {
					$methodResult = $result;
				}
			}

			if ($fetchConfig['dataTrans']) {
				$methodResult = call_user_func_array($fetchConfig['dataTrans'], array($methodResult));
			}
		}

		// 返回的数据类型
		$returnData = array();
		if ($fetchConfig['dataType'] == 'list') {
			if ($schemeThisType == 'free') {
				$returnData = array(
					'list' => $methodResult['list'],
					'pageInfo' => $methodResult['pageInfo'],
				);
			} else {
				$returnData['list'] = array();
				if ($withPage) {
					$returnData['pageInfo'] = $methodResultPageInfo;
				}
				foreach ($methodResult as $e) {
					$returnData['list'][] = $this->getEntityItem((object) $e, $fields);
				}
			}
		} else if ($fetchConfig['dataType'] == 'detail') {
			$returnData = $this->getEntityItem((object) $methodResult, $fields);
		} else if ($fetchConfig['dataType'] == 'result') {
			$returnData = $methodResult;
		}

		return array(
			'code' => 0,
			'message' => '',
			'timestrap' => $request->params('_timestrap', 0),
			'res' => $returnData,
		);
	}

	private function getSchemeList () {
		if (isset($this->options['schemeDir'])) {
			foreach (glob($this->options['schemeDir'].'/*.json') as $file) {
				$name = str_replace($this->options['schemeDir'], '', $file);
				$name = str_replace('.json', '', $name);
				$name = trim($name, '/');
				$this->schemeList[$name] = json_decode(file_get_contents($file), true);
			}
		} else if (isset($this->options['dataQLDir'])) {
			$configs = include ($this->options['dataQLDir'] . '/__index.php');
			foreach ($configs as $name => $null) {
				$className = 'DataQL_' . PadBaseString::padStrtoupper($name);
				$class = (new $className());
				$class->dataql = $this;
				$this->schemeList[$name] = $class->getData();
			}
		}

		$this->schemeList['app_api'] = array(
			'config' => array(
				'source' => 'controller::app_api',
			),
			'fetchField' => array(),
			'fetchMethod' => array(
				'report_live' => array(
					"dataType" => "result",
				),
				'report_location' => array(
					"dataType" => "result",
				),
				'fs_info' => array(
					"dataType" => "result",
				),
			),
		);
	}

	private function getEntityItem ($entity, $fields) {
		$regField = '([a-zA-Z0-9_&]+)';
		$regFieldParam = '([a-zA-Z0-9_&,]+)';
		$regList = array(
			'fldAsWorkflow' => '/wfDetail\('.$regField.'\.'.$regField.'\)\s+as\s+'.$regField.'/',
			'fldAsScheme' => '/'.$regField.'\('.$regField.'\.'.$regField.'\)\s+as\s+'.$regField.'/',
			'fldAsSchemeList' => '/'.$regField.'\(\['.$regField.'\.'.$regField.'\]\)\s+as\s+'.$regField.'/',
			'fldAsMethod' => '/'.$regField.'\['.$regFieldParam.'\]\s+as\s+'.$regField.'/',
			'fldAsMethodScheme' => '/'.$regField.'\['.$regFieldParam.'\]\('.$regField.'\.'.$regField.'\)\s+as\s+'.$regField.'/',
			'fldAsMethodSchemeList' => '/'.$regField.'\['.$regFieldParam.'\]\(\['.$regField.'\.'.$regField.'\]\)\s+as\s+'.$regField.'/',
		);
		//$fields = array_unique($fields);

		$fieldFormat = array();
		foreach ($fields as $idx => $fld) {
			if (is_string($fld) && strpos($fld, '|') !== false) {
				list($fld, $format) = explode('|', $fld);
				$fields[$idx] = $fld;
				$fieldFormat[$fld] = $format;
			}
		}

		$schemeList = $this->schemeList;

		$retItem = array();
		foreach ($fields as $fld) {
			$key = $fld;

			// 字段是回调类型
			if ($fld instanceof PadLib_Dataql_Fields_Callback) {
				$retItem[$fld->field] = call_user_func_array($fld->callback, array($entity));
				continue;
			}

			if (strpos($fld, ' as ') !== false) {
				if (preg_match($regList['fldAsWorkflow'], $fld, $match)) {
					array_shift($match);
					list($wfName, $wfHostId, $key) = $match;
					$workflow = workflow($wfName)->get($entity->$wfHostId);
					$retItem[$key] = array(
						'status' => $workflow->getStatus(),
						'statusNamed' => $workflow->getStatusNamed(),
						'allowFire' => $workflow->getAllowFireList(),
					);
				} else if (preg_match($regList['fldAsScheme'], $fld, $match)) {
					array_shift($match);
					list($fld, $schemeEntity, $schemeFld, $key) = $match;

					if (!is_object($entity->$fld)) {
						if (is_string($entity->$fld) || is_numeric($entity->$fld)) {
							list($schemeThisType, $entityName) = explode('::', $schemeList[$schemeEntity]['config']['source']);
							$entity->$fld = ety($entityName)->get($entity->$fld);
						}
					}

					if ($entity->$fld->isnull) {
						$retItem[$key] = null;
					} else {
						if (!isset($schemeList[$schemeEntity])) {
							throw new PadErrorException('scheme not found: ' . $schemeEntity);
						}
						$scmConfig = $schemeList[$schemeEntity];

						$fields = $scmConfig['fetchField'][$schemeFld];
						$retItem[$key] = $this->getEntityItem($entity->$fld, $fields);
					}
				} else if (preg_match($regList['fldAsMethod'], $fld, $match)) {
					array_shift($match);
					list($method, $methodP0, $key) = $match;
					$methodP0 = explode(',', $methodP0);
					foreach ($methodP0 as $k => $v) {
						if (isset($this->contextParams[$v])) {
							$methodP0[$k] = $this->contextParams[$v];
						}
					}
					$retItem[$key] = call_user_func_array(array($entity, $method), $methodP0);
				} else if (preg_match($regList['fldAsMethodScheme'], $fld, $match)) {
					array_shift($match);
					list($method, $methodP0, $schemeEntity, $schemeFld, $key) = $match;
					$methodP0 = explode(',', $methodP0);
					foreach ($methodP0 as $k => $v) {
						if (isset($this->contextParams[$v])) {
							$methodP0[$k] = $this->contextParams[$v];
						}
					}
					$ret = call_user_func_array(array($entity, $method), $methodP0);
					$scmConfig = $schemeList[$schemeEntity];
					$fields = $scmConfig['fetchField'][$schemeFld];

					$retItem[$key] = $this->getEntityItem($ret, $fields);
				} else if (preg_match($regList['fldAsMethodSchemeList'], $fld, $match)) {
					array_shift($match);
					list($method, $methodP0, $schemeEntity, $schemeFld, $key) = $match;
					$methodP0 = explode(',', $methodP0);
					foreach ($methodP0 as $k => $v) {
						if (isset($this->contextParams[$v])) {
							$methodP0[$k] = $this->contextParams[$v];
						}
					}
					$retList = call_user_func_array(array($entity, $method), $methodP0);

					$scmConfig = $schemeList[$schemeEntity];
					$fields = $scmConfig['fetchField'][$schemeFld];

					$retItem[$key] = array();
					foreach ($retList as $i) {
						$retItem[$key][] = $this->getEntityItem($i, $fields);
					}
				} else if (preg_match($regList['fldAsSchemeList'], $fld, $match)) {
					array_shift($match);
					list($fld, $schemeEntity, $schemeFld, $key) = $match;

					$scmConfig = $schemeList[$schemeEntity];
					$fields = $scmConfig['fetchField'][$schemeFld];

					$retItem[$key] = array();
					foreach ($entity->$fld as $i) {
						$retItem[$key][] = $this->getEntityItem($i, $fields);
					}
				} else {
					list($getTo, $key) = explode(' as ', $fld);
					$retItem[$key] = $entity->get($getTo);
				}
			} else {
				$entity = (object) $entity;
				$retItem[$key] = $entity->$fld;
			}

			if (is_object($retItem[$key]) && isset($retItem[$key]->isnull) && $retItem[$key]->isnull) {
				$retItem[$key] = null;
			}

			if (isset($fieldFormat[$fld])) {
				$retItem[$fld] = $this->formatValue($retItem[$fld], $fieldFormat[$fld]);
			}
		}
		return $retItem;
	}

	private function formatValue ($value, $format) {
		if ($format == 'datetime') {
			return $value == 0 ? 0 : date('Y-m-d H:i', $value);
		} else if ($format == 'date') {
			return $value == 0 ? 0 : date('Y-m-d', $value);
		} else if ($format == 'money') {
			return $value == 0 ? 0 : money($value / 100 );
		}
	}

	/**
	 * 获取workflow的状态
	 * @param $wfname
	 * @param $action
	 * @param $getParam
	 * @return array
	 * @throws PadException
	 */
	public function fetchWorkflow ($wfname, $action, $getParam) {
		$action = $getParam('action', $action);
		$id = $getParam('id');
		$params = $getParam('params', array());

		vcheck($id, '!empty', 'ID 不存在');

		$returnData = null;
		$workflow = workflow($wfname)->get($id);
		if ($action == 'wfDetail') {
			$returnData = array(
				'status' => $workflow->getStatus(),
				'statusNamed' => $workflow->getStatusNamed(),
				'allowFire' => $workflow->getAllowFireList(),
			);
		} else {
			// 替换参数
			foreach ($params as $k => $value) {
				if (isset($this->contextParams[$value])) {
					$params[$k] = $this->contextParams[$value];
				}
			}

			// 执行fire
			array_unshift($params, $action);
			$workflow->checkAllowFire($action, $params);

			$fireResult = call_user_func_array(array($workflow, 'fire'), $params);

			$returnData = array(
				'succ' => true,
				'status' => $workflow->getStatus(),
				'statusNamed' => $workflow->getStatusNamed(),
				'allowFire' => $workflow->getAllowFireList(),
				'fireResult' => $fireResult,
			);
		}

		return array(
			'code' => 0,
			'message' => '',
			'res' => $returnData,
		);
	}
}

