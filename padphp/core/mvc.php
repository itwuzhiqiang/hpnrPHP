<?php

class PadMvc {
	public $bootFile;
	public $bootDir;
	public $templateDir;
	public $templateCompileDir;
	public $requestUrl;
	public $fullUrl;
	public $defaultDisplayParams = array();
	public $defaultDisplayDatas = array();
	public $activeRequest;
	public $activeResponse;
	public $urlContainerClass;
	public $routerAnalysisOk = false;

	/**
	 * @var PadMvcRouter
	 */
	public $router;

	public $controllerMapping = array();
	public $cptControllerOpen = true;
	public $extendsCptList = array();
	public $errorTemplate;
	public $appType = 'phtml';
	public $routeType = 'phtml';

	public $gOptions = array();
	public $gPost = array();
	public $gGet = array();
	public $gFiles = array();
	public $gServer = array();

	/**
	 * 模板的一些变量定义
	 */
	public $templateConsts = array(
		'helper' => 'helper',
		'error' => 'error',
		'layout' => 'layout',
		'request' => 'request',
		'response' => 'response',
	);

	public function __construct($bootFile, $configFile = null, $params = array()) {
		$this->gOptions = $params;
		$this->gServer = isset($params['gServer']) ? $params['gServer'] : $_SERVER;
		$this->gGet = isset($params['gGet']) ? $params['gGet'] : $_GET;
		$this->gPost = isset($params['gPost']) ? $params['gPost'] : $_POST;
		$this->gFiles = isset($params['gFiles']) ? $params['gFiles'] : $_FILES;

		// 强制追加的变量
		$this->gServer['HTTP_REAL_IP'] = self::getIp();

		// 初始化
		$this->bootFile = $bootFile;
		$this->bootDir = (is_dir($this->bootFile) ? $this->bootFile : dirname($bootFile));
		$this->templateDir = realpath($this->bootDir . DIRECTORY_SEPARATOR . 'template');

		// 默认的controller位置
		PadCore::autoload('Controller_', $this->bootDir . DIRECTORY_SEPARATOR . 'controller');
		PadCore::autoload('Helper_', $this->bootDir . DIRECTORY_SEPARATOR . 'helper');
		PadCore::autoload('DataQL_', $this->bootDir . DIRECTORY_SEPARATOR . 'dataql');

		// mvc配置文件
		$router = array();
		$domain = array();
		$defaultDisplayParam = array();
		$defaultDisplayData = array();

		if ($configFile === null) {
			$configFile = $this->bootDir . DIRECTORY_SEPARATOR . 'mvc-config.php';
		}

		if (is_string($configFile) && file_exists($configFile)) {
			include ($configFile);
		} else if (is_array($configFile)) {
			extract($configFile);
		}

		$this->defaultDisplayParams = $defaultDisplayParam;
		$this->defaultDisplayDatas = $defaultDisplayData;

		if (isset($appType)) {
			$this->appType = $appType;
		}

		if (isset($appRouteType)) {
			$this->routeType = $appRouteType;
		}

		if (isset($template_compile_dir) && is_dir($template_compile_dir)) {
			$this->templateCompileDir = realpath($template_compile_dir);
		}

		if (isset($template_consts)) {
			$this->templateConsts = array_merge($this->templateConsts, $template_consts);
		}

		// cpt优先加载
		if (isset($extendsCptList)) {
			$this->extendsCptList = $extendsCptList;
			foreach ($this->extendsCptList as $key => $config) {
				PadCore::loadCpt($key);
				$webCtlClassName = 'CptModel_'.PadBaseString::padStrtoupper($key).'_Webctl';
				$webCtlFucName = $webCtlClassName.'::mvcConfig';
				if (is_callable($webCtlFucName)) {
					$addConfigs = call_user_func($webCtlFucName);
					foreach ($addConfigs['router'] as $routerItem) {
						$router[] = $routerItem;
					}
				}
			}
		}
		if (isset($cptControllerOpen)) {
			$this->cptControllerOpen = $cptControllerOpen;
		}

		// 进入的url
		$this->requestUrl = isset($this->gServer['REQUEST_URI']) ? $this->gServer['REQUEST_URI'] : '/';

		// 获取当前的请求地址
		$fullUrl = self::isHttps() ? 'https://' : 'http://';
		if (isset($this->gServer['HTTP_X_FORWARDED_HOST'])) {
			$fullUrl .= $this->gServer['HTTP_X_FORWARDED_HOST'];
		} else if (isset($this->gServer['HTTP_HOST'])) {
			$fullUrl .= $this->gServer['HTTP_HOST'];
		}
		$fullUrl .= $this->gServer['REQUEST_URI'];
		$this->fullUrl = $fullUrl;

		// ping
		if ($this->requestUrl === '/_/ping' || strpos($this->requestUrl, '/_/ping?') === 0) {
			header('Content-Type: application/json');
			echo json_encode(array(
				'status' => 'ok',
				'server' => 'padphp',
				'version' => '3.0.0',
			));
			exit;
		}

		// 路由器解析
		$this->router = new PadMvcRouter($this);

		if (isset($url_suffix)) {
			$this->router->urlSuffix = $url_suffix;
		}
		if (isset($url_prefix)) {
			$this->router->urlPrefix = $url_prefix;
		}

		if (isset($domain_default)) {
			$this->router->domainDefault = $domain_default;
		} else {
			$this->router->domainDefault = (isset($this->gServer['HTTPS']) ? 'https' : 'http') . ':/'.'/' . $this->gServer['HTTP_HOST'];
		}

		if (isset($error_template)) {
			$this->errorTemplate = $error_template;
		}

		// 默认的系统路由器
		$this->controllerMapping['PadPkgConfig'] = 'PadPkgControllerConfig';
		$this->controllerMapping['Pdoc'] = 'PadLib_MvcPdoc';
		$this->crudMenuList = array();

		foreach ($router as $r) {
			$this->router->add($r);
		}
		foreach ($domain as $r) {
			$this->router->addDomain($r);
		}
		$this->router->add(array(
			'/',
			'Index,Index',
			null,
			$this->router->domainDefault
		));

		// pdoc开启
		if (isset($pdocEnable) && $pdocEnable) {
			$this->router->add(array(
				'/pdoc',
				'Pdoc,Index',
				null,
				$this->router->domainDefault
			));
		}

		// padreact配置变量
		$this->defaultDisplayDatas['PAD_REACT_WEB_BUILD_VERSION'] = 'v0';

		// URL生成容器
		$this->urlContainerClass = new PadMvcUrlContainer();
	}

