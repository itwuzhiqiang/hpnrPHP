<?php

class PadOrmEntity {

	/**
	 * 缓存的const定义数组
	 *
	 * @var array
	 */
	public static $loaderCacheList;
	
	/* loader的pair返回值定义数组 */
	public static $loaderPairCacheList = array();
	
	/**
	 * 实体是否为空
	 *
	 * @var bool
	 */
	public $isnull = false;

	/**
	 * 内部调用的，虽然是public变量
	 *
	 * @var unknown
	 */
	public $_pvtVars;
	
	/* 分区表参数 */
	private $_partitionParams = array();
	
	/* 查询的数据库信息 */
	public $_dbName;
	public $_dbRealName;
	public $_db;
	public $_dbTableName;

	static public function getLoader () {
		$model = $GLOBALS['pad_core']->orm->getEntityModel(get_called_class());
		return $model->loader();
	}

	static public function getEntity ($mix) {
		$model = $GLOBALS['pad_core']->orm->getEntityModel(get_called_class());
		return $model->get($mix);
	}

	static public function createEntity ($mix = array()) {
		$model = $GLOBALS['pad_core']->orm->getEntityModel(get_called_class());
		return $model->create($mix);
	}
	
	static public function getEntityBy ($params = array()) {
		$model = $GLOBALS['pad_core']->orm->getEntityModel(get_called_class());
		return $model->getby($params);
	}

	static public function getModel () {
		$model = $GLOBALS['pad_core']->orm->getEntityModel(get_called_class());
		return $model;
	}
	
	/**
	 * 加载loader的const配置信息
	 *
	 * @param string $className 一般是__CLASS__
	 * @param string $name 配置名字
	 */
	static public function loaderConst($className, $name) {
		if (! isset(self::$loaderCacheList)) {
			self::$loaderCacheList = array();
		}
		
		if (! isset(self::$loaderCacheList[$className])) {
			$loaderClassName = $GLOBALS['pad_core']->orm->entityClassNameLoaderMap[$className];
			$loader = new $loaderClassName();
			self::$loaderCacheList[$className] = $loader;
		}
		
		return self::$loaderCacheList[$className]->$name;
	}

	/**
	 * 获得当前实体名称
	 */
	public function entityName() {
		return $this->_pvtVars->etyConfig->entityName;
	}
	
	/**
	 * 设置分区表
	 * @param unknown $data
	 * @return PadOrmEntity
	 */
	public function setPartition($data){
		PadOrmLoader::baseSetPartition($this->_pvtVars->etyConfig, $this, $data);
		return $this;
	}
	
	/**
	 * 设置查询的数据库和查询的数据表
	 * @param string $dbName
	 * @param string $dbTableName
	 */
	public function setDb($dbName = null, $dbRealName = null, $dbTableName = null){
		PadOrmLoader::baseSetDb($this->_pvtVars->etyConfig, $this, $dbName, $dbRealName, $dbTableName);
		return $this;
	}

	/**
	 * 所有的字段导出为一个数组
	 *
	 * @return array
	 */
	public function datas($autoLoad = true) {
		$return = array();
		$return['_id'] = $this->_pvtVars->id;
		foreach ($this->_pvtVars->etyConfig->fields as $name => $null) {
			if ($autoLoad || isset($this->$name)) {
				$return[$name] = $this->$name;
			}
		}
		return $return;
	}

	/**
	 * 所有的字段导出为一个数组(新添加,魔术方法格式)
	 *
	 * @return array
	 */
	public function __datas($autoLoad = true){
		return $this->datas($autoLoad);
	}

	/**
	 * 将所有的字段dump出来，一般用于debug
	 *
	 * @param unknown_type $func
	 */
	public function dump($func = 'print_r') {
		if (! in_array($func, array(
			'print_r',
			'var_dump'
		))) {
			$GLOBALS['pad_core']->error(E_ERROR, 'func one of print_r|var_dump');
		}
		$func($this->datas());
	}

	/**
	 * 创建一个新的实体
	 *
	 * @return PadOrmEntity
	 */
	public function create() {
		$GLOBALS['pad_core']->orm->addCreateEntity($this);
		return $this;
	}

	/**
	 * 获得当前实体对应的加载器
	 */
	public function loader() {
		return $GLOBALS['pad_core']->orm->getEntityModel($this->_pvtVars->etyConfig->entityName)->loader();
	}
	
