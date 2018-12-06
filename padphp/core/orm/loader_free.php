<?php

class PadOrmLoaderFree extends PadOrmLoader {
	public $baseLoader;
	
	public function __construct($baseLoader) {
		$loaders = func_get_args();
		if (count($loaders) == 1) {
			$this->baseLoader = $loaders[0];
		} else {
			$this->baseLoader = $loaders;
		}
		$this->_db = $loaders[0]->_db;
	}
	
	public function query () {
		$args = func_get_args();
		if (count($this->query) == 0) {
			$isQuery = true;
			$fields = '*';
			if (strpos($args[0], 'fields') === 0) {
				preg_match('/fields(.*)/i', $args[0], $match);
				$fields = $match[1];
				$isQuery = false;
			}
			
			if (is_array($this->baseLoader)) {
				$sqlList = array();
				foreach ($this->baseLoader as $index => $sloader) {
					$sqlList[] = $sloader->_getQueryString();
					foreach ($sloader->queryBinds as $value) {
						$this->queryBinds[] = $value;
					}
				}
				$this->query[] = 'SELECT '.$fields.' FROM (' . implode(' UNION ALL ', $sqlList) . ') AS _t0';
			} else {
				$this->query[] = 'SELECT '.$fields.' FROM (' . $this->baseLoader->_getQueryString() . ') AS _t0';
				foreach ($this->baseLoader->queryBinds as $value) {
					$this->queryBinds[] = $value;
				}
			}
			
			if ($isQuery) {
				call_user_func_array(array('parent', __FUNCTION__), $args);
			}
		} else {
			call_user_func_array(array('parent', __FUNCTION__), $args);
		}
		return $this;
	}
}




