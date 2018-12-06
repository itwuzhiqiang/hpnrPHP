<?php

/**
 * Created by PhpStorm.
 * User: macmini
 * Date: 16/2/10
 * Time: 上午11:37
 */
class PadLib_React {

	public $_options = array();

	static public function responseHtml ($data = array()) {
		$element = PadLib_React_Element::createFromArray($data);
		return 'ReactDOM.render(' . $element->renderToReact() . ', document.getElementById("reactApp"));';
	}

	public function __construct($options = array()) {
		$this->_options = $options;
	}

	public function getDom () {
		return new PadLib_React_Dom($this);
	}

	public function createElement ($elementName, $props = array(), $children = array()) {
		return new PadLib_React_Element($elementName, $props, $children);
	}

	public function render (PadLib_React_Element $element) {
		return $element->renderToReact();
	}
}