	public function model(){
		return $GLOBALS['pad_core']->orm->getEntityModel($this->_pvtVars->etyConfig->entityName);
	}

	/**
	 * 设置一个字段
	 *
	 * @param string $name
	 * @param string $value
	 * @return bool
	 */
	public function set($name, $value) {
		if (strpos($name, '.') !== false) {
			$fields = explode('.', $name);
			$fld = array_pop($fields);
			$updateEntity = $this->get(implode('.', $fields));
			return $updateEntity->set($fld, $value);
		}

		if (isset($this->$name) && $this->$name == $value) {
			return null;
		}

		if (method_exists($this, '_set_' . $name)) {
			$this->{'_set_' . $name}($value);
		} else {
			// 如果不是新建的实体，或者直接new的实体
			if (! $this->_pvtVars->isCreate && ! $this->_pvtVars->isNewer) {
				$this->_pvtVars->isUpdate = true;
				$GLOBALS['pad_core']->orm->entityChangedList[$this->_pvtVars->etyConfig->entityName][$this->_pvtVars->id] = $this;

				if (! isset($this->_pvtVars->formerDatas[$name])) {
					$this->_pvtVars->formerDatas[$name] = $this->$name;
				}
			}

			if (is_array($value)) {
				$this->_specialSet($name, $value);
			} else {
				// 直接赋值一定要清除特殊值
				unset($this->_pvtVars->specialDatas[$name]);
				$this->$name = $value;
			}
		}

		$after = '_afterSet_' . $name;
		if (method_exists($this, $after)) {
			$this->$after();
		}
	}
	
	public function deleteRelation ($key) {
		$relations = $this->_pvtVars->etyConfig->relations;
		if (!isset($relations[$key])) {
			$GLOBALS['pad_core']->error(E_ERROR, 'not found relation %s', $key);
		}
		
		$relationCfg = $relations[$key];
		if ($relationCfg['type'] == 'hasone' || $relationCfg['type'] == 'hasmany') {
			$loader = $GLOBALS['pad_core']->orm->getEntityModel($relationCfg['entity_name'])->loader();
			$datalist = $loader->query('where @'.$relationCfg['belongto_key'].'.id = ?', $this->id)->getList();
			foreach ($datalist as $ety) {
				$ety->delete();
				$ety->flush();
			}
		}
		return true;
	}
	
	public function setRelation($key, $value){
		$relations = $this->_pvtVars->etyConfig->relations;
		if (!isset($relations[$key])) {
			$GLOBALS['pad_core']->error(E_ERROR, 'not found relation %s', $key);
		}
		
		$relationCfg = $relations[$key];
		if ($relationCfg['type'] == 'many2many') {
			$this->_setHasManyFields($relationCfg['entity_name'], $relationCfg['this_link'], $relationCfg['to_link'], $value);
		}
	}
	
	private function _setHasManyFields($entityName, $thisRelationName, $toRelationName, $values){
		$model = $GLOBALS['pad_core']->orm->getEntityModel($entityName);
		$entityConfig = $GLOBALS['pad_core']->orm->getEntityConfig($entityName);
		$thisRelationId = $entityConfig->relations[$thisRelationName]['link_field'];
		$toRelationId = $entityConfig->relations[$toRelationName]['link_field'];
		
		$entityDataList = $model->query('where @'.$thisRelationName.'.id = ?', $this->id)
			->getList();

		$entityList = array();
		foreach ($entityDataList as $entity) {
			$entityList[$entity->$toRelationId] = $entity;
		}
		
		foreach ($values as $index => $val) {
			if (isset($entityList[$val])) {
				if (method_exists($entityList[$val], 'setUnDelete')) {
					$entityList[$val]->setUnDelete();
				}
				unset($entityList[$val]);
				unset($values[$index]);
			}
		}
		
		foreach ($entityList as $entity) {
			if (method_exists($entity, 'setDelete')) {
				$entity->setDelete();
			} else {
				$entity->delete();
			}
		}
		
		foreach ($values as $val) {
			$model->createOrUpdate($thisRelationId.','.$toRelationId, array(
				$thisRelationId => $this->id,
				$toRelationId => $val,
			));
		}
	}

	/**
	 * 设置多个字段的值，和sets一样
	 *
	 * @param unknown_type $vars
	 */
	public function sets($vars) {
		foreach ($vars as $name => $value) {
			$this->set($name, $value);
		}
	}

