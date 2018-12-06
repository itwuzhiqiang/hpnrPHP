<?php

class PadOrmLoader {

	public static $tableCounter = 1;

	public static $cachedRelationAttr = array();

	public $entityName;
	public $etyConfig;
	public $query = array();
	public $queryBinds = array();
	
	public $queryPage;
	public $queryPageInfo;

	public $cacheConfigs = array();
	private $_joinAs;
	
	public $_noPageQueryPrefix;
	public $_dbName;
	public $_dbRealName;
	public $_db;
	public $_dbTableName;
	
	/* 分区表参数 */
	private $_partitionParams = array();
	
	/**
	 * 设置分区参数
	 * @param unknown $etyConfig
	 * @param unknown $object
	 * @param unknown $data
	 * @return PadOrmLoader
	 */
	static public function baseSetPartition($etyConfig, $object, $data){
		if (!isset($etyConfig->dbPartitionConfig)) {
			$GLOBALS['pad_core']->error(E_ERROR, 'not suppoy partition');
		}
		
		if (!is_callable($etyConfig->dbPartitionConfig)) {
			$GLOBALS['pad_core']->error(E_ERROR, 'partition callback error');
		}
		
		// 字符串自动转化成数组
		if (is_string($data)) {
			$dataArray = array();
			$data = str_replace(',', '&', $data);
			parse_str($data, $dataArray);
			$data = $dataArray;
		}
		
		$partitionResult = call_user_func_array($etyConfig->dbPartitionConfig, array($data));
		
		$dbName = null;
		$dbRealName = null;
		$dbTableName = null;
		if (isset($partitionResult['db_name'])) {
			$dbName = $partitionResult['db_name'];
		} else {
			$dbName = $etyConfig->dbName.'.ptt';
		}

		if (isset($partitionResult['db_table_name'])) {
			$dbTableName = str_replace('@@', $dbName, $partitionResult['db_table_name']);
		}
		
		self::baseSetDb($etyConfig, $object, $dbName, $dbRealName, $dbTableName);
	}
	
	/**
	 * 设置DB参数
	 * @param unknown $etyConfig
	 * @param unknown $object
	 * @param string $dbName
	 * @param string $dbRealName
	 * @param string $dbTableName
	 */
	static public function baseSetDb($etyConfig, $object, $dbName = null, $dbRealName = null, $dbTableName = null){
		if ($dbName) {
			$object->_dbName = $dbName;
		} else {
			$object->_dbName = $etyConfig->dbName;
		}
		
		if ($dbRealName) {
			$object->_dbRealName = $dbRealName;
		} else {
			$object->_dbRealName = $etyConfig->dbRealName;
		}
		
		if ($dbTableName) {
			$object->_dbTableName = $dbTableName;
		} else {
			$object->_dbTableName = $etyConfig->dbTableName;
		}
		$object->_db = $GLOBALS['pad_core']->database->getPerform($object->_dbName);
		
		$fieldMask = $object->_db->fieldMask;
		if (is_object($etyConfig->dbTableName) && is_callable($etyConfig->dbTableName)) {
			$sql = call_user_func_array($etyConfig->dbTableName, array(null));
			$sql = str_replace(array("\t", "\n"), ' ', $sql);
			$sql = preg_replace("/\s+/", ' ', $sql);
			$object->_dbTableName = '(' . trim($sql) . ')';
		} else {
			$object->_dbTableName = $fieldMask.$object->_dbRealName.$fieldMask.'.'.$fieldMask.$object->_dbTableName.$fieldMask;
		}
	}
	
