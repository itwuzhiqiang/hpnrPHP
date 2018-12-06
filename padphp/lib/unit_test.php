<?php

class PadLib_UnitTest {
	public $funcSuccCount = 0;
	public $funcFailCount = 0;
	
	public $assertCount = 0;
	public $assertSuccCount = 0;
	public $assertFailCount = 0;
	
	public $functionTempFailCount = 0;
	
	public function assertHandler($file, $line, $code){
		$debugBacktrace = debug_backtrace();
		$debug = $debugBacktrace[2];
		
		if ($code) {
			$this->outline("   "."- assert \033[1;40;31mfail\033[0m: function [", $debug['function'], "], line [", $debug['line'], '], code [', $code, ']');
		} else {
			$this->outline("   "."- assert \033[1;40;31mfail\033[0m: function [", $debug['function'], "], line [", $debug['line'], ']');
		}
		$this->assertSuccCount--;
		$this->assertFailCount++;
		$this->functionTempFailCount++;
	}
	
	protected function outlineNoNewline(){
		$items = func_get_args();
		echo implode('', $items);
	}
	
	protected function outline(){
		$items = func_get_args();
		echo implode('', $items), "\n";
	}
	
	/**
	 * 断言Bool
	 * @param unknown_type $bool
	 */
	public function assertBool($bool){
		$this->assertCount++;
		$this->assertSuccCount++;
		assert($bool);
	}
	
	/**
	 * 断言相等
	 * @param unknown_type $value1
	 * @param unknown_type $value2
	 */
	public function assertEqual($value1, $value2){
		$this->assertCount++;
		$this->assertSuccCount++;
		assert($value1 === $value2);
	}

	/**
	 * 输出信息
	 * @param unknown_type $message
	 */
	protected function printLog($message){
		$this->outline("   "."- log: ", $message);
	}

	/**
	 * 运行
	 */
	final public function run(){
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_QUIET_EVAL, 1);
		assert_options(ASSERT_CALLBACK, array($this, 'assertHandler'));
		
		$methods = array();
		foreach(get_class_methods($this) as $method){
			if (!(strpos($method, '_') === 0 && strpos($method, '__') !== 0)) {
				continue;
			}

			$this->outline("  "."Execute Method: [ {$method} ]");
			$succ = null;
			$this->functionTempFailCount = 0;
			$this->assertSuccCount = 0;
			$this->assertCount = 0;
			$assertSuccCount = 0;
			try {
				$succ = $this->$method();
			} catch (Exception $e) {
				$succ = false;
			}
			
			print("" . '  .........Total: '.$this->assertCount.', OK: '.$this->assertSuccCount . ', Fail: ' . ($this->assertCount - $this->assertSuccCount) . "\n");
			if ($this->functionTempFailCount == 0) {
				$this->funcSuccCount++;
			} else {
				$this->funcFailCount++;
			}
			
			$debugBacktrace = debug_backtrace();
			$debug = $debugBacktrace[0];
			//$this->outline("  "."- func   \033[1;40;31mfail\033[0m: line [", $debug['line'], '], function [', $debug['function'], ']');
			//$this->outline("  "."- func   \033[1;40;32msucc\033[0m: line [", $debug['line'], '], function [', $debug['function'], ']');
		}
		
		$functionStr = 'FuncSuccCount: '. $this->funcSuccCount. ', FuncFailCount: '. $this->funcFailCount. ', AssertSuccCount: '. $this->assertFailCount. "\n";
		if ($this->funcFailCount > 0) {
			$functionStr = "\033[1;40;31m" . $functionStr . "\033[0m";
		} else {
			$functionStr = "\033[1;40;32m" . $functionStr . "\033[0m";
		}
		$this->outline($functionStr);
	}
}



