<?php

class PadLib_Eex {
	private $request;
	private $options = array();

	public function __construct($mvcRequest, $options = array()) {
		$this->request = $mvcRequest;
		$this->options = $options;
		//var_dump($mvcRequest);
	}

	public function getOption($key, $def = null) {
		return isset($this->options[$key]) ? $this->options[$key] : $def;
	}

	public function isMockMode() {
		return isset($this->options['mock']) && $this->options['mock'];
	}

	public function getDoc($file) {
		$content = file_get_contents($file);

		$doc = new DOMDocument('1.0', 'UTF-8');

		// 去除换行，制表符
		$content = str_replace(array("\t", "\n"), ' ', $content);
		$content = preg_replace('/\s+/', ' ', $content);

		// 去除标签之间的空白字符
		$content = preg_replace('/<\/(.*?)>\s+<(.*?)/i', '</$1><$2', $content);
		$content = preg_replace('/\/>\s+<(.*?)/i', '/><$1', $content);

		// 特殊符号
		$content = preg_replace_callback('/({.*?})/', function ($match) {
			$content = $match[0];
			$content = htmlspecialchars($content);
			return $content;
		}, $content);

		$doc->loadXML($content, LIBXML_NOERROR);
		return $doc;
	}

	public function getNodeResult(DOMElement $xmlNode) {
		$fields = array();
		foreach ($xmlNode->childNodes as $fieldNode) {
			if ($fieldNode instanceof DOMElement) {
				$fieldName = strval($fieldNode->tagName);
				$nodeAttrs = $this->getNodeAttrs($fieldNode);

				if ($fieldNode->childNodes->length > 0) {
					$fields[$fieldName] = array(
						'fieldType' => isset($nodeAttrs['fieldType']) ? $nodeAttrs['fieldType'] : 'entity',
						'fields' => $this->getNodeResult($fieldNode),
					);
				} else {
					$fields[$fieldName] = array_merge(array(
						'field' => (isset($nodeAttrs['field']) ? $nodeAttrs['field'] : $fieldName),
						'fieldType' => 'field',
						'format' => (isset($nodeAttrs['format']) ? $nodeAttrs['format'] : 'none'),
						'mock' => 'string',
					), $nodeAttrs);
				}
			}
		}
		return $fields;
	}

	public function getNodeAttrs($xmlNode) {
		$attributes = array();
		if (isset($xmlNode->attributes)) {
			foreach ($xmlNode->attributes as $key => $value) {
				if ($value instanceof DOMAttr) {
					$attributes[$value->nodeName] = strval($value->nodeValue);
				}
			}
		}
		return $attributes;
	}

	public function getConfigResult() {
		$exxQuery = $this->request->param('eexQuery');
		if (!$exxQuery) {
			return array(
				'code' => '500',
				'message' => 'eex查询失败，eexQuery参数不存在',
			);
		}

		$dataXmlFile = null;
		$dqKey = null;

		$defaultDir = $this->request->mvc->bootDir;
		$dataXmlDir = $this->getOption('appDir', $defaultDir);

		if (strpos($exxQuery, 'page.') === 0) {
			list($pageKey, $dqKey) = explode('.', substr($exxQuery, 5));
			if (strpos($pageKey, 'padapi/') === 0) {
				list($cpt, $cptPage) = explode('/', substr($pageKey, 7));
				$dataXmlFile = __DIR__ . '/../../padapi/component/' . $cpt . '/backend/' . $cptPage . '/data.xml';
			} else {
				$dataXmlFile = $dataXmlDir . '/padapp/pages/' . $pageKey . '/data.xml';
			}
		}

		if (!file_exists($dataXmlFile)) {
			return array(
				'code' => '501',
				'message' => 'eex查询失败，不存在配置文件',
			);
		}

		$doc = $this->getDoc($dataXmlFile);
		$xpath = new DOMXPath($doc);
		$queryResult = $xpath->query('/data/' . $dqKey);
		if ($queryResult->length <= 0) {
			return array(
				'code' => '502',
				'message' => 'eex查询失败，不存在节点[' . $dqKey . ']',
			);
		}

		$dqNode = $queryResult[0];
		$attrs = $this->getNodeAttrs($dqNode);

		return array(
			'code' => 0,
			'res' => array(
				'type' => (isset($attrs['type']) ? $attrs['type'] : 'loader'),
				'mock' => (isset($attrs['mock']) && $attrs['mock'] == 'true'),
				'fields' => $this->getNodeResult($dqNode),
			)
		);
	}

	/**
	 * @param $mix PadOrmEntity | PadOrmLoader
	 * @return array
	 */
	public function getResult($mix) {
		$config = $this->getConfigResult();
		if ($config['code'] > 0) {
			return $config;
		}

		$config = $config['res'];
		if ($config['type'] == 'loader') {
			return $this->getResultLoader($mix, $config);
		} else if ($config['type'] == 'entity') {
			return $this->getResultEntity($mix, $config);
		}
	}

