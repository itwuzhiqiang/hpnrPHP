<?php

class PadBaseKeepProcess {

	public $configs = array();

	public function __construct($runFiles, $configs = array()) {
		$this->configs = array_merge(array(
			'log_file' => '/dev/null',
			'sleep' => 2,
			'php_exec' => '/software/php/bin/php'
		), $configs);
		
		$his = date('Hi');
		while (date('Hi') == $his) {
			$this->execute($runFiles);
			sleep($this->configs['sleep']);
		}
	}

	public function execute($runFiles = array()) {
		ob_start();
		system('ps -ef|grep php');
		$list = ob_get_contents();
		ob_end_clean();
		
		$list = trim($list);
		$list = explode("\n", $list);
		
		foreach ($list as $row) {
			foreach ($runFiles as $shell => $count) {
				if (strpos($row, $shell) !== false) {
					$runFiles[$shell] --;
				}
			}
		}
		
		foreach ($runFiles as $shell => $count) {
			for ($i = 0; $i < $count; $i ++) {
				echo 'restart [' . $shell . '][' . $i . '] ...', "\n";
				exec('(' . $this->configs['php_exec'] . ' ' . $shell . ' >> ' . $this->configs['log_file'] . ' &)');
			}
		}
	}
}





