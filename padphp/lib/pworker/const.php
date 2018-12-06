<?php

class PadLib_Pworker_Const {
	static public $KeyPrefix = 'pw.';
	const JobUpTimeInterval = 2000;
	
	/**
	 * 设置全局KEY
	 * @param unknown $keyPrefix
	 */
	static public function setKeyPrefix ($keyPrefix) {
		self::$KeyPrefix = $keyPrefix;
	}
	
	static public function redisKeyPreJobList(){
		return self::$KeyPrefix.'jobPreList';
	}
	
	static public function redisKeyJobRunningList(){
		return self::$KeyPrefix.'jobRunningList';
	}
	
	static public function redisKeyJobAllList(){
		return self::$KeyPrefix.'jobAllList';
	}
	
	static public function redisKeyJobCrontabAllList(){
		return self::$KeyPrefix.'jobCrontabAllList';
	}
	
	static public function redisKeyTagAllList () {
		return self::$KeyPrefix.'tagAllList';
	}
	
	/**
	 * 新的tag，永不过期
	 * @param unknown $tag
	 * @return string
	 */
	static public function redisKeyJobTagList($tag){
		return self::$KeyPrefix.'tagJobList.'.$tag;
	}
	
	/**
	 * 老的Tag
	 * @param unknown $tag
	 * @return string
	 */
	static public function redisKeyUserTagJobList($tag){
		return self::$KeyPrefix.'jobTagJobIds.'.$tag;
	}
	
	static public function redisKeyJobList($type){
		return self::$KeyPrefix.'jobList.'.$type;
	}
	
	static public function redisKeyJobInfo($jobId){
		return self::$KeyPrefix.'jobInfo.'.$jobId;
	}
	
	static public function redisKeyJobRunLog($jobId){
		return self::$KeyPrefix.'jobRunLog.'.$jobId;
	}
	
	static public function redisKeyJobWatchHistory($jobId){
		return self::$KeyPrefix.'jobRunLog.'.$jobId.'.watch_history';
	}
	
	static public function redisKeyJobWatchKey($jobId){
		return self::$KeyPrefix.'jobRunLog.'.$jobId.'.watch';
	}
	
	static public function redisKeyJobWatchLogKey($jobId, $tag){
		return self::$KeyPrefix.'jobRunLog.'.$jobId.'.watch.'.$tag;
	}
	
	static public function redisKeyJobAddRes($jobId){
		return self::$KeyPrefix.'jobAddResult.'.$jobId;
	}
	
	static public function redisKeyJobRealtimeRes($jobId){
		return self::$KeyPrefix.'jobRealtimeResult.'.$jobId;
	}
}


