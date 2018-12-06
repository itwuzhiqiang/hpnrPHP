<?php

class PadOrm {
	static public $tableCounter = 1;
	
	public $entityContainerClass;

	public $entityConfigMap = array();

	public $entityMatrixMap = array();

	public $entityModelMap = array();

	public $entityClassNameLoaderMap = array();

	public $isUseMemoryCache = true;

	public $entityLoadedList = array();

	public $entityChangedList = array();

	public $entityNewList = array();

	public $entityNull;

	public $flush;

	public $disableTrans = false;

	public $cacheRedis;

	public $cacheCommonConfig;

	public function __construct($bootfile, $configs) {
		
		/**
		 * 默认的entity目录位置
		 */
		PadCore::autoload('Entity_', dirname($bootfile) . DIRECTORY_SEPARATOR . 'entity');
		PadCore::autoload('EntityLoader_', dirname($bootfile) . DIRECTORY_SEPARATOR . 'entity_loader');
		PadCore::autoload('Report_', dirname($bootfile) . DIRECTORY_SEPARATOR . 'report');
		PadCore::autoload('Crontab_', dirname($bootfile) . DIRECTORY_SEPARATOR . 'crontab');

		/**
		 * 全局共享的空实体
		 */
		$this->entityNull = new PadOrmEntityNull();
		$this->flush = new PadOrmFlush();
		$this->entityContainerClass = new PadOrmEntityContainer();
		
		/**
		 * 缓存
		 */
		if (isset($configs['cache_redis_id'])) {
			$this->cacheRedis = PadBaseRedis::getPerform($configs['cache_redis_id']);
			if (isset($configs['cache_common_config'])) {
				$this->cacheCommonConfig = $configs['cache_common_config'];
			}
		}
	}

	public function clean () {
		$this->entityConfigMap = array();
		$this->entityModelMap = array();
		$this->entityMatrixMap = array();
		$this->entityClassNameLoaderMap = array();

		$this->entityLoadedList = array();
		$this->entityChangedList = array();
		$this->entityNewList = array();
	}

	public function disableMemoryCache() {
		$this->isUseMemoryCache = false;
	}

	public function disableTrans() {
		$this->disableTrans = true;
	}

	public function flush() {
		$this->flush->execute();
	}
	
	public function commit(){
		$this->flush();
		foreach ($GLOBALS['pad_core']->database->connects as $database) {
			$database->commit();
		}
	}

	/**
	 * fversion
	 */
	public function getFVervion() {
		static $version;
		if (! isset($version)) {
			$version = 1000;
		}
		return $version ++;
	}

	/**
	 * 注册新的实体
	 */
	public function registerEntityModel($entityName, $entityClass = 'PadOrmEntity', $loaderClass = 'PadOrmLoader') {
		if (!$entityClass) {
			throw new PadException('$entityClass is null');
		}

		$etyConfig = new stdclass();
		if (is_subclass_of($entityClass, 'PadOrmEntity')) {
			$etyConfig = new PadOrmEntityConfig($entityName);
		} else {
			$etyConfig->entityBaseMatrix = new $entityClass();
		}
		$etyConfig->entityClassName = $entityClass;
		$etyConfig->loaderClassName = $loaderClass;
		
		$this->entityConfigMap[$entityName] = $etyConfig;
		$this->entityClassNameLoaderMap[$entityClass] = $loaderClass;
		
		$this->entityLoadedList[$entityName] = array();
		$this->entityChangedList[$entityName] = array();
		$this->entityNewList[$entityName] = array();
	}

	/**
	 * 获得实体模型
	 */
	public function getEntityModel($entityName, $params = array()) {
		if ($entityName == 'free_loader') {
			$class = new ReflectionClass('PadOrmLoaderFree');
			$instance = $class->newInstanceArgs($params);
			return $instance;
		}

		if (strpos($entityName, '@') !== false) {
			list($cpt, $cptEntityName) = explode('@', $entityName);
			return $GLOBALS['pad_core']->cpt->get($cpt)->ety($cptEntityName);
		}
		
		if (! isset($this->entityConfigMap[$entityName])) {
			$this->tryDefaultEtyClass($entityName);
		}
		
		if (! isset($this->entityModelMap[$entityName])) {
			if (! isset($this->entityConfigMap[$entityName])) {
				$GLOBALS['pad_core']->error(E_ERROR, 'orm entity config "%s" not found', $entityName);
			}
			$this->entityModelMap[$entityName] = new PadOrmModel($entityName);
		}
		return $this->entityModelMap[$entityName];
	}

