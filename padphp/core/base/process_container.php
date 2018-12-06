<?php

/**
 * 注意：只能在linux下运行
 */
class PadBaseProcessContainer {
	
	/* 配置 */
	public $configs = array();
	
	/* 要执行的命令 */
	public $commends = array();
	
	/* 开始时间 */
	private $_startTime = 0;
	
	/* 运行的子进程 */
	private $_children = array();
	
	/* 初始化 */
	public function __construct($configs = array()) {
		$this->configs = array_merge(
				array(
					// 同时运行数
					'max_children' => 3,
					// 超时时间(秒)
					'timeout' => 6,
					// 主进程检查时间间隔
					'parent_interval' => 0.1,
					// log输出，null表示直接echo到主进程
					'log_file' => null
				), $configs);
		$this->_startTime = microtime(true);
	}

	/**
	 * 添加执行的脚本
	 */
	public function addCommend($commend) {
		$this->commends[] = $commend;
	}

	/**
	 * 回收子进程
	 */
	public function checkChindren() {
		foreach ($this->_children as $pid => $children) {
			if (file_exists('/proc/' . $pid . '/status')) {
				$statusStr = file_get_contents('/proc/' . $pid . '/status');
				if (strpos($statusStr, '(zombie)') !== false) {
					pcntl_waitpid($pid, $status);
					$this->pushLog('process ' . $pid . ' zombied');
					unset($this->_children[$pid]);
				} else 
					if (microtime(true) - $children['start_time'] > $children['timeout']) {
						$this->pushLog('process ' . $pid . ' timeout');
						posix_kill($pid, SIGTERM);
						unset($this->_children[$pid]);
					}
			} else {
				$this->pushLog('process ' . $pid . ' exited');
				unset($this->_children[$pid]);
			}
		}
	}

	/**
	 * 添加一个错误日志
	 */
	public function pushLog($log) {
		$line = implode("\t", array(
			date('Y-m-d H:i:s'),
			$log
		)) . "\n";
		if ($this->configs['log_file'] == null) {
			echo $line;
		} else {
			file_put_contents($this->configs['log_file'], $line, FILE_APPEND);
		}
	}

	/**
	 * 运行
	 */
	public function execute() {
		while (true) {
			/**
			 * 没有命令，没有子进程，则退出
			 */
			if (count($this->commends) == 0 && count($this->_children) == 0) {
				break;
			}
			
			/**
			 * 弹出一个执行的脚本
			 */
			if (count($this->_children) < $this->configs['max_children'] && count($this->commends) > 0) {
				$commend = array_shift($this->commends);
				$pid = pcntl_fork();
				if ($pid == - 1) {
					$this->pushLog('child fork fail');
					exit();
				} else 
					if ($pid == 0) {
						ob_start();
						system($commend, $ret);
						$output = ob_get_contents();
						ob_end_clean();
						$hn = substr($output, - 1);
						$mpid = getmypid();
						$this->pushLog("process {$mpid} output:\n" . $output);
						exit($ret);
					} else {
						$this->_children[$pid] = array(
							'start_time' => microtime(true),
							'timeout' => $this->configs['timeout']
						);
						$this->pushLog("process {$pid} created");
					}
			}
			$this->checkChindren();
			usleep($this->configs['parent_interval'] * 1000 * 1000);
		}
		$this->pushLog('execute time: ' . (microtime(true) - $this->_startTime));
	}
}


