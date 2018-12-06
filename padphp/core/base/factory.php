<?php

class PadBaseFactory {
	static public $instances = array();

	static public function register ($name, $callback) {
		if (!isset(self::$instances[$name])) {
			self::$instances[$name] = call_user_func_array($callback, array());

		}
		return self::$instances[$name];
	}
}
