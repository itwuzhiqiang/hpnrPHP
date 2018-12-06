<?php

class PadLib_AppTpl_AbstractNode {

	/**
	 * @var PadLib_AppTpl_Compile
	 */
	public $compile;

	public function __construct($compile) {
		$this->compile = $compile;
	}

	public function renderContent () {

	}

	/**
	 * 获得一个模版的内容
	 * @param $template
	 * @return string
	 */
	public function fetchTemplate ($template) {
		ob_start();
		include($this->compile->compileDir . '/template/' . $template . '.php');
		return ob_get_clean();
	}
}

