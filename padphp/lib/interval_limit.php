<?php

class PadLib_IntervalLimit {
	const RedisSaveHashKey = 'PadLib::IntervalLimitData';
	
	private $_redis;
	private $_options;
	
	public function __construct($options = array()){
		$this->_options = array_merge(array(
			'redisHost' => '0.0.0.0',
			'redisPort' => '6379',
			'messageInterval' => 3,
			'messageTemplate' => '等待时间: #time#',
		), $options);
	}
	
	public function getRedis(){
		if ($this->_redis === null) {
			$this->_redis = new Redis();
			$this->_redis->connect($this->_options['redisHost'], $this->_options['redisPort']);
		}
		return $this->_redis;
	}
	
	/**
	 * 处理分钟限制
	 * @param string $key KEY
	 * @param number $minuteNum 分钟限制
	 */
	public function wait($key, $minuteNum){
		$cacheId = $key.':lastPreMiTime';
		$miInterval = 60*1000 / $minuteNum;
		$lastMiTime = $this->getRedis()->hGet(self::RedisSaveHashKey, $cacheId);
		if ($lastMiTime === false) {
			$lastMiTime = microtime(true) * 1000;
		}
		
		$reminMiTime = $miInterval - (microtime(true) * 1000 - $lastMiTime);
		if ($reminMiTime > 0) {
			usleep(ceil($reminMiTime) * 1000);
		}
		$this->getRedis()->hSet(self::RedisSaveHashKey, $cacheId, microtime(true) * 1000);
		
		return array(
			'status' => 0,
			'res' => array(
				'sleepMiTime' => ($reminMiTime > 0 ? ceil($reminMiTime) : 0),
			),
		);
	}
	
	/**
	 * 按秒限制
	 * @param unknown $key
	 * @param unknown $minuteNum
	 */
	public function swait($key, $minuteNum){
		$cacheId = $key.':lastPreMiSedTime';
		$lastTime = $this->getRedis()->hGet(self::RedisSaveHashKey, $cacheId);
		$lastTime = intval($lastTime);
		$nowTime = ceil(microtime(true) * 1000);
		$reminTime = $lastTime + $minuteNum * 1000 - $nowTime;
		if ($reminTime > 0) {
			while ($reminTime > 0) {
				echo str_replace('#time#', $reminTime.' ms', $this->_options['messageTemplate']), "\n";
				$usleep = ($reminTime > $this->_options['messageInterval']*1000 ? $this->_options['messageInterval']*1000 : $reminTime);
				usleep($usleep * 1000);
				$reminTime -= $this->_options['messageInterval']*1000;
			}
		}
		$this->getRedis()->hSet(self::RedisSaveHashKey, $cacheId, $nowTime);
		
		return array(
			'status' => 0,
			'res' => array(
				'sleepMiTime' => ($reminTime > 0 ? ceil($reminTime) : 0),
			),
		);
	}
}