	/**
	 * 根据id加载实体
	 */
	public function loadByIds($ids, $isAssocReturn = false, $defauleStmt = null) {
		$return = array();
		$pg_orm = $GLOBALS['pad_core']->orm;
		$fvervion = $pg_orm->getFVervion();
		
		/**
		 * 内存存在数据，从内存加载，不存在，填充空实体
		 */
		if ($pg_orm->isUseMemoryCache) {
			foreach ($ids as $idx => $id) {
				if (! isset($pg_orm->entityLoadedList[$this->entityName][$id])) {
					$pg_orm->entityLoadedList[$this->entityName][$id] = $pg_orm->entityNull;
					if ($isAssocReturn) {
						$return[$id] = $pg_orm->entityNull;
					}
				} else {
					if ($isAssocReturn) {
						$return[$id] = $pg_orm->entityLoadedList[$this->entityName][$id];
					}
					unset($ids[$idx]);
				}
			}
		} else {
			foreach ($ids as $idx => $id) {
				if ($isAssocReturn) {
					$return[$id] = $pg_orm->entityNull;
				}
			}
		}

		/**
		 * 如果为ids为空，可以直接返回了
		 */
		if (empty($ids) && ! $defauleStmt) {
			return $return;
		}
		
		/**
		 * 从缓存加载
		 */
		$cacheRedis = $GLOBALS['pad_core']->orm->cacheRedis;
		if ($cacheRedis && 0) {
			$cacheIds = array();
			$idsKeys = array_keys($ids);
			$idsValues = array_values($ids);
			
			$res = $cacheRedis->hmGet('ety.item@' . $this->entityName, $ids);
			foreach ($ids as $idx => $id) {
				if (isset($res[$id]) && $res[$id]) {
					$row = $res[$id];
					$class = clone $this->etyConfig->entityBaseMatrix;
					$class->_pvtVars = clone $this->etyConfig->entityBaseMatrix->_pvtVars;
					$class->_pvtVars->id = $id;
					$class->_pvtVars->fversion = $fvervion;
					foreach ($row as $field => $val) {
						$class->$field = $val;
					}
					
					if ($pg_orm->isUseMemoryCache) {
						$pg_orm->entityLoadedList[$this->entityName][$id] = $class;
					}
					
					unset($ids[$idx]);
					
					if ($isAssocReturn) {
						$return[$id] = $class;
					}
				}
			}
		}
		
		if (empty($ids) && ! $defauleStmt) {
			return $return;
		}
		
		/**
		 * 从数据库加载
		 */
		$fieldMask = $this->_db->fieldMask;
		$fieldsSql = $fieldMask . implode($fieldMask . ',' . $fieldMask, $this->etyConfig->fieldsGroups[0]) . $fieldMask;
		
		$sth = $defauleStmt;
		if ($sth === null) {
			assert('$startTime = microtime(true)*10000;');
			$ids = array_values($ids);
			$idPre = array_fill(0, count($ids), '?');
			$sql = 'SELECT ' . $fieldsSql . ' FROM ' . $this->_dbTableName
				 . ' WHERE ' . $this->etyConfig->pkField . ' IN (' . implode(',', $idPre) . ')';
			$sth = $this->_db->prepare($sql);
			$this->_db->bindValues($sth, $ids);
			
			$sth->execute();
			assert('$GLOBALS[\'pad_core\']->debug->write(\'db\', $this->_db->debugCreateSql($sql, $ids), microtime(true)*10000 - $startTime);');
		}
		
		while (($row = $sth->fetch(PDO::FETCH_ASSOC)) !== false) {
			$id = $row[$this->etyConfig->pkField];
			
			// 使用dftStmt时，不能覆盖已经存在的实体
			if ($pg_orm->isUseMemoryCache) {
				if ($defauleStmt && isset($pg_orm->entityLoadedList[$this->entityName][$id])) {
					if ($isAssocReturn) {
						$return[$id] = $pg_orm->entityLoadedList[$this->entityName][$id];
					}
					continue;
				}
			}
			
			$class = clone $this->etyConfig->entityBaseMatrix;
			$class->_pvtVars = clone $this->etyConfig->entityBaseMatrix->_pvtVars;
			$class->_pvtVars->id = $id;
			$class->_pvtVars->fversion = $fvervion;
			foreach ($row as $field => $val) {
				$class->$field = $val;
			}
			
			if ($pg_orm->isUseMemoryCache) {
				$pg_orm->entityLoadedList[$this->entityName][$id] = $class;
			}
			
			if ($cacheRedis && 0) {
				$cacheRedis->hSet('ety.item@' . $this->entityName, $id, $row);
			}
			
			if ($isAssocReturn) {
				$return[$id] = $class;
			}
		}
		return $return;
	}

	/**
	 * 根据id加载实体的异步字段
	 */
	public function loadLazyFieldsByIds($field, $ids, $entities = array()) {
		$pg_orm = $GLOBALS['pad_core']->orm;
		
		$fields = null;
		foreach ($this->etyConfig->fieldsGroups as $idx => $vfields) {
			if (in_array($field, $vfields)) {
				$fields = $vfields;
				break;
			}
		}
		$fields[] = $this->etyConfig->pkField;
		
		$fieldMask = $this->_db->fieldMask;
		$fieldsSql = $fieldMask . implode($fieldMask . ',' . $fieldMask, $fields) . $fieldMask;
		
		$ids = array_values($ids);
		$idsPre = array_fill(0, count($ids), '?');
		$sql = 'SELECT ' . $fieldsSql . ' FROM ' . $this->_dbTableName
			 . ' WHERE ' . $this->etyConfig->pkField . ' IN (' . implode(',', $idsPre) .')';
		$sth = $this->_db->prepare($sql);
		$this->_db->bindValues($sth, $ids);
		$sth->execute();
		
		while (($row = $sth->fetch(PDO::FETCH_ASSOC)) !== false) {
			$id = $row[$this->etyConfig->pkField];
			if ($pg_orm->isUseMemoryCache) {
				$class = $pg_orm->entityLoadedList[$this->entityName][$id];
			} else {
				$class = $entities[$id];
			}
			foreach ($row as $field => $val) {
				$class->$field = $val;
			}
		}
	}

	public function newly($datas = array()) {
		return $GLOBALS['pad_core']->orm->getEntityModel($this->entityName)->newly($datas);
	}
	
	public function model(){
		return $GLOBALS['pad_core']->orm->getEntityModel($this->entityName);
	}

	/**
	 * 获得数据传输对象
	 * @return PadLib_FetchOrm_Fetcher
	 */
	public function fetcher() {
		return new PadLib_Dataer_Fetcher($this);
	}

	public function loader(){
		return $this->model()->loader();
	}

	public function relationLoader ($rk) {
		$relation = $this->etyConfig->relations[$rk];
		return $GLOBALS['pad_core']->orm->getEntityModel($relation['entity_name'])->loader();
	}
	
	public function cloneThis () {
		return clone $this;
	}
	
	public function setPartition($data){
		self::baseSetPartition($this->etyConfig, $this, $data);
		return $this;
	}
	
	public function fieldExists ($fieldName) {
		return isset($this->etyConfig->fields[$fieldName]);
	}
	
	public function getFieldList () {
		return $this->etyConfig->fields;
	}

	public function relationExists ($relationName) {
		return isset($this->etyConfig->relations[$relationName]);
	}

