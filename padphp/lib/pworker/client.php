<?php

class PadLib_Pworker_Client {
	public $redis;
	private $_options;
	
	public function __construct($options = array()){
		$this->_options = array_merge(array(
			'keyPrefix' => 'pw.',
			'redisHost' => '127.0.0.1',
			'redisPort' => 6379,
		), $options);
		PadLib_Pworker_Const::setKeyPrefix($this->_options['keyPrefix']);
		
		$this->redis = new Redis();
		$this->redis->connect($this->_options['redisHost'], $this->_options['redisPort']);
	}
	
	private function _getClass($func, $params, $options){
		$jobClass = new PadLib_Pworker_Client_JobOne();
		$jobClass->func($func);
		$jobClass->params($params);
		$jobClass->options($options);
		return $jobClass;
	}
	
	public function runTest($func, $params = array(), $options = array()){
		$class = $this->_getClass($func, $params, $options);
		$jobData = $class->getData();
		$jobData['options']['id'] = uniqid();
		$worker = new PadLib_Pworker_WorkerProcess_WorkerTest($jobData);
		return $worker->work();
	}
	
	public function runJob($func, $params = array(), $options = array()){
		$class = $this->_getClass($func, $params, $options);
		return $this->exec($class);
	}
	
	public function runBackground($func, $params = array(), $options = array()){
		$class = $this->_getClass($func, $params, $options);
		return $this->execBackground($class);
	}
	
	private function getRunFileFunction () {
		return function ($jobInfo, $file, $params) {
			$paramsArray = array();
			foreach ($params as $k => $v) {
				$paramsArray[] = $k . '=' . $v;
			}
			$shell = $file . ' ' . implode(' ', $paramsArray);
			system($_SERVER['_'] . ' ' . $shell);
		};
	}
	
	public function runFile ($file, $params = array()) {
		return $this->runJob($this->getRunFileFunction(), array($file, $params));
	}
	
	public function runFileBackground ($file, $params = array()) {
		return $this->runBackground($this->getRunFileFunction(), array($file, $params));
	}
	
	public function newSerial($options = array()){
		$class = new PadLib_Pworker_Client_JobSerial();
		$class->options($options);
		return $class;
	}
	
	public function execBackground($class){
		$class->options['isBackground'] = true;
		return $this->exec($class);
	}
	
	/**
	 * 获得任务信息
	 * @param unknown $jodId
	 */
	public function getJobInfo($jodId){
		$info = $this->redis->hGetAll(PadLib_Pworker_Const::redisKeyJobInfo($jodId));
		$return = array();
		if (isset($info['return'])) {
			$return = json_decode($info['return'], true);
		}
		$return['_raw'] = $info;
		return $return;
	}
	
	/**
	 * 根据任务ID，获取实时的输出日志
	 * @param unknown $jodId
	 */
	public function popRunLog($jodId, $tag, $num = 10){
		$jobInfo = $this->getJobInfo($jodId);
		if (!$jobInfo['_raw']) {
			return array(
				'status' => 100,
				'msg' => 'jobid not found',
				'jobId' => $jodId,
				'tag' => $tag,
				'res' => false,
			);
			return;
		}
		
		// 加入tag
		$isNew = (!$this->redis->sismember(PadLib_Pworker_Const::redisKeyJobWatchKey($jodId), $tag));
		$this->redis->expire(PadLib_Pworker_Const::redisKeyJobWatchKey($jodId), 3600);
		$this->redis->sadd(PadLib_Pworker_Const::redisKeyJobWatchKey($jodId), $tag);
		
		// 读取输出结果
		$lines = array();
		if ($isNew) {
			$lines = $this->redis->lrange(PadLib_Pworker_Const::redisKeyJobWatchHistory($jodId), 0, -1);
		}
		$returnStatus = 0;
		for (; $num >= 0; $num--) {
			$line = $this->redis->rpop(PadLib_Pworker_Const::redisKeyJobWatchLogKey($jodId, $tag));
			if ($line !== false) {
				$lines[] = $line;
			} else if ($jobInfo['_raw']['status'] == PadLib_Pworker_Server_Jobhandler::JobStatusFinish) {
				// 任务是结束状态，证明日志已经被拿完
				$returnStatus = 100;
			}
		}
		
		// 没有获取到数据，并且任务已经结束
		// 需要考虑，因为num的限制，任务结束，但是日志没有拿完
		if (empty($lines) && $jobInfo['_raw']['status'] == PadLib_Pworker_Server_Jobhandler::JobStatusFinish) {
			return array(
				'status' => 100,
				'tag' => $tag,
				'res' => false,
			);
		} else {
			return array(
				'status' => $returnStatus,
				'tag' => $tag,
				'res' => $lines,
			);
		}
	}
	
