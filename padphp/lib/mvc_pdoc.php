<?php

class PadLib_MvcPdoc {

	public function __construct(PadMvcRequest $request, PadMvcResponse $response) {
		$response->templateLayout('&'. __DIR__ . '/mvc_pdoc/layout.php');
	}

	public function doIndex (\PadMvcRequest $request, \PadMvcResponse $response) {
		$bootDir = dirname($GLOBALS['pad_core']->mvc->bootFile);

		$componentList = array();

		$componentList['default'] = array(
			'component' => 'Api接口',
			'controllers' => array(),
		);

		foreach (glob($bootDir . '/controller/*.php') as $path) {
			$controller = basename($path);
			$controller = str_replace('.php', '', $controller);
			$componentList['default']['controllers'][$controller] = $controller;
		}

		$response->set('componentList', $componentList);
		$response->template('&'. __DIR__ . '/mvc_pdoc/index.php');
	}

	public function doController(\PadMvcRequest $request, \PadMvcResponse $response) {
		$component = $request->param('component');
		$controller = $request->param('controller');

		$className = 'Controller_' . PadBaseString::padStrtoupper($controller);
		$classRef = new \ReflectionClass($className);
		$class = new $className();

		$methods = array();
		foreach ($classRef->getMethods() as $me) {
			$name = $me->getName();
			if (substr($name, 0, 2) !== 'do') {
				continue;
			}

			$comment = new DocParser();
			$commentParse = $comment->parse($me->getDocComment());
			try {
				$mRequest = new PadMvcRequest($GLOBALS['pad_core']->mvc, 'Test', 'Test', array());
				$mRequest->mode = 'document';
				$mResponse = new PadMvcResponse($GLOBALS['pad_core']->mvc,'Test', 'Test', array());
				$class->$name($mRequest, $mResponse);
			} catch (PadBizException $e) {}

			$methods[] = array(
				'name' => PadBaseString::padStrtolower(substr($name, 2)),
				'description' => (isset($commentParse['description']) ? $commentParse['description'] : '没有描述'),
				'return' => (isset($commentParse['return']) ? $commentParse['return'] : '没有描述'),
				'params' => $mRequest->documentParams,
			);
		}

		$response->set('methods', $methods);
		$response->template('&'. __DIR__ . '/mvc_pdoc/controller.php');
	}
}

class DocParser {
	private $params = array();

	function parse($doc = '') {
		if ($doc == '') {
			return $this->params;
		}
		// Get the comment
		if (preg_match('#^/\*\*(.*)\*/#s', $doc, $comment) === false)
			return $this->params;
		$comment = trim($comment [1]);
		// Get all the lines and strip the * from the first character
		if (preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false)
			return $this->params;
		$this->parseLines($lines [1]);
		return $this->params;
	}

	private function parseLines($lines) {
		foreach ($lines as $line) {
			$parsedLine = $this->parseLine($line); // Parse the line

			if ($parsedLine === false && !isset ($this->params ['description'])) {
				if (isset ($desc)) {
					// Store the first line in the short description
					$this->params ['description'] = implode(PHP_EOL, $desc);
				}
				$desc = array();
			} elseif ($parsedLine !== false) {
				$desc [] = $parsedLine; // Store the line in the long description
			}
		}
		$desc = implode(' ', $desc);
		if (!empty ($desc))
			$this->params ['long_description'] = $desc;
	}

	private function parseLine($line) {
		// trim the whitespace from the line
		$line = trim($line);

		if (empty ($line))
			return false; // Empty line

		if (strpos($line, '@') === 0) {
			if (strpos($line, ' ') > 0) {
				// Get the parameter name
				$param = substr($line, 1, strpos($line, ' ') - 1);
				$value = substr($line, strlen($param) + 2); // Get the value
			} else {
				$param = substr($line, 1);
				$value = '';
			}
			// Parse the line and return false if the parameter is valid
			if ($this->setParam($param, $value))
				return false;
		}

		return $line;
	}

	private function setParam($param, $value) {
		if ($param == 'param' || $param == 'return')
			$value = $this->formatParamOrReturn($value);
		if ($param == 'class')
			list ($param, $value) = $this->formatClass($value);

		if (empty ($this->params [$param])) {
			$this->params [$param] = $value;
		} else if ($param == 'param') {
			$arr = array(
				$this->params [$param],
				$value
			);
			$this->params [$param] = $arr;
		} else {
			$this->params [$param] = $value + $this->params [$param];
		}
		return true;
	}

	private function formatClass($value) {
		$r = preg_split("[|]", $value);
		if (is_array($r)) {
			$param = $r [0];
			parse_str($r [1], $value);
			foreach ($value as $key => $val) {
				$val = explode(',', $val);
				if (count($val) > 1)
					$value [$key] = $val;
			}
		} else {
			$param = 'Unknown';
		}
		return array(
			$param,
			$value
		);
	}

	private function formatParamOrReturn($string) {
		$pos = strpos($string, ' ');

		$type = substr($string, 0, $pos);
		return '(' . $type . ')' . substr($string, $pos + 1);
	}
}