	/**
	 * 检查字段的回调方法
	 * @param unknown $vars
	 * @param string $id
	 * @return multitype:unknown Ambigous <NULL, mixed, unknown>
	 */
	public function checkDatas ($vars, $id = null) {
		$return = array();
		
		// 根据配置检测
		if (method_exists($this, '_check_config')) {
			// 通过配置验证
			$configs = $this->_check_config();
			foreach ($vars as $fld => $value) {
				$cfg = null;
				if (isset($configs[$fld]) && $configs[$fld]) {
					$cfg = $configs[$fld];
				}

				if (!$cfg) {
					continue;
				}

				$res = null;
				if (is_object($cfg)) {
					$res = call_user_func_array($cfg, array($value, $id));
				} else if (is_array($cfg) && is_string($cfg[0]) && strpos($cfg[0], 'v:') === 0) {
					// 字符串v:开头,就是使用vcheck验证
					if (!PadBaseValidation::quickCheck($value, substr($cfg[0], 2))) {
						$res = $cfg[1];
					}
				} else if (is_array($cfg) && $cfg[0][0] != '/') {
					$res = call_user_func_array($cfg[0], array($value, $id));
				} else if (is_array($cfg) && $cfg[0][0] == '/' && !preg_match($cfg[0], $value)) {
					// 正则验证
					$res = $cfg[1];
				}

				if ($res) {
					$return[$fld] = $res;
				}
			}
		}
		
		// 通过字段预检查验证
		foreach ($vars as $fld => $value) {
			if (isset($return[$fld])) {
				continue;
			}
			
			$method = '_checkfld_'.$fld;
			if (method_exists($this, $method) && ($res = $this->$method($value, $id))) {
				$return[$fld] = $res;
			}
		}
		return $return;
	}
	
	/**
	 * 设置查询的数据库和查询的数据表
	 * @param string $dbName
	 * @param string $dbTableName
	 */
	public function setDb($dbName = null, $dbRealName = null, $dbTableName = null){
		self::baseSetDb($this->etyConfig, $this, $dbName, $dbRealName, $dbTableName);
		return $this;
	}

	public function selectDbTable ($name) {
		if (is_object($this->etyConfig->dbTableName) && is_callable($this->etyConfig->dbTableName)) {
			$sql = call_user_func_array($this->etyConfig->dbTableName, array($name));
			$sql = str_replace(array("\t", "\n"), ' ', $sql);
			$sql = preg_replace("/\s+/", ' ', $sql);
			$this->_dbTableName = '(' . trim($sql) . ')';
		}
		return $this;
	}

	public function query() {
		$argvs = func_get_args();
		$select = array_shift($argvs);
		$select = str_replace(array(
			"\t",
			"\n"
		), ' ', $select);
		
		$this->query[] = $select;
		foreach ($argvs as $v) {
			$this->queryBinds[] = $v;
		}
		return $this;
	}
	
	/**
	 * 查询的语句不写入到分页的查询中
	 */
	public function queryNp() {
		$argvs = func_get_args();
		$argvs[0] = $this->_noPageQueryPrefix.$argvs[0];
		return call_user_func_array(array($this, 'query'), $argvs);
	}
	
	public function kjQuery($type) {
		
	}
	
	public function _fields() {
		$argvs = func_get_args();
		if (isset($this->query[0]) && strpos($this->query[0], 'fields') === 0) {
			// fields不是第一次调用 
			$this->query[0] = 'fields ' . $argvs[0];
			return $this;
		} else if (isset($this->query[0]) && strpos($this->query[0], 'fields') !== 0) {
			array_unshift($this->query, 'fields ' . $argvs[0]);
			return $this;
		} else {
			$argvs[0] = 'fields '.$argvs[0];
			return call_user_func_array(array($this, 'query'), $argvs);
		}
	}
	
	public function _where() {
		$argvs = func_get_args();
		$argvs[0] = 'where '.$argvs[0];
		return call_user_func_array(array($this, 'query'), $argvs);
	}
	
	public function _and() {
		$argvs = func_get_args();
		$argvs[0] = 'and '.$argvs[0];
		return call_user_func_array(array($this, 'query'), $argvs);
	}
	
	public function _orderBy() {
		$argvs = func_get_args();
		$argvs[0] = 'order by '.$argvs[0];
		return call_user_func_array(array($this, 'query'), $argvs);
	}
	
	public function _limit() {
		$argvs = func_get_args();
		$argvs[0] = 'limit '.$argvs[0];
		return call_user_func_array(array($this, 'query'), $argvs);
	}