	/**
	 * 根据实体类名获得实体
	 *
	 * @param unknown $className
	 * @return multitype:
	 */
	public function getEntityModelByClass($className, $params = array()) {
		$entityName = PadBaseString::padStrtolower($className);
		return $this->getEntityModel($entityName, $params);
	}

	/**
	 * 获得实体配置
	 */
	public function getEntityConfig($entityName) {
		if (! isset($this->entityConfigMap[$entityName])) {
			$this->tryDefaultEtyClass($entityName);
		}
		$entityName = str_replace('@', '/', $entityName);

		$etyConfig = $this->entityConfigMap[$entityName];
		if (method_exists($etyConfig, 'init')) {
			$etyConfig->init();
		}
		return $etyConfig;
	}

	public function entityDbSourceExists($entityName) {
		static $tables;
		if (!isset($tables)) {
			$tables = array();
		}

		$dbId = (isset($config['db_id']) ? $config['db_id'] : 'default');
		if (!isset($tables[$dbId])) {
			$tables[$dbId] = database($dbId)->getCol('show tables');
		}

		$upName = PadBaseString::padStrtoupper($entityName);
		$entityClass = 'Entity_' . $upName;
		if (!PadAutoload::classExists($entityClass)) {
			return false;
		}

		$config = call_user_func_array($entityClass . '::config', array());
		if (!isset($config['db_table_name'])) {
			return true;
		}
		return in_array($config['db_table_name'], $tables[$dbId]);
	}

	/**
	 * 添加一个新建的实体
	 */
	public function addCreateEntity($entity, $vars = array()) {
		$entity->_pvtVars->isNewer = false;
		$entity->_pvtVars->isCreate = true;
		$entity->_pvtVars->newIndex = count($this->entityNewList[$entity->_pvtVars->etyConfig->entityName]);
		$this->entityNewList[$entity->_pvtVars->etyConfig->entityName][] = $entity;
		
		$pkField = $entity->_pvtVars->etyConfig->pkField;
		if ($entity->$pkField === null && $entity->_pvtVars->etyConfig->pkFieldIsAutoIncr) {
			unset($entity->$pkField);
		}
		
		foreach ($vars as $name => $value) {
			$entity->set($name, $value);
		}
		
		return $entity;
	}

	/**
	 * ------------------------------------------------------------
	 */
	private function tryDefaultEtyClass($entityName) {
		if (strpos($entityName, '@') !== false) {
			list($cpt, $ety) = explode('@', $entityName);
			cpt($cpt)->ety($ety);
			return;
		}

		// 没有手动配置的映射关系，使用Entity_*和EntityLoader_*查找类
		// 包访问的方式
		if (strpos($entityName, '\\') !== false) {
			$upName = $entityName;
			$upLoaderName = str_replace('\Entity\\', '\EntityLoader\\', $upName);
			$dftEClsHas = PadAutoload::classExists($upName);
			$dftFClsHas = PadAutoload::classExists($upLoaderName);
			if ($dftEClsHas) {
				$this->registerEntityModel($entityName, $upName, $dftFClsHas ? $upLoaderName : 'PadOrmLoader');
			}
		} else {
			$upName = PadBaseString::padStrtoupper($entityName);
			$dftEClsHas = PadAutoload::classExists('Entity_' . $upName);
			$dftFClsHas = PadAutoload::classExists('EntityLoader_' . $upName);
			if ($dftEClsHas) {
				$this->registerEntityModel($entityName, 'Entity_' . $upName, $dftFClsHas ? 'EntityLoader_' . $upName : 'PadOrmLoader');
			}
		}
	}
}

/**
 *
 * @author huchunhui
 *         实体加载器
 */
class PadOrmEntityContainer {

	/**
	 * __call方法作为自动加载器
	 *
	 * @param unknown $function
	 * @param unknown $params
	 * @return Ambigous <multitype:, multitype:>
	 */
	public function __call($function, $params) {
		return $GLOBALS['pad_core']->orm->getEntityModelByClass($function, $params);
	}
	
	/**
	 * 返回正常的数据
	 * @param mix $res
	 * @return array
	 */
	public function res($res){
		return (object) array(
			'status' => 0,
			'message' => '',
			'res' => (object) $res,
		);
	}
	
	/**
	 * 返回失败的数据，需要错误号和错误消息
	 * @param int $code
	 * @param string $message
	 * @return array
	 */
	public function errorRes($status, $message = ''){
		return (object) array(
			'status' => $status,
			'message' => $message,
			'res' => null,
		);
	}
}






