<?php

class PadMvcHelperForm {

	public $_attrs = array();

	public $_styles = array();

	/**
	 * 设置属性
	 */
	public function attr($attrs) {
		if (is_string($attrs)) {
			foreach (explode(',', $attrs) as $tmp) {
				$tmp = explode('=', $tmp);
				if (isset($tmp[1]) && $tmp[0] && $tmp[1]) {
					$this->_attrs[$tmp[0]] = $tmp[1];
				}
			}
		}
		return $this;
	}

	/**
	 * 设置style
	 */
	public function style($styles) {
		if (is_string($styles)) {
			foreach (explode(',', $styles) as $tmp) {
				$tmp = explode('=', $tmp);
				if (isset($tmp[1]) && $tmp[0] && $tmp[1]) {
					$this->_styles[$tmp[0]] = $tmp[1];
				}
			}
		}
		return $this;
	}

	/**
	 * 生成input标签
	 */
	public function text($name, $dvalue = null) {
		$dvalue = ($dvalue === null ? $dvalue : (string) $dvalue);
		$this->_attrs['name'] = $name;
		$this->_attrs['type'] = 'text';
		$this->_attrs['value'] = $dvalue;
		return '<input' . $this->_joinAttrs() . $this->_joinStyles() . ' />';
	}

	public function password($name, $dvalue = null) {
		$dvalue = ($dvalue === null ? $dvalue : (string) $dvalue);
		$this->_attrs['name'] = $name;
		$this->_attrs['type'] = 'password';
		$this->_attrs['value'] = $dvalue;
		return '<input' . $this->_joinAttrs() . $this->_joinStyles() . ' />';
	}

	/**
	 * hidden隐藏域
	 */
	public function hidden($name, $dvalue = null) {
		$dvalue = ($dvalue === null ? $dvalue : (string) $dvalue);
		$this->_attrs['name'] = $name;
		$this->_attrs['type'] = 'hidden';
		$this->_attrs['value'] = $dvalue;
		return '<input' . $this->_joinAttrs() . $this->_joinStyles() . ' />';
	}
	
	public function hiddens($datas, $deleteKeys = array()) {
		$defaultDatas = array();
		if (isset($GLOBALS['pad_core']) && isset($GLOBALS['pad_core']->mvc->activeRequest)) {
			$defaultDatas = $GLOBALS['pad_core']->mvc->activeRequest->params;
			if (isset($GLOBALS['pad_core']->mvc->activeRequest->defaultUrlParams)) {
				$defaultDatas = array_merge($defaultDatas, $GLOBALS['pad_core']->mvc->activeRequest->defaultUrlParams);
			}
		}
		
		if (!is_array($datas)) {
			$datasString = str_replace(',', '&', $datas);
			parse_str($datasString, $datas);
		}
		$datas = array_merge($defaultDatas, $datas);
		
		if (!is_array($deleteKeys)) {
			$deleteKeys = explode(',', $deleteKeys);
		}
		
		$return = array();
		foreach($datas as $k => $v){
			if (!in_array($k, $deleteKeys)) {
				$return[] = '<input type="hidden" name="'.$k.'" value="'.$v.'" />';
			}
		}
		return implode('', $return);
	}

	/**
	 * 多行文本
	 */
	public function textarea($name, $dvalue = null) {
		$dvalue = ($dvalue === null ? $dvalue : (string) $dvalue);
		$this->_attrs['name'] = $name;
		return '<textarea' . $this->_joinAttrs() . $this->_joinStyles() . '>' . ((string) $dvalue) . '</textarea>';
	}

