<?php

class PadLib_AppTpl_Compile {

	public function __construct($options) {
		$this->classPrefix = $options['classPrefix'];
		$this->compileDir = $options['compileDir'];
		PadCore::autoload($this->classPrefix, $this->compileDir);
	}

	public function fetch ($name) {
		if ($name == 'application') {
			$className = $this->getClassName('Application');
			$application = new $className($this);
			return $application->fetch();
		} else if (strpos($name, 'page/') === 0) {
			$className = $this->getClassName(PadBaseString::padStrtoupper(substr($name, 5)));
			$page = new $className($this);
			return $page->fetch();
		}
	}

	private function getClassName ($class) {
		return $this->classPrefix . $class;
	}
}