	public function cache() {
		$argvs = func_get_args();
		$cacheCfg = implode(';', $argvs);
		$cacheCfg = str_replace(' ', '', $cacheCfg);
		$items = explode(';', $cacheCfg);

		$configs = array();
		$configs['items'] = array();
		foreach ($items as $k => $v) {
			$tmp = explode('=', $v);
			if ($tmp[0] == 'expire') {
				$configs['expire'] = isset($tmp[1]) ? $tmp[1] : 0;
			} elseif (substr($v, 0, 1) == '@') {
				if (! isset($configs['entities'])) {
					$configs['items'] = array();
					$configs['items_full'] = array();
				}
				$configs['items'][] = substr($v, 1);
			}
		}
		
		if ($configs && ! isset($configs['expire'])) {
			$configs['expire'] = 0;
		}
		
		if (isset($configs['expire']) && $configs['expire'] <= 0) {
			$configs['expire'] = 3600;
		}
		
		/**
		 * 与哪些关联信息有关
		 */
		$configs['items'] = array_unique($configs['items']);
		
		foreach ($configs['items'] as $line) {
			if (strpos($line, '@') === 0) {
				if ($line == '@') {
					$configs['items_full'][] = array(
						$this->entityName,
						null,
						null,
						null
					);
				} else {
					$tmp = explode('.', substr($line, 2));
					$firEtyName = $this->entityName;
					$latEtyName = null;
					$latEtyId = array_pop($tmp);
					
					foreach ($tmp as $rk) {
						if ($latEtyName === null) {
							$latEtyName = $firEtyName;
						}
						
						$etyCfg = $GLOBALS['pad_core']->orm->getEntityConfig($latEtyName);
						if (! isset($etyCfg->relations[$rk])) {
							$GLOBALS['pad_core']->error(E_ERROR, '[cache] ' . $this->entityName . ' not found relation[' . $rk . ']');
						}
						
						$rlc = $etyCfg->relations[$rk];
						$latEtyName = $rlc['entity_name'];
					}
					
					if ($latEtyName === null) {
						$GLOBALS['pad_core']->error(E_ERROR, '[cache] ' . $this->entityName . ' not found latEtyName');
					}
					
					$configs['items_full'][] = array(
						$firEtyName,
						implode('.', $tmp),
						$latEtyName,
						$latEtyId
					);
				}
			} else {
				$configs['items_full'][] = array(
					$line,
					null,
					null,
					null
				);
			}
		}
		
		/**
		 * 保存缓存配置
		 */
		if ($configs) {
			$this->cacheConfigs = $configs;
			$this->cacheConfigs['raw'] = $cacheCfg;
		}
		
		return $this;
	}

	/**
	 * 或者缓存ID
	 */
	private function _getCacheId($prefix, $query) {
		return 'data@' . $prefix . '::' . substr(md5($this->cacheConfigs['raw'] . $query . implode('_', $this->queryBinds)), 8, 16);
	}

	/**
	 * 获得/设置缓存
	 */
	private function _ctrlCache($cacheId, $data = null) {
		$cacheRedis = $GLOBALS['pad_core']->orm->cacheRedis;
		if ($data === null) {
			return $cacheRedis->get($cacheId);
		} elseif ($this->cacheConfigs && $this->cacheConfigs['expire'] >= 0) {
			if (isset($this->cacheConfigs['items_full'])) {
				foreach ($this->cacheConfigs['items_full'] as $row) {
					if ($row[1] === null) {
						$cacheRedis->sAdd('ety.ids@' . $row[0], $cacheId);
					} else {
						/**
						 * 实体利用过的关系
						 */
						$cacheRedis->sAdd('ety.rlt@' . $row[0], $row[1]);
						/**
						 * 关系下使用的KEY
						 */
						$cacheRedis->sAdd('ety.ids@' . $row[0] . '.' . $row[2] . '.' . $row[3], $cacheId);
					}
				}
			}
			return $cacheRedis->setex($cacheId, $this->cacheConfigs['expire'], $data);
		}
	}