	public function exec($class){
		$jobData = $class->getData();
		if (!isset($jobData['options']['id'])) {
			$jobData['options']['id'] = uniqid();
		}
		
		if (!isset($jobData['options']['isBackground'])) {
			$jobData['options']['isBackground'] = false;
		}
		
		$res = $this->redis->rpush(PadLib_Pworker_Const::redisKeyPreJobList(), json_encode($jobData));
		$jobAddResultString = $this->redis->blpop(PadLib_Pworker_Const::redisKeyJobAddRes($jobData['options']['id']), 3);
		if (!$jobAddResultString) {
			return array(
				'status' => 1001,
				'msg' => 'Add Job Fail',
			);
		}
		list($key, $jobAddResult) = $jobAddResultString;
		$jobAddResult = json_decode($jobAddResult, true);
		if ($jobAddResult['status'] > 0) {
			return $jobAddResult;
		}
		
		if ($jobData['options']['isBackground']) {
			return $jobAddResult;
		} else {
			$jobRealtimeResultString = $this->redis->blpop(PadLib_Pworker_Const::redisKeyJobRealtimeRes($jobData['options']['id']), isset($jobData['options']['timeout']) ? $jobData['options']['timeout'] + 10 : 30);
			if (!$jobRealtimeResultString) {
				return array(
					'status' => 1002,
					'msg' => 'Realtime Result Fail',
				);
			}
			list($key, $jobRealtimeResult) = $jobRealtimeResultString;
			return json_decode($jobRealtimeResult, true);
		}
	}
	
	public function getJobIdsByTag($tagName){
		$redisTagName = PadLib_Pworker_Const::redisKeyUserTagJobList($tagName);
		return $this->redis->sMembers($redisTagName);
	}
	
	public function dumpRunLog($jobIds){
		$jobIdList = array_combine($jobIds, $jobIds);
		$tagId = 'test.'.uniqid();
		while (true) {
			foreach ($jobIds as $jobId) {
				usleep(500 * 1000);
				$logRes = $this->popRunLog($jobId, $tagId, 10);
				if ($logRes['res'] === false) {
					unset($jobIdList[$jobId]);
				}
				
				if (count($jobIdList) == 0) {
					break 2;
				}
				
				if ($logRes['res']) {
					foreach ($logRes['res'] as $line) {
						echo $jobId.' => ', $line, "\n";
					}
				}
			}
		}
	}
	
	public function execShellJob ($crontabConfig = null) {
		$argv = $_SERVER['argv'];
		if (!isset($argv[2])) {
			pprint('empty job content');
			return;
		}
		$func = $argv[2];
		
		if (strpos($func, 'crontab::') === 0) {
			$crontabKey = substr($func, strlen('crontab::'));
			$crontab = null;
			$crontabConfig = include($crontabConfig);
			foreach ($crontabConfig as $item) {
				if ($crontabKey == $item[0]) {
					$crontab = $item;
				}
			}
			
			if ($crontab) {
				$res = $this->execBackground($crontab[2]);
				print_r($res);
			} else {
				pprint('not found crontab key:', $crontabKey);
			}
		} else {
			$res = $this->runBackground('function::function ($jobInfo) {'.$func.'}');
			print_r($res);
		}
	}
	
	static public function getFunctionCode($funcString){
		$func = null;
		if (is_string($funcString) && strpos($funcString, '::') !== false) {
			list($class, $method) = explode('::', $funcString);
			$func = new ReflectionMethod($class, $method);
		} else if (is_array($funcString)) {
			list($object, $funcName) = $funcString;
			$func = new ReflectionMethod($object, $funcName);
		} else {
			$func = new ReflectionFunction($funcString);
		}
	
		$filename = $func->getFileName();
		$startLine = $func->getStartLine() - 1;
		$endLine = $func->getEndLine();
		$length = $endLine - $startLine;
		$source = file($filename);
		$body = implode('', array_slice($source, $startLine, $length));
	
		$functionTagPos = strpos($body, 'function');
		$pos = $functionTagPos;
		$leftDem = 0;
		while (($npos = strpos(substr($body, $pos), '{')) !== false) {
			$leftDem++;
			$pos += $npos + 1;
		}
	
		$pos = $functionTagPos;
		while (($npos = strpos(substr($body, $pos), '}')) !== false) {
			$leftDem--;
			$pos += $npos + 1;
			if ($leftDem <= 0) {
				break;
			}
		}
		$body = substr($body, $functionTagPos, $pos - $functionTagPos);
		$body = preg_replace('/^function(.*?)(use.*?\))/i', 'function$1', $body);
		return $body;
	}
}