	public function getUrl($op, $params = array()) {
		if (strpos($op, '&') === 0) {
			return substr($op, 1);
		}
		
		if (is_string($params)) {
			$sparams = array();
			foreach (explode(',', $params) as $p) {
				$tmp = explode('=', $p);
				if (isset($tmp[1])) {
					$tmp[0] = trim($tmp[0]);
					$tmp[1] = trim($tmp[1]);
					if ($tmp[0]) {
						$sparams[$tmp[0]] = (isset($tmp[1]) ? $tmp[1] : '');
					}
				} else {
					$tmp[0] = trim($tmp[0]);
					$sparams[$tmp[0]] = '';
				}
			}
			$params = $sparams;
		}
		
		$controller = 'Index';
		$action = 'Index';
		if ($this->activeRequest) {
			$controller = $this->activeRequest->controller;
			$action = $this->activeRequest->action;
		}
		
		if ($this->activeRequest && isset($params[':merge']) && $params[':merge']) {
			$params = array_merge($this->activeRequest->params, $params);
			unset($params[':merge']);
			
			if (isset($params[':unset'])) {
				$unset = explode(',', $params[':unset']);
				foreach ($unset as $f) {
					unset($params[$f]);
				}
				unset($params[':unset']);
			}
		}

		if ($this->activeResponse && $this->activeResponse->defaultUrlParams) {
			$params = array_merge($this->activeResponse->defaultUrlParams, $params);
		}
		
		if (isset($params[':default']) && $params[':default']) {
			$params = array_merge($params[':default'], $params);
			unset($params[':default']);
		}
		if ($op) {
			$tmp = explode(',', $op);
			if (! isset($tmp[1])) {
				$action = $tmp[0];
			} else {
				$controller = $tmp[0];
				$action = $tmp[1];
			}
		}
		return $this->router->unAnalysis($controller, $action, $params);
	}

	public function process($initParams = array()) {
		$this->routerAnalysisOk = $this->router->analysis($this->requestUrl);
		$this->processExecute();
	}

