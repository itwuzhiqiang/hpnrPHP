<?php

/**
 * Created by PhpStorm.
 * User: macmini
 * Date: 16/2/10
 * Time: 上午11:57
 */
class PadLib_React_Element {

	public $elementName;
	public $props = array();
	public $children = array();

	static public function createFromArray ($data) {
		$elementName = $data['_element'];
		unset($data['_element']);
		$children = (isset($data['_children']) ? $data['_children'] : array());
		unset($data['_children']);

		return new self($elementName, $data, $children);
	}

	public function __construct($elementName = null, $props = array(), $children = array()) {
		$this->elementName = $elementName;
		$this->props = $props;
		$this->children = $children;
	}

	public function getString ($string) {
		if (strpos($string, '(') === 0) {
			return trim($string, '()');
		} else {
			return '"' . $string . '"';
		}
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

	public function renderToReact () {
		$childrenList = array();
		foreach ($this->children as $idx => $children) {
			if (is_string($children)) {
				$childrenList[] = $this->getString($children);
			} else if (is_array($children)) {
				$children = self::createFromArray($children);
				$children->props['key'] = 'c' . $idx;
				$childrenList[] = $children->renderToReact();
			} else {
				$children->props['key'] = 'c' . $idx;
				$childrenList[] = $children->renderToReact();
			}
		}

		$return = array();
		$return[] = $this->getString($this->elementName);

		if ($this->props || $childrenList) {
			if (!$this->props) {
				$return[] = '{}';
			} else {
				$propsResult = array();
				foreach ($this->props as $key => $value) {
					if (is_callable($value)) {
						$newChild = new PadLib_React_Creater();
						$value($newChild);
						$propsResult[] = $key . ': ' . $newChild->getElement()->renderToReact();
					} else if ($value instanceof PadLib_React_Element) {
						$propsResult[] = $key . ': ' . $value->renderToReact();
					} else if ($value instanceof PadLib_React_Creater) {
						$propsResult[] = $key . ': ' . $value->getElement()->renderToReact();
					} else if (is_string($value) && strpos($value, '(') === 0) {
						$propsResult[] = $key . ': ' . trim($value, '()');
					} else {
						$propsResult[] = $key . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE);
					}
				}
				$return[] = '{' . implode(',', $propsResult) . '}';
			}
		}

		if ($childrenList) {
			$return[] = '[' . implode(",\n", $childrenList) . ']';
		}

		return 'React.createElement(' . implode(', ', $return) . ')';
	}
}

