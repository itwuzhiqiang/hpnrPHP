<?php

class PadBaseUnitTest {

	public $funcSuccCount = 0;

	public $funcFailCount = 0;

	public $assertFailCount = 0;

	public function assertHandler($file, $line, $code) {
		$this->outline("- assert \033[1;40;31mfail\033[0m: line [", $line, '], code [', $code, ']');
		$this->assertFailCount ++;
	}

	protected function outline() {
		$items = func_get_args();
		echo implode('', $items), "\n";
	}

	final public function run() {
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_QUIET_EVAL, 1);
		assert_options(ASSERT_CALLBACK, array(
			$this,
			'assertHandler'
		));
		
		$methods = array();
		foreach (get_class_methods($this) as $method) {
			if (! (strpos($method, '_') === 0 && strpos($method, '__') !== 0)) {
				continue;
			}
			
			$succ = null;
			try {
				$succ = $this->$method();
			} catch (Exception $e) {
				$succ = false;
			}
			
			$debugBacktrace = debug_backtrace();
			$debug = $debugBacktrace[0];
			if ($succ === null || $succ === true) {
				/*
				 * $this->outline("- func \033[1;40;32msucc\033[0m: line [",
				 * $debug['line'], '], function [', $debug['function'], ']');
				 */
				$this->funcSuccCount ++;
			} else {
				$this->outline("- func   \033[1;40;31mfail\033[0m: line [", $debug['line'], '], function [', $debug['function'], ']');
				$this->funcFailCount ++;
			}
		}
		
		$this->outline('FuncSuccCount: ', $this->funcSuccCount, ', FuncFailCount: ', $this->funcFailCount, ', AssertSuccCount: ', $this->assertFailCount);
	}
}



