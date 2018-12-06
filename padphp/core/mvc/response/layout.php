<?php

class PadMvcResponseLayout {

	private $blockNameStack = array();

	private $blockCacheQueryStack = array();

	private $blockHtmlList = array();

	private $blockWarpTemp = null;
	
	public function __construct() {}

	public function setBlock($blockName, $content) {
		return $this->blockHtmlList[$blockName] = $content;
	}

	public function blockBegin($blockName, $cacheQuery = null) {
		array_unshift($this->blockNameStack, $blockName);
		ob_start(array($this, 'blockCallback'));
		
		$cacheRedis = null;
		if ($cacheQuery && $cacheRedis) {
			array_unshift($this->blockCacheQueryStack, $cacheQuery);
			$html = $cacheRedis->get($cacheQuery);
			if ($html) {
				echo $html;
				return false;
			}
		}
		
		return true;
	}

	public function blockCallback($buffer) {
		$blockName = array_shift($this->blockNameStack);
		if (!isset($this->blockHtmlList[$blockName])) {
			$this->blockHtmlList[$blockName] = '';
		}
		$this->blockHtmlList[$blockName] .= $buffer;
		
		$cacheQuery = array_shift($this->blockCacheQueryStack);
		$cacheRedis = null;
		if ($cacheQuery && $cacheRedis) {
			$cacheRedis->set($cacheQuery, $buffer);
		}
		
		return $this->blockHtmlList[$blockName];
	}

	public function blockEnd() {
		ob_end_clean();
	}

	public function blockEndDisplay() {
		ob_end_flush();
	}

	public function displayBlock($blockName, $default = null) {
		echo isset($this->blockHtmlList[$blockName]) ? trim($this->blockHtmlList[$blockName]) : $default;
	}

	public function getBlock($blockName, $default = null) {
		return isset($this->blockHtmlList[$blockName]) ? trim($this->blockHtmlList[$blockName]) : $default;
	}
}



