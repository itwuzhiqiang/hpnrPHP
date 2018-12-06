<?php

class PadLib_Pworker_Client_Admin {
	private $_server;
	private $_redis;
	
	public function __construct($server) {
		$this->_server = $server;
		$this->_redis = $this->_server->getRedis();
	}
	
	public function showProcessList(){
		$keys = $this->_redis->keys(PadLib_Pworker_Const::redisKeyJobInfo('*'));
		$statusMap = array(
			PadLib_Pworker_Server_Jobhandler::JobStatusNew => 'New',
			PadLib_Pworker_Server_Jobhandler::JobStatusRuning => 'Runing',
			PadLib_Pworker_Server_Jobhandler::JobStatusFinish => 'Finish',
		);
		
		$row = array('JOB_ID', 'UpTime', 'RunTime', 'Pid', 'Status', 'lastLog');
		array_unshift($row, "%-20s%-20s%-10s%-10s%-10s%s\n");
		call_user_func_array('printf', $row);
		
		$total = 0;
		foreach ($keys as $jobId) {
			$info = $this->_redis->hGetAll($jobId);
			
			if ($info['status']  != PadLib_Pworker_Server_Jobhandler::JobStatusFinish) {
				$jobYId = str_replace(PadLib_Pworker_Const::redisKeyJobInfo(''), '', $jobId);
				
				$row = array();
				$row[] = $jobYId;
				$row[] = (isset($info['upTime']) ? date('m/d/H:i:s', ceil($info['upTime']/1000)) : '-1');
				$row[] = isset($info['cTime']) ? ceil((microtime(true)*1000 - $info['cTime'])).'ms' : '-';
				$row[] = isset($info['pid']) ? $info['pid'] : '-';
				$row[] = $statusMap[$info['status']];
				$row[] = '['.mb_substr((isset($info['lastRunLog']) ? $info['lastRunLog'] : '-'), 0, 20, 'utf8').']';
				array_unshift($row, "%-20s%-20s%-10s%-10s%-10s%s\n");
				call_user_func_array('printf', $row);
				
				if ($total++ > 100) {
					break;
				}
			}
		}
		echo implode("\t", array('Total', $total)), "\n";
	}
}

