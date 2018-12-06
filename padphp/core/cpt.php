<?php

class PadCpt {
	public $eventList = array();
	public $configPaths = array();
	public $config = array();
	public $tableList;

	static public function emit () {
		$argvs = func_get_args();
		$eventName = array_shift($argvs);

		foreach ($GLOBALS['pad_core']->cpt->eventList as $item) {
			if ($item['eventName'] == $eventName) {
				call_user_func_array($item['cbk'], $argvs);
			}
		}
	}

	static public function on ($eventName, $cbk) {
		$GLOBALS['pad_core']->cpt->eventList[] = array(
			'eventName' => $eventName,
			'cbk' => $cbk,
		);
	}
	
	public function __construct() {
		PadAutoload::register('PadCptEntityAbstract', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/entity_abstract.php');
		PadAutoload::register('PadCptEntityLoaderAbstract', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/entity_loader_abstract.php');
		PadAutoload::register('PadCptModelAbstract', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/model_abstract.php');
		PadAutoload::register('PadCptModelWebctlAbstract', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/model_abstract.php');

		PadAutoload::register('PadCptMixinCore', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/mixin_core.php');
		PadAutoload::register('PadCptMixinAdminGrid', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/mixin_admin_grid.php');
		PadAutoload::register('PadCptMixinAdminPassport', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/mixin_admin_passport.php');
		PadAutoload::register('PadCptMixinAdminRole', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/mixin_admin_role.php');

		PadAutoload::register('PadCptMixinAdminCrud', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/mixin_admin_crud.php');
		PadAutoload::register('PadCptMixinAdminGrid2', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/mixin_admin_grid2.php');
		PadAutoload::register('PadCptAdminBuilder_', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/admin_builder/');
		PadAutoload::register('PadCptCrud_', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/crud/');
		PadAutoload::register('PadCptRest_', $GLOBALS['pad_core']->envConfigs['cpt_dir'].'/core_class/rest/');
	}

	public function runRestServer ($bootFile) {
		$requestUri = substr($_SERVER['REQUEST_URI'], 1);
		if (strpos($requestUri, '?') !== false) {
			list($requestUri) = explode('?', $requestUri, 2);
		}

		$rest = new PadCptRest_Rest($bootFile);
		$rest->runWebServer();
	}

	public function lookupConfig ($path) {
		$dir = (is_dir($path) ? $path : dirname($path));
		if (file_exists($dir.'/cpt-config.php')) {
			$configData = include($dir.'/cpt-config.php');
			if (is_array($configData)) {
				foreach ($configData as $name => $data) {
					if (isset($this->config[$name])) {
						$this->config[$name] = array_merge($this->config[$name], $data);
					} else {
						$this->config[$name] = $data;
					}
				}
				$this->configPaths[] = $dir.'/cpt-config.php';
			}
		}
	}
	
	public function config ($name, $default = null) {
		$cptName = '@';
		if (strpos($name, '.') !== false) {
			list($cptName, $name) = explode('.', $name, 2);
		}
		return isset($this->config[$cptName][$name]) ? $this->config[$cptName][$name] : $default;
	}
	
	public function load ($name) {
		if (isset($GLOBALS['pad_core']->cptLoadedList[$name])) {
			return;
		}
		
		if (!isset($GLOBALS['pad_core']->envConfigs['cpt_dir'])) {
			$GLOBALS['pad_core']->error(E_ERROR, 'CPT Dir Not Found');
		}
		
		$baseDir = $GLOBALS['pad_core']->envConfigs['cpt_dir'].DIRECTORY_SEPARATOR.'component'.DIRECTORY_SEPARATOR.$name;
		$baseAppDir = $GLOBALS['pad_core']->envConfigs['boot_dir'].DIRECTORY_SEPARATOR.'cpt'.DIRECTORY_SEPARATOR.$name;
		if (!is_dir($baseDir) && !is_dir($baseAppDir)) {
			$GLOBALS['pad_core']->error(E_ERROR, 'CPT Dir "'.$name.'" Not Found');
		}
		
		$prefixClassName = PadBaseString::padStrtoupper($name);
		$loadParams = array(
			'classPrefix' => $prefixClassName.'_',
		);
		PadAutoload::registerMulti('Entity_'.$prefixClassName.'_', $baseDir.DIRECTORY_SEPARATOR.'entity', $loadParams);
		PadAutoload::registerMulti('EntityLoader_'.$prefixClassName.'_', $baseDir.DIRECTORY_SEPARATOR.'entity_loader', $loadParams);
		PadAutoload::registerMulti('CptModel_'.$prefixClassName.'_', $baseDir.DIRECTORY_SEPARATOR.'model', $loadParams);
		
		$prefixAppClassName = 'App'.$prefixClassName;
		$loadAppParams = array(
			'classPrefix' => $prefixAppClassName.'_',
		);
		PadAutoload::registerMulti('Entity_'.$prefixAppClassName.'_', $baseAppDir.DIRECTORY_SEPARATOR.'entity', $loadAppParams);
		PadAutoload::registerMulti('EntityLoader_'.$prefixAppClassName.'_', $baseAppDir.DIRECTORY_SEPARATOR.'entity_loader', $loadAppParams);
		PadAutoload::registerMulti('CptModel_'.$prefixAppClassName.'_', $baseAppDir.DIRECTORY_SEPARATOR.'model', $loadAppParams);

		// 查找cpt的默认配置和app中特殊的配置
		$retConfigs = array();
		foreach (array($baseDir, $baseAppDir) as $thisCptDir) {
			if (file_exists($thisCptDir.DIRECTORY_SEPARATOR.'config.php')) {
				$eventListenrOn = function ($eventName, $cbk) use ($name) {
					PadCpt::on($name . '.' . $eventName, $cbk);
				};
				$configs = include($thisCptDir.DIRECTORY_SEPARATOR.'config.php');

				// 处理依赖
				if (isset($configs['depend'])) {
					$configs['depend'] = (is_array($configs['depend']) ? $configs['depend'] : explode(',', $configs['depend']));
					foreach ($configs['depend'] as $name) {
						self::loadCpt($name);
					}
				}

				if (is_array($configs) || is_object($configs)) {
					$retConfigs = array_merge($retConfigs, $configs);
				}
			}
		}
		$GLOBALS['pad_core']->cptLoadedList[$name] = $retConfigs;
	}
	
	/**
	 * 获取一个组件，并且自动加载
	 * @param unknown $name
	 * @return PadCptContainer
	 */
	public function get ($name) {
		$this->load($name);
		$prefixClassName = PadBaseString::padStrtoupper($name);
		$modelClassName = 'CptModel_'.$prefixClassName.'_';
		$config = isset($this->config[$name]) ? $this->config[$name] : array();
		return new PadCptContainer($GLOBALS['pad_core']->envConfigs['cpt_dir'], $name, $modelClassName, $config);
	}
	
	public function loadVendor ($venderName) {
		include($GLOBALS['pad_core']->envConfigs['cpt_dir'] . '/vendor/' . $venderName . '/_load.php');
	}
}

class PadCptContainer {
	public $baseDir;
	public $cptName;
	public $className;
	public $config;

	public function __construct ($baseDir, $cptName, $className, $config) {
		$this->baseDir = $baseDir;
		$this->cptName = $cptName;
		$this->className = $className;
		$this->config = $config;
	}
	
	public function checkConfig ($names) {
		$return = true;
		$miss = array();
		$names = (is_array($names) ? $names : explode(',', $names));
		foreach ($names as $key) {
			if (!isset($this->config[$key])) {
				$return = false;
				$miss[] = $key;
			}
		}
		
		if (!$return && true) {
			$GLOBALS['pad_core']->error(E_ERROR, 'config miss: ' . implode(',', $miss));
		}
		
		return $return;
	}

	public function requestRest ($query, $params = array()) {
		if (is_string($params)) {
			$paramsString = str_replace(',', '&', $params);
			$params = array();
			parse_str($paramsString, $params);
		}

		list($model, $method) = explode('/', substr($query, 1));
		$rest = new PadCptRest_Rest();
		return $rest->runMethod($this->cptName.'/'.$model, $method, $params);
	}

	public function requestTask ($query, $params = array()) {
		if (is_string($params)) {
			$paramsString = str_replace(',', '&', $params);
			$params = array();
			parse_str($paramsString, $params);
		}

		list($model, $method) = explode('/', substr($query, 1));
		$rest = new PadCptRest_Rest();
		return $rest->runTask($this->cptName.'/'.$model, $method, $params);
	}

	public function requestAddonRest ($addon, $query, $params = array()) {
		$projectDir = $GLOBALS['pad_core']->envConfigs['boot_dir'] . '/..';

		$port = 0;
		if ($addon == 'nodejs') {
			$port = file_get_contents($projectDir . '/var/run/nodejs.service.port');
		} else if ($addon == 'python') {
			$port = file_get_contents($projectDir . '/var/run/python.service.port');
		}

		$url = 'http://127.0.0.1:' . $port . $query;
		if ($params) {
			$url = $url . '?' . http_build_query($params);
		}

		$content = file_get_contents($url);
		$json = json_decode($content, true);
		return $json['res'];
	}
	
	public function config ($key, $default = null) {
		return isset($this->config[$key]) ? $this->config[$key] : $default;
	}
	
	public function getWebctlAppTplPath ($tplName) {
		return '&'.$GLOBALS['pad_core']->envConfigs['boot_dir'].'/cpt/'.$this->cptName.'/_webctltpl/'.$tplName.'.php';
	}

	public function getWebctlTplPath ($tplName) {
		return '&'.$this->baseDir.'/component/'.$this->cptName.'/_webctltpl/'.$tplName.'.php';
	}

	public function etyExists ($ety) {
		if (pad('cpt')->tableList === null) {
			pad('cpt')->tableList = database('default')->getCol('show tables');
		}

		$entityName = PadBaseString::padStrtoupper($this->cptName.'/'.$ety);
		$className = 'Entity_' . $entityName;
		if (!PadAutoload::classExists($className)) {
			return false;
		}
		$config = $className::config();
		$dbTableName = $config['db_table_name'];
		return in_array($dbTableName, pad('cpt')->tableList);
	}

	public function ety($ety) {
		$entitySignName = null;
		$appEntityName = null;
		$entityName = null;
		if (strpos($ety, '/') === false) {
			$entitySignName = $this->cptName.'/'.$ety;
			$appEntityName = PadBaseString::padStrtoupper('app_'.$this->cptName.'/'.$ety);
			$entityName = PadBaseString::padStrtoupper($this->cptName.'/'.$ety);
		} else {
			$entitySignName = $ety;
			$appEntityName = PadBaseString::padStrtoupper('app_'.$ety);
			$entityName = PadBaseString::padStrtoupper($ety);
		}
		
		$regEntityClass = null;
		if (PadAutoload::classExists('Entity_' . $appEntityName)) {
			$regEntityClass = 'Entity_' . $appEntityName;
		} else if (PadAutoload::classExists('Entity_' . $entityName)) {
			$regEntityClass = 'Entity_' . $entityName;
		}
		
		$regEntityLoaderClass = null;
		if (PadAutoload::classExists('EntityLoader_' . $appEntityName)) {
			$regEntityLoaderClass = 'EntityLoader_' . $appEntityName;
		} else if (PadAutoload::classExists('EntityLoader_' . $entityName)) {
			$regEntityLoaderClass = 'EntityLoader_' . $entityName;
		} else {
			$regEntityLoaderClass = 'PadOrmLoader';
		}
		
		//if (true || !PadAutoload::classExists($regEntityClass)) {
		if (!isset($GLOBALS['pad_core']->orm->entityConfigMap[$entitySignName])) {
			$GLOBALS['pad_core']->orm->registerEntityModel($entitySignName, $regEntityClass, $regEntityLoaderClass);
		}
		return ety($entitySignName);
	}

	public function webctlProcess ($func, $params) {
		$actionName = $func;
		list($request, $response) = $params;
		$class = $this->cpt()->Webctl();
		$class->$func($request, $response);
	}

	public function model () {
		$argvs = func_get_args();
		$modelName = array_shift($argvs);
		$modelName = PadBaseString::padStrtoupper($modelName);
		return call_user_func_array(array($this, '__call'), array($modelName, $argvs));
	}

	public function __call ($func, $params) {
		$prefixClassName = PadBaseString::padStrtoupper($this->cptName);
		
		$defaultAppClassName = 'CptModel_App'.$prefixClassName.'_'.'Default';
		$newAppClassName = 'CptModel_App'.$prefixClassName.'_'.$func;
		
		$defaultClassName = 'CptModel_'.$prefixClassName.'_'.'Default';
		$newClassName = 'CptModel_'.$prefixClassName.'_'.$func;
		
		$defaultAppClass = null;
		if (class_exists($defaultAppClassName)) {
			$defaultAppClass = new $defaultAppClassName();
		}
		$defaultClass = null;
		if (class_exists($defaultClassName)) {
			$defaultClass = new $defaultClassName();
		}
		
		$loads = array(
			array($defaultAppClass, $newAppClassName),
			array($defaultClass, $newClassName)
		);
		foreach ($loads as $item) {
			list($dftClass, $nClsName) = $item;
			if ($dftClass && method_exists($dftClass, $func)) {
				$dftClass->cptName = $this->cptName;
				if (method_exists($dftClass, 'init')) {
					$dftClass->init();
				}
				return call_user_func_array(array($dftClass, $func), $params);
			} else if (class_exists($nClsName)) {
				$reflect  = new ReflectionClass($nClsName);
				$instance = $reflect->newInstanceArgs($params);
				$instance->cptName = $this->cptName;
				if (method_exists($instance, 'init')) {
					$instance->init();
				}
				return $instance;
			} else if (in_array($func, array('emit'))) {
				// 没有default文件,进入默认的方法
				$reflect = new ReflectionClass('PadCptModelAbstract');
				$instance = $reflect->newInstanceWithoutConstructor();
				$instance->cptName = $this->cptName;
				return call_user_func_array(array($instance, $func), $params);
			}
		}
		$GLOBALS['pad_core']->error(E_ERROR, 'Call Error: cpt('.$this->cptName.').'.$func.'()');
		return false;
	}
}




