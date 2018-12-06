<?php
if (!function_exists('pad')) {
	$GLOBALS['pad_core']->error(E_NOTICE, 'function "pad" is exists');
}

/**
 * 获取Pad对象
 *
 * @param string $action
 * @return PadCptContainer
 */
function pad($action = null) {
	return $action ? $GLOBALS['pad_core']->$action : $GLOBALS['pad_core'];
}

/**
 * @param $name
 * @return mixed
 */
function config($name) {
	return $GLOBALS['pad_core']->getConfig($name);
}

/**
 * 获得数据库连接
 *
 * @param unknown $name
 */
function database($name) {
	return $GLOBALS['pad_core']->database->getPerform($name);
}

/**
 * 获得缓存连接
 *
 * @param unknown $name
 */
function cache($name) {
	return $GLOBALS['pad_core']->cache->getPerform($name);
}

/**
 * 获得一个实体
 *
 * @param unknown $name
 */
function ety() {
	$args = func_get_args();
	$etyName = array_shift($args);
	return $GLOBALS['pad_core']->orm->getEntityModel($etyName, $args);
}

/**
 * 获得一个cpt
 *
 * @param null $name
 * @return mixed
 */
function cpt($name = null) {
	if ($name === null) {
		return $GLOBALS['pad_core']->cpt;
	}
	return $GLOBALS['pad_core']->cpt->get($name);
}

/**
 * 获得一个模型
 *
 * @param $name
 * @return mixed
 */
function mdl($name) {
	static $myModel;
	if (!isset($myModel)) {
		$myModel = new PadModel();
	}
	return $myModel->get($name);
}

/**
 * 获得一个工作流模型
 * @param $name
 * @return PadWorkflowModel
 */
function workflow($name = null) {
	if ($name === null) {
		return $GLOBALS['pad_core']->workflow;
	}
	return $GLOBALS['pad_core']->workflow->get($name);
}

/**
 * 数据报表
 */
function report($name = null) {
	return PadReport::getModel($name);
}

/**
 * 返回一个空实体
 */
function ety_null() {
	return $GLOBALS['pad_core']->orm->entityNull;
}

/**
 * MVC的组装URL函数
 *
 * @param unknown $op
 * @param unknown $mix
 */
function url($op, $mix = array()) {
	if ($op == '@refer') {
		return $GLOBALS['pad_core']->mvc->activeRequest->getReferUrl($mix);
	} else {
		return $GLOBALS['pad_core']->mvc->getUrl($op, $mix);
	}
}

/**
 * 返回MVC的helper
 *
 * @param unknown $name
 * @param unknown $params
 * @return PadMvcHelperForm PadMvcHelperPager unknown
 */
function helper($name, $params = array()) {
	if (in_array($name, array('form', 'pager'))) {
		if ($name == 'form') {
			return new PadMvcHelperForm();
		} elseif ($name == 'pager') {
			return new PadMvcHelperPager($params);
		}
	} else {
		$className = 'Helper_' . PadBaseString::padStrtoupper($name);
		return new $className($params);
	}
}

/**
 * 快速获得一个MVC的值
 *
 * @param unknown $name
 * @param string $default
 * @return Ambigous <string, NULL>
 */
function form_value($name, $default = null) {
	$formValue = null;
	foreach (explode('.', $name) as $key) {
		if ($formValue === null) {
			$formValue = $GLOBALS['pad_core']->mvc->activeRequest->params;
		}

		if (isset($formValue[$key])) {
			$formValue = $formValue[$key];
		} else {
			$formValue = null;
			break;
		}
	}

	return $formValue === null ? $default : $formValue;
}

/**
 * 快速获得一个MVC的值
 *
 * @param unknown $name
 * @param string $default
 * @return Ambigous <string, NULL, multitype:>
 */
function reqval($name, $default = null) {
	$formValue = null;
	foreach (explode('.', $name) as $key) {
		if ($formValue === null) {
			$formValue = $GLOBALS['pad_core']->mvc->activeRequest->params;
			$formValue = array_merge($formValue, $_GET);
			$formValue = array_merge($formValue, $_POST);
		}

		if (isset($formValue[$key])) {
			$formValue = $formValue[$key];
		} else {
			$formValue = null;
			break;
		}
	}

	return $formValue === null ? $default : $formValue;
}

/**
 * 无
 *
 * @param unknown $name
 * @param unknown $value
 * @param unknown $html
 * @param string $default
 * @return unknown string
 */
function form_value_out($name, $value, $html, $default = null) {
	$val = reqval($name, $default);
	if ($val == $value) {
		return $html;
	} else {
		return '';
	}
}

/**
 * 无
 *
 * @return unknown
 */
function pick_not_null() {
	$argvs = func_get_args();
	foreach ($argvs as $v) {
		if (strval($v)) {
			return $v;
		}
	}
}

/**
 * 无
 *
 * @param unknown $number
 * @param string $qfs
 * @param number $size
 * @return string
 */
function money($number, $qfs = '', $size = 2) {
	return number_format($number, $size, '.', $qfs);
}

/**
 * 打印输出
 */
function pprint() {
	// 这个函数只有在shell运行，才打印输出
	if (!isset($_SERVER['REQUEST_URI'])) {
		call_user_func_array('PadBaseShell::output', func_get_args());
	}
}

/**
 * 打印输出，第一行是时间
 */
function pprintLog() {
	$argvs = func_get_args();
	array_unshift($argvs, date('Y-m-d/H:i:s'));
	call_user_func_array('pprint', $argvs);
}

