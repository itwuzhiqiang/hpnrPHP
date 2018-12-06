<?php

class PadBaseReport {
	const SQL_TIME_WHERE = 'WHERE ###WHERE_TIME###';

	private $_params = array();
	private $_dataItemList = array();
	private $_reportOptions = array(
		// day,hour,minute30,minute10,minute5,minute
		'cycle' => 'day',

		// 值类型(row,top)
		'valueType' => 'row',

		// 持久化缓存到数据库的配置
		'save' => array(
			// 存储到数据库的名字
			'tableName' => '',
			// 最多保存的纪录数
			'maxRecord' => 660000,
		),
	);

	private $_isCanSave = false;
	private $_maxTimeId = -1;
	private $_filterFields = array();
	public $_useCacheWrite = false;

	/**
	 * 设置参数
	 * @param $key
	 * @param $value
	 */
	public function setOption($key, $value) {
		if ($key == 'save') {
			vcheck($value, 'array', 'key为save是,参数值必须是数组');
			$this->_reportOptions[$key] = array_merge($this->_reportOptions[$key], $value);
		} else {
			$this->_reportOptions[$key] = $value;
		}
	}

	/**
	 * 设置参数
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function setParam($key, $value) {
		if (!in_array($key, $this->_filterFields)) {
			throw new PadException($key . ' 不是筛选条件');
		}
		$this->_params[$key] = $value;
		return $this;
	}

	/**
	 * 获得参数
	 * @param $key
	 * @param null $default
	 * @return mixed|null
	 */
	public function getParam($key, $default = null) {
		return isset($this->_params[$key]) ? $this->_params[$key] : $default;
	}

	/**
	 * 设置过滤条件的字段
	 * @param $fields
	 * @return $this
	 */
	public function setFilterFields($fields) {
		if (is_string($fields)) {
			$fields = explode(',', $fields);
		}
		$this->_filterFields = $fields;
		return $this;
	}

	/**
	 * 添加一个item数据项目
	 * @param PadOrmLoaderReportData $data
	 */
	public function addDataItem(PadBaseReportData $data) {
		$data->report = $this;
		$this->_dataItemList[] = $data;
	}

