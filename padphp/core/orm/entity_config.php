<?php

class PadOrmEntityConfig {

	public $isInit = false;

	public $entityName;

	public $entityClassName;

	public $loaderClassName;

	public $dbName;
	public $dbTableName;
	public $dbPartitionConfig;

	public $pkField;

	public $pkFieldIsAutoIncr = false;

	public $fields = array();

	public $fieldsInfo = array();

	public $fieldsGroups = array();

	public $relations = array();

	public $cacheVersions = array();

	public $labels = array();

	public $entityBaseMatrix;

	public $db;

	public $cacheVersion;

	public $cacheData;

	public function __construct($entityName) {
		$this->entityName = $entityName;
	}

	public function init() {
		if ($this->isInit)
			return null;
		$this->isInit = true;
		
		// 非PadOrmEntity的子类，直接跳出，所有逻辑由类自身监管
		if (! is_subclass_of($this->entityClassName, 'PadOrmEntity')) {
			$GLOBALS['pad_core']->error(E_ERROR, 'entity "%s" must subclass_of "%s"', $this->entityName, 'PadOrmEntity');
		}
		
		// 读取配置
		$abstractConfig = array();
		$entityConfig = array();
		if (method_exists($this->entityClassName, 'baseConfig')) {
			$abstractConfig = call_user_func($this->entityClassName . '::baseConfig');
		}
		if (method_exists($this->entityClassName, 'config')) {
			$entityConfig = call_user_func($this->entityClassName . '::config');
		}
		
		$baseConfig = array(
			'db_name' => 'default',
			'db_table_name' => $this->entityName,
			'fields_groups' => array(),
			'relations' => array(),
			'fields_types_extend_db' => false,
			'fields_types' => array(),
			'labels' => array()
		);
		$configs = array_merge($baseConfig, $abstractConfig, $entityConfig);
		
		$this->dbName = $configs['db_name'];
		$this->dbRealName = $configs['db_name'];
		$this->db = $GLOBALS['pad_core']->database->getPerform($configs['db_name']);
		if (isset($configs['db_real_name'])) {
			$this->dbRealName = $configs['db_real_name'];
		} else if (isset($this->db->configs['database'])) {
			$this->dbRealName = $this->db->configs['database'];
		}
		$this->dbTableName = $configs['db_table_name'];
		$dbTableName = $this->dbTableName;
		$this->dbPartitionConfig = (isset($configs['db_partition']) ? $configs['db_partition'] : null);
		
		// 缓存id
		if (isset($configs['cache_version_name'])) {
			$this->cacheVersion = $GLOBALS['pad_core']->cache->getPerform($configs['cache_version_name']);
		}
		
		if (isset($configs['cache_data_name'])) {
			$this->cacheData = $GLOBALS['pad_core']->cache->getPerform($configs['cache_data_name']);
		}
		
		// 表和字段描述
		$this->labels = $configs['labels'];
		
		// 强制不使用事务
		if ($GLOBALS['pad_core']->orm->disableTrans) {
			$this->db->configs['use_trans'] = false;
		}
		
		$fieldsInfo = null;
		if (!isset($configs['db_table_fields'])) {
			if (isset($configs['apc_cache_fields_info']) && $configs['apc_cache_fields_info']) {
				$fieldsInfo = apc_fetch($GLOBALS['pad_core']->instanceKey . '.entity.config.fields_info.' . $this->entityName);
				if ($fieldsInfo === false) {
					$fieldsInfo = $this->db->getFieldsInfo($this->dbTableName);
					apc_store($GLOBALS['pad_core']->instanceKey . '.entity.config.fields_info.' . $this->entityName, $fieldsInfo);
				}
			} else {
				$fieldsInfo = $this->db->getFieldsInfo($dbTableName);
			}

			// 没有找个字段信息，表不存在
			if (empty($fieldsInfo)) {
				$GLOBALS['pad_core']->error(E_ERROR, 'entity "%s" not found db table name[%s]', $this->entityName, $this->dbTableName);
			}
		} else {
			$baseFieldInfo = array(
				'name' => null,
				'type' => 'varchar(255)',
				'is_auto_incr' => false,
				'is_big' => false,
				'is_unique' => false,
				'is_pri' => false,
			);
			$fieldsInfoRet = array();
			$isFirst = true;
			foreach ($configs['db_table_fields'] as $fd => $cfg) {
				if (is_array($cfg)) {
					$fieldsInfoRet[$fd] = array_merge($baseFieldInfo, $cfg);
				} else {
					$fieldsInfoRet[$fd] = array_merge($baseFieldInfo, array(
						'name' => $cfg,
						'is_pri' => $isFirst,
					));
				}
				$isFirst = false;
			}
			$fieldsInfo = $fieldsInfoRet;
		}
		
		foreach ($fieldsInfo as $info) {
			if ($info['is_pri']) {
				$this->pkField = $info['name'];
				$this->pkFieldIsAutoIncr = $info['is_auto_incr'];
			}
			$this->fields[$info['name']] = $info['name'];
			$this->fieldsInfo[$info['name']] = $info;
		}
		
		if (isset($configs['pk_field'])) {
			$this->pkField = $configs['pk_field'];
		}
		
		// 主键是必须存在的
		if ($this->pkField == null) {
			$GLOBALS['pad_core']->error(E_ERROR, 'entity "%s" not found primary key', $this->entityName);
		}
		
		// 判断出字段分组
		$maxGroupIdx = 0;
		$inGroupFields = array();
		if (isset($configs['fields_groups'][0])) {
			$fields = explode(',', $configs['fields_groups'][0]);
			if (! in_array($this->pkField, $fields)) {
				$fields[] = $this->pkField;
			}
			$this->fieldsGroups[] = $fields;
			unset($configs['fields_groups'][0]);
			$inGroupFields += $fields;
			$maxGroupIdx ++;
		}
		
		if ($configs['fields_groups']) {
			foreach ($configs['fields_groups'] as $line) {
				$this->fieldsGroups[] = explode(',', $line);
				$inGroupFields += $fields;
				$maxGroupIdx ++;
			}
		}
		
		// 指定外的字段按小，大字段分成两组
		if (count($inGroupFields) < count($fieldsInfo)) {
			foreach ($fieldsInfo as $info) {
				if (! in_array($info['name'], $inGroupFields)) {
					if (! $info['is_big']) {
						$this->fieldsGroups[$maxGroupIdx][] = $info['name'];
					} else {
						$this->fieldsGroups[$maxGroupIdx + 1][] = $info['name'];
					}
				}
			}
		}
		
		// 关系字段
		foreach ($configs['relations'] as $key => $cfgline) {
			$array = explode(',', str_replace(' ', '', $cfgline));
			
			$rltConfig = array();
			$rltConfig['type'] = array_shift($array);
			$rltConfig['entity_name'] = array_shift($array);
			
			if ($rltConfig['type'] == 'belongto') {
				if (! isset($array[0])) {
					$GLOBALS['pad_core']->error(E_ERROR, 'entity "%s" not set link_field', $rltConfig['entity_name']);
				}
				$rltConfig['link_field'] = $array[0];
				$tmp = explode(';', $array[0]);
				if (isset($tmp[1])) {
					$rltConfig['link_field'] = $tmp[0];
					$rltConfig['link_where'] = explode('=', $tmp[1]);
				}
			} elseif ($rltConfig['type'] == 'hasone' || $rltConfig['type'] == 'hasmany') {
				if (! isset($array[0])) {
					$GLOBALS['pad_core']->error(E_ERROR, 'entity "%s" not set belongto key', $rltConfig['entity_name']);
				}
				$rltConfig['belongto_key'] = $array[0];
			} elseif ($rltConfig['type'] == 'many2many') {
				list($rltConfig['this_link'], $rltConfig['to_link']) = explode(':', $array[0]);
			} else {
				$GLOBALS['pad_core']->error(E_ERROR, 'entity "%s" not relation type "%s" error', $rltConfig['entity_name'], $rltConfig['type']);
			}
			
			$this->relations[$key] = $rltConfig;
		}
		
		// 字段的限定条件
		$this->fieldsTypes = array();
		foreach ($this->fields as $field => $drop) {
			if (! $configs['fields_types_extend_db'] && ! isset($configs['fields_types'][$field])) {
				continue;
			}
			
			$typeString = strtolower($fieldsInfo[$field]['type']);
			preg_match('/\((\d+)\)/', $typeString, $match);
			$fieldType = 'int';
			$fieldLength = ($match ? $match[1] : 0);
			if (strpos($typeString, 'int') !== false) {
				$fieldType = 'int';
			} elseif (strpos($typeString, 'char') !== false || strpos($typeString, 'text') !== false) {
				$fieldType = 'char';
			} else {
				$fieldType = 'unkown';
			}
			
			$this->fieldsTypes[$field] = array(
				'field_type' => $fieldType,
				'field_length' => $fieldLength,
				'is_unsigned' => (strpos($typeString, 'unsigned') > 0),
				'is_unique' => $fieldsInfo[$field]['is_unique'],
				'is_allow_empty' => false,
				'in_pair' => array(),
				'regex' => array(),
				'callback' => array()
			);
			
			if (isset($configs['fields_types'][$field])) {
				$this->_fillFieldType($field, $this->fieldsTypes[$field], $configs['fields_types'][$field]);
			}
		}
		
		// 原始模型
		$entityClass = $this->entityClassName;
		$this->entityBaseMatrix = new $entityClass();
		$this->entityBaseMatrix->_entityName = $this->entityName;
		
		$this->entityBaseMatrix->_pvtVars = new stdclass();
		$this->entityBaseMatrix->_pvtVars->etyConfig = $this;
		$this->entityBaseMatrix->_pvtVars->id = 0;
		$this->entityBaseMatrix->_pvtVars->fversion = 0;
		$this->entityBaseMatrix->_pvtVars->isFlush = false;
		$this->entityBaseMatrix->_pvtVars->isNewer = false;
		$this->entityBaseMatrix->_pvtVars->isCreate = false;
		$this->entityBaseMatrix->_pvtVars->isUpdate = false;
		$this->entityBaseMatrix->_pvtVars->isDelete = false;
		$this->entityBaseMatrix->_pvtVars->formerDatas = array();
		$this->entityBaseMatrix->_pvtVars->specialDatas = array();
		$this->entityBaseMatrix->setDb();
		
		foreach ($this->fieldsGroups[0] as $field) {
			$this->entityBaseMatrix->$field = null;
		}
		
		// 数据更新时，需要更新的版本号
		if (isset($configs['cache_versions'])) {
			$this->cacheVersions = $configs['cache_versions'];
		}
	}
	
	public function getDbTableName($object){
		if (!isset($this->dbTablePartitionName)) {
			return $this->dbTableName;
		} else {
			$tableName = $this->dbTableName;
			$partitionConfig = $this->dbTablePartitionConfig;
			if ($partitionConfig['type'] == 'byFieldValue') {
				$fields = explode(',', $partitionConfig['fields']);
				foreach ($fields as $field) {
					$tableName .= '_'.$object->$field;
				}
			}
			return $tableName;
		}
	}

	// 生成配置
	private function _fillFieldType($field, &$baseConfig, $cfgString) {
		if (is_string($cfgString)) {
			$cfgString = str_replace(array(
				"\r",
				"\n"
			), '', $cfgString);
			$items = explode(',', $cfgString);
			foreach ($items as $item) {
				if ($item == 'allow_empty') {
					$baseConfig['is_allow_empty'] = true;
				} else 
					if (preg_match('/regex\((.*?)\)/', $item, $match)) {
						$baseConfig['regex'][] = $match[1];
					}
			}
		}
	}
}