	/**
	 * 自加减
	 *
	 * @param string $name
	 * @param mix $value
	 */
	public function incr($name, $value) {
		$this->set($name, array(
			'@incr',
			$value
		));
	}

	/**
	 * 删除实体，同时也删除数据库对应的数据
	 */
	public function delete() {
		if ($this->_pvtVars->isCreate) {
			unset($GLOBALS['pad_core']->orm->entityNewList[$this->_pvtVars->etyConfig->entityName][$this->_pvtVars->newIndex]);
		} else {
			$this->_pvtVars->isDelete = true;
			$GLOBALS['pad_core']->orm->entityChangedList[$this->_pvtVars->etyConfig->entityName][$this->_pvtVars->id] = $this;
		}
	}

	/**
	 * 业务删除纪录,不会从数据库删除
	 */
	public function bizDelete() {
		$this->set('is_delete', 1);
		$this->flush();
	}

	/**
	 * 获得实体的字段，或者级联对象
	 *
	 * @param string $name
	 * @return mix
	 */
	public function get($name) {
		$list = explode('.', $name);
		$return = $this;
		foreach ($list as $k) {
			if (is_array($return)) {
				$return = (object) $return;
			}
			$return = $return->$k;
		}
		return $return;
	}

	/**
	 * 魔术方法加载，和get()一样
	 *
	 * @param string $name
	 * @return mix
	 */
	public function __get($name) {
		if (isset($this->_pvtVars->etyConfig->relations[$name])) {
			$rltConfig = $this->_pvtVars->etyConfig->relations[$name];
			if ($rltConfig['type'] == 'hasone' || $rltConfig['type'] == 'belongto') {
				$this->_loadRelation($name);
			} else if ($rltConfig['type'] == 'many2many' || $rltConfig['type'] == 'hasmany') {
				return $this->_loadRelation($name);
			}
		} else 
			if ($name == $this->_pvtVars->etyConfig->pkField) {
				// 请求到未提交的自增主键
				$this->flush();
			} else {
				if (isset($this->_pvtVars->etyConfig->fields[$name])) {
					// 请求到延迟加载的字段
					$this->_loadLazyField($name);
				} else {
					if (method_exists($this, '_get_' . $name)) {
						// 请求到_get_的方法
						return $this->{'_get_' . $name}();
					} else {
						$GLOBALS['pad_core']->error(E_NOTICE, 'entity "%s" field "%s" not found', $this->_pvtVars->etyConfig->entityName, $name);
						return NULL;
					}
				}
			}
		return $this->$name;
	}

	/**
	 * 根据loader上返回pair的函数，直接返回pair的结果
	 * @param unknown $loaderFunc
	 * @param unknown $field
	 * @return Ambigous <NULL, unknown>
	 */
	public function getPairValue ($loaderFunc, $fieldName) {
		if (!isset(self::$loaderPairCacheList[$loaderFunc])) {
			self::$loaderPairCacheList[$loaderFunc] = $this->loader()->$loaderFunc();
		}
		$pair = self::$loaderPairCacheList[$loaderFunc];
		$default = isset($pair['__default']) ? $pair['__default'] : null;
		$default = str_replace('#value#', $this->$fieldName, $default);
		return isset($pair[$this->$fieldName]) ? $pair[$this->$fieldName] : $default;
	}

	/**
	 * 提交前的数据检查
	 */
	public function checkToFlush () {
		$errorResult = $this->loader()->checkDatas($this->datas(), $this->_pvtVars->isCreate ? null : $this->_pvtVars->id);
		if ($errorResult) {
			$exception = new PadException('数据提交失败', 17000);
			foreach ($errorResult as $field => $message) {
				$exception->set($field, $message);
			}
			throw $exception;
		}
	}

	/**
	 * 是否是新建的实体
	 * @return mixed
	 */
	public function isCreate () {
		return $this->_pvtVars->isCreate;
	}
	
	/**
	 * 强制设置不往数据库更新
	 *
	 * @param bool $bool
	 */
	public function forceSetNotFlush($bool = false) {
		$this->_pvtVars->isFlush = $bool;
	}

	/**
	 * 提交前，后自动调用这个方法
	 */
	public function onFlush() {}

	/**
	 * 提交后，会自动调用的方法
	 *
	 * @param string $faction 写入方式[insert,update,delete]
	 * @param array $fdatas 数组
	 */
	public function afterFlush($faction, $fdatas) {}