function pdebuglogSetup($key) {
	$GLOBALS['pad_core']->debug = new PadDebug('log');
	$GLOBALS['pad_core']->envArgv['debuglogkey'] = $key;
}

function _assertPdebuglog() {
	$argvs = func_get_args();
	array_unshift($argvs, date('Y-m-d/H:i:s'));
	foreach ($argvs as $idx => $value) {
		if (!is_string($value)) {
			$argvs[$idx] = json_encode($value);
		}
	}
	$line = implode("\t", $argvs);
	return $GLOBALS['pad_core']->debug->writeLog($line);
}

function pdebuglog() {
	$constructorArgs = func_get_args();
	assert('call_user_func_array(\'_assertPdebuglog\', $constructorArgs);');
}

function vcheck($value, $match, $callback = null) {
	return PadBaseValidation::quickCheck($value, $match, $callback);
}

function pService($module) {
	return new PadRest($GLOBALS['pad_core']->service, $module);
}

/**
 * 请求Api接口
 * @param $uri
 * @param array $params
 */
function padApiRequest($uri, $params = array()) {
	$pCurl = new PadLib_Pcurl();
	$content = null;
	if (defined('PAD_PROJECT')) {
		$url = 'http://nginx' . $uri;
		if ($params) {
			$url .= '?' . http_build_query($params);
		}
		list($content, $info) = $pCurl->get($url, array(
			'headers' => array(
				'Host' => 'papi.' . PAD_PROJECT . '.padapp',
			),
		));
	} else {
		$url = config('domain.papi') . $uri;
		if ($params) {
			$url .= '?' . http_build_query($params);
		}
		list($content, $info) = $pCurl->get($url);
	}

	return json_decode($content, true);
}

/**
 * 请求数据接口，约定的形式
 * @param $url
 * @param array $params
 */
function vRestRequest($url, $params = array()) {
	$pCurl = new PadLib_Pcurl();
	$url .= '?' . http_build_query($params);
	list($content, $info) = $pCurl->get($url);
	$data = json_decode($content, true);
	if (isset($data['code']) && isset($data['message']) && $data['code'] > 0) {
		throw new PadBizException($data['message'], $data['code']);
	} else {
		return $data;
	}
}

/**
 * @param $url
 * @param array $params
 * @return mixed
 */
function netRequest($url, $params = array()) {
	$urlParse = parse_url($url);

	$headers = array();
	$rMethod = $urlParse['scheme'];
	$httpType = 'http';

	if (strpos($rMethod, '+') > 0) {
		list($rMethod, $httpType) = explode('+', $rMethod);
	}

	if (substr($urlParse['host'], -7) === '.padpkg') {
		// php.demo.padpkg格式，以padpkg结尾，表示是内部域名
		// 指向127.0.0.1
		list($padpkgCgi, $padpkg) = explode('.', $urlParse['host']);
		$rawUrl = $httpType . '://127.0.0.1' . $urlParse['path'];
		$headers = array(
			'Host:' . $padpkgCgi . '.' . $padpkg . '.padpkg',
		);
	} else {
		// 其他的host则是外部域名，不需要处理
		$rawUrl = $httpType . '://' . $urlParse['host'] . $urlParse['path'];
	}

	if ($rMethod === 'get') {
		$rUrl = $rawUrl;
		if ($params) {
			$rUrl .= '?' . http_build_query($params);
		}
	} else {
		$rUrl = $rawUrl;
	}

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $rUrl);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	// https
	if ($httpType === 'https') {
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
	}

	// 设置自定义header
	if ($headers) {
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	}

	// post方法提交
	if ($rMethod === 'post') {
		curl_setopt($curl, CURLOPT_POST, 1);
		if ($params) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		}
	}

	// 获取结果
	$content = curl_exec($curl);
	curl_close($curl);
	return $content;
}

/**
 * @param $url
 * @param array $params
 * @return mixed
 * @throws PadBizException
 */
function ppiRequest($url, $params = array()) {
	$content = netRequest($url, $params);
	$result = json_decode($content, true);

	if (isset($result['code']) && (isset($result['message']) || isset($result['res']))) {
		if ($result['code'] > 0) {
			throw new PadBizException($result['message'], $result['code']);
		} else {
			return $result['res'];
		}
	}
	return $result;
}

/**
 * @param $url
 * @param array $params
 * @return array
 * @throws PadBizException
 */
function pkgRequest($url, $params = array()) {
	$urlParse = parse_url($url);
	$cgi = 'php';
	$padpkg = $urlParse['host'];
	if (strpos($urlParse['host'], '.') > 0) {
		list($cgi, $padpkg) = explode('.', $urlParse['host']);
	}

	$endpoint = null;
	if (defined('PADPKG_NAME')) {
		$cfgKey = 'pkg.' . PADPKG_NAME . '.pkgpoint.' . $padpkg;
		$endpoint = config($cfgKey);
	}

	if ($endpoint === null) {
		$cfgKey = 'pkgpoint.' . $padpkg;
		$endpoint = config($cfgKey);
	}

	if ($endpoint === null) {
		$endpoint = $padpkg . '.padpkg';
	}

	$endpoint = $cgi . '.' . $endpoint;
	$search = $urlParse['scheme'] . '://' . $urlParse['host'] . '/';
	$replace = $urlParse['scheme'] . '://' . $endpoint . '/';
	$endpointUrl = str_replace($search, $replace, $url);
	return ppiRequest($endpointUrl, $params);
}
