<?php 

class PadBaseShell {
	
	static public function getParam($key, $def = null){
		static $params;
		if (!isset($params)) {
			global $argv;
			$params = array();
			foreach($argv as $line){
				$tmp = explode('=', $line);
				$k = $tmp[0];
				$v = isset($tmp[1]) ? $tmp[1] : false;
				$params[$k] = $v;
			}
		}
		return isset($params[$key]) ? $params[$key] : $def;
	}

	static public function getEnv($key, $def = null){
		return isset($_SERVER[$key]) ? $_SERVER[$key] : $def;
	}

	static public function getRequest($key, $def = null){
		$paramVal = self::getParam($key);
		$envVal = self::getEnv($key);
		if ($paramVal !== null) {
			return $paramVal;
		} else if ($envVal !== null) {
			return $envVal;
		} else {
			return $def;
		}
	}
	
	static public function output(){
		$argvs = func_get_args();
		echo implode("\t", $argvs), "\n";
	}
	
	static public function backgroundRun ($logFile = null) {
		$isBackground = self::getParam('--background');
		if ($isBackground !== null) {
			$argv = $_SERVER['argv'];
			unset($argv[0]);
			foreach ($argv as $i => $value) {
				if ($value == '--background') {
					unset($argv[$i]);
				}
			}
			if ($logFile == null) {
				$scriptName = str_replace('.php', '', basename($_SERVER['PHP_SELF']));
				$logFile = dirname($_SERVER['PHP_SELF']).'/'.$scriptName.'.runlog';
			}
			
			$run = $_SERVER['PHP_SELF'] . ' ' . implode(' ', $argv);
			pprint('background: '.$run);
			pprint('logFile: '.$logFile);
			system('(' . $_SERVER['_'] . ' '. $run .' >> '.$logFile.' &)');
			exit;
		}
	}

	static public function parseParams () {
		global $argv;
		$params = array();
		foreach($argv as $line){
			$tmp = explode('=', $line);
			$k = $tmp[0];
			$v = isset($tmp[1]) ? $tmp[1] : false;
			$params[$k] = $v;
		}
		return $params;
	}

	static public function getSystemResult ($system) {
		ob_start();
		system($system);
		return ob_get_clean();
	}
}