	/**
	 * 提交当前实体的更改
	 *
	 * @param unknown_type $force
	 * @param unknown_type $useAfterFlush
	 * @return boolean
	 */
	public function flush($force = false, $useAfterFlush = true) {
		$pg_orm = $GLOBALS['pad_core']->orm;
		$this->_pvtVars->isFlush = true;
		$etyConfig = $this->_pvtVars->etyConfig;
		$pkField = $etyConfig->pkField;
		
		// 存在分区配置，自动加载
		if ($this->_pvtVars->etyConfig->dbPartitionConfig) {
			$datas = $this->datas(false);
			$this->setPartition($datas);
		}
		$fieldMask = $this->_db->fieldMask;
		
		// 获得数据库表名
		$dbTableName = $etyConfig->getDbTableName($this);
		
		$faction = 'update';
		$fdatas = array();
		
		if ($this->_pvtVars->isCreate || $this->_pvtVars->isDelete || $this->_pvtVars->isUpdate) {
			$this->onFlush();
		}
		
		$succ = true;
		if ($this->_pvtVars->isCreate) {
			$faction = 'create';
			$datas = $this->_getChangedDatas();
			
			$this->_checkDatasByFieldType($datas);
			$succ = $this->_db->insert($this->_dbTableName, $datas);
			if ($succ && $this->_pvtVars->etyConfig->pkFieldIsAutoIncr) {
				$id = $this->_db->getInsertId();
				$this->$pkField = $id;
				$this->_pvtVars->id = $id;
			} else {
				$this->_pvtVars->id = $this->$pkField;
			}
			
			/**
			 * 提交后，从newList迁移到loadedList
			 */
			$this->_pvtVars->isCreate = false;
			unset($GLOBALS['pad_core']->orm->entityNewList[$etyConfig->entityName][$this->_pvtVars->newIndex]);
			
			if ($pg_orm->isUseMemoryCache) {
				$GLOBALS['pad_core']->orm->entityLoadedList[$etyConfig->entityName][$this->$pkField] = $this;
			}
		} elseif ($this->_pvtVars->isDelete) {
			$faction = 'delete';
			$succ = $this->_db->delete($this->_dbTableName, $fieldMask . $etyConfig->pkField . $fieldMask . ' = \'' . $this->_pvtVars->id . '\'');
			
			/**
			 * 防止二次删除
			 */
			if ($pg_orm->isUseMemoryCache) {
				unset($GLOBALS['pad_core']->orm->entityLoadedList[$etyConfig->entityName][$this->$pkField]);
			}
		} elseif ($this->_pvtVars->isUpdate) {
			$faction = 'update';
			$datas = $this->_getChangedDatas();
			$fdatas = $this->_pvtVars->formerDatas;
			
			$this->_checkDatasByFieldType($datas);
			$succ = $this->_db->update($this->_dbTableName, $datas, 
					$fieldMask . $etyConfig->pkField . $fieldMask . ' = \'' . $this->_pvtVars->id . '\'');
		}
		
		/**
		 * 更新实体对应的缓存
		 */
		$GLOBALS['pad_core']->orm->flush->refreshCacheEntity($this, ($faction == 'update' ? $fdatas : array()));
		
		/**
		 * flush后的回调函数
		 */
		if ($useAfterFlush) {
			$this->afterFlush($faction, $fdatas);
		}
		
		/**
		 * 恢复状态
		 */
		$this->_pvtVars->isCreate = false;
		$this->_pvtVars->isUpdate = false;
		$this->_pvtVars->isDelete = false;
		$this->_pvtVars->specialDatas = array();
		
		return $succ;
	}

