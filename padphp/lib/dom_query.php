<?php

include(__DIR__.'/_inc.url_to_absolute.php');

class PadLib_DomQuery {
	public $isnull = false;
	private $_dom;
	private $_element;
	private $_options;
	
	public function __construct($mix, $options = array()){
		$this->_options = $options;
		if (is_string($mix)) {
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			libxml_use_internal_errors(true);

			/*
			$tidy = new tidy;
			$config = array(
				'indent' => true,
				'output-xhtml' => true,
				'wrap' => 200,
			);
			$tidy->parseString($mix, $config, 'utf8');
			$tidy->cleanRepair();
			*/

			if (strpos($mix, 'content="text/html;') === false) {
				$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $mix);
			} else {
				$dom->loadHTML($mix);
			}
			libxml_use_internal_errors(false);
			$this->_element = $dom->documentElement;
		} else {
			$this->_element = $mix;
		}
	}
	
	public function getElement(){
		return $this->_element;
	}
	
	public function getXpathQuery($query){
		$query = '//'.$query;
		
		// class的语法
		$query = preg_replace_callback('/\.([a-zA-Z0-9-_]+)/', function ($matches) {
			$className = $matches[1];
			return '[contains(@class,"'.$className.'")]';
		}, $query);
		
		// 按ID查询的语法
		$query = preg_replace_callback('/#([a-zA-Z0-9-_]+)/', function ($matches) {
			$id = $matches[1];
			return '[@id="'.$id.'"]';
		}, $query);
		
		// 按属性查询的语法
		$query = preg_replace_callback('/\[([@a-zA-Z0-9_-]+)([=\*!\^~]+)([a-zA-Z0-9-_]+)\]/', function ($matches) {
			list($null, $k, $fh, $v) = $matches;
			$k = trim($k, '@');
			$v = trim($v, '"');
				
			if ($fh == '=') {
				return '[@'.$k.'="'.$v.'"]';
			} else if ($fh == '*=') {
				return '[contains(@'.$k.',"'.$v.'")]';
			} else {
				$GLOBALS['pad_core']->error(E_ERROR, 'symbol not support "'.$fh.'"');
			}
		}, $query);
		
		$query = preg_replace_callback('/\[([@a-zA-Z0-9_\-\(\)]+)\]/', function ($matches) {
			if (is_numeric($matches[1])) {
				return '['.$matches[1].']';
			} elseif (strpos($matches[1], '@') === false) {
				return '[@'.$matches[1].']';
			} else {
				return '['.$matches[1].']';
			}
		}, $query);
	
		// 替换空格
		$query = preg_replace('/\s+/', '//', $query);

		// 没有tagName指定，用*替换
		$query = preg_replace('/\/\[/', '/*[', $query);
		return $query;
	}

	/**
	 * 查询语法核心
	 * @param unknown $query
	 * @return NULL|string|multitype:PadLib_DomQuery
	 */
	public function query($query){
		if (!$this->_element) {
			return new PadLibDomNull();
		}
		$query = $this->getXpathQuery($query);

		$dom = new DOMDocument();
		$cloned = $this->_element->cloneNode(true);
		$dom->appendChild($dom->importNode($cloned, true));
		
		$xpath = new DOMXpath($dom);
		$return = array();
		foreach ($xpath->query($query) as $el) {
			$return[] = new PadLib_DomQuery($el, $this->_options);
		}
		return $return;
	}
	
	/**
	 * 始终返回一个数组
	 * @param unknown $query
	 * @param string $node
	 * @return Ambigous <NULL, string, multitype:PadLib_DomQuery >
	 */
	public function findList($query, $node = null){
		return $this->query($query, $node);
	}
	
	/**
	 * 如果多条记录，返回数组；如果单条，返回对象；
	 * @param unknown $query
	 * @param string $node
	 * @return NULL|mixed|Ambigous <NULL, string, multitype:PadLib_DomQuery >
	 */
	public function find($query, $node = null){
		$list = $this->query($query, $node);
		if (count($list) == 0) {
			return new PadLibDomNull();
		} elseif (count($list) == 1) {
			return array_shift($list);
		} else {
			return $list;
		}
	}
	
	/**
	 * 获得属性。absHref是一个特殊的属性
	 * @param unknown $name
	 * @return NULL|Ambigous <boolean, string>
	 */
	public function attr($name){
		if ($name == 'absHref') {
			$attr = $this->_element->getAttribute('href');
			if (!$attr) {
				return null;
			}
			return url_to_absolute($this->_options['url'], $attr);
		} else if ($name == 'absSrc') {
			$attr = $this->_element->getAttribute('src');
			if (!$attr) {
				return null;
			}
			return url_to_absolute($this->_options['url'], $attr);
		} else {
			return $this->_element->getAttribute($name);
		}
	}
	
	/**
	 * 返回html
	 * @return string
	 */
	public function html(){
		$innerHTML= '';
		$children = $this->_element->childNodes;
		foreach ($children as $child) {
			$innerHTML .= $child->ownerDocument->saveXML( $child );
		}
		return $innerHTML;
	}
	
	public function parent(){
		return new PadLib_DomQuery($this->_element->parentNode, $this->_options);
	}
	
	public function next(){
		$nextSibling = $this->_element->nextSibling;
		if ($nextSibling) {
			return new PadLib_DomQuery($nextSibling, $this->_options);
		} else {
			return new PadLibDomNull();
		}
	}
	
	public function remove ($query = null) {
		if ($query) {
			$query = $this->getXpathQuery($query);
			$xpath = new DOMXpath($this->_element->ownerDocument);
			foreach ($xpath->query($query) as $node) {
				$node->parentNode->removeChild($node);
			}
		} else {
			$this->_element->parentNode->removeChild($this->_element);
		}
	}
	
	public function outerHtml(){
		return $this->_element->ownerDocument->saveXML($this->_element);
	}
	
	/**
	 * 返回节点的text
	 */
	public function text(){
		return $this->_element->textContent;
	}
	
	public function getDocHtml($content){
		return '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>'
			. $content .'</body></html>';
	}
}

class PadLibDomNull {
	public $isnull = true;
	
	public function __get($key) {
		return new PadLibDomNull();
	}
	
	public function __call($func, $params) {
		return new PadLibDomNull();
	}
	
	public function __toString() {
		return '';
	}
}


