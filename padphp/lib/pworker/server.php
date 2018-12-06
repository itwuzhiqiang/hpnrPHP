<?php

class PadLib_Pworker_Server {
	public $bootFile;
	public $options;
	public $serverBase;
	
	public $processList = array();
	public $forkPids = array();
	public $shellOptions = array();
	
	public function __construct($bootFile, $options = array()){
		$this->bootFile = $bootFile;
		$this->options = $options;
		$this->serverBase = new PadLib_Pworker_ServerBase($this->bootFile, $this->options);
		$this->initShellOptions();
		
		declare(ticks = 1);
		pcntl_signal(SIGTERM, array($this, 'signalHandler'));
		pcntl_signal(SIGINT, array($this, 'signalHandler'));
	}
	
	public function signalHandler($signal) {
		foreach ($this->processList as $pid => $info) {
			posix_kill($info['pid'], SIGKILL);
		}
		// 必须exit， 不然不造成主进程不退出
		exit;
	}
	
	public function newSubProcess ($key, $functionCode) {
		$procDesc = array(
			0 => array("pipe", "r+"),
			1 => array("pipe", "w+"),
			2 => array("pipe", "w+")
		);
		$processPipes = array();
		$proc = proc_open($this->serverBase->options['processHandlers']['php'].' --keyname='.$key, $procDesc, $processPipes, __DIR__, array(
			'PwReturnPos' => uniqid(),
		));
		foreach ($processPipes as $pipe) {
			stream_set_blocking($pipe, 0);
		}
		
		$jobData = array(
			'options' => array(
				'id' => uniqid(),
			),
			'function' => $functionCode,
			'params' => array($this->bootFile, $this->options),
		);
		fwrite($processPipes[0], json_encode($jobData));
		fclose($processPipes[0]);
		
		$processStatus = proc_get_status($proc);
		$pid = $processStatus['pid'];
		$this->processList[$pid] = array(
			'proc' => $proc,
			'processPipes' => $processPipes,
			'pid' => $pid,
			'functionKey' => $key,
			'functionCode' => $functionCode,
		);
		file_put_contents($this->shellOptions['pidfile'], $pid."\n", FILE_APPEND);
		$this->printOutput('new subprocess['.$pid.'] '.$key."\n");
	}
	
	public function work () {
		$workList = array(
			'forkProcWorker' => array(5, 'PadLib_Pworker_Server_ExecuteWorker'),
			'forkProcClientJober' => array(1, 'PadLib_Pworker_Server_ExecuteJober'),
			'forkProcBackground' => array(1, 'PadLib_Pworker_Server_ExecuteBackground'),
			'forkProcWebserver' => array(1, 'PadLib_Pworker_Server_ExecuteWebserver'),
		);
		
		$this->processList = array();
		foreach ($workList as $key => $item) {
			list($num, $func) = $item;
			
			for ($i = 0; $i < $num; $i++) {
				$functionCode = 'function::function ($jobInfo, $bootFile, $options) { $class = new '.$func.'($bootFile, $options); $class->work(); }';
				$this->newSubProcess($key, $functionCode);
			}
		}
		
		while ($this->processList) {
			foreach ($this->processList as $idx => $processInfo) {
				$processHandler = $processInfo['proc'];
				$pipes = $processInfo['processPipes'];
				$pid = $processInfo['pid'];
				
				$processStatus = proc_get_status($processHandler);
				$isNotRuning = !$processStatus['running'];
				
				$readpipe = $pipes[1];
				$processStreamsChanged = 0;
				if (is_resource($readpipe)) {
					$readpipes = array($readpipe);
					$processStreamsChanged = stream_select($readpipes, $null, $null, 0, 100 * 1000);
				}
				
				if ($processStreamsChanged) {
					$content = stream_get_contents($readpipe);
					$this->printOutput($content);
				}
				
				if ($isNotRuning) {
					$this->newSubProcess($processInfo['functionKey'], $processInfo['functionCode']);
					proc_terminate($processHandler, 9);
					unset($this->processList[$idx]);
				}
			}
		}
	}
	
	public function printOutput ($content) {
		if ($this->shellOptions['daemon'] != 'yes') {
			echo $content;
		} else {
			file_put_contents($this->shellOptions['logfile'].'.log', $content, FILE_APPEND);
		}
	}
	
	public function daemon(){
		$pid = pcntl_fork();
		if ($pid == -1) {
		} else if ($pid) {
			echo 'pid: ', $this->shellOptions['pidfile'], ':', $pid, ' - ', $this->shellOptions['action'], "\n";
			file_put_contents($this->shellOptions['pidfile'], $pid."\n", FILE_APPEND);
		} else {
			$this->work();
		}
	}
	
	public function initShellOptions () {
		$shortopts = '';
		$longopts = array(
			'help::',
			'daemon::',
			'pidfile:',
			'action:'
		);
		$options = getopt($shortopts, $longopts);
		$options = array_merge(array(
			'pidfile' => '/tmp/pworker.'.md5(realpath($this->bootFile)).'.pid',
			'logfile' => '/tmp/pworker.'.md5(realpath($this->bootFile)),
			'action' => null,
			'daemon' => 'no',
		), $options);
		
		$this->shellOptions = $options;
	}
	
	public function process(){
		$options = $this->shellOptions;
		if (isset($options['help'])) {
			echo '     --action=[start | stop | restart]', "\n";
			echo '     --daemon=[yes | no]', "\n";
			echo '     --pidfile=[filepath] default:/tmp/md5(bootfile).pid', "\n";
			echo '     --logfile=[filepath] default:/tmp/md5(bootfile).log', "\n";
			exit;
		}
		
		if ($options['action'] == 'stop' || $options['action'] == 'restart' || $options['action'] == 'start') {
			if (file_exists($options['pidfile'])) {
				$pids = file_get_contents($options['pidfile']);
				$pids = trim($pids);
				$pids = explode("\n", $pids);
				foreach ($pids as $pid) {
					posix_kill($pid, SIGTERM);
				}
				echo 'kill process: ' . implode(',', $pids) . "\n";
				unlink($options['pidfile']);
			}
		}
		
		if ($options['action'] == 'start' || $options['action'] == 'restart') {
			if ($options['daemon'] == 'yes') {
				$this->daemon();
			} else {
				$this->work();
			}
		}
		
		if (!isset($options['action'])) {
			echo 'not --action, exit!', "\n";
		}
	}
}