	/**
	 * 加载关联对象
	 *
	 * @param string $name 对象名称
	 */
	private function _loadRelation($name) {
		$pg_orm = $GLOBALS['pad_core']->orm;
		$etyConfig = $this->_pvtVars->etyConfig;
		$rltConfig = $this->_pvtVars->etyConfig->relations[$name];
		
		$rltLinkField = null;
		$attachWhere = null;
		if ($rltConfig['type'] == 'belongto') {
			$rltLinkField = $rltConfig['link_field'];
			if (isset($rltConfig['link_where'])) {
				$attachWhere = $rltConfig['link_where'];
			}
			
			$rltSimilarEntities = array();
			$rltSimilarIds[] = $this->$rltLinkField;
			
			// 开启内存缓存，从内存找类似的实体批量加载
			if ($pg_orm->isUseMemoryCache) {
				foreach ($pg_orm->entityLoadedList[$etyConfig->entityName] as $ety) {
					if (! $ety->isnull && ! isset($ety->$name) && $ety->_pvtVars->fversion == $this->_pvtVars->fversion) {
						if (! $attachWhere || $ety->{$attachWhere[0]} == $attachWhere[1]) {
							$rltSimilarEntities[] = $ety;
							$rltSimilarIds[] = $ety->$rltLinkField;
						} else {
							$ety->$name = $pg_orm->entityNull;
						}
					}
				}
			}
			$rltList = $pg_orm->getEntityModel($rltConfig['entity_name'])->commonLoader->loadByIds(array_unique($rltSimilarIds), true, null, null);
			
			foreach ($rltSimilarEntities as $ety) {
				$ety->$name = $rltList[$ety->$rltLinkField];
			}
			
			if (! $attachWhere || $this->{$attachWhere[0]} == $attachWhere[1]) {
				$this->$name = $rltList[$this->$rltLinkField];
			} else {
				$this->$name = $pg_orm->entityNull;
			}
		} elseif ($rltConfig['type'] == 'hasone') {
			$rltEtyConfig = $GLOBALS['pad_core']->orm->getEntityConfig($rltConfig['entity_name']);
			$pkField = $this->_pvtVars->etyConfig->pkField;
			$rltEtyRltConfig = $rltEtyConfig->relations[$rltConfig['belongto_key']];
			
			$rltSimilarEntities[$this->$pkField] = $this;
			$rltSimilarIds[] = $this->$pkField;
			
			if ($pg_orm->isUseMemoryCache) {
				foreach ($pg_orm->entityLoadedList[$etyConfig->entityName] as $ety) {
					if (! $ety->isnull && ! isset($ety->$name) && $ety->_pvtVars->fversion == $this->_pvtVars->fversion) {
						$rltSimilarEntities[$ety->$pkField] = $ety;
						$rltSimilarIds[] = $ety->$pkField;
					}
				}
			}
			
			$rltSimilarIds = array_unique($rltSimilarIds);
			$etylist = $GLOBALS['pad_core']->orm->getEntityModel($rltConfig['entity_name'])
				->loader()
				->query('where @' . $rltConfig['belongto_key'] . '.' . $pkField . ' IN (' . implode(',', $rltSimilarIds) . ')')
				->getList();
			
			foreach ($etylist as $ety) {
				if (! $ety->{$rltConfig['belongto_key']}->isnull) {
					$rltSimilarEntities[$ety->{$rltConfig['belongto_key']}->$pkField]->$name = $ety;
					unset($rltSimilarEntities[$ety->{$rltConfig['belongto_key']}->$pkField]);
				}
			}
			
			foreach ($rltSimilarEntities as $ety) {
				$ety->$name = $pg_orm->entityNull;
			}
		} elseif ($rltConfig['type'] == 'hasmany') {
			$loader = $GLOBALS['pad_core']->orm->getEntityModel($rltConfig['entity_name'])->loader();
			$loader->query('where @' . $rltConfig['belongto_key'] . '.' . $this->_pvtVars->etyConfig->pkField . ' = ?', $this->id);
			return $loader;
		} elseif ($rltConfig['type'] == 'many2many') {
			$loader = $GLOBALS['pad_core']->orm->getEntityModel($rltConfig['entity_name'])->loader();
			return $loader->query('where @'.$rltConfig['this_link'].'.id = ?', $this->id);
		}
	}

	/**
	 * 加载延迟字段
	 *
	 * @param string $name
	 * @return mix
	 */
	private function _loadLazyField($name) {
		$pg_orm = $GLOBALS['pad_core']->orm;
		$etyConfig = $this->_pvtVars->etyConfig;
		
		if ($pg_orm->isUseMemoryCache) {
			$similarIds = array();
			foreach ($pg_orm->entityLoadedList[$etyConfig->entityName] as $ety) {
				if (! $ety->isnull && $ety->_pvtVars->fversion == $this->_pvtVars->fversion) {
					$similarIds[] = $ety->_pvtVars->id;
				}
			}
			$pg_orm->getEntityModel($etyConfig->entityName)->commonLoader->loadLazyFieldsByIds($name, $similarIds);
		} else {
			$pg_orm->getEntityModel($etyConfig->entityName)->commonLoader->loadLazyFieldsByIds($name, array(
				$this->_pvtVars->id
			), array(
				$this->_pvtVars->id => $this
			));
		}
		
		return null;
	}

