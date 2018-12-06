<?php

class PadLib_SubProcess {
	
	static public function run ($mix, $params = array()) {
		$process = new self($mix, $params);
	}
	
	public function __construct($mix, $params = array()) {
		$functionCode = self::getFunctionCode($mix);
		
		$procDesc = array(
			0 => array("pipe", "r+"),
			1 => array("pipe", "w+"),
			2 => array("pipe", "w+")
		);
		$processPipes = array();
		$proc = proc_open($_SERVER['_'], $procDesc, $processPipes, __DIR__, array(
			'PwReturnPos' => uniqid(),
		));
		foreach ($processPipes as $pipe) {
			stream_set_blocking($pipe, 0);
		}
		$jsonString = json_encode(array(
			'function_code' => $functionCode,
			'params' => $params,
		));
		$code = array();
		$code[] = '<?'.'php ';
		$code[] = '$jsonString = \''.str_replace('\'', '\\\'', $jsonString).'\';';
		$code[] = '$jobData = json_decode($jsonString, true);';
		$code[] = '$func = eval(\'return \'.$jobData[\'function_code\'].\';\');';
		$code[] = '$return = call_user_func_array($func, $jobData[\'params\']);';
		$code[] = 'echo json_encode($return);';
		fwrite($processPipes[0], implode('', $code));
		fclose($processPipes[0]);
		
		$isRun = true;
		while ($isRun) {
			$processStatus = proc_get_status($proc);
			$isRun = $processStatus['running'];
			
			$pipes = array($processPipes[1]);
			$processStreamsChanged = stream_select($pipes, $null, $null, 0, 100 * 1000);
			if ($processStreamsChanged > 0) {
				foreach ($pipes as $pipe) {
					$content = stream_get_contents($pipe);
					var_dump($content);
				}
			}
		}
		proc_terminate($proc, 9);
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

