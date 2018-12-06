<?php
/**
 * Created by PhpStorm.
 * User: macmini
 * Date: 16/2/10
 * Time: 下午6:09
 */

class PadLib_React_Creater {

	private $options = array();
	private $elementName;
	private $props = array();
	private $children = array();
	private $resultString;

	public function __construct($options = array()) {
		$this->options = array_merge(array(
			'renderType' => 'main',
			'renderId' => 'reactApp',
		), $options);
	}

	/**
	 * @param $elementName
	 * @return $this
	 */
	public function element ($elementName) {
		$this->elementName = $elementName;
		return $this;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function setProp($key, $value) {
		$this->props[$key] = $value;
		return $this;
	}

	public function getProp($key, $default = null) {
		return isset($this->props[$key]) ? $this->props[$key] : $default;
	}

	/**
	 * @param $list
	 * @return $this
	 */
	public function setProps($list) {
		foreach ($list as $k => $v) {
			$this->setProp($k, $v);
		}
		return $this;
	}

	/**
	 * @param $callback
	 * @return $this
	 */
	public function appendChild ($childDefine) {
		$arguments = func_get_args();
		foreach ($arguments as $childDefine) {
			if (is_callable($childDefine)) {
				$newChild = new PadLib_React_Creater();
				$childDefine($newChild);
				$this->children[] = $newChild->getElement();
			} else if (is_string($childDefine)) {
				if (strpos($childDefine, '(') === 0) {
					$this->children[] = new PadLib_React_Element($childDefine);
				} else {
					$this->children[] = $childDefine;
				}
			} else if (is_object($childDefine) && $childDefine instanceof PadLib_React_Element) {
				$this->children[] = $childDefine;
			} else if (is_object($childDefine) && $childDefine instanceof PadLib_React_Creater) {
				$this->children[] = $childDefine->getElement();
			} else if (is_array($childDefine)) {
				$this->children[] = $childDefine;
			}
		}
		return $this;
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @return $this
	 */
	public function __call($name, $arguments) {
		if (count($arguments) == 1) {
			$this->props[$name] = $arguments[0];
		} else {
			$this->props[$name] = $arguments;
		}
		return $this;
	}

	public function getElement () {
		return new PadLib_React_Element($this->elementName, $this->props, $this->children);
	}

	public function renderElement () {
		return $this->getElement()->renderToReact();
	}

	public function getData () {
		$children = array();
		foreach ($this->children as $c) {
			$children[] = $c->getData();
		}

		return array(
			'element' => $this->elementName,
			'props' => $this->props,
			'children' => $children,
		);
	}

	public function getDataJson () {
		return json_encode($this->getData(), JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public function render ($renderId = null) {
		if (!$renderId) {
			$renderId = $this->options['renderId'];
		}

		if ($this->options['renderType'] == 'main') {
			$return = array();
			$return[] = '<script type="text/javascript">';
			$return[] = 'ReactDOM.render(' . $this->getElement()->renderToReact() . ', document.getElementById("' . $renderId . '"));';
			$return[] = '</script>';
			return implode("\n", $return);
		} else {
			return $this->getElement()->renderToReact();
		}
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->render();
	}
}



