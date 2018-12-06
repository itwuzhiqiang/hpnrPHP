<?php

class PadOrmModel {

	public $entityName;

	public $etyConfig;

	public $commonLoader;

	public function __construct($entityName) {
		$this->entityName = $entityName;
		$this->etyConfig = $GLOBALS['pad_core']->orm->getEntityConfig($this->entityName);
		
		/**
		 * 可重用的Loader，用于按id加载实体等
		 */
		$loaderClass = $this->etyConfig->loaderClassName;
		$this->commonLoader = new $loaderClass();
		$this->commonLoader->entityName = $this->entityName;
		$this->commonLoader->etyConfig = $this->etyConfig;
		$this->commonLoader->_noPageQueryPrefix = '|'.uniqid().rand(1000, 9999).'|';
		$this->commonLoader->setDb();
		
		if ($GLOBALS['pad_core']->orm->cacheCommonConfig) {
			$this->commonLoader->cache($GLOBALS['pad_core']->orm->cacheCommonConfig);
		}
	}

	public function fieldExists ($field) {
		return isset($this->etyConfig->fields[$field]);
	}

	public function newly($datas = array()) {
		$newEntity = clone ($this->etyConfig->entityBaseMatrix);
		$newEntity->_pvtVars = clone ($this->etyConfig->entityBaseMatrix->_pvtVars);
		$newEntity->_pvtVars->isNewer = true;
		return $newEntity;
	}

	public function create($vars = array()) {
		$newEntity = $this->newly();
		$newEntity->create();
		$newEntity->sets($vars);
		return $newEntity;
	}
	
	public function createByPriField($keyFields, $datas, $paParams = array()){
		return $this->createOrUpdate($keyFields, $datas, $paParams);
	}
	
	public function createByUniqueField($keyFields, $datas, $paParams = array()){
		return $this->createOrUpdate($keyFields, $datas, $paParams);
	}

	public function createOrUpdate($pkFields, $vars, $paParams = array()) {
		$pkPriField = $this->etyConfig->pkField;
		// 如果数据中包含主键，则直接按主键更新
		if (isset($vars[$pkPriField]) && $vars[$pkPriField]) {
			$entity = $this->get($vars[$pkPriField]);
			if ($entity->isnull) {
				$entity = $this->create(array(
					$pkPriField => $vars[$pkPriField],
				));
			}
			$entity->sets($vars);
			$entity->flush();
			return $entity;
		} else {
			// 主键无值，删除
			unset($vars[$pkPriField]);
		}
		
		if (is_string($pkFields)) {
			$pkFields = explode(',', $pkFields);
		}
		
		// 先查找数据
		$fdatas = array();
		foreach ($pkFields as $k) {
			$fdatas[$k] = $vars[$k];
		}
		
		// 尝试读取数据
		$entity = ety_null();
		if ($this->etyConfig->dbPartitionConfig) {
			$entity = $this->getby($fdatas, $vars);
		} else {
			$entity = $this->getby($fdatas);
		}
		
		// 如果不存在，新建数据
		if ($entity->isnull) {
			$entity = $this->create($vars);
		}
		foreach ($pkFields as $k) {
			unset($vars[$k]);
		}
		$entity->sets($vars);
		$entity->flush();
		return $entity;
	}

	/**
	 * 如果数组包含Id,执行更新,否者执行新建
	 * @param $datas
	 * @return multitype|null
	 */
	public function replace ($datas) {
		$entity = null;
		if (isset($datas['id'])) {
			$entity = $this->get($datas['id']);
			if ($entity->isnull) {
				$entity = null;
			} else {
				unset($datas['id']);
			}
		}

		if ($entity === null) {
			$entity = $this->create();
		}

		$entity->sets($datas);
		return $entity;
	}

	/**
	 * 获得一个实体或者实体列表
	 *
	 * @param unknown $mix
	 * @return multitype: mixed
	 */
	public function get($mix) {
		if (is_array($mix)) {
			if (empty($mix)) {
				return array();
			}
			return $this->commonLoader->loadByIds($mix, true);
		} elseif ($mix) {
			if ($GLOBALS['pad_core']->orm->isUseMemoryCache) {
				$this->commonLoader->loadByIds(array(
					$mix
				));
				return $GLOBALS['pad_core']->orm->entityLoadedList[$this->entityName][$mix];
			} else {
				$list = $this->commonLoader->loadByIds(array(
					$mix
				), true);
				return array_shift($list);
			}
		} else {
			return $GLOBALS['pad_core']->orm->entityNull;
		}
	}

	/**
	 * 获得加载器
	 */
	public function loader() {
		return clone $this->commonLoader;
	}
	
	/**
	 * 通过字段查找
	 */
	public function getby($datas, $paParams = array()){
		$loader = clone $this->commonLoader;
		$loader->query('where 1');
		foreach ($datas as $k => $v) {
			$loader->query('and @'.$k.' = ?', $v);
		}
		$loader->query('limit 1');
		
		// 如果有分区表
		if ($paParams) {
			$loader->setPartition($paParams);
		}
		
		return $loader->get();
	}

	/**
	 * 获得加载器或者单个实体
	 *
	 * @param unknown $function
	 * @param unknown $params
	 * @return mixed
	 */
	public function __call($function, $params) {
		if (strpos($function, 'getby_') === 0) {
			$query = substr($function, strlen('getby_'));
			$fileds = explode('__', $query);
			$fdatas = array();
			foreach ($fileds as $idx => $filed) {
				$fdatas[$filed] = $params[$idx];
			}
			return $this->getby($fdatas);
		} else {
			$loader = clone $this->commonLoader;
			return call_user_func_array(array(
				$loader,
				$function
			), $params);
		}
	}

	public function __get($name) {
		return $this->commonLoader->$name;
	}
}
