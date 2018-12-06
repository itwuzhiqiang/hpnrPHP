<?php

class PadLib_PadngxInstall {

	public function __construct($options = array()) {
		$this->options = array_merge(array(
			'runtimeEnv' => 'product',
			'update' => false,
			'fVersion' => '1.0.0',

			'dbHost' => '127.0.0.1',
			'dbPort' => 3306,
			'dbUser' => 'root',
			'dbPassword' => '',
			'dbName' => 'db_' . uniqid(),
		), $options);
	}

	public function installMysql() {
		// 需要更新的sql文件
		$this->sqlFiles = glob('/project/src/install/mysql/*.sql');

		$dsn = 'mysql:host='.$this->options['dbHost'].';port='.$this->options['dbPort'];
		$userName = $this->options['dbUser'];
		$password = $this->options['dbPassword'];
		$charset = 'utf8';
		$dbName = $this->options['dbName'];

		$this->pdo = new PDO($dsn, $userName, $password);
		$this->pdo->query('SET NAMES ' . $charset);

		// 创建数据库
		$this->pdo->exec('DROP DATABASE IF EXISTS ' . $dbName);
		echo 'DROP DATABASE: ' . $dbName . "\n";

		$this->pdo->exec('CREATE DATABASE IF NOT EXISTS ' . $dbName);
		$this->pdo->exec('USE ' . $dbName);
		echo 'CREATE DATABASE: ' . $dbName . "\n";

		// 导入建表文件
		foreach ($this->sqlFiles as $sql) {
			$fileVersion = str_replace('.sql', '', basename($sql));
			if (true || version_compare($this->version, $fileVersion, '>=')) {
				$this->pdo->exec(file_get_contents($sql));
				echo "INSTALL VERSIN: " . $fileVersion . ' > ' . $this->pdo->errorCode() . "\n";
			}
		}

		// 写入到运行配置
		$runtimeConfig = array(
			'database.default' => array(
				'driver' => 'pdo',
				'dsn' => $dsn.';dbname=' . $dbName,
				'database' => $dbName,
				'username' => $userName,
				'password' => $password,
				'use_trans' => false
			),
		);

		$jsonFile = '/data/config/runtime-' . $this->options['runtimeEnv'] . '.json';
		if (defined('PADPKG_NAME')) {
			$jsonFile = '/data/config/runtime-' . PADPKG_NAME . '-' . $this->options['runtimeEnv'] . '.json';
		}
		file_put_contents($jsonFile, json_encode($runtimeConfig));
	}

	public function execute() {
		$this->installMysql();
	}
}
