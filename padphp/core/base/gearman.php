<?php

class PadBaseGearman {

	public $configs = array();

	public $storageDb;

	public $storageDbTable;

	public function __construct($bootFile, $configs) {
		$this->configs = array_merge(
				array(
					'boot_file' => $bootFile,
					'hosts' => array(
						'127.0.0.1:5865'
					),
					'php_exec' => 'php',
					'pid_file' => '/tmp/padgm_' . md5($bootFile) . '.pid',
					'log_file' => '/tmp/padgm_' . md5($bootFile) . '.log',
					'php_init_file' => false,
					'worker_count' => 5,
					'storage' => false
				), $configs);
		
		if ($this->configs['storage']) {
			if (strpos($this->configs['storage'], '@mysql') === 0) {
				list ($null, $configId, $table) = explode(':', $this->configs['storage']);
				$this->storageDb = $GLOBALS['pad_core']->database->getPerform($configId);
				$this->storageDb->configs['use_trans'] = false;
				$this->storageDbTable = $table;
			}
		}
		
		if (! $this->storageDb) {
			print('storage not found' . "\n");
			exit();
		}
		
		$action = $this->reqval('action');
		if ($action == 'stop') {
			if (file_exists($this->configs['pid_file'])) {
				system('kill `cat ' . $this->configs['pid_file'] . '`');
				system('rm -rf ' . $this->configs['pid_file']);
			} else {
				print('pid file not found' . "\n");
				exit();
			}
		} elseif ($action == 'start') {
			if (file_exists($this->configs['pid_file'])) {
				system('kill `cat ' . $this->configs['pid_file'] . '`');
				system('rm -rf ' . $this->configs['pid_file']);
			}
			
			$workerCount = $this->reqval('worker_count', $this->configs['worker_count']);
			for ($i = 0; $i < $workerCount; $i ++) {
				$pid = pcntl_fork();
				if ($pid == - 1) {
					echo 'pcntl_fork error', "\n";
					exit();
				} else 
					if ($pid) {
						echo 'worker[', $i, '] created at pid:', $pid, "\n";
						file_put_contents($this->configs['pid_file'], $pid . "\n", FILE_APPEND);
					} else {
						$gmworker = new GearmanWorker();
						foreach ($this->configs['hosts'] as $hostStr) {
							list ($host, $port) = explode(':', $hostStr);
							$gmworker->addServer($host, $port);
						}
						$gmworker->addFunction("worker", array(
							$this,
							'_workProcess'
						));
						while ($gmworker->work()) {
							file_put_contents($this->configs['log_file'], date('Y/m/d H:i:s') . ': worker[' . $i . '] run finish' . "\n", FILE_APPEND);
						}
						exit(0);
					}
			}
		} elseif ($action == 'status') {
			$fields = array(
				'id',
				'begin_time',
				'end_time',
				'run_time'
			);
			while (true) {
				system('clear');
				$list = $this->storageDb->getAll('select * from ' . $this->storageDbTable . ' where end_time = 0');
				foreach ($list as $item) {
					unset($item['stdout']);
					echo implode("\t", $item), "\n";
				}
				sleep(3);
			}
		} else {
			print('action not found' . "\n");
			exit();
		}
	}