	/**
	 * 获得需要存入数据库的字段
	 *
	 * @return array
	 */
	private function _getChangedDatas() {
		$datas = array();
		
		if ($this->_pvtVars->isCreate) {
			$pkField = $this->_pvtVars->etyConfig->pkField;
			if (isset($this->$pkField)) {
				$datas[$pkField] = $this->$pkField;
			}
			foreach ($this->_pvtVars->etyConfig->fields as $field) {
				if (isset($this->$field) && $this->$field !== null) {
					$datas[$field] = $this->$field;
				}
			}
		} elseif ($this->_pvtVars->isUpdate) {
			foreach ($this->_pvtVars->formerDatas as $field => $value) {
				if (isset($this->_pvtVars->specialDatas[$field])) {
					$datas[$field] = $this->_pvtVars->specialDatas[$field];
				} else {
					$datas[$field] = $this->$field;
				}
			}
		}
		
		return $datas;
	}

	/**
	 * flush前，通过flied_type检查值的合法性
	 *
	 * @param array $datas
	 */
	private function _checkDatasByFieldType($datas) {
		$tableName = $this->_pvtVars->etyConfig->entityName;
		foreach ($datas as $field => $value) {
			/**
			 * 没有field_type规则，进行下一个
			 */
			$fieldType = null;
			if (isset($this->_pvtVars->etyConfig->fieldsTypes[$field])) {
				$fieldType = $this->_pvtVars->etyConfig->fieldsTypes[$field];
			} else {
				continue;
			}
			
			/**
			 * 允许为空值时，传入的value为FALSE值，则都放行
			 */
			if ($fieldType['is_allow_empty'] && ! $value) {
				continue;
			}
			
			/**
			 * 值自增，不用检查，进行下一个
			 */
			if (is_array($value) && $value[0] == '@incr') {
				if ($fieldType['field_type'] != 'int') {
					$GLOBALS['pad_core']->error(E_ERROR, 'field "%s.%s" not int, can\'t incr', $tableName, $field);
				} else {
					continue;
				}
			}
			
			/**
			 * 如果是字符串，检查长度
			 */
			if ($fieldType['field_type'] == 'char' && ($fieldType['field_length'] > 0 && strlen($value) > $fieldType['field_length'])) {
				$GLOBALS['pad_core']->error(E_ERROR, 'field "%s.%s" length > ?', $tableName, $field, strlen($value));
			}
			
			/**
			 * 如果是数字，检查是否数字串
			 */
			if ($fieldType['field_type'] == 'int' && ! is_numeric($value)) {
				$GLOBALS['pad_core']->error(E_ERROR, 'field "%s.%s" must be numeric', $tableName, $field);
			}
			
			/**
			 * 检查regex规则
			 */
			foreach ($fieldType['regex'] as $item) {
				if (! preg_match($item, $value)) {
					$GLOBALS['pad_core']->error(E_ERROR, 'field "%s.%s" check fail: regex(%s)', $tableName, $field, $item);
				}
			}
		}
	}

	/**
	 * 特殊字段赋值
	 *
	 * @param string $name
	 * @param mix $value
	 */
	private function _specialSet($name, $value) {
		if ($this->_pvtVars->isCreate) {
			$GLOBALS['pad_core']->error(E_ERROR, 'new entity can\'t self operate');
		}
		
		if (! isset($this->_pvtVars->specialDatas[$name])) {
			$this->_pvtVars->specialDatas[$name] = $value;
			if ($value[0] == '@incr') {
				$this->{$name} += $value[1];
			}
		} elseif ($this->_pvtVars->specialDatas[$name][0] != $value[0]) {
			$GLOBALS['pad_core']->error(E_ERROR, 'special can\'t double type');
		} else {
			if ($value[0] == '@incr') {
				$this->{$name} += $value[1];
				$this->_pvtVars->specialDatas[$name][1] += $value[1];
			}
		}
	}
}

/**
 * 空实体的类
 * 始终返回NULL
 *
 * @author zy-huchunhui
 *        
 */
class PadOrmEntityNull {

	public $isnull = true;

	public function __get($key) {
		return $this;
	}

	public function __set($key, $value) {
		return $this;
	}

	public function __call($function, $args) {
		return $this;
	}

	public function __tostring() {
		return '';
	}
}



