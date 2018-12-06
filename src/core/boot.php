<?php
/**
 * Created by PhpStorm.
 * User: macmini
 * Date: 16/2/10
 * Time: 下午7:42
 */
define('APP_CORE_DIR', dirname(__FILE__));
define('PAD_ENVIRONMENT', $_SERVER['PADENV']);
date_default_timezone_set('PRC');

if (PAD_ENVIRONMENT == 'dev') {
	ini_set('display_errors', 'on');
}

include(__DIR__ . '/../../padphp/core/_boot.php');
$envConfigs = array(
	'debug_auth' => 'padphp,padphp',
	'config_dir' => dirname(__file__) . '/../config',
	'config_tags' => array('product', 'test', 'tdev', 'dev'),
);

if (PAD_ENVIRONMENT == 'dev') {
	$envConfigs['config_environment'] = 'dev';
	$envConfigs['error_environment'] = 'dev';
} else {
	$envConfigs['config_environment'] = PAD_ENVIRONMENT;
	$envConfigs['error_environment'] = 'product';
}

// 全局函数
PadCore::autoload('Model_', APP_CORE_DIR . '/model');
PadAutoload::register('Model_', __DIR__ . '/model');
PadAutoload::register('Welib_', __DIR__ . '/lib');

PadCore::init(__file__, $envConfigs);
PadCore::initOrm(__file__);