	public function getData($timeValue = 'realtime', $timeValue2 = false) {
		$timestrapFrom = 0;
		$timestrapTo = 0;

		if (strpos($timeValue, '-') === 0) {
			$timestrapFrom = (time() - substr($timeValue, 1));
			$timestrapTo = time() + 3600;
		} else if (strpos($timeValue, '@') !== false && strpos($timeValue2, '@') !== false) {
			$timestrapFrom = substr($timeValue, 1);
			$timestrapTo = substr($timeValue2, 1);
		} else {
			if ($timeValue == 'realtime') {
				$timestrapFrom = strtotime($this->_formatValue($timeValue));
				$timestrapTo = time();
			} else if ($timeValue == 'all') {
				$timestrapFrom = 0;
				$timestrapTo = time() + 3600;
			} else {
				$timestrapFrom = strtotime($this->_formatValue($timeValue));
				if ($timeValue2) {
					$timestrapTo = strtotime($this->_formatValue($timeValue2, true));
				} else {
					$timestrapTo = strtotime($this->_formatValue($timeValue, true));
				}
			}
		}

		$dataType = 'row';
		if ($this->_reportOptions['valueType'] == 'top') {
			$dataType = 'list';
		}

		// 从存储端读取
		if ($this->getSaveTableName()) {
			$maxTimeId = $this->getSaveMaxTimeId();
			$split = $this->splitString($maxTimeId);
			$maxTimeTimestrap = strtotime(implode('-', array_slice($split, 0, 3)) . ' ' . implode(':', array_slice($split, 3, 3)));

			$wheres = array();
			foreach ($this->_params as $k => $v) {
				$wheres[] = $k . ' = "' . $v . '"';
			}
			if ($wheres) {
				$wheres = ' and ' . implode(' and ', $wheres);
			} else {
				$wheres = '';
			}

			$fromTimeId = date('YmdHis', $timestrapFrom);
			$toTimeId = date('YmdHis', $timestrapTo);
			$dataSaved = database('default')->getRow('
				select ' . $this->_getFieldSql() . ' from ' . $this->getSaveTableName() . '
				where timeId >= ? and timeId <= ? ' . $wheres . '
			', $fromTimeId, $toTimeId);

			if ($maxTimeTimestrap > $timestrapTo) {
				return $dataSaved;
			} else {
				$timestrapFrom = $maxTimeTimestrap;
			}
		}

		// 合并存储端和实时读取的数据
		$ret = array();
		if ($dataType == 'row') {
			// 从实时系统读取
			$dataFetch = array();
			foreach ($this->_dataItemList as $data) {
				$dataArray = $data->getData($timestrapFrom, $timestrapTo, $dataType);
				$dataFetch = array_merge($dataFetch, $dataArray);
			}

			foreach ($this->_dataItemList as $data) {
				foreach ($data->fields as $name => $field) {
					$vSaved = (isset($dataSaved[$name]) ? $dataSaved[$name] : 0);
					$vFetch = (isset($dataFetch[$name]) ? $dataFetch[$name] : 0);

					if ($field['dataType'] == 'sum') {
						$ret[$name] = $vSaved + $vFetch;
					} else if ($field['dataType'] == 'avg') {
						$num = 0;
						if (isset($dataSaved[$name])) {
							$num += 1;
						}
						if (isset($dataFetch[$name])) {
							$num += 1;
						}
						$ret[$name] = ($vSaved + $vFetch) / max($num, 1);
					} else if ($field['dataType'] == 'value') {
						$ret[$name] = $vFetch;
					}
				}
			};

			foreach ($this->getFieldList() as $field => $config) {
				if (isset($ret[$field])) {
					$ret[$field] = PadBaseReportData::formatValue($config['type'], $ret[$field]);
				}
			}
		} else if ($dataType == 'list') {
			foreach ($this->_dataItemList as $data) {
				$dataArray = $data->getData($timestrapFrom, $timestrapTo, $dataType);
				foreach ($dataArray as $k => $itemData) {
					if (!isset($ret[$k])) {
						$ret[$k] = array();
					}
					$ret[$k] = array_merge($ret[$k], $itemData);
				}
			}
		}
		return $ret;
	}

	public function getDataChart ($fields, $timeValue = 'realtime', $timeValue2 = false) {
		$data = $this->getData($timeValue, $timeValue2);
		if (is_string($fields)) {
			$fields = explode(',', $fields);
		}

		$reData = array();
		$series = array();
		$fieldsList = $this->getFieldList();
		foreach ($fields as $fld) {
			if (isset($fieldsList[$fld])) {
				$config = $fieldsList[$fld];
				$series[$fld] = array(
					'name' => $config['name'],
				);
				$reData[$fld] = $data[$fld];
			}
		}

		return array(
			'series' => $series,
			'data' => $reData,
		);
	}

	public function getDataByDataQL($timeValue = 'realtime', $timeValue2 = false, $params = array()) {
		foreach ($params as $key => $val) {
			$this->setParam($key, $val);
		}
		return $this->getData($timeValue, $timeValue2);
	}

	private function _getFieldSql() {
		$fieldsSql = array();

		foreach ($this->_params as $k => $v) {
			$fieldsSql[] = '"' . $v . '" AS ' . $k;
		}

		foreach ($this->_dataItemList as $data) {
			foreach ($data->fields as $name => $field) {
				$valueName = $name;
				if ($field['dataType'] == 'sum') {
					$valueName = 'SUM(' . $name . ')';
				} else if ($field['dataType'] == 'avg') {
					$valueName = 'AVG(' . $name . ')';
				}
				$fieldsSql[] = $valueName . ' AS ' . $name;
			}
		}
		return implode(',', $fieldsSql);
	}

	private function _formatValue($value, $isTo = false) {
		$fromTime = ($value == 'realtime' ? 0 : $value);
		list($type, $typeValue) = $this->getCycleValue();

		if ($fromTime <= 0) {
			if ($type == 'day') {
				$fromTime = date('Ymd');
			} else if ($type == 'hour') {
				$fromTime = date('Ymd') . (date('H') - date('H') % $typeValue);
			} else if ($type == 'minute') {
				$fromTime = date('YmdH') . (date('i') - date('i') % $typeValue);
			}
		}

		$split = $this->splitString($fromTime);
		if ($type == 'day') {
			vcheck($split, 'array(3)', '格式不对,参数必须是YYYYmmdd格式');
			$return = $split[0] . '-' . $split[1] . '-' . $split[2];
			if ($isTo) {
				return $return . ' 23:59:59';
			} else {
				return $return . ' 00:00:00';
			}
		} else if ($type == 'hour') {
			vcheck($split, 'array(4)', '格式不对,参数必须是YYYYmmddHH格式');
			$return = $split[0] . '-' . $split[1] . '-' . $split[2];
			if ($split[3] % $typeValue != 0) {
				throw new PadException('小时间隔不支持');
			}
			if ($isTo) {
				$split[3] += $typeValue;
			}
			if ($split[3] >= 24) {
				return $return . ' 23:59:59';
			} else {
				return $return . ' ' . $split[3] . ':00:00';
			}
		} else if ($type == 'minute') {
			vcheck($split, 'array(5)', '格式不对,参数必须是YYYYmmddHHii格式');
			$return = $split[0] . '-' . $split[1] . '-' . $split[2] . ' ' . $split[3];
			if ($split[4] % $typeValue != 0) {
				throw new PadException('分钟间隔不支持');
			}
			if ($isTo) {
				$split[4] += $typeValue;
			}
			if ($split[4] >= 60) {
				return $return . '59:59';
			} else {
				return $return . ':' . $split[4] . ':00';
			}
		}
	}

	private function getCycleValue() {
		$type = 'day';
		$typeValue = 1;
		if (strpos($this->_reportOptions['cycle'], 'minute') !== false) {
			$type = 'minute';
			$typeValue = str_replace('minute', '', $this->_reportOptions['cycle']);
		} else if (strpos($this->_reportOptions['cycle'], 'hour') !== false) {
			$type = 'hour';
			$typeValue = str_replace('hour', '', $this->_reportOptions['cycle']);
		} else if (strpos($this->_reportOptions['cycle'], 'day') !== false) {
		}

		if ($typeValue == '') {
			$typeValue = 1;
		} else {
			$typeValue = intval($typeValue);
		}
		return array($type, $typeValue);
	}

	private function splitString($value) {
		$ret = array();
		$isFirst = true;
		$maxLen = strlen($value);
		$pos = 0;
		while ($pos < $maxLen) {
			if ($isFirst) {
				$ret[] = substr($value, $pos, 4);
				$pos += 4;
			} else {
				$ret[] = substr($value, $pos, 2);
				$pos += 2;
			}
			$isFirst = false;
		}
		return $ret;
	}

	/**
	 * 获取区间数据统计结果
	 * @param $from
	 * @param $to
	 * @return array
	 */
	public function getRangeListData($from, $to = null) {
		if ($this->_reportOptions['valueType'] != 'row') {
			throw new PadException('数据类型为row时,才能获取区间列表数据');
		}

		if (strpos($from, '-') === 0) {
			$from = '@' . (time() - substr($from, 1));
			$to = '@' . time();
		}

		if (strpos($from, '@') !== false && strpos($to, '@') !== false) {
			$fromTime = substr($from, 1);
			$toTime = substr($to, 1);

			list($type, $typeValue) = $this->getCycleValue();
			if ($type == 'day') {
				$fromTime = date('Ymd', $fromTime);
			} else if ($type == 'hour') {
				$fromTime = date('Ymd', $fromTime) . (date('H', $fromTime) - date('H') % $typeValue);
			} else if ($type == 'minute') {
				$fromTime = date('YmdH', $fromTime) . (date('i', $fromTime) - date('i') % $typeValue);
			}
			$fromTime = strtotime($this->_formatValue($fromTime));
		} else {
			$fromTime = strtotime($this->_formatValue($from));
			$toTime = strtotime($this->_formatValue($to, true));
		}

		list($cycle, $cycleValue) = $this->getCycleValue();
		$cycleLoopTime = 0;
		if ($cycle == 'day') {
			$cycleLoopTime = 24 * 3600;
		} else if ($cycle == 'hour') {
			$cycleLoopTime = 3600 * $cycleValue;
		} else if ($cycle == 'minute') {
			$cycleLoopTime = 60 * $cycleValue;
		}

		// 从save端获取数据
		$saveDataList = array();
		if ($this->getSaveTableName()) {
			$wheres = array();
			foreach ($this->_params as $k => $v) {
				$wheres[] = $k . ' = "' . $v . '"';
			}
			if ($wheres) {
				$wheres = ' and ' . implode(' and ', $wheres);
			} else {
				$wheres = '';
			}

			// @todo toTime有问题
			$saveDataList = database('default')->getAll('
				select timeId, ' . $this->_getFieldSql() . ' from ' . $this->getSaveTableName() . '
				where timeId >= ? and timeId <= ? ' . $wheres . '
				group by timeId
			', date('YmdHis', $fromTime), date('YmdHis', $toTime));
			$saveDataList = PadBaseArray::arrayKeyAsIndex($saveDataList, 'timeId');
		}

		// 获取数据列表
		$rangeList = array();
		for ($time = $fromTime; $time < $toTime; $time += $cycleLoopTime) {
			$data = null;
			$timeId = date('YmdHis', $time);
			if ($this->getSaveMaxTimeId() >= $timeId) {
				if (!isset($saveDataList[$timeId])) {
					continue;
				}
				$data = $saveDataList[$timeId];
				unset($data['id']);
				unset($data['timeId']);
			} else {
				$data = $this->getData('@' . $time, '@' . ($time + $cycleLoopTime));
			}
			$data['_timeStr'] = date('Y-m-d H:i:s', $time);

			list($cycleType) = $this->getCycleValue();
			if ($cycleType == 'day') {
				$data['_timeDisplay'] = date('m-d', $time);
			} else if ($cycleType == 'hour') {
				$data['_timeDisplay'] = date('d H', $time);
			} else if ($cycleType == 'minute') {
				$data['_timeDisplay'] = date('H:i', $time);
			}

			foreach ($this->getFieldList() as $field => $config) {
				if (isset($data[$field])) {
					$data[$field] = PadBaseReportData::formatValue($config['type'], $data[$field]);
				}
			}
			$rangeList[] = $data;
		}
		return $rangeList;
	}

	public function getRangeListDataByDataQL($from, $to = null, $params = array()) {
		foreach ($params as $key => $val) {
			$this->setParam($key, $val);
		}
		return $this->getRangeListData($from, $to);
	}

	public function getRangeListDataChart ($fields, $from, $to = null) {
		$dataList = $this->getRangeListData($from, $to);
		if (is_string($fields)) {
			$fields = explode(',', $fields);
		}

		$series = array();
		foreach ($fields as $field) {
			$title = $field;
			foreach ($this->_dataItemList as $dItem) {
				foreach ($dItem->fields as $fld => $config) {
					if ($fld == $field) {
						$title = $config['name'];
					}
				}
			}

			$series[$field] = array(
				'name' => $title,
			);
		}

		$data = array();
		foreach ($dataList as $dataItem) {
			$data[$dataItem['_timeDisplay']] = array();
			foreach ($fields as $field) {
				$data[$dataItem['_timeDisplay']][$field] = $dataItem[$field];
			}
		}

		return array(
			'series' => $series,
			'data' => $data,
		);
	}

	/**
	 * 当前报表的事件范围
	 * @param $fromTimestamp
	 * @param $toTimestamp
	 */
	public function setTimeRange($fromTimestamp, $toTimestamp) {
		$this->fromTimestamp = $fromTimestamp;
		$this->toTimestamp = $toTimestamp;
	}

	/**
	 * @param $field
	 * @return string
	 */
	public function getTimeRangeSql($field) {
		return $field . ' > ' . $this->fromTimestamp . ' and ' . $field . ' < ' . $this->toTimestamp;
	}

	public function getSaveTableName() {
		if (!$this->_reportOptions['save']['tableName']) {
			return null;
		} else {
			return 'report_' . $this->_reportOptions['save']['tableName'];
		}
	}

	/**
	 * 获得创表的SQL
	 * @return array
	 */
	public function getSaveScheme() {
		$ret = array();
		$ret[] = 'CREATE TABLE ' . $this->getSaveTableName() . ' (';
		$ret[] = 'id INT NOT NULL AUTO_INCREMENT,';
		$ret[] = 'timeId CHAR(16) NOT NULL DEFAULT "0",';

		foreach ($this->_filterFields as $field) {
			$ret[] = $field . ' CHAR(32) NOT NULL DEFAULT "",';
		}

		foreach ($this->_dataItemList as $data) {
			foreach ($data->getScheme() as $fld => $line) {
				$ret[] = $fld . ' ' . $line . ',';
			}
		}
		$ret[] = 'PRIMARY KEY (id)';
		$ret[] = ');';
		return implode("\n", $ret) . "\n";
	}

	/**
	 * 最大的存储号
	 * @return int
	 */
	public function getSaveMaxTimeId() {
		if ($this->_maxTimeId < 0 && $this->getSaveTableName()) {
			$timeId = database('default')->getOne('select max(timeId) from ' . $this->getSaveTableName());
			$this->_maxTimeId = $timeId ? $timeId : 0;
		}
		return $this->_maxTimeId;
	}

	/**
	 * 保存数据到数据库
	 */
	public function saveData() {
		if (!$this->_isCanSave) {
			return false;
		}

		foreach ($this->_filterFields as $field) {
			vcheck($this->_params, $field . ' => !null', '过滤条件' . $field . '没有设置');
		}

		$time = $this->_formatValue('realtime');
		$timestrap = strtotime($time);
		$timeId = date('YmdHis', $timestrap);

		$data = array();
		foreach ($this->_filterFields as $field) {
			$data[$field] = $this->getParam($field);
		}
		foreach ($this->_dataItemList as $dataItem) {
			$dataArray = $dataItem->getData($time, time(), 'row');
			$data = array_merge($data, $dataArray);
		}

		$tableName = $this->getSaveTableName();
		$data['timeId'] = $timeId;
		$has = database('default')->getOne('select count(*) from ' . $tableName . ' where timeId = ?', $timeId);
		if ($has <= 0) {
			database('default')->insert($tableName, $data, true);
		}
		return true;
	}

	/**
	 * 存储会1分钟运行一次
	 * 是否是可以保存到数据库的时机
	 */
	public function checkTimeForSave() {
		$retBool = false;
		list($type, $typeValue) = $this->getCycleValue();
		if ($type == 'day') {
			$retBool = (date('Hi') == '0000');
		} else if ($type == 'hour') {
			$retBool = (date('H') % $typeValue == 0 && date('i') == '00');
		} else if ($type == 'minute') {
			$retBool = (date('i') % $typeValue == 0);
		}
		$this->_isCanSave = $retBool;
		return $retBool;
	}

	public function getFieldList () {
		$fields = array();
		foreach ($this->_dataItemList as $dItem) {
			foreach ($dItem->fields as $fld => $config) {
				$fields[$fld] = $config;
			}
		}
		return $fields;
	}

	public function cacheForceWrite () {
		$this->_useCacheWrite = true;
		return $this;
	}
}

class PadBaseReportData {
	/**
	 * @var PadBaseReport
	 */
	public $report;
	public $_fields = array();

	private $loader;
	private $fieldNamePrefix = '';
	private $timestampField = 'create_time';
	private $onGetData;

	private $_useCache = false;
	private $_useCacheWrite = false;
	private $_useCacheKey = false;
	private $_useCacheExpire = 0;
	private $_useCacheKeyId;

	public function __construct() {
	}

	public function useCache ($key, $expire = 3600, $flag = 1) {
		$this->_useCache = true;
		$this->_useCacheKey = $key;
		$this->_useCacheExpire = $expire;
		$this->_useCacheKeyId = $this->_useCacheKey;
		$this->_useCacheWrite = $this->report->_useCacheWrite;

		return $this;
	}

	public function __get($name) {
		if ($name == 'fields') {
			$fieldsList = array();
			if (is_callable($this->_fields)) {
				$fieldsList = call_user_func_array($this->_fields, array($this->report));
			} else {
				$fieldsList = $this->_fields;
			}

			foreach ($fieldsList as $i => $config) {
				$fieldsList[$i] = array_merge(array(
					'name' => $i,
					'type' => 'int',
					'dataType' => 'sum',
				), $config);
			}

			$this->fields = $fieldsList;
			return $fieldsList;
		}
	}

	public function setLoader($loader) {
		$this->loader = $loader;
		return $this;
	}

	public function setTimestampField($field) {
		$this->timestampField = $field;
		return $this;
	}

	public function setFieldNamePrefix($str) {
		$this->fieldNamePrefix = $str;
		return $this;
	}

	public function setFields($fields) {
		$this->_fields = $fields;
		return $this;
	}

	public function getScheme() {
		$ret = array();
		foreach ($this->fields as $fld => $config) {
			if ($config['type'] == 'int') {
				$ret[$this->fieldNamePrefix . $fld] = 'INT NOT NULL DEFAULT 0';
			} else if ($config['type'] == 'string') {
				$ret[$this->fieldNamePrefix . $fld] = 'VARCHAR(255) NOT NULL DEFAULT ""';
			} else if (strpos($config['type'], 'float') !== false) {
				$ret[$this->fieldNamePrefix . $fld] = 'FLOAT NOT NULL DEFAULT 0';
			}
		}
		return $ret;
	}

	public function getParam($key, $default = null) {
		return $this->report->getParam($key, $default);
	}

	public function getRedis () {
		static $redis;
		if (!isset($redis)) {
			$redisConfig = config('redis.report.cache');
			$redis = new Redis();
			$redis->connect($redisConfig['host'], $redisConfig['port']);

			if (isset($redisConfig['auth'])) {
				$redis->auth($redisConfig['auth']);
			}
		}
		return $redis;
	}

	public function setOnData ($cbk) {
		$this->onGetData = $cbk;
		return $this;
	}

	public function getData($fromTimestamp, $toTimestamp, $dataType = 'row') {
		$this->report->fromTimestamp = $fromTimestamp;
		$this->report->toTimestamp = $toTimestamp;

		if ($this->onGetData) {
			call_user_func_array($this->onGetData, array($this->report));
		}

		if ($this->_useCache && !$this->_useCacheWrite) {
			$data = $this->getRedis()->get($this->_useCacheKeyId);
			if ($data !== false) {
				return json_decode($data, true);
			}
		}

		if ($this->_useCache && !$this->_useCacheWrite) {
			return array();
		}

		$fieldsList = array();
		if (is_callable($this->fields)) {
			$fieldsList = call_user_func_array($this->fields, array($this->report));
		} else {
			$fieldsList = $this->fields;
		}

		$valueList = array();
		if ($this->loader) {
			$loader = null;
			if (is_callable($this->loader)) {
				$this->report->setTimeRange($fromTimestamp, $toTimestamp);
				$loader = call_user_func_array($this->loader, array($this->report));
			} else if ($this->loader) {
				$loader = $this->loader;
			}

			$fieldsArray = array();
			foreach ($this->fields as $fld => $config) {
				$fieldsArray[] = $config['value'] . ' AS ' . $this->fieldNamePrefix . $fld;
			}
			$loader->_fields(implode(',', $fieldsArray));

			if ($dataType == 'row') {
				$valueList = (array)$loader->getData();
			} else if ($dataType == 'list') {
				$valueList = $loader->getDataList();
				foreach ($valueList as $idx => $i) {
					$valueList[$idx] = (array)$i;
				}
			}
		} else {
			$ret = array();
			foreach ($this->fields as $fld => $config) {
				if (is_callable($config['value'])) {
					$ret[$this->fieldNamePrefix . $fld] = call_user_func_array($config['value'], array($this->report, $fromTimestamp, $toTimestamp));
				} else {
					$ret[$this->fieldNamePrefix . $fld] = $config['value'];
				}
			}
			$valueList = (array)$ret;
		}

		foreach ($fieldsList as $fld => $config) {
			if ($dataType == 'row') {
				$this->_formatValue($config['type'], $valueList, $this->fieldNamePrefix . $fld);
			} else if ($dataType == 'list') {
				foreach ($valueList as $idx => $row) {
					$this->_formatValue($config['type'], $valueList[$idx], $this->fieldNamePrefix . $fld);
				}
			}
		}

		$returnRet = null;
		if ($dataType == 'list') {
			$ret = array();
			foreach ($valueList as $item) {
				$ret[$item['itemId']] = $item;
			}
			$returnRet = $ret;
		} else {
			$returnRet = $valueList;
		}

		if ($this->_useCache) {
			$this->getRedis()->set($this->_useCacheKeyId, json_encode($returnRet));
			if ($this->_useCacheExpire > 0) {
				$this->getRedis()->expire($this->_useCacheKeyId, $this->_useCacheExpire);
			}
		}

		return $returnRet;
	}

	private function _formatValue($fldType, &$row, $key) {
		$row[$key] = self::formatValue($fldType, $row[$key]);
	}

	static public function formatValue ($fldType, $value) {
		if ($fldType == 'int') {
			return intval($value);
		} else if ($fldType == 'string') {
			return strval($value);
		} else if (strpos($fldType, 'float') !== false) {
			$value = floatval($value);
			if (preg_match('/^float\((\d+),(\d+)\)$/', $fldType, $match)) {
				$xsLen = intval($match[2]);
				$value = ceil(floatval($value) * (pow(10, $xsLen))) / (pow(10, $xsLen));
			}
			return $value;
		} else if ($fldType == 'value') {
			return $value;
		}
	}
}

trait PadBaseReportMixAbstract {
	public $report;

	public function construct() {
		$this->report = new PadBaseReport();
	}

	public function __call($name, $arguments) {
		return call_user_func_array(array($this->report, $name), $arguments);
	}
}

class PadBaseReportAbstract {
	use PadBaseReportMixAbstract;

	public function __construct() {
		$this->construct();
	}
}






