<?php

class PadBaseRedis {

	static public function getPerform($key) {
		static $performList;
		if (! isset($performList)) {
			$performList = array();
		}
		
		if (! isset($performList[$key])) {
			$config = $GLOBALS['pad_core']->getConfig($key);
			$dsn = $config['dsn'];
			list ($host, $port) = explode(':', $dsn);
			$performList[$key] = new Redis();
			$performList[$key]->connect($host, $port);
			if (isset($config['options'])) {
				foreach ($config['options'] as $k => $v) {
					$performList[$key]->setOption($k, $v);
				}
			}
		}
		return $performList[$key];
	}
}