	public function dbExecute() {
		$argvs = func_get_args();
		$sql = array_shift($argvs);
		$sql = str_replace('@@@', $this->_dbTableName, $sql);
		$stmt = $this->_db->prepare($sql);
		foreach ($argvs as $index => $val) {
			$stmt->bindValue($index + 1, $val, is_integer($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		return $stmt->execute();
	}

	/**
	 * 设置分页
	 *
	 * @return self
	 */
	public function page($page, $pageSize = 16, $pageQuery = null) {
		$this->queryPage = array(
			$page,
			$pageSize,
			$pageQuery
		);
		return $this;
	}

	/**
	 * 获得分页信息
	 *
	 * @return multitype:number NULL
	 */
	public function getPageInfo() {
		if (! $this->queryPage) {
			$GLOBALS['pad_core']->error(E_ERROR, 'finder->page() not call, can\'t use getPageInfo()');
		}
		
		$cacheRedis = $GLOBALS['pad_core']->orm->cacheRedis;
		if (! $this->queryPageInfo) {
			$query = $this->_getPageQueryString();
			$count = 0;
			if ($cacheRedis && $this->cacheConfigs) {
				$cacheId = $this->_getCacheId('getpageinfo', $query);
				if (($count = $this->_ctrlCache($cacheId)) === false) {
					$stmt = $this->_getStmt($query);
					$count = $stmt->fetchColumn();
					$this->_ctrlCache($cacheId, $count);
				}
			} else {
				$stmt = $this->_getStmt($query);
				$count = $stmt->fetchColumn();
			}
			
			$this->queryPageInfo = array(
				'total' => intval($count),
				'page' => intval($this->queryPage[0]),
				'page_size' => intval($this->queryPage[1]),
				'page_total' => ceil(intval($count)/$this->queryPage[1]),
			);
		}
		return $this->queryPageInfo;
	}

	public function getPageBeign () {
		return (intval($this->queryPage[0]) - 1) * intval($this->queryPage[1]);
	}

	/**
	 * 获得实体列表
	 */
	public function getList() {
		$cacheRedis = $GLOBALS['pad_core']->orm->cacheRedis;
		if (($cacheRedis && $this->cacheConfigs) || (isset($this->query[0]) && strpos($this->query[0], '_pkid') !== false)) {
			$query = $this->_getQueryString(1);
			$ids = array();
			if ($cacheRedis && $this->cacheConfigs) {
				$cacheId = $this->_getCacheId('getlist', $query);
				
				if (($ids = $this->_ctrlCache($cacheId)) === false) {
					$stmt = $this->_getStmt($query);
					$ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
					$this->_ctrlCache($cacheId, $ids);
				}
			} else {
				$stmt = $this->_getStmt($query);
				$ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
			}
			return $this->loadByIds($ids, true);
		} else {
			$stmt = $this->_getStmt($this->_getQueryString(1));
			return $this->loadByIds(array(), true, $stmt);
		}
	}

	/**
	 *
	 * @param $callback
	 * @return array
	 */
	public function getListMap($callback) {
		$datalist = $this->getList();
		if (!$datalist) {
			$datalist = array();
		}
		$return = array();
		foreach (array_values($datalist) as $index => $entity) {
			$return[$index] = call_user_func_array($callback, array($entity, $index, $this));
		}
		return $return;
	}

	/**
	 * 获得一个实体
	 */
	public function get() {
		$list = $this->getList();
		return count($list) > 0 ? array_shift($list) : $GLOBALS['pad_core']->orm->entityNull;
	}

	/**
	 * 获得数据stdclass列表
	 */
	public function getDataList() {
		$cacheRedis = $GLOBALS['pad_core']->orm->cacheRedis;
		if ($cacheRedis && $this->cacheConfigs) {
			$query = $this->_getQueryString(1);
			$cacheId = $this->_getCacheId('getdatalist', $query);
			
			if (($return = $this->_ctrlCache($cacheId)) === false) {
				$stmt = $this->_getStmt($query);
				$return = $stmt->fetchAll(PDO::FETCH_OBJ);
				$this->_ctrlCache($cacheId, $return);
			}
			return $return;
		} else {
			$queryString = $this->_getQueryString(1);
			$stmt = $this->_getStmt($queryString);
			return $stmt->fetchAll(PDO::FETCH_OBJ);
		}
	}

	/**
	 * 获得一个stdclass
	 */
	public function getData() {
		$list = $this->getDataList();
		return count($list) > 0 ? array_shift($list) : new stdClass();
	}

	/**
	 * 获取第一列
	 */
	public function getCol($col = null) {
		if ($col) {
			array_unshift($this->query, 'fields '.$col);
		}
		
		$cacheRedis = $GLOBALS['pad_core']->orm->cacheRedis;
		if ($cacheRedis && $this->cacheConfigs) {
			$query = $this->_getQueryString(1);
			$cacheId = $this->_getCacheId('getcol', $query);
			
			if (($return = $this->_ctrlCache($cacheId)) === false) {
				$stmt = $this->_getStmt($query);
				$return = $stmt->fetchAll(PDO::FETCH_COLUMN);
				$this->_ctrlCache($cacheId, $return);
			}
			return $return;
		} else {
			$queryString = $this->_getQueryString(1);
			$stmt = $this->_getStmt($queryString);
			return $stmt->fetchAll(PDO::FETCH_COLUMN);
		}
	}
	
	/**
	 * 获取一个关联数组
	 */
	public function getPair() {
		$datalist = $this->getDataList();
		if (!$datalist) {
			return array();
		}
		
		$firstItem = array_slice($datalist, 0, 1);
		$firstItem = array_shift($firstItem);
		if (!array_key_exists('pairKey', $firstItem) || !array_key_exists('pairValue', $firstItem)) {
			$GLOBALS['pad_core']->error(E_ERROR, 'pairKey Or pairvalue Not found!');
		}
		
		$return = array();
		foreach ($datalist as $item) {
			$return[$item->pairKey] = $item->pairValue;
		}
		return $return;
	}

	/**
	 * 无论什么情况，获取第一行的第一列
	 */
	public function getOne($col = null) {
		if ($col) {
			array_unshift($this->query, 'fields '.$col);
		}
		
		$cacheRedis = $GLOBALS['pad_core']->orm->cacheRedis;
		if ($cacheRedis && $this->cacheConfigs) {
			$query = $this->_getQueryString(1);
			$cacheId = $this->_getCacheId('getone', $query);
			
			if (($res = $this->_ctrlCache($cacheId)) === false) {
				$stmt = $this->_getStmt($query);
				$res = $stmt->fetchColumn();
				$this->_ctrlCache($cacheId, $res);
			}
			return $res;
		} else {
			$query = $this->_getQueryString(1);
			$stmt = $this->_getStmt($query);
			return $stmt->fetchColumn();
		}
	}
	
	/**
	 * 获得当前查询的SQL语句
	 */
	public function getSql(){
		$query = $this->_getQueryString(1);
		return $this->_db->debugCreateSql($query, $this->queryBinds);
	}

	/**
	 * 仅用于获取COUNT(*)类的情况，不传参数，则替换fields为COUNT(*)，否则使用参数作为COUNT
	 */
	public function getCount($field = null) {
		$cacheRedis = $GLOBALS['pad_core']->orm->cacheRedis;
		if ($cacheRedis && $this->cacheConfigs) {
			$query = $this->_getQueryString(2, $field);
			$cacheId = $this->_getCacheId('getcount', $query);
			
			if (($count = $this->_ctrlCache($cacheId)) === false) {
				$stmt = $this->_getStmt($query);
				$count = $stmt->fetchColumn();
				$this->_ctrlCache($cacheId, intval($count));
			}
			return $count;
		} else {
			$stmt = $this->_getStmt($this->_getQueryString(2, $field));
			return intval($stmt->fetchColumn());
		}
	}

	/**
	 * 混合实体和数据，允许传入参数
	 * 例如：getMixList('order@asKeyOrder', 'id');
	 */
	public function getMixList($mix1 = null, $mix2 = null) {
		$return = array();
		if (is_string($mix1) && is_string($mix2)) {
			$ids = array();
			$list = $this->getDataList();
			foreach ($list as $idx => $item) {
				$ids[] = $item->$mix2;
			}
			
			if (strpos($mix1, '@') === false) {
				$entityName = $mix1;
				$model = $GLOBALS['pad_core']->orm->getEntityModel($entityName);
				$model->get($ids);
				
				$return = array();
				foreach ($list as $item) {
					$eitem = $model->get($item->$mix2);
					foreach ($item as $k => $v) {
						$eitem->$k = $v;
					}
					$return[] = $eitem;
				}
			} else {
				list ($entityName, $ekey) = explode('@', $mix1);
				$model = $GLOBALS['pad_core']->orm->getEntityModel($entityName);
				$model->get($ids);
				
				$return = $list;
				foreach ($return as $idx => $item) {
					$return[$idx]->$ekey = $model->get($item->$mix2);
				}
			}
		} else {}
		return $return;
	}
	
	/**
	 * 把一个loader的结果，作为一个Join
	 * @param PadOrmLoader $loader 
	 * @param String $onCond 添加，例如: this(asName).id = @userId
	 * @return PadOrmLoader
	 */
	public function joinLoader($loader, $onCond,$joinType='left'){
		preg_match('/this\(([0-9a-zA-z]+)\)/', $onCond, $asMatch);
		$joinAs = '_t'.PadOrm::$tableCounter++;
		$asName = $asMatch[1];
		self::$cachedRelationAttr[$this->entityName][$asName] = array(null, $joinAs, null);

		$onCond = str_replace('this('.$asName.')', $joinAs, $onCond);
		$this->query($joinType.' join ('.$loader->_getQueryString().') as '.$joinAs.' on '.$onCond);
		foreach ($loader->queryBinds as $value) {
			$this->queryBinds[] = $value;
		}
		return $this;
	}

	/**
	 * 把一个loader的结果，作为一个Join
	 * @param PadOrmLoader $loader
	 * @param String $onCond 添加，例如: this(asName).id = @userId
	 * @return PadOrmLoader
	 */
	public function rightJoinLoader($loader, $onCond){
		preg_match('/this\(([0-9a-zA-z]+)\)/', $onCond, $asMatch);
		$joinAs = '_t'.PadOrm::$tableCounter++;
		$asName = $asMatch[1];
		self::$cachedRelationAttr[$this->entityName][$asName] = array(null, $joinAs, null);

		$onCond = str_replace('this('.$asName.')', $joinAs, $onCond);
		$this->query('right join ('.$loader->_getQueryString().') as '.$joinAs.' on '.$onCond);
		foreach ($loader->queryBinds as $value) {
			$this->queryBinds[] = $value;
		}
		return $this;
	}
	
	/**
	 * 全连接查询，用多个loader作为结果
	 */
	public function unionLoader(){
		$argvs = func_get_args();
		if (is_array($argvs[0])) {
			return call_user_func_array(array($this, 'unionLoader'), $argvs[0]);
		} else {
			$fileds = null;
			$fm = $this->_db->fieldMask;
			if (isset($this->query[0]) && preg_match('/fields(.*)/i', $this->query[0], $match)) {
				$fileds = $match[1];
				unset($this->query[0]);
			} else {
				$fileds = '_t0.' . $fm . implode($fm . ',_t0.' . $fm, $this->etyConfig->fieldsGroups[0]) . $fm;
			}
			
			$queryList = array();
			$queryBinds = array();
			foreach ($argvs as $loader) {
				if (isset($loader->query[0]) && (strpos(strtolower($loader->query[0]), 'select') === 0 || strpos(strtolower($loader->query[0]), 'fields') === 0)) {
				} else {
					array_unshift($loader->query, 'SELECT '.$fileds.' FROM @@ AS _t0');
				}
				
				$queryList[] = '('.$loader->_getQueryString().')';
				foreach ($loader->queryBinds as $bind) {
					$queryBinds[] = $bind;
				}
			}
			
			$mainSql = 'SELECT ' . $fileds . ' FROM (' . implode(' UNION ALL ', $queryList) . ') AS _t0';
			array_unshift($queryBinds, $mainSql);
			
			$this->query = array();
			call_user_func_array(array($this, 'query'), $queryBinds);
	
			return $this;
		}
	}
	
	public function update ($ids = null, $vars = array()) {
		$entityList = array();
		$model = $GLOBALS['pad_core']->orm->getEntityModel($this->entityName);
		if ($ids !== null) {
			$entityList = $model->get($ids);
		}
		foreach ($entityList as $entity) {
			$entity->sets($vars);
			$entity->flush();
		}
	}
	
	public function delete ($ids = null) {
		$entityList = array();
		$model = $GLOBALS['pad_core']->orm->getEntityModel($this->entityName);
		if ($ids !== null) {
			$entityList = $model->get($ids);
		}
		foreach ($entityList as $entity) {
			$entity->delete();
			$entity->flush();
		}
	}
	
	/**
	 * -----------------------------------------
	 */
	public function _getQueryString($fieldType = 0, $pfields = null) {
		// 如果是pdo，对特殊的处理
		if ($this->_db->driver == 'pdo') {
			$fm = $this->_db->fieldMask;
			if (isset($this->query[0]) && preg_match('/fields(.*)/i', $this->query[0], $match)) {
				$this->_farmatRelationField();
				
				foreach ($this->query as $idx => $query) {
					if (strpos($query, 'fields') === 0) {
						$fields = $this->query[$idx];
						preg_match('/fields(.*)/i', $fields, $match);
						
						unset($this->query[$idx]);
						array_unshift($this->query, 'SELECT ' . $match[1] . ' FROM ' . $this->_dbTableName . ' AS _t0');
					}
				}
			} elseif (! isset($this->query[0]) || ! preg_match('/^select.*/i', trim($this->query[0]))) {
				// 关系的字段格式化，自动加上join
				$this->_farmatRelationField();
				
				if ($fieldType == 0) {
					array_unshift($this->query, 'SELECT _t0.' . $fm . $this->etyConfig->pkField . $fm . ' FROM ' . $this->_dbTableName . ' AS _t0');
				} elseif ($fieldType == 1) {
					array_unshift($this->query, 
							'SELECT _t0.' . $fm . implode($fm . ',_t0.' . $fm, $this->etyConfig->fieldsGroups[0]) . $fm . ' FROM ' .
									 $this->_dbTableName . ' AS _t0');
				} elseif ($fieldType == 2) {
					$field = 'COUNT(*)';
					if ($pfields) {
						$field = str_replace('@', '_t0.', $pfields);
					}
					array_unshift($this->query, 'SELECT ' . $field . ' FROM ' . $this->_dbTableName . ' AS _t0');
				}
			} else if (isset($this->query[0]) && preg_match('/^select.*/i', trim($this->query[0]))) {
				$sqlNum = count($this->query);
				// 无论什么情况，都要格式化SQL
				$this->_farmatRelationField();
				$sqlNumNew = count($this->query);
				
				// select打头的情况，JOIN查询是在前面的，需要把SELECT抽出来
				if ($sqlNumNew > $sqlNum) {
					$selectSql = array_slice($this->query, $sqlNumNew - $sqlNum);
					$selectSql = array_shift($selectSql);
					array_splice($this->query, $sqlNumNew - $sqlNum, 1);
					array_unshift($this->query, $selectSql);
				}
			}
		}
		
		// 代替默认的表名
		foreach ($this->query as $idx => $query) {
			$this->query[$idx] = str_replace('@@', $this->_dbTableName, $query);
		}
		
		// 替代特殊的约定规则
		$query = implode(' ', $this->query);
		$query = str_replace(array("\n", "\r", $this->_noPageQueryPrefix), '', $query);
		
		// 如果有分页
		if ($this->queryPage) {
			if ($this->_db->driver == 'pdo') {
				$query .= ' LIMIT ' . $this->queryPage[1] . ' OFFSET ' . ($this->queryPage[0] - 1) * $this->queryPage[1];
			} else {
				$query .= "\n" . implode("\t", $this->queryPage);
			}
		}
		
		return $query;
	}

	/**
	 * 分页语句的生成
	 */
	private function _getPageQueryString() {
		// 防止先调用分页出现问题
		$this->_getQueryString(1);
		
		$queryList = $this->query;
		foreach ($queryList as $idx => $string) {
			if (strpos($string, $this->_noPageQueryPrefix) === 0) {
				unset($queryList[$idx]);
			}
		}
		
		$pagePrefixQuery = '';
		$query = '';
		if ($this->_db->driver == 'pdo') {
			// 自定义取总条数的语句
			if ($this->queryPage[2]) {
				$pagePrefixQuery = str_replace('@@', $this->_dbTableName, $this->queryPage[2]) . ' ';
			} else {
				$pagePrefixQuery = $queryList[0];
				$pagePrefixQuery = preg_replace('/^SELECT(.*?)FROM/', 'SELECT COUNT(*) FROM', $pagePrefixQuery) . ' ';
			}
			$query = implode(' ', array_slice($queryList, 1));
		} else {
			$query = implode(' ', $queryList);
		}

		$query = str_replace(array(
			"\n",
			"\r"
		), '', $query);
		return $pagePrefixQuery . $query;
	}

	/**
	 * 格式化关系的字符串
	 */
	private function _farmatRelationField() {
		if (! isset(self::$cachedRelationAttr[$this->entityName])) {
			self::$cachedRelationAttr[$this->entityName] = array();
		}
		$fieldMask = $this->_db->fieldMask;
		
		/**
		 * 已经join的表
		 */
		$hasJoined = array();
		$joinList = array();
		foreach ($this->query as &$query) {
			while (preg_match('/@[a-zA-Z0-9\-_\.]+/', $query, $match)) {
				$vfield = $match[0];
				if ($vfield == '@.') {
					$query = str_replace($vfield, '_t0.', $query);
					continue;
				}
				
				if (strpos($vfield, '.') === false) {
					$query = str_replace($vfield, '_t0.' . substr($vfield, 1), $query);
					continue;
				}
				
				$vfieldSplit = explode('.', substr($vfield, 1));
				$vfieldLastField = array_pop($vfieldSplit);
				$vfieldPrefix = implode('.', $vfieldSplit);
				
				/**
				 * 缓存的状态
				 */
				$isCached = false;
				if (isset(self::$cachedRelationAttr[$this->entityName][$vfieldPrefix])) {
					$cache = self::$cachedRelationAttr[$this->entityName][$vfieldPrefix];
					$tmpquery = str_replace('@' . $vfieldPrefix, $cache[1], $query);
					if (strpos($tmpquery, '.') === false) {
						$query = $tmpquery;
						$vstring = $cache[1];
						$isCached = true;
					}
				}
				
				if (! $isCached) {
					$vstring = null;
					$pvstring = '_t0';
					$etyConfig = $this->etyConfig;
					$maxCachedIndex = 0;
					
					for ($i = count($vfieldSplit); $i > 0; $i --) {
						$cachePrefix = implode('.', array_slice($vfieldSplit, 0, $i));
						
						if (isset(self::$cachedRelationAttr[$this->entityName][$cachePrefix])) {
							$maxCachedIndex = $i;
							$cache = self::$cachedRelationAttr[$this->entityName][$cachePrefix];
							
							$etyConfig = null;
							$pvstring = $cache[1];
							$vstring = $cache[1];
							if ($cache[0] !== null) {
								$etyConfig = $GLOBALS['pad_core']->orm->getEntityConfig($cache[0]);
								$pvstring = $cache[1];
								$vstring = $cache[1];
							}
							// 最长命中，返回
							break;
						}
					}
					
					if ($maxCachedIndex < count($vfieldSplit)) {
						for ($i = $maxCachedIndex; $i < count($vfieldSplit); $i ++) {
							$rltConfig = $etyConfig->relations[$vfieldSplit[$i]];
							$etyConfig = $GLOBALS['pad_core']->orm->getEntityConfig($rltConfig['entity_name']);
							
							$cachePrefix = implode('.', array_slice($vfieldSplit, 0, $i + 1));
							$vstring = '_t' . (PadOrm::$tableCounter ++);
							self::$cachedRelationAttr[$this->entityName][$cachePrefix] = array(
								$rltConfig['entity_name'],
								$vstring,
								$pvstring
							);
							$pvstring = $vstring;
						}
					}
				}
				
				/**
				 * 替换sql
				 */
				$query = str_replace($vfield, $vstring . '.' . $vfieldLastField, $query);
				
				/**
				 * 添加自动join的
				 */
				$joins = array();
				$etyConfig = $this->etyConfig;
				foreach ($vfieldSplit as $index => $name) {
					$cachePrefix = implode('.', array_slice($vfieldSplit, 0, $index + 1));
					$cache = self::$cachedRelationAttr[$this->entityName][$cachePrefix];
					
					if ($cache[0] === null) {
						$hasJoined[$cachePrefix] = true;
						continue;
					}
					
					$rltConfig = $etyConfig->relations[$name];
					$etyConfig = $GLOBALS['pad_core']->orm->getEntityConfig($rltConfig['entity_name']);

					if (! isset($hasJoined[$cachePrefix])) {
						$hasJoined[$cachePrefix] = true;
						
						$joinType = null;
						$linkField = array();
						$lWhere = array();
						if ($rltConfig['type'] == 'belongto') {
							$joinType = 'LEFT ';
							$linkField = array(
								$etyConfig->pkField,
								$rltConfig['link_field']
							);
							if (isset($rltConfig['link_where'])) {
								$lWhere = $rltConfig['link_where'];
								$lWhere[0] = $cache[2] . '.' . $lWhere[0];
							}
						} elseif ($rltConfig['type'] == 'hasone' || $rltConfig['type'] == 'hasmany') {
							$joinType = 'LEFT ';
							$linkField = array(
								$etyConfig->relations[$rltConfig['belongto_key']]['link_field'],
								$this->etyConfig->pkField
							);
							if (isset($etyConfig->relations[$rltConfig['belongto_key']]['link_where'])) {
								$lWhere = $etyConfig->relations[$rltConfig['belongto_key']]['link_where'];
								$lWhere[0] = $cache[1] . '.' . $lWhere[0];
							}
						}
						
						$sql = $joinType . 'JOIN ' . $fieldMask . $etyConfig->dbRealName . $fieldMask . '.' . $fieldMask .  $etyConfig->dbTableName . $fieldMask . ' AS ' . $cache[1] . ' ON ' . $cache[1] . '.' . $linkField[0] . ' = ' . $cache[2] .
								 '.' . $linkField[1];
						if ($lWhere) {
							$sql .= ' AND ' . $lWhere[0] . ' = \'' . $lWhere[1] . '\'';
						}
						array_push($joins, $sql);
					}
				}
				
				/**
				 * 压入join的语句
				 */
				foreach ($joins as $join) {
					$joinList[] = $join;
				}
			}
		}
		
		foreach (array_reverse($joinList) as $join) {
			array_unshift($this->query, $join);
		}
	}

	/**
	 * 获得数据库stmt
	 */
	private function _getStmt($queryString) {
		assert('$startTime = microtime(true)*10000;');
		$stmt = $this->_db->prepare($queryString);
		$this->_db->bindValues($stmt, $this->queryBinds);
		
		assert('$GLOBALS[\'pad_core\']->debug->write(\'db\', $this->_db->debugCreateSql($queryString, $this->queryBinds), microtime(true)*10000 - $startTime);');
		$succ = $stmt->execute();
		
		if (! $succ) {
			$errinfo = $stmt->errorInfo();
			$GLOBALS['pad_core']->error(E_ERROR, 'SqlError, code "%s", message "%s"', $errinfo[1], $errinfo[2]);
		}
		return $stmt;
	}
}
