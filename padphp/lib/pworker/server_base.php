<?php

class PadLib_Pworker_ServerBase {
	public $bootFile;
	public $options;
	public $redis;
	
	public function __construct($bootFile, $options = array()){
		$this->bootFile = $bootFile;
		$this->options = array_merge(array(
			'keyPrefix' => 'pw.',
			'redis' => array(
				'host' => '127.0.0.1',
				'port' => 6379,
			),
			'webserver_server_host' => '0.0.0.0',
			'webserver_server_port' => 6833,
			'webserver_auth' => 'pworker:pworker',
			'processHandlers' => array(
				//'php' => 'php /myproject/ecfenxi/test/tmp/job.php',
				//'nodejs' => 'node /myproject/ecfenxi/test/tmp/job.js',
			),
			'maxProcess' => 2,
			'workerNames' => array(),
			'workerDefaultOptions' => array(
				'timeout' => 10000,
				'handlerName' => 'php',
			),
		), $options);
		PadLib_Pworker_Const::setKeyPrefix($this->options['keyPrefix']);
		
		// 检查redis连接问题
		$redis = $this->getRedis();
		try {
			$redis->ping();
		} catch (Exception $e) {
			echo 'redis connect error', "\n";
		}
	}
	
	public function getRedis(){
		$redis = new Redis();
		$redis->connect($this->options['redis']['host'], $this->options['redis']['port']);
		return $redis;
	}
	
	public function writeLog(){
		$argvs = func_get_args();
		$type = array_shift($argvs);
		array_unshift($argvs, ceil(microtime(true)*1000));
		array_unshift($argvs, '['.date('Y/m/d H:i:s').']');
		echo implode("\t", $argvs), "\n";
	}
}