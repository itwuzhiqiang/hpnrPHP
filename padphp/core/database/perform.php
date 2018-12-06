<?php

class PadDatabasePerform {

	/**
	 * mysql的小字段前缀
	 */
	public static $mysqlSmallFieldPreFix = array(
		'int',
		'tinyint',
		'smallint',
		'mediumint',
		'bigint',
		'char',
		'varchar',
		'float',
		'double',
		'decimal'
	);

	public $name;

	public $className;

	public $configs;

	public $driver;

	public $isConnect = false;

	public $isWrited = false;

	/**
	 * 下面两个属性故意注释，以便__get调用connect
	 */
	/**
	 * public $handle;
	 */
	/**
	 * public $fieldMask = '';
	 */
	public function __construct($name, $className, $configs) {
		$this->name = $name;
		$this->className = $className;
		$this->configs = $configs;
		$this->driver = $configs['driver'];
	}

	public function __get($name) {
		$this->connect();
		return $this->$name;
	}

	public function connect() {
		if (! $this->isConnect) {
			$className = $this->className;
			$this->handle = new $className($this->configs['dsn'], $this->configs['username'], $this->configs['password']);
			$this->isConnect = true;
			
			if ($this->handle->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
				$this->fieldMask = isset($this->configs['field_mask']) ? $this->configs['field_mask'] : '`';
				$this->handle->query('SET NAMES ' . $this->configs['charset']);
			}
		}
	}

	/**
	 * 进入写模式
	 */
	public function inWriteMode() {
		if ($this->configs['use_trans'] && ! $this->isWrited) {
			$this->handle->beginTransaction();
			$this->isWrited = true;
		}
	}

	public function commit() {
		// 使用了事务，并且是连接和写状态
		if ($this->configs['use_trans'] && $this->isConnect && $this->isWrited) {
			$this->handle->commit();
			$this->isWrited = false;
		}
	}

	public function rollback() {
		// 使用了事务，并且是连接和写状态
		if ($this->configs['use_trans'] && $this->isConnect && $this->isWrited) {
			$this->handle->rollback();
		}
	}

	public function prepare($string) {
		$return = $this->handle->prepare($string);
		return $return;
	}

	public function bindValues($stmt, $values) {
		foreach ($values as $index => $val) {
			if ($val === null) {
				$stmt->bindValue($index + 1, null, PDO::PARAM_NULL);
			} elseif (is_integer($val)) {
				$stmt->bindValue($index + 1, $val, PDO::PARAM_INT);
			} elseif (is_resource($val)) {
				$stmt->bindValue($index + 1, $val, PDO::PARAM_LOB);
			} else {
				$stmt->bindValue($index + 1, $val, PDO::PARAM_LOB);
			}
		}
	}

