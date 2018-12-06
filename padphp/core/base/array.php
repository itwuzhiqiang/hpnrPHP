<?php

class PadBaseArray {

	public static $sortKey;

	public static $sortType;

	/**
	 * 二维数组到关联值数组
	 */
	static public function array2pair($array, $key1, $key2) {
		$return = array();
		$key2List = explode('@', $key2);
		foreach ($array as $item) {
			$item = (array) $item;
			$v = array();
			foreach ($key2List as $k) {
				$v[] = $item[$k];
			}
			$return[$item[$key1]] = implode('-', $v);
		}
		return $return;
	}

	/**
	 * 二维数组中的某项值作为KEY
	 */
	static public function arrayKeyAsIndex($array, $key) {
		$return = array();
		foreach ($array as $item) {
			$keyVal = (is_array($item) ? $item[$key] : $item->$key);
			$return[$keyVal] = $item;
		}
		return $return;
	}

	/**
	 * 二维数组中的某项值排序
	 */
	static public function arraySortByValue(&$array, $key, $type = 'number_desc') {
		self::$sortKey = $key;
		self::$sortType = (strpos($type, '_') === false ? 'number_' . $type : $type);
		uasort($array, array(
			'PadBaseArray',
			'_CMPFunctionT1'
		));
	}

	/**
	 * list2array
	 */
	static public function list2array($list, $keys) {
		$keys = explode(',', $keys);
		$keysLen = count($keys);
		$listLen = count($list);
		
		$return = array();
		for ($i = 0; $i < $listLen / $keysLen; $i ++) {
			$tmp = array();
			foreach ($keys as $idx => $k) {
				$tmp[$k] = isset($list[$i * $keysLen + $idx]) ? $list[$i * $keysLen + $idx] : false;
			}
			$return[] = $tmp;
		}
		return $return;
	}

	static public function arrayKeyToCol($array, $key) {
		$return = array();
		foreach ($array as $k => $item) {
			$return[] = $item->$key;
		}
		return $return;
	}

	/**
	 * 根据列表创建分页
	 */
	static public function buildPage($datalist, $page, $pageSize) {
		$pageInfo = array(
			'total' => count($datalist),
			'page' => $page,
			'page_size' => $pageSize
		);
		return array(
			array_slice($datalist, ($page - 1) * $pageSize, $pageSize),
			$pageInfo
		);
	}

	/**
	 * 根据2维列表，创建Total，KEY不变
	 */
	static public function listGetTotal($datalist, $keys4total) {
		$total = array();
		
		$totalNotKeys = array();
		$totalKeys = array();
		if (substr($keys4total, 0, 1) == '@') {
			$totalNotKeys = explode(',', substr($keys4total, 0, 1));
		} else {
			$totalKeys = explode(',', $keys4total);
		}
		
		foreach ($datalist as $item) {
			$item = (array) $item;
			foreach ($item as $k => $v) {
				if (in_array($k, $totalKeys) || ! in_array($k, $totalNotKeys)) {
					if (! isset($total[$k])) {
						$total[$k] = 0;
					}
					$total[$k] += $v;
				}
			}
		}
		
		return $total;
	}
	
	static public function removeValue ($array, $value) {
		if (($key = array_search($value, $array)) !== false) {
    		unset($array[$key]);
		}
		return $array;
	}

	/**
	 * --------------------------------
	 */
	static public function _CMPFunctionT1($item1, $item2) {
		$sortKey = self::$sortKey;
		$sortType = self::$sortType;
		
		$val1 = is_object($item1) ? $item1->$sortKey : $item1[$sortKey];
		$val2 = is_object($item2) ? $item2->$sortKey : $item2[$sortKey];
		
		if ($sortType == 'number_desc') {
			if ($val1 == $val2) {
				return 0;
			}
			return ($val1 < $val2) ? 1 : - 1;
		} elseif ($sortType == 'number_asc') {
			if ($val1 == $val2) {
				return 0;
			}
			return ($val1 < $val2) ? - 1 : 1;
		} elseif ($sortType == 'string_asc') {
			return strcmp($val1, $val2);
		} elseif ($sortType == 'string_desc') {
			return 0 - strcmp($val1, $val2);
		} else {
			return 0;
		}
	}
}
