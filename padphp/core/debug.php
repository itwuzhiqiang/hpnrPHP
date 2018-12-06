<?php

class PadDebug {
	public $logFileKey;
	public $logFileBaseDir;
	
	public $_params = array();
	public $startTime = 0;
	public $databaseReadCount = 0;
	public $databaseReadTime = 0;
	public $cacheReadCount = 0;
	public $debugString = array();

	public function __construct($params = array()) {
		$this->startTime = microtime(true) * 1000;
		$this->logFileBaseDir = sys_get_temp_dir();
		$this->_params = (is_string($params) && $params ? explode(',', $params) : true);
		
		if (isset($GLOBALS['pad_core']->envArgv['--pad-debug'])) {
			self::debugAuth();
		}
	}
	
	static public function debugAuth () {
		if (!isset($GLOBALS['pad_core']->envConfigs['debug_auth'])) {
			$GLOBALS['pad_core']->error(E_ERROR, 'not set env_config[debug_auth]');
		}
			
		if (!$GLOBALS['pad_core']->envConfigs['is_shell_run']) {
			list($userName, $password) = explode(',', $GLOBALS['pad_core']->envConfigs['debug_auth']);
			PadMvc::httpAuth($userName, $password);
		}
	}

	public function startXhprof() {
		if (function_exists('xhprof_enable')) {
			xhprof_enable(XHPROF_FLAGS_NO_BUILTINS);
		}
	}

	public function write($type, $string, $time = -1) {
		$time = ceil($time) / 10;
		
		if ($type == 'db') {
			$this->databaseReadCount ++;
			$this->databaseReadTime += $time;
		} elseif ($type == 'cache') {
			$this->cacheReadCount ++;
		}
		
		$debugString = null;
		if ($GLOBALS['pad_core']->envConfigs['is_shell_run']) {
			$debugString = "\033[33m" . strtoupper($type) . ':' . "\033[0m " . implode(sprintf("%-11s", "\n"), str_split($string, 150)) . "\n";
			if (isset($GLOBALS['pad_core']->envArgv['--pad-debug']) && (!is_array($this->_params) || in_array($type, $this->_params))) {
				echo $debugString;
			}
		} else {
			$debugString = strtoupper($type) . '[' . $time . ']:' . $string . "<br />\n";
			if (isset($GLOBALS['pad_core']->envArgv['--pad-debug']) && (!is_array($this->_params) || in_array($type, $this->_params))) {
				$this->debugString[] = $debugString;
			}
		}
		return true;
	}
	
	public function initWriteLog () {
		if (!$this->logFileKey) {
			if (isset($GLOBALS['pad_core']->envArgv['debuglogkey'])) {
				$this->logFileKey = $GLOBALS['pad_core']->envArgv['debuglogkey'];
			} elseif ($GLOBALS['pad_core']->envConfigs['is_shell_run']) {
				$this->logFileKey = md5(implode(' ', $_SERVER['argv']));
			} else {
				$this->logFileKey = md5((isset($_SERVER['HTTPS']) ? 'https:/'.'/' : 'http:/'.'/') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			}
			
			if (!is_dir($this->logFileBaseDir.DIRECTORY_SEPARATOR.'pdebuglog')) {
				mkdir($this->logFileBaseDir.DIRECTORY_SEPARATOR.'pdebuglog');
			}
			
			$logfile = $this->logFileBaseDir.DIRECTORY_SEPARATOR.'pdebuglog'.DIRECTORY_SEPARATOR.$this->logFileKey;
			file_put_contents($logfile, '');
		}
	}
	
	public function writeLog ($line) {
		if ($this->_params !== true && !in_array('log', $this->_params)) {
			return true;
		}
		
		$this->initWriteLog();
		$logfile = $this->logFileBaseDir.DIRECTORY_SEPARATOR.'pdebuglog'.DIRECTORY_SEPARATOR.$this->logFileKey;
		if ($GLOBALS['pad_core']->envConfigs['is_shell_run']) {
			$logLine = $line."\n";
			echo str_replace("\t", ' ', $logLine);
		}
		file_put_contents($logfile, $line."\n", FILE_APPEND);
		return true;
	}

	public function output() {
		$total[] = 'Mem: ' . ceil(memory_get_usage()/1024) . 'K';
		$total[] = 'ExecuteTime: ' . ceil(microtime(true) * 1000 - $this->startTime) . 'ms';
		$total[] = 'Database: ' . $this->databaseReadCount . 'cnt/' . $this->databaseReadTime . 'ms';
		$total[] = 'CacheReadCount: ' . $this->cacheReadCount;
		
		$entityCount = 0;
		foreach ($GLOBALS['pad_core']->orm->entityLoadedList as $entityName => $list) {
			$entityCount += count($list);
		}
		$total[] = 'EntityCount: ' . $entityCount;
		
		if (function_exists('xhprof_enable')) {
			$xhprofData = array();
			foreach (xhprof_disable() as $key => $item) {
				$file = null;
				$func = null;
				
				if ($key == 'main()') {
					$file = 'main()';
					$func = 'main()';
				} else {
					list ($file, $func) = explode('==>', $key);
				}
				
				if (strpos($func, '/') === false) {
					if (! isset($xhprofData[$func])) {
						$xhprofData[$func] = array(
							'time' => 0,
							'cnt' => 0
						);
					}
					$xhprofData[$func]['cnt'] += $item['ct'];
					$xhprofData[$func]['time'] += $item['wt'];
				}
			}
			uasort($xhprofData, 'PadDebug::_cmpCallbackXhprofData');
			foreach ($xhprofData as $key => $item) {
				$this->debugString[] = implode(', ', array(
					$key,
					ceil($item['time']/1000) . 'ms',
					$item['cnt']
				)) . "\n";
			}
		}
		
		if (! $GLOBALS['pad_core']->envConfigs['is_shell_run']) {
			echo '<div style="position:absolute; z-index:999; background:#111; color:#DDD; line-height:18px; word-wrap:break-word; word-break:break-all; font-size:11px; top:0px; left:0px; height:360px; width:100%;">';
			echo '<div style="padding:0 5px 0 5px; background:#000; color:#FFF; border-bottom:1px solid #FFF;"><b>' . implode(', ', $total) . '</b></div>';
			echo '<div style="overflow-y:auto; overflow-x:hidden; height:340px; padding:0 5px 0 5px;"><div>';
			echo implode('</div><div style="margin:0 0 3px 0;">', $this->debugString);
			echo '</div></div></div>';
		} else {
			array_unshift($this->debugString, "\n" . '-----------------------------------------------------' . "\n");
			array_unshift($this->debugString, implode(', ', $total));
			array_unshift($this->debugString, '-----------------------------------------------------' . "\n");
			echo implode('', $this->debugString);
		}
	}

	static public function _cmpCallbackXhprofData($v1, $v2) {
		return $v1['time'] < $v2['time'];
	}
}

