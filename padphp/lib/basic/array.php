<?php

class PadLib_Basic_Array {
	
	static public function item2object($array){
		$return = array();
		foreach ($array as $item) {
			$return[] = (object) $item;
		}
		return $return;
	}
	
	static public function getItem($array, $key, $default = null){
		return isset($array[$key]) ? $array[$key] : $default;
	}
}

