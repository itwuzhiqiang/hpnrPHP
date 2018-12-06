<?php

class PadMvcResponse {
	const PADPHP_HEADER = '';

	const TYPE_JUMP = 101;

	const TYPE_HTML = 102;

	const TYPE_TEXT = 103;

	const TYPE_PRINT = 104;
	const TYPE_JSON = 114;
	
	const TYPE_REDIRECT = 105;

	const TYPE_TEMPLATE_CODE = 106;
	const TYPE_END = 108;

	public $mvc;
	public $controller;
	public $action;
	public $params = array();
	public $hasException = false;
	
	public $isStopAction = false;
	public $displayType;
	public $displayParams = array();
	public $displayDatas = array();
	public $defaultUrlParams = array();
	public $error;
	public $layout;
	public $helper;

	public function __construct($mvc, $controller, $action, $params) {
		$this->mvc = $mvc;
		$this->controller = $controller;
		$this->action = $action;
		$this->params = $params;
		$this->displayType = self::TYPE_HTML;

		if ($this->mvc->appType == 'rest') {
			$this->displayType = self::TYPE_JSON;
		}
		
		$this->displayParams = array_merge(array(
			'layout_name' => 'default',
			'charset' => 'utf-8'
		), $mvc->defaultDisplayParams);
		
		$this->displayDatas = $mvc->defaultDisplayDatas;
		
		$this->error = new PadMvcHelperError();
		$this->displayDatas[$mvc->templateConsts['error']] = $this->error;
		
		$this->layout = new PadMvcResponseLayout();
		$this->displayDatas[$mvc->templateConsts['layout']] = $this->layout;
		
		$this->helper = new PadMvcHelper();
		$this->displayDatas[$mvc->templateConsts['helper']] = $this->helper;
	}

	/**
	 * 获得往页面输入的内容
	 * @param unknown $name
	 * @return multitype:
	 */
	public function getData ($name, $def = array()) {
		return isset($this->displayDatas[$name]) ? $this->displayDatas[$name] : $def;
	}
	
	public function stopAction() {
		$this->isStopAction = true;
	}

	public function getDisplayParam($key, $default = null) {
		return isset($this->displayParams[$key]) ? $this->displayParams[$key] : $default;
	}

	public function templateLayout ($name) {
		$this->setDisplayParam('layout_name', $name);
	}

	public function setDisplayParam($key, $value) {
		$this->displayParams[$key] = $value;
	}

	public function set($key, $value) {
		$this->displayDatas[$key] = $value;
	}

	public function template($path) {
		$this->displayType = self::TYPE_HTML;
		$this->displayParams['template_path'] = $path;
	}
	
	public function lookupTemplate ($paths = array()) {
		foreach ($paths as $path) {
			$newPath = $this->_getTemplateFile($path);
			if (file_exists($newPath)) {
				$this->displayParams['template_path'] = '&'.$newPath;
				break;
			}
		}
	}

	public function jump($controller, $action, $params = array()) {
		$this->displayType = self::TYPE_JUMP;
		$this->displayParams['jump_controller'] = $controller;
		$this->displayParams['jump_action'] = $action;
		$this->displayParams['jump_params'] = $params;
	}

	public function text($text) {
		$this->displayType = self::TYPE_TEXT;
		$this->displayParams['print_text'] = $text;
	}

	public function dtext($text) {
		$this->displayType = self::TYPE_PRINT;
		$this->displayParams['print_text'] = $text;
	}
	
	public function end () {
		$this->displayType = self::TYPE_END;
	}
	
	public function json($json) {
		$this->displayType = self::TYPE_JSON;
		$this->displayParams['json_data'] = $json;
	}

	public function pipe ($url) {
		$oCurl = curl_init();

		$header[] = "Content-type: application/x-www-form-urlencoded";
		$user_agent = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36";
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_HTTPHEADER,$header);
		curl_setopt($oCurl, CURLOPT_HEADER, true);
		curl_setopt($oCurl, CURLOPT_NOBODY, false);
		curl_setopt($oCurl, CURLOPT_USERAGENT,$user_agent);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($oCurl, CURLOPT_POST, false);

		$sContent = curl_exec($oCurl);
		$headerSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
		$header = substr($sContent, 0, $headerSize - 4);
		$body = substr($sContent, $headerSize);

