<?php

class PadMvcSysController_System {
	
	public function __construct (PadMvcRequest $req, PadMvcResponse $res) {
		PadDebug::debugAuth();
	}
	
	public function doGetDebugLog (PadMvcRequest $req, PadMvcResponse $res) {
		$key = $req->param('key');
		if (strpos($key, 'http:/'.'/') !== false) {
			$key = md5($key);
		}
		
		$debug = new PadDebug();
		$logfile = $debug->logFileBaseDir.DIRECTORY_SEPARATOR.'pdebuglog'.DIRECTORY_SEPARATOR.$key;
		$content = file_get_contents($logfile);
		$res->dtext($content);
	}
}



