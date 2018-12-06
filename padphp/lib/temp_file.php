<?php

class PadLib_TempFile {
	public $filePath;

	public function __construct($extName = '') {
		$this->filePath = tempnam("/tmp/padphp-tempfile", uniqid());
		if ($extName) {
			$this->filePath .= '.' . $extName;
		}
		// 防止提前被回收
		if (!isset($GLOBALS['PadLib_TempFile'])) {
			$GLOBALS['PadLib_TempFileClass'] = array();
		}
		$GLOBALS['PadLib_TempFileClass'][] = $this;
	}

	public function getPath() {
		return $this->filePath;
	}

	public function __destruct() {
		if (file_exists($this->filePath)) {
			unlink($this->filePath);
		}
	}
}