	private function processExecute($initParams = array()) {
		$controller = $this->router->controller;
		$action = $this->router->action;
		$params = PadMvcRouter::trimParams($this->router->params);
		$referErrorMessages = array();

		do {
			$baseControllerClass = null;
			if (is_object($controller)) {
				$baseControllerClass = $controller;
				$controller = get_class($controller);
			}

			$request = new PadMvcRequest($this, $controller, $action, $params);
			$response = new PadMvcResponse($this, $controller, $action, $params);

			$response->displayDatas[$this->templateConsts['request']] = $request;
			$response->displayDatas[$this->templateConsts['response']] = $response;
			
			if (isset($initParams['responseHook'])) {
				call_user_func_array($initParams['responseHook'], array($response));
			}
			$response->error->referErrors = $referErrorMessages;
			
			$this->activeRequest = $request;
			$this->activeResponse = $response;
			if (! $this->routerAnalysisOk) {
				$response->displayStatus(404);
				break;
			}

			$controllerName = null;
			$controllerClass = null;
			$ctrlNameLower = null;
			$ctrlNameLowerPrefix = null;			

			if (!$baseControllerClass) {
				$ctrlNameLower = PadBaseString::padStrtolower($controller);
				$ctrlNameLowerPrefix = $ctrlNameLower;
				if (explode('/', $ctrlNameLower) !== false) {
					list($ctrlNameLowerPrefix) = explode('/', $ctrlNameLower);
				}
			} else {
				$controllerName = $controller;
				$controllerClass = $baseControllerClass;
			}
			$actionName = 'do' . $action;
			
			if ($controllerClass) {
			} else if (isset($this->extendsCptList[$ctrlNameLowerPrefix])) {
				if ($this->cptControllerOpen || (isset($this->extendsCptList[$ctrlNameLowerPrefix]['cptControllerOpen']) 
					&& $this->extendsCptList[$ctrlNameLowerPrefix]['cptControllerOpen'])) {
					PadCore::loadCpt($ctrlNameLowerPrefix);
					$className = PadBaseString::padStrtoupper($ctrlNameLower);
					$className = str_replace(PadBaseString::padStrtoupper($ctrlNameLowerPrefix), 'Webctl', $className);
					$controllerClass = cpt($ctrlNameLowerPrefix)->$className();
				} else {
					$controllerName = 'Controller_' . $controller;
				}
			} else if (isset($this->controllerMapping[$controller])) {
				$controllerName = $this->controllerMapping[$controller];
			} else {
				$controllerName = 'Controller_' . $controller;
			}
			
			// 如果没有定义
			if (!$controllerClass) {
				// 类class没有找到
				if (!PadAutoload::classExists($controllerName)) {
					if ($GLOBALS['pad_core']->envConfigs['error_environment'] == 'dev') {
						$GLOBALS['pad_core']->error(E_ERROR, 'controller class "%s" not found', $controllerName);
					} else {
						$response->displayStatus(404);
					}
					return null;
				} else {
					$controllerClass = new $controllerName($request, $response);
				}
			}
			
			$controllerClass->request = $request;
			$controllerClass->response = $response;
			if (method_exists($controllerClass, 'controllerConstruct')) {
				$controllerClass->controllerConstruct($request, $response);
			}
			
			$beforeReturn = null;
			if (method_exists($controllerClass, 'beforeResponse')) {
				$beforeReturn = $controllerClass->beforeResponse($request, $response);
			}

			$actionReturn = null;
			if (! $response->isStopAction) {
				if (! method_exists($controllerClass, '__call') && ! method_exists($controllerClass, $actionName)) {
					if ($GLOBALS['pad_core']->envConfigs['error_environment'] == 'dev') {
						$GLOBALS['pad_core']->error(E_ERROR, 'action "%s->%s" not found', get_class($controllerClass), $actionName);
					} else {
						$response->displayStatus(404);
					}
					return null;
				}
				
				if ($beforeReturn === null) {
					if ($this->appType == 'rest') {
						try {
							$actionReturn = $controllerClass->$actionName($request, $response);
						} catch (PadBizException $e) {
							$actionReturn = array(
								'code' => $e->getCode(),
								'message' => $e->getMessage(),
								'res' => null,
							);
						} catch (PadException $e) {
							throw $e;
						}
					} else {
						$actionReturn = $controllerClass->$actionName($request, $response);
					}
				
					if (method_exists($controllerClass, 'afterResponse')) {
						$controllerClass->afterResponse($request, $response, $actionReturn);
					}
				} else {
					if (method_exists($controllerClass, 'afterResponse')) {
						$controllerClass->afterResponse($request, $response, $beforeReturn);
					}
				}
			}
			
			if ($response->displayType == PadMvcResponse::TYPE_JUMP) {
				$controller = $response->displayParams['jump_controller'];
				$action = $response->displayParams['jump_action'];
				$params = $response->displayParams['jump_params'];
				$referErrorMessages = $response->error->errors;
			} else {
				$response->display($actionReturn);
				break;
			}
		} while (true);

		// 将数据库连接设置为异常模式
		foreach ($GLOBALS['pad_core']->database->connects as $connect) {
			$connect->handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}

		// 提交
		$isError = false;
		$errorMessage = null;
		try {
			$GLOBALS['pad_core']->destruct();
		} catch (Exception $error) {
			$errorMessage = $error->getMessage();
			$isError = true;
		}

		// 发生错误，直接break
		if ($isError) {
			throw new PadException('Database Exception: ' . $errorMessage);
		}
	}