	/**
	 * 生成select标签
	 */
	public function select($name, $datas, $dvalue = null) {
		$dvalue = ($dvalue === null ? $dvalue : (is_object($dvalue) ? (string) $dvalue : $dvalue));
		$array = array();
		foreach ($datas as $k => $v) {
			if (is_array($v)) {
				$array[] = '<optgroup label="'.$v['name'].'">';
				foreach ($v['items'] as $k => $v) {
					$tmp = ((($k == $dvalue && $dvalue !== null) || (is_array($dvalue) && in_array($k, $dvalue))) ? ' selected' : '');
					$array[] = '<option value="' . $k . '"' . $tmp . '>' . $v . '</option>';
				}
				$array[] = '</optgroup>';
			} else {
				$tmp = ((($k == $dvalue && $dvalue !== null) || (is_array($dvalue) && in_array($k, $dvalue))) ? ' selected' : '');
				$array[] = '<option value="' . $k . '"' . $tmp . '>' . $v . '</option>';
			}
		}
		$this->_attrs['name'] = $name;
		
		return '<select' . $this->_joinAttrs() . $this->_joinStyles() . '>' . implode('', $array) . '</select>';
	}

	/**
	 * 生成checkbox标签
	 */
	public function checkbox($name, $datas, $dvalue = null) {
		//$dvalue = ($dvalue === null ? $dvalue : (string) $dvalue);
		$dvalue = (array) $dvalue;
		$array = array();
		$this->_attrs['name'] = $name;
		$this->_attrs['type'] = 'checkbox';
		$attrsString = $this->_joinAttrs() . $this->_joinStyles();
		foreach ($datas as $k => $v) {
			//$tmp = (($k == $dvalue && $dvalue !== null) ? ' checked' : '');
			$tmp = (in_array($k, $dvalue) ? ' checked' : '');
			$array[] = '<label><input' . $attrsString . ' value="' . $k . '"' . $tmp . '> ' . $v . '</label>';
		}
		return implode('', $array);
	}

	/**
	 * 生成radio标签
	 */
	public function radio($name, $datas, $dvalue = null) {
		$dvalue = ($dvalue === null ? $dvalue : (string) $dvalue);
		$array = array();
		$this->_attrs['name'] = $name;
		$this->_attrs['type'] = 'radio';
		$attrsString = $this->_joinAttrs() . $this->_joinStyles();
		foreach ($datas as $k => $v) {
			$tmp = (($k == $dvalue && $dvalue !== null) ? ' checked' : '');
			$array[] = '<label class="ld"><input' . $attrsString . ' value="' . $k . '"' . $tmp . '> ' . $v . '</label>';
		}
		return implode('', $array);
	}

	public function __call($function, $params = array()) {
		$value = array_shift($params);
		$this->_attrs[$function] = $value;
		return $this;
	}

	/**
	 * 来源的数据
	 */
	public function referdata() {
		$array = array();
		$controller = $GLOBALS['pad_core']->mvc->activeRequest->controller;
		$action = $GLOBALS['pad_core']->mvc->activeRequest->action;
		$request = $GLOBALS['pad_core']->mvc->activeRequest;
		$params = array_merge($_GET, $_POST);
		$str = null;
		if (isset($params['_padreferdata'])) {
			$str = $params['_padreferdata'];
		} else {
			$str = base64_encode(serialize(array(
				$controller,
				$action,
				$params
			)));
		}
		$hiddenReferdata = $this->hidden('_padreferdata', $str);
		
		$referUrl = $request->getReferUrl();
		$hiddenReferUrl = $this->hidden('referUrl', $referUrl);
		
		return $hiddenReferdata.$hiddenReferUrl;
	}

	/**
	 * ----------------------------------------------------------------
	 */
	
	/**
	 * 根据当前环境获得dvalue
	 */
	public function _dvalue($name, $dvalue) {
		return $dvalue;
	}

	/**
	 * 获得属性的连接字符串
	 */
	public function _joinAttrs() {
		$array = array();
		foreach ($this->_attrs as $k => $v) {
			$array[] = $k . '="' . htmlspecialchars($v) . '"';
		}
		$this->_attrs = array();
		return $array ? ' ' . implode(' ', $array) : '';
	}

	/**
	 * 获得样式的连接字符串
	 */
	public function _joinStyles() {
		$array = array();
		foreach ($this->_styles as $k => $v) {
			$array[] = $k . ':' . $v . ';';
		}
		$this->_styles = array();
		return $array ? ' style="' . implode(' ', $array) . '"' : '';
	}
}


