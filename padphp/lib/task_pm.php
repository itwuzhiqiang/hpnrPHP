<?php

class PadLib_TaskPm {

	public function __construct() {
	}

	public function setLog ($log) {
		echo $log . "\n";
		flush();
	}

	public function setContext ($data) {
		echo json_encode($data) . "\n";
		flush();
	}
}

