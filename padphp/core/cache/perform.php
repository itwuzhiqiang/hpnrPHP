<?php

class PadCachePerform {

	public $name;

	public $className;

	public $configs;

	public $driver;

	/**
	 * public $handle; 故意注释，以便__call
	 */
	public function __construct($name, $className, $configs) {
		$this->name = $name;
		$this->className = $className;
		$this->configs = $configs;
		$this->driver = $this->configs['driver'];
	}

	public function __get($name) {
		if ($name == 'handle') {
			$className = $this->className;
			$this->handle = new $className();
			foreach ((array) $this->configs['servers'] as $serverLine) {
				$tmp = explode(':', $serverLine);
				$this->handle->addServer($tmp[0], isset($tmp[1]) ? $tmp[1] : 11211, isset($tmp[2]) ? $tmp[2] : 100);
			}
			return $this->handle;
		}
	}
	
	public function get($key) {
		assert('$GLOBALS[\'pad_core\']->debug->write(\'cache\', \'get > \'.$key);');
		return $this->handle->get($key);
	}

	public function set($key, $value, $expire = 0) {
		assert('$GLOBALS[\'pad_core\']->debug->write(\'cache\', \'set > \'.$key);');
		return $this->handle->set($key, $value, $expire);
	}

	public function increment($key, $value = 1) {
		assert('$GLOBALS[\'pad_core\']->debug->write(\'cache\', \'increment > \'.$key);');
		return $this->handle->increment($key, $value);
	}

	public function delete($key) {
		assert('$GLOBALS[\'pad_core\']->debug->write(\'cache\', \'delete > \'.$key);');
		return $this->handle->delete($key);
	}
}