	public function getAll() {
		$argvs = func_get_args();
		$sql = array_shift($argvs);
		$this->getQuickStatement($sth, $sql, $argvs);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getCol() {
		$argvs = func_get_args();
		$sql = array_shift($argvs);
		$this->getQuickStatement($sth, $sql, $argvs);
		return $sth->fetchAll(PDO::FETCH_COLUMN);
	}

	public function getRow() {
		$argvs = func_get_args();
		$sql = array_shift($argvs);
		$this->getQuickStatement($sth, $sql, $argvs);
		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	public function getOne() {
		$argvs = func_get_args();
		$sql = array_shift($argvs);
		$this->getQuickStatement($sth, $sql, $argvs);
		return $sth->fetchColumn();
	}

	public function insert($tableName, $datas, $replace = false) {
		if (! $this->isConnect) {
			$this->connect();
		}
		
		$fieldsSql = $this->fieldMask . implode($this->fieldMask . ',' . $this->fieldMask, array_keys($datas)) . $this->fieldMask;
		$valuesSql = implode(',', array_fill(0, count($datas), '?'));
		$sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $tableName . '(' . $fieldsSql . ') VALUES (' . $valuesSql . ')';
		return $this->getQuickStatement($sth, $sql, array_values($datas), true);
	}

	public function getInsertId() {
		return $this->handle->lastInsertId();
	}

	public function update($tableName, $datas, $where) {
		if (! $this->isConnect) {
			$this->connect();
		}
		
		$fieldsSql = array();
		$binds = array();
		foreach ($datas as $field => $val) {
			if (! is_array($val)) {
				$fieldsSql[] = $this->fieldMask . $field . $this->fieldMask . ' = ?';
				$binds[] = $val;
			} elseif ($val[0] == '@incr') {
				$fieldsSql[] = $this->fieldMask . $field . $this->fieldMask . ' = ' . $this->fieldMask . $field . $this->fieldMask . ' + ?';
				$binds[] = $val[1];
			}
		}
		$fieldsSql = implode(',', $fieldsSql);
		$sql = 'UPDATE ' . $tableName . ' SET ' . $fieldsSql . ' WHERE ' . $where;
		return $this->Getquickstatement($sth, $sql, $binds, true);
	}

	public function delete($tableName, $where) {
		$sql = 'DELETE FROM ' . $tableName . ' WHERE ' . $where;
		return $this->getQuickStatement($sth, $sql, array(), true);
	}

	public function getFieldsInfo($tableName) {
		$return = array();
		if ($this->handle->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
			$row = array();
			$string = 'SHOW COLUMNS FROM ' . $tableName;

			assert('$startTime = microtime(true)*1000;');
			$stmt = $this->prepare($string);
			$stmt->execute();
			assert('$GLOBALS[\'pad_core\']->debug->write(\'db\', $string, microtime(true)*1000 - $startTime);');
			
			foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $val) {
				$row['name'] = $val['Field'];
				$row['type'] = $val['Type'];
				$row['is_pri'] = ($val['Key'] == 'PRI');
				$row['is_auto_incr'] = ($val['Extra'] == 'auto_increment');
				$row['is_big'] = true;
				$row['is_unique'] = ($val['Key'] == 'PRI' || $val['Key'] == 'UNI');
				foreach (self::$mysqlSmallFieldPreFix as $type) {
					if (strpos($val['Type'], $type) === 0) {
						$row['is_big'] = false;
						break;
					}
				}
				$return[$row['name']] = $row;
			}
		} else {
			$GLOBALS['pad_core']->error(E_ERROR, 'getFieldsInfo only support mysql');
		}
		return $return;
	}

	public function execute($sql, $binds = array(), $isWrite = true) {
		return $this->Getquickstatement($sth, $sql, $binds, $isWrite);
	}
	
	/**
	 * 用数据获取连接的信息
	 */
	public function getConnectInfo(){
		$return = array(
			'username' => $this->configs['username'],
			'password' => $this->configs['password'],
			'charset' => $this->configs['charset'],
		);
		
		$temp = explode(':', $this->configs['dsn']);
		$return['driver'] = $temp[0];
		$temp = explode(';', $temp[1]);
		foreach ($temp as $item) {
			if ($item) {
				list($key, $val) = explode('=', $item);
				$return[$key] = $val;
			}
		}
		return $return;
	}

	/**
	 * 为了debug能够输出完成的SQL语句
	 */
	public function debugCreateSql($sql, $values = array()) {
		$sqlPCount = substr_count($sql, '?');
		$PCount = count($values);
		
		$replaceArray = array();
		$posi = 0;
		$spstring = 'SDSKD!@#$%DDD' . time();
		while (($pos = strpos($sql, '?')) !== false) {
			$sql = substr($sql, 0, $pos) . $posi . $spstring . substr($sql, $pos + 1);
			$replaceArray[$posi . $spstring] = isset($values[$posi]) ? $values[$posi] : '';
			$posi ++;
		}
		
		foreach ($replaceArray as $key => $string) {
			if ($string === null) {
				$string = '';
			} elseif (is_integer($string)) {
				$string = $string;
			} elseif (is_resource($string)) {
				$string = '?';
			} else {
				$string = '"' . mb_substr($string, 0, 30, 'utf8') . '"';
			}
			$sql = str_replace($key, $string, $sql);
		}
		return $sql;
	}

	/**
	 * --------------------------------------------------------
	 */
	private function getQuickStatement(&$sth, $sql, $values = array(), $isWrite = false) {
		if ($isWrite) {
			$this->inWriteMode();
		}
		
		assert('$startTime = microtime(true)*10000;');
		$sth = $this->prepare($sql);
		$this->bindValues($sth, $values);
		$res = $sth->execute();
		assert('$GLOBALS[\'pad_core\']->debug->write(\'db\', $this->debugCreateSql($sql, $values), microtime(true)*10000 - $startTime);');
		
		// 需要尝试重连的错误code
		$retryCodes = array(2006);
		if (! $res) {
			$errinfo = $sth->errorInfo();
			$GLOBALS['pad_core']->error(E_ERROR, 'SqlError, code "%s", message "%s"', $errinfo[1], $errinfo[2]);
			if (in_array($errinfo[1], $retryCodes)) {
				$this->isConnect = false;
				$this->connect();
			}
		}
		
		return $res;
	}
}