	/**
	 * @param $loader PadOrmLoader
	 * @param $config
	 * @return array
	 */
	private function getResultLoader($loader, $config) {
		$page = $this->request->param('page', 1);
		$pageSize = $this->request->param('page_size', 10);

		if ($this->isMockMode() || $config['mock']) {
			$list = array();
			for ($i = 0; $i < $pageSize; $i++) {
				$list[] = $this->getMockData($config['fields']);
			}

			$total = 55;
			return array(
				'code' => 0,
				'res' => array(
					'list' => $list,
					'pageInfo' => array(
						'total' => $total,
						'page' => $page,
						'page_size' => $pageSize,
						'page_total' => ceil($total / $pageSize),
					),
				),
			);
		}

		$loader = $loader->page($page, $pageSize);
		$dataList = $loader->getList();
		$pageInfo = $loader->getPageInfo();

		$resultList = array();
		foreach ($dataList as $entity) {
			$resultList[] = $this->getData($entity, $config['fields']);
		}

		$return = array(
			'code' => 0,
			'res' => array(
				'list' => $resultList,
				'pageInfo' => $pageInfo,
			),
		);

		return $return;
	}

	/**
	 * @param $entity PadOrmEntity
	 * @param $config
	 */
	private function getResultEntity($entity, $config) {
		if ($this->isMockMode() || $config['mock']) {
			$data = $this->getMockData($config['fields']);
			return array(
				'code' => 0,
				'res' => $data,
			);
		}

		$return = array(
			'code' => 0,
			'res' => $this->getData($entity, $config['fields']),
		);

		return $return;
	}

	/**
	 * 获取单个数据
	 * @param $entity
	 * @param $fields
	 */
	private function getData($entity, $fields) {
		$return = array();
		foreach ($fields as $key => $field) {
			if ($field['fieldType'] == 'field') {
				$fieldKey = $field['field'];
				$value = $entity->$fieldKey;
				$return[$key] = $this->format($field['format'], $value);
			} else if ($field['fieldType'] == 'entity') {
				$return[$key] = $this->getData($entity, $field['fields']);
			}
		}
		return $return;
	}

	/**
	 * 获取模拟的数据结果
	 * @param $fields
	 * @return array
	 */
	private function getMockData($fields) {
		$return = array();
		foreach ($fields as $key => $field) {
			if ($field['fieldType'] == 'field') {
				$value = $this->getMockField($field['mock']);
				$return[$key] = $this->format($field['format'], $value);
			} else if ($field['fieldType'] == 'entity') {
				$return[$key] = $this->getMockData($field['fields']);
			}
		}
		return $return;
	}

	/**
	 * 单个的模拟数据
	 * @param $typeString
	 * @return int|string
	 */
	private function getMockField($typeString) {
		$type = $typeString;
		$props = array();
		if (strpos($typeString, '?') > -1) {
			list($type, $query) = explode('?', $typeString);
			$query = str_replace(',', '&', $query);
			parse_str($query, $props);
		}

		$getProp = function ($k, $def = null) use ($props) {
			return isset($props[$k]) ? $props[$k] : $def;
		};

		$getNumProp = function ($key, $def) use ($props, $getProp) {
			$size = $getProp($key, $def);
			$min = 0;
			$max = 0;
			if (strpos($size, '-') === false) {
				$min = $size;
				$max = $size;
			} else {
				list($min, $max) = explode('-', $size);
			}
			return rand($min, $max);
		};

		if ($type == 'int') {
			return $getNumProp('range', '1000-9999');
		} else if ($type == 'string') {
			$type = $getProp('type', 'cn');
			$sizeValue = $getNumProp('size', '5-8');
			return $this->randString($sizeValue, $type);
		} else if ($type == 'image-url') {
			$bg = $getProp('bg', 'EEEEEE');
			return 'http://iph.href.lu/' . $getProp('size', '200x100') . '?text=PADEEX&bg=' . $bg;
		} else if ($type == 'url') {
			return 'http://www.padkeji.com/';
		} else if ($type == 'time') {
			$format = $getProp('format');
			if ($format) {
				return date($format, time() + rand(-10000, 10000));
			} else {
				return time() + rand(-10000, 10000);
			}
		} else {
			return 'unknown[' . $type . ']';
		}
	}

	private function randString($len = 6, $type = '', $addChars = '') {
		$str = '';
		switch ($type) {
			case 'en':
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
				break;
			case 'number':
				$chars = str_repeat('0123456789', 3);
				break;
			case 'cn':
				$chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传" . $addChars;
				break;
		}
		if ($len > 10) {//位数过长重复字符串一定次数
			$chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
		}
		if ($type != 'cn') {
			$chars = str_shuffle($chars);
			$str = substr($chars, 0, $len);
		} else {
			for ($i = 0; $i < $len; $i++) {
				$str .= mb_substr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1);
			}
		}
		return $str;
	}

	/**
	 * 格式化输出结果
	 * @param $typeString
	 * @param $value
	 * @return false|string
	 */
	private function format ($typeString, $value) {
		$type = $typeString;
		$props = array();
		if (strpos($typeString, '?') > -1) {
			list($type, $query) = explode('?', $typeString);
			$query = str_replace(',', '&', $query);
			parse_str($query, $props);
		}

		$getProp = function ($k, $def = null) use ($props) {
			return isset($props[$k]) ? $props[$k] : $def;
		};

		if ($type == 'date') {
			$fm = $getProp('format', 'Y-m-d');
			return date($fm, $value);
		} else {
			return $value;
		}
	}
}

