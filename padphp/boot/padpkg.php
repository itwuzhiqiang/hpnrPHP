<?php

include __DIR__ . "/../core/_boot.php";
define('PAD_ENVIRONMENT', $_SERVER['PAD_ENVIRONMENT']);

if (isset($_SERVER['DEV_USER'])) {
	define('PAD_DEV_USER', $_SERVER['DEV_USER']);
}

/**
 * 启动入口文件
 * @param $bootFile
 */
function padpkgStart($bootFile) {
	$bootDir = dirname($bootFile);
	$configs = array();
	$pkgKeyPrefix = 'pkg.' . PADPKG_NAME . '.';

	// 默认配置
	if (file_exists($bootDir . '/config.php')) {
		$configs = include($bootDir . '/config.php');
	}

	// 环境定义的配置
	$envConfigFile = $bootDir . '/config-' . PAD_ENVIRONMENT . '.php';
	if (PAD_ENVIRONMENT != 'product' && file_exists($envConfigFile)) {
		$eConfigs = include($envConfigFile);
		$configs = array_merge($configs, $eConfigs);
	}

	// 运行配置
	$runtimeEnv = PAD_ENVIRONMENT;
	$jsonPath = '/data/config/runtime-' . PADPKG_NAME . '-' . $runtimeEnv . '.json';
	if (file_exists($jsonPath)) {
		$uConfigs = json_decode(file_get_contents($jsonPath), true);
		$configs = array_merge($configs, $uConfigs);
	}

	$dConfigs = array();
	foreach ($configs as $k => $v) {
		$dConfigs[$pkgKeyPrefix . $k] = $v;
	}

	// 项目的配置
	$projConfigPath = '/run/proj.config.php';
	if (file_exists($projConfigPath)) {
		$pConfig = include $projConfigPath;
		foreach ($pConfig as $key => $v) {
			if (strpos($key, $pkgKeyPrefix) === 0) {
				$dConfigs[$key] = $v;
			}
		}
	}

	$envConfigs = array(
		'debug_auth' => 'padphp,padphp',
		'configs' => $dConfigs,
		'config_dir' => $bootDir . '/config',
		'config_tags' => array(),
	);

	if (PAD_ENVIRONMENT == 'dev' || PAD_ENVIRONMENT == 'devpkg') {
		$envConfigs['config_environment'] = 'dev';
		$envConfigs['error_environment'] = 'dev';
	} else {
		$envConfigs['config_environment'] = PAD_ENVIRONMENT;
		$envConfigs['error_environment'] = 'product';
	}

	PadCore::init($bootFile, $envConfigs);
	PadCore::initOrm($bootFile);
	PadCore::initFinish();
}

function padpkgMvcStart($bootFile) {
	$GLOBALS['pad_core']->mvc = new PadMvc($bootFile, array(
		'appType' => 'rest',
		'appRouteType' => 'rest',
		'pdocEnable' => true,
		'router' => array(
			array('/_/config/set', 'PadPkgConfig,SetValue'),
			array('/_/configs', 'PadPkgConfig,Index'),
		),
	));
	$GLOBALS['pad_core']->mvc->process();
}

class PadPkgControllerConfig {

	public function doIndex (PadMvcRequest $request, PadMvcResponse $response) {
		$response->json(array(
			'code' => 0,
			'res' => array(
				'configs' => $GLOBALS['pad_core']->configs,
			)
		));
	}

	public function doSetValue (PadMvcRequest $request, PadMvcResponse $response) {
		$key = $request->defineParam('key', null, '!empty', 'key');
		$value = $request->defineParam('value', null, '!empty', 'value');

		$configFile = '/data/config/runtime-' . PADPKG_NAME . '-' . PAD_ENVIRONMENT . '.json';
		if (file_exists($configFile)) {
			$json = file_get_contents($configFile);
			$json = json_decode($json, true);
		} else {
			$json = array();
		}

		if ($value === '@delete') {
			unset($json[$key]);
		} else {
			$json[$key] = $value;
		}
		file_put_contents($configFile, json_encode($json));

		$response->json(array(
			'code' => 0,
			'res' => array(
				'values' => array(
					$key => $value,
				)
			)
		));
	}
}