	/**
	 * 请求一个action的结果
	 * @param $controller
	 * @param $action
	 * @param array $params
	 */
	public function requestAction ($controller, $action, $params = array()) {
		$request = new PadMvcRequest($this, $controller, $action, $params);
		$response = new PadMvcResponse($this, $controller, $action, $params);

		if (isset($this->controllerMapping[$controller])) {
			$controllerName = $this->controllerMapping[$controller];
		} else {
			$controllerName = 'Controller_' . $controller;
		}

		$class = new $controllerName();
		$actionName = 'do' . $action;
		$actionReturn = $class->$actionName($request, $response);

		if (isset($response->displayParams['json_data']) && $response->displayParams['json_data'] !== null) {
			return $response->displayParams['json_data'];
		} else {
			return $actionReturn;
		}
	}

	/**
	 * http的认证
	 * @param unknown $userName
	 * @param unknown $password
	 */
	static public function httpAuth ($userName, $password) {
		$gServer = array();
		if (isset($GLOBALS['pad_core']->mvc)) {
			$gServer = $GLOBALS['pad_core']->mvc->gServer;
		} else {
			$gServer = $_SERVER;
		}

		$isPass = true;
		if (!isset($gServer['PHP_AUTH_USER']) || !isset($gServer['PHP_AUTH_PW'])) {
			$isPass = false;
		} else if ($gServer['PHP_AUTH_USER'] != $userName || $gServer['PHP_AUTH_PW'] != $password) {
			$isPass = false;
		}
	
		if (!$isPass) {
			header('WWW-Authenticate: Basic realm="PadPHP CRUD Auth"');
			header('HTTP/1.0 401 Unauthorized');
			exit;
		}
	}

	/**
	 * 判断是不是https
	 * @return bool
	 */
	static public function isHttps () {
		if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
			return true;
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
			return true;
		} elseif (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
			return true;
		}
		return false;
	}
	
	static public function getIp () {
		$gServer = array();
		if (isset($GLOBALS['pad_core']->mvc)) {
			$gServer = $GLOBALS['pad_core']->mvc->gServer;
		} else {
			$gServer = $_SERVER;
		}

		$ip = null;
		if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
			$ip = getenv("HTTP_CLIENT_IP");
		} elseif (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		} elseif (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
			$ip = getenv("REMOTE_ADDR");
		} elseif (isset($gServer['REMOTE_ADDR']) && $gServer['REMOTE_ADDR'] && strcasecmp($gServer['REMOTE_ADDR'], "unknown")) {
			$ip = $gServer['REMOTE_ADDR'];
		} else {
			$ip = "";
		}
		$ips = explode(',', str_replace(' ', '', $ip));
		$rip = '0.0.0.0';
		for ($i = 0; $i < count($ips); $i++) {
			if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $ips[$i])) {
				$rip = $ips[$i];
				break;
			}
		}
		return $rip;
	}
}

/**
 *
 * @author huchunhui
 *         生成URL的类
 */
class PadMvcUrlContainer {

	private $_controllerParams = array();

	/**
	 * 传入参数
	 *
	 * @param unknown $key
	 * @return PadMvcUrlContainer
	 */
	public function __get($key) {
		if (! isset($this->_controllerParams[0])) {
			$this->_controllerParams[] = $key;
		}
		return $this;
	}

	/**
	 * 实际的获得URL
	 *
	 * @param unknown $function
	 * @param unknown $params
	 * @return mixed
	 */
	public function __call($function, $params) {
		$this->_controllerParams[] = $function;
		$controllerParams = $this->_controllerParams;
		$this->_controllerParams = array();
		array_unshift($params, implode(',', $controllerParams));
		return call_user_func_array(array(
			$GLOBALS['pad_core']->mvc,
			'getUrl'
		), $params);
	}
}





