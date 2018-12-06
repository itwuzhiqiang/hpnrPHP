<?php

class PadLib_Pworker_Manage_Job {
	private $_server;
	private $_redis;
	
	public function __construct($server) {
		$this->_server = $server;
		$this->_redis = $this->_server->getRedis();
	}
	
	public function getJobList(){
		$keys = $this->_redis->keys(PadLib_Pworker_Const::redisKeyJobInfo('*'));
		$statusMap = array(
			PadLib_Pworker_Server_Jobhandler::JobStatusNew => 'New',
			PadLib_Pworker_Server_Jobhandler::JobStatusRuning => 'Runing',
			PadLib_Pworker_Server_Jobhandler::JobStatusFinish => 'Finish',
		);
		
		$return = array();
		$row = array('JOB_ID', 'UpTime', 'RunTime', 'Pid', 'Status', 'lastLog', 'Ctrl');
		$return[] = $row;
		
		$total = 0;
		foreach ($keys as $jobId) {
			$info = $this->_redis->hGetAll($jobId);
			
			if ($info['status']  != PadLib_Pworker_Server_Jobhandler::JobStatusFinish || true) {
				$jobYId = str_replace(PadLib_Pworker_Const::redisKeyJobInfo(''), '', $jobId);
				$row = array();
				$row[] = $jobYId;
				$row[] = (isset($info['upTime']) ? date('m/d/H:i:s', ceil($info['upTime']/1000)) : '-1');
				$row[] = isset($info['cTime']) ? ceil((microtime(true)*1000 - $info['cTime'])).'ms' : '-';
				$row[] = isset($info['pid']) ? $info['pid'] : '-';
				$row[] = $statusMap[$info['status']];
				$row[] = '['.mb_substr((isset($info['lastRunLog']) ? $info['lastRunLog'] : '-'), 0, 20, 'utf8').']';
				$row[] = '
					[<a href="/process.view?id='.$jobYId.'">View</a>]
					[<a href="/process.kill?id='.$jobYId.'">Kill</a>]
					[<a href="/process.delete?id='.$jobYId.'">Delete</a>]
				';
				$return[] = $row;
				
				if ($total++ > 100) {
					break;
				}
			}
		}
		return $return;
	}
}