	public function _workProcess($job) {
		$workload = $job->workload();
		$workloadArray = unserialize($workload);
		
		/**
		 * 没有设置超时，默认是24小时的超时时间
		 */
		if (! isset($workloadArray['timeout'])) {
			$workloadArray['timeout'] = 24 * 3600;
		}
		
		$descriptorspec = array(
			0 => array(
				'pipe',
				'r'
			),
			1 => array(
				'pipe',
				'w'
			),
			2 => array(
				'pipe',
				'w',
				'a'
			)
		);
		
		$cwd = dirname($this->configs['boot_file']);
		$env = array();
		$epi = 0;
		$env['myargvs[' . $epi ++ . ']'] = $job->unique();
		$env['myargvs[' . $epi ++ . ']'] = serialize($this->configs);
		foreach ($workloadArray['argv'] as $v) {
			$env['myargvs[' . $epi ++ . ']'] = $v;
		}
		
		$outlog = array(
			'id' => $job->unique(),
			'begin_time' => time(),
			'end_time' => 0,
			'run_time' => 0,
			'is_timeout' => 0,
			'state' => '@start'
		);
		$this->storageDb->insert($this->storageDbTable, $outlog, true);
		unset($outlog['state']);
		
		$runtimeStart = microtime(true) * 1000;
		$endtime = time() + $workloadArray['timeout'];
		$process = proc_open($this->configs['php_exec'], $descriptorspec, $pipes, $cwd, $env);
		if (is_resource($process)) {
			if ($this->configs['php_init_file']) {
				fwrite($pipes[0], '<?php include(\'' . $this->configs['php_init_file'] . '\'); ?>');
			}
			
			if (isset($workloadArray['file'])) {
				fwrite($pipes[0], '<?php include("' . $workloadArray['file'] . '"); ?>');
			}
			
			fwrite($pipes[0], 
					'<?php
				$myargvs = $_SERVER["myargvs"];
				$id = $myargvs[0];
				$configsStr = $myargvs[1];
				unset($myargvs[0]);
				unset($myargvs[1]);

				$job = new PadBaseGearmanJob($id, unserialize($configsStr));
				array_unshift($myargvs, $job);
			?>');
			fwrite($pipes[0], '<?php echo call_user_func_array("' . $workloadArray['function'] . '", $myargvs); ?>');
			fclose($pipes[0]);
			
			$stdout = '';
			do {
				$timeleft = $endtime - time();
				$streamRead = array(
					$pipes[1]
				);
				$write = null;
				$exeptions = null;
				stream_select($streamRead, $write, $exeptions, $timeleft, null);
				if (! empty($streamRead)) {
					$stdout .= fread($pipes[1], 8192);
				}
				
				if ($timeleft <= 0) {
					$outlog['is_timeout'] = 1;
					proc_terminate($process);
					break;
				}
			} while (! feof($pipes[1]) && $timeleft > 0);
			
			fclose($pipes[1]);
			proc_close($process);
			
			$outlog['end_time'] = time();
			$outlog['run_time'] = intval(microtime(true) * 1000 - $runtimeStart);
			$outlog['stdout'] = $stdout;
			
			if ($this->storageDb) {
				$this->storageDb->update($this->storageDbTable, $outlog, 'id=\'' . $outlog['id'] . '\'');
			}
			return $stdout;
		} else {
			echo 'proc_open error', "\n";
			exit();
		}
	}

	private function reqval($k, $def = null) {
		static $params;
		
		if (! isset($params)) {
			global $argv;
			$params = array();
			foreach ($argv as $line) {
				$tmp = explode('=', $line);
				$kk = $tmp[0];
				$vv = isset($tmp[1]) ? $tmp[1] : true;
				$params[$kk] = $vv;
			}
		}
		
		return isset($params[$k]) ? $params[$k] : $def;
	}
}

class PadBaseGearmanJob {

	public $svConfigs = array();

	public $id;

	public $storageDb = null;

	public $storageDbTable = null;

	public function __construct($id, $svConfigs) {
		$this->id = $id;
		$this->svConfigs = $svConfigs;
		if ($this->svConfigs['storage']) {
			if (strpos($this->svConfigs['storage'], '@mysql') === 0) {
				list ($null, $configId, $table) = explode(':', $this->svConfigs['storage']);
				$this->storageDb = $GLOBALS['pad_core']->database->getPerform($configId);
				$this->storageDb->configs['use_trans'] = false;
				$this->storageDbTable = $table;
			}
		}
	}

	public function setState($state) {
		if ($this->storageDb) {
			$this->storageDb->update($this->storageDbTable, array(
				'state' => $state
			), 'id=\'' . $this->id . '\'');
		}
	}
}