		$headerLines = explode("\r\n", $header);
		$status = array_shift($headerLines);
		foreach ($headerLines as $line) {
			header($line);
		}
		curl_close($oCurl);
		echo $body;
		exit;
	}

	public function eexResult ($loader, $options = array()) {
		$eex = new PadLib_Eex($GLOBALS['pad_core']->mvc->activeRequest, $options);
		$this->displayType = self::TYPE_JSON;
		$this->displayParams['json_data'] = $eex->getResult($loader);
	}
	
	public function templateCode($code) {
		$this->displayType = self::TYPE_TEMPLATE_CODE;
		$this->displayParams['template_code'] = $code;
	}

	public function redirect($op, $params = array()) {
		$this->displayType = self::TYPE_REDIRECT;
		if (strpos($op, '&') === 0) {
			$this->displayParams['redirect_url'] = substr($op, 1);
		} else {
			$this->displayParams['redirect_url'] = $this->mvc->getUrl($op, $params);
		}
	}

	public function reactCreater ($creater = null) {
		if (!$creater) {
			$creater = new PadLib_React_Creater();
		}
		$this->displayType = self::TYPE_TEXT;
		$this->displayParams['print_text'] = $creater;
		return $creater;
	}

	public function callAction ($controller, $action, $params = array()) {
		$request = new PadMvcRequest($GLOBALS['pad_core']->mvc, $controller, $action, $params);
		$response = new PadMvcResponse($GLOBALS['pad_core']->mvc, $controller, $action, $params);

		$GLOBALS['pad_core']->mvc->activeRequest = $request;
		$GLOBALS['pad_core']->mvc->activeResponse = $response;

		$controllerClass = 'Controller_' . PadBaseString::padStrtoupper($controller);
		$actionMethod = 'do' . PadBaseString::padStrtoupper($action);

		$controllerObject = new $controllerClass($request, $response);
		call_user_func_array(array($controllerObject, $actionMethod), array($request, $response));

		return array($request, $response);
	}

	public function message($message, $buttons = array()) {
		$this->displayParams['template_path'] = '/_message';
		$this->displayDatas['coreMessageStr'] = $message;
		$this->displayDatas['coreMessageButtons'] = $buttons;
	}

	public function error($key, $message) {
		return $this->error->message($key, $message);
	}

	public function notError() {
		return ! $this->error->hasError();
	}

	public function hasError() {
		return $this->error->hasError();
	}

	public function forError($type = null) {
		return $this->error->execute($type);
	}
	
	public function fetch($template = null, $vars = array()){
		extract($this->params);
		extract($this->displayDatas);
		extract($vars);
		
		$__tmpfile = $this->compileTemplateFile($this->_getTemplateFile($template));
		ob_start();
		include($__tmpfile);
		return ob_get_clean();
	}

	public function display($actionReturn = null) {
		$padphpHeader = 'X-Powered-By2: PadPHP';
		if (defined('PADPHP_PUB_VERSION')) {
			$padphpHeader .= '-'.PADPHP_PUB_VERSION;
		}
		$this->_header($padphpHeader);

		// MD5 HOSTNAME
		if (isset($_SERVER['HOSTNAME'])) {
			$this->_header('X-Server-Name: ' . strtoupper(substr(md5($_SERVER['HOSTNAME']), 8, 8)));
		}
		
		if (isset($this->displayParams['display_before'])) {
			$req = $this->mvc->activeRequest;
			$res = $this->mvc->activeResponse;
			call_user_func_array($this->displayParams['display_before'], array($req, $res));
		}
		
		switch ($this->displayType) {
			case self::TYPE_HTML:
			case self::TYPE_TEXT:
				$this->_header('Content-Type: text/html; charset=' . $this->displayParams['charset']);
				extract($this->params);
				extract($this->displayDatas);
				
				if (isset($this->displayParams['layout_name'])) {
					$this->layout->blockBegin('@content');
					$this->displayMainContent();
					$this->layout->blockEnd();
					if (strpos($this->displayParams['layout_name'], '&') !== 0) {
						$__tmpfile = $this->compileTemplateFile(
								$this->mvc->templateDir . DIRECTORY_SEPARATOR . '_layout.' . $this->displayParams['layout_name'] . '.php');
						include ($__tmpfile);
					} else {
						include (substr($this->displayParams['layout_name'], 1));
					}
				} else {
					$this->displayMainContent();
				}
				break;
			case self::TYPE_TEMPLATE_CODE:
				eval('?>' . $this->displayParams['template_code']);
				break;
			case self::TYPE_PRINT:
				echo isset($this->displayParams['print_text']) ? $this->displayParams['print_text'] : 'null text';
				break;
			case self::TYPE_JSON:
				$this->_header('Content-Type: application/json; charset=' . $this->displayParams['charset']);
				echo isset($this->displayParams['json_data']) ? json_encode($this->displayParams['json_data']) : json_encode($actionReturn);
				break;
			case self::TYPE_REDIRECT:
				$this->displayStatus(301, array(
					'redirect_url' => $this->displayParams['redirect_url']
				));
				break;
			case self::TYPE_END:
				break;
			default:
				$GLOBALS['pad_core']->error(E_ERROR, 'PadMvcResponse display type "%s" error', $this->displayType);
		}
	}

	public function displayMainContent() {
		if ($this->displayType == self::TYPE_HTML) {
			extract($this->params);
			extract($this->displayDatas);
			$__tmpfile = $this->compileTemplateFile($this->_getTemplateFile());
			if (file_exists($__tmpfile)) {
				include ($__tmpfile);
			} else if ($GLOBALS['pad_core']->envConfigs['error_environment'] == 'dev') {
				echo '<div style="padding: 10px; text-align:center;">';
				echo '<div>TemplatePath: ', $__tmpfile, ' not found<br /><br /></div>';
				echo '</div>';
			}
		} elseif ($this->displayType == self::TYPE_TEXT) {
			echo isset($this->displayParams['print_text']) ? $this->displayParams['print_text'] : 'null text';
		}
	}

	/**
	 * 返回输出的内容
	 */
	public function getDisplayContent() {
		ob_start();
		$this->display();
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * 输出状态码
	 */
	public function displayStatus($status, $datas = array()) {
		switch ($status) {
			case 301:
				$this->_header("HTTP/1.1 301 Moved Permanently", true, 301);
				$this->_header('Location:' . $datas['redirect_url']);
				break;
			case 404:
				$this->_header("HTTP/1.1 404 Not Found", true, 404);
				$this->_header("Status: 404 Not Found");
				break;
			case 500:
				$this->_header("HTTP/1.0 500 Internal Server Error", true, 500);
				break;
			default:
				$GLOBALS['pad_core']->error(E_ERROR, 'PadMvcResponse->displayStatus(%s) error', $status);
		}
		
		/**
		 * 这几个状态强制不缓存
		 */
		if (in_array($status, array(
			301,
			404,
			500
		))) {
			$this->displayNotCacheHeader();
		}
	}

	public function displayNotCacheHeader() {
		$this->_header("Cache-Control: no-cache, must-revalidate");
		$this->_header("Expires: Mon, 26 Jul 1970 05:00:00 GMT");
	}
	
	public function getTemplateAbsPath ($tpl) {
		return $this->_getTemplateFile($tpl);
	}
	
	public function getTemplatePath($tpl){
		$tplPath = $this->_getTemplateFile($tpl);
		return $this->compileTemplateFile($tplPath);
	}

	/**
	 * -------------------------------------
	 */
	private function compileTemplateFile($file) {
		if (! file_exists($file) || ! $this->mvc->templateCompileDir) {
			return $file;
		}

		// 当前文件路径和需要缓存的文件路径
		$file = realpath($file);
		$ofile = str_replace($this->mvc->templateDir, $this->mvc->templateCompileDir, $file);
		if (file_exists($ofile) && filemtime($ofile) < filemtime($file)) {
			return $ofile;
		}
		
		// 创建目录
		$ofileDir = dirname($ofile);
		if (!is_dir($ofileDir)) {
			mkdir($ofileDir, 0755, true);
		}
		
		// 逐行处理, 主要是去除模板中的空格，tab，换行
		$fileLines = file($file);
		foreach ($fileLines as $idx => $line) {
			$line = trim($line);
			if ($line) {
				$fileLines[$idx] = $line."\n";
			} else {
				unset($fileLines[$idx]);
			}
		}
		// 写入文件
		file_put_contents($ofile, '<!-- compileAt['.date('Y-m-d H:i:s').'] -->'."\n");
		file_put_contents($ofile, implode("", $fileLines), FILE_APPEND);

		return $ofile;
	}
	
	private function _header ($string, $replace = true, $http_response_code = null) {
		if ($this->displayType == self::TYPE_END) {
			return;
		}

		if (isset($this->mvc->gOptions['headerCallback'])) {
			call_user_func_array($this->mvc->gOptions['headerCallback'], array($string, $replace, $http_response_code));
		} else {
			header($string, $replace, $http_response_code);
		}
	}

	public function header ($string, $replace = true, $http_response_code = null) {
		$this->_header($string, $replace, $http_response_code);
	}
	
	private function _getTemplateFile($dfile = null) {
		$fileAppend = (isset($this->displayParams['template_name_append']) ? $this->displayParams['template_name_append'] : '');
		
		$baseTemplateFile = $this->mvc->templateDir . DIRECTORY_SEPARATOR . PadBaseString::padStrtolower($this->controller) . '.' .
				 PadBaseString::padStrtolower($this->action) . $fileAppend . '.php';
		
		if (! isset($this->displayParams['template_path']) && $dfile === null) {
			return $baseTemplateFile;
		} else {
			$tmppath = $dfile;
			if (!$tmppath && isset($this->displayParams['template_path'])) {
				$tmppath = $this->displayParams['template_path'];
			}
			
			if (strpos($tmppath, '&') === 0) {
				return substr($tmppath, 1);
			} elseif (strpos($tmppath, '/') === 0) {
				return $this->mvc->templateDir . $tmppath . $fileAppend . '.php';
			} elseif (strpos($tmppath, '.') === false) {
				return $this->mvc->templateDir . DIRECTORY_SEPARATOR . PadBaseString::padStrtolower($this->controller) . '.' .
					$tmppath . $fileAppend . '.php';
			} else {
				return dirname($baseTemplateFile) . '/' . $tmppath . $fileAppend . '.php';
			}
		}
	}
}


