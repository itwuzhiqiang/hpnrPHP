<?php

class PadCache {

	public $configs = array();

	public $connects = array();

	public function __construct() {}

	public function getPerform($name) {
		if (! isset($this->configs[$name])) {
			$GLOBALS['pad_core']->error(E_ERROR, 'not find cache "%s"', $name);
		}
		
		if (! isset($this->connects[$name])) {
			$configs = array_merge(array(
				'driver' => 'memcached'
			), $this->configs[$name]);
			
			if ($configs['driver'] == 'memcached') {
				$this->connects[$name] = new PadCachePerform($name, 'Memcached', $configs);
			} else {
				$GLOBALS['pad_core']->error(E_ERROR, 'not support cache type "%s"', $configs['driver']);
			}
		}
		
		return $this->connects[$name];
	}
}

