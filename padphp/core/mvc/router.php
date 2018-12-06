<?php

class PadMvcRouter {
	public $mvc;
	public $urlSuffix = '.phtml';
	public $urlPrefix = '';

	public $controller = 'Index';
	public $action = 'Index';
	public $params = array();
	public $routerList = array();
	public $domainCurrent;
	public $domainDefault;
	public $rouerRuleType = 'default';

	public function __construct($mvc) {
		$this->mvc = $mvc;
		$httpScheme = 'http';
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$httpScheme = 'https';
		}
		$this->domainCurrent = $httpScheme . ':/'.'/' . strtolower($_SERVER['HTTP_HOST']);
	}

	/**
	 * 添加一个路由规则
	 */
	public function add($router) {
		preg_match_all('/\(\:(.*?)\)/', $router[0], $paramItems);
		$params = array_unique($paramItems[1]);
		sort($params);
		
		$tmpstr = explode(',', $router[1]);
		$initParams = array();
		if (isset($router[2])) {
			if (is_string($router[2])) {
				parse_str($router[2], $initParams);
			} else {
				$initParams = $router[2];
			}
		}
		
		$tmp = array(
			'rule' => $router[0],
			'castr' => $router[1],
			'controller' => $tmpstr[0],
			'action' => isset($tmpstr[1]) ? $tmpstr[1] : 'Index',
			'init_params' => $initParams,
			'params' => $params,
			'domain' => isset($router[3]) ? $router[3] : $this->domainDefault
		);
		$this->routerList[] = $tmp;
	}

	/**
	 * 根据url解析成controller,action
	 */
	public function analysis($analysisUri) {
		/**
		 * 去除?后的内容
		 */
		if (($wpos = strpos($analysisUri, '?')) !== false) {
			$analysisUri = substr($analysisUri, 0, $wpos);
		}
		
		if ($this->urlPrefix) {
			$ruleString = $this->urlPrefix;
			preg_match_all('/\(\:(.*?)\)/', $ruleString, $paramItems);
			$paramsKeys = array();
			foreach ($paramItems[1] as $pmKey) {
				$paramsKeys[] = $pmKey;
			}
			
			$ruleString = $this->urlPrefix;
			foreach ($paramItems[0] as $idx => $item) {
				$ruleString = str_replace($item, '###param###', $ruleString);
			}
			$ruleString = preg_quote($ruleString, '/');
			$ruleString = '^' . str_replace('###param###', '([^\/]+?)', $ruleString) . '($|\/)';
			
			$params = array();
			if (preg_match('/' . $ruleString . '/', $analysisUri, $matches)) {
				foreach ($paramsKeys as $idx => $pmKey) {
					$params[$pmKey] = $matches[$idx + 1];
				}
				$this->params = $params;
				$analysisUri = preg_replace('/' . $ruleString . '/', '/', $analysisUri);
			}
		}
		
		/**
		 * op参数优先级高于pad_static_rule
		 */
		if (strpos($analysisUri, $this->urlSuffix) !== false) {
			$padRuleAnalysisUri = trim($analysisUri, '/');
			$padRuleAnalysisUri = preg_replace('/\\' . $this->urlSuffix . "$/", '', $padRuleAnalysisUri);
			$tmp = explode('.', $padRuleAnalysisUri);
			if (isset($tmp[1])) {
				$this->controller = PadBaseString::padStrtoupper($tmp[0]);
				$this->action = PadBaseString::padStrtoupper($tmp[1]);
			} else {
				$this->controller = PadBaseString::padStrtoupper($tmp[0]);
			}
			$this->params = array_merge($this->mvc->gPost, $this->params);
			$this->params = array_merge($this->mvc->gGet, $this->params);
			return true;
		}
		
		/**
		 * 路由解析
		 */
		$isMatchRouter = false;
		foreach ($this->routerList as $routerRule) {
			if ($this->domainCurrent != $routerRule['domain']) {
				continue;
			}
			if (strpos($routerRule['castr'], '*') !== false) {
				continue;
			}
			
			$ruleString = $routerRule['rule'];
			preg_match_all('/\(\:(.*?)\)/', $ruleString, $paramItems);
			
			$paramsKeys = array();
			$paramsRules = array();
			foreach ($paramItems[0] as $idx => $item) {
				$ruleString = str_replace($item, '###param###', $ruleString);
				$paramsKeys[$idx] = $paramItems[1][$idx];
			}
			$ruleString = preg_quote($ruleString, '/');
			$ruleString = '^' . str_replace('###param###', '([^\/]+?)', $ruleString) . '$';
			
			$params = $this->params;
			if (preg_match('/' . $ruleString . '/', $analysisUri, $matches)) {
				$isMatchRouter = true;
				foreach ($paramsKeys as $idx => $key) {
					$params[$key] = urldecode($matches[$idx + 1]);
				}
				$this->controller = $routerRule['controller'];
				$this->action = $routerRule['action'];
				$this->params = array_merge($routerRule['init_params'], $params);
				break;
			}
		}
		
		if ($isMatchRouter) {
			// 合并传入参数
			$this->params = array_merge($this->mvc->gPost, $this->params);
			$this->params = array_merge($this->mvc->gGet, $this->params);
			return true;
		} else if ($this->mvc->routeType == 'rest' && strpos($analysisUri, $this->urlSuffix) === false) {
			$padRuleAnalysisUri = trim($analysisUri, '/');
			$tmp = explode('/', $padRuleAnalysisUri);
			$action = array_pop($tmp);
			$this->controller = PadBaseString::padStrtoupper(implode('/', $tmp));
			$this->action = PadBaseString::padStrtoupper($action);
			$this->params = array_merge($this->mvc->gPost, $this->params);
			$this->params = array_merge($this->mvc->gGet, $this->params);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 根据controller,action反解析成url
	 */
	public function unAnalysis($controller, $action, $params) {
		$returnUrl = null;
		
		// 有URL前置
		$urlPrefix = '';
		if ($this->urlPrefix) {
			$urlPrefix = $this->urlPrefix;
			foreach ($params as $key => $value) {
				if (strpos($urlPrefix, '(:'.$key.')') !== false) {
					$urlPrefix = str_replace('(:'.$key.')', urlencode($value), $urlPrefix);
					unset($params[$key]);
				}
			}
		}
		
		$tmpparams = $params;
		$minParamDiff = 100;
		$minParamDiffTmp = 0;
		
		$hitDomain = null;
		foreach ($this->routerList as $routerItem) {
			if ($controller == $routerItem['controller'] && $action == $routerItem['action']) {
				
				// 合并init的参数KEY
				$tmpparams = $params;
				
				// 如果定义初始化参数，初始化参数key和值必须一致
				if ($routerItem['init_params']) {
					if (array_diff_assoc($routerItem['init_params'], $params)) {
						continue;
					} else {
						foreach ($routerItem['init_params'] as $k => $null) {
							unset($tmpparams[$k]);
						}
					}
				}
				
				$paramsKeys = array_keys($tmpparams);
				sort($paramsKeys);
				
				// 传入参数必须大于默认的参数
				if (! array_diff($routerItem['params'], $paramsKeys)) {
					if (! array_diff($paramsKeys, $routerItem['params'])) {
						// 地址和参数完全命中
						$returnUrl = $routerItem['rule'];
						foreach ($params as $key => $value) {
							$returnUrl = str_replace('(:' . $key . ')', urlencode($value), $returnUrl);
						}
						$minParamDiff = 0;
						$hitDomain = $routerItem['domain'];
						break;
					} else {
						// 地址和参数非完全命中
						$minParamDiffTmp = count($paramsKeys) - count($routerItem['params']);
						if ($minParamDiffTmp < $minParamDiff) {
							$minParamDiff = $minParamDiffTmp;
							$returnUrl = $routerItem['rule'];
							foreach ($routerItem['params'] as $key) {
								$returnUrl = str_replace('(:' . $key . ')', urlencode($tmpparams[$key]), $returnUrl);
								unset($tmpparams[$key]);
							}
							$hitDomain = $routerItem['domain'];
						}
					}
				}
			}
		}
		
		if ($returnUrl === null) {
			if ($controller == 'Index' && $action == 'Index') {
				$returnUrl = '/';
				if ($this->domainCurrent !== $this->domainDefault) {
					$returnUrl = $this->domainDefault . '/';
				}
			} else if ($this->mvc->routeType == 'phtml') {
				$returnUrl = '/' . PadBaseString::padStrtolower($controller) . '.' . PadBaseString::padStrtolower($action) . $this->urlSuffix;
			} else if ($this->mvc->routeType == 'rest') {
				$returnUrl = '/' . PadBaseString::padStrtolower($controller) . '/' . PadBaseString::padStrtolower($action);
			}
		}

		if ($minParamDiff > 0 && $tmpparams) {
			$returnUrl .= (strpos($returnUrl, '?') !== false ? '&' : '?') . http_build_query($tmpparams);
		}
		$returnUrl = $urlPrefix . $returnUrl;
		
		// 命中域名，并且域名不为当前域名
		if ($hitDomain !== null && $this->domainCurrent !== $hitDomain) {
			$returnUrl = $hitDomain . $returnUrl;
		} elseif ($hitDomain === null && $this->domainCurrent !== $this->domainDefault) {
			$returnUrl = $this->domainDefault . $returnUrl;
		}
		
		return $returnUrl;
	}

	/**
	 * 自动trim传入参数
	 */
	static public function trimParams($item) {
		return is_array($item) ? array_map('PadMvcRouter::trimParams', $item) : trim($item);
	}
}

