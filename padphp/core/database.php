<?php

class PadDatabase {
	public $configs = array();
	public $connects = array();

	public function __construct() {
	}

	public function getPerform($name) {
		if (!isset($this->configs[$name])) {
			$GLOBALS['pad_core']->error(E_ERROR, 'not find database "%s"', $name);
		}

		if (!isset($this->connects[$name])) {
			$configs = array_merge(array(
				'driver' => 'pdo',
				'dsn' => null,
				'username' => null,
				'password' => null,
				'charset' => 'utf8',
				'use_trans' => false
			), $this->configs[$name]);

			// 事务是始终开启的
			// $configs['use_trans'] = true;

			if ($configs['driver'] == 'pdo') {
				$this->connects[$name] = new PadDatabasePerform($name, 'PDO', $configs);
			} else if ($configs['driver'] == 'customize') {
				$className = $configs['class_name'];
				$this->connects[$name] = new $className($name, 'CUSTOMIZE', $configs);
			} else {
				$GLOBALS['pad_core']->error(E_ERROR, 'not support database type "%s"', $configs['driver']);
			}
		}

		return $this->connects[$name];
	}
}



