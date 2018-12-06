<?php

class PadMvcHelperPager {

	public $engine = null;

	public $op = null;

	public $params = null;

	public $pageInfo = null;

	public $requestParams;
	public $requestBaseUrl;

	public function __construct($pageInfo, $requestBaseUrl = null, $requestParams = null) {
		if (! empty($pageInfo)) {
			$this->pageInfo = (object) $pageInfo;
			if (! isset($this->pageInfo->page_total)) {
				$this->pageInfo->page_total = intval(ceil($this->pageInfo->total / $this->pageInfo->page_size));
			}
		}

		if ($requestParams === null) {
			if (isset($GLOBALS['pad_core']->mvc)) {
				$requestParams = $GLOBALS['pad_core']->mvc->gGet;
			} else {
				$requestParams = $_POST + $_GET;
			}
		}
		$this->requestParams = $requestParams;

		if ($requestBaseUrl === null) {
			$baseUrl = $_SERVER['REQUEST_URI'];
			if ($pos = strpos($baseUrl, '?')) {
				$baseUrl = substr($baseUrl, 0, $pos);
			}
			$requestBaseUrl = $baseUrl;
		}
		$this->requestBaseUrl = $requestBaseUrl;
	}

	public function result($template = null) {
		if (empty($this->pageInfo) || $this->pageInfo->total <= 0) {
			return '';
		}

		$poolSize = 7;
		$poolSizeAvg = ($poolSize - 1) / 2;
		$html[] = '<div>';

		if ($this->pageInfo->page > 1) {
			$html[] = '<a page="' . ($this->pageInfo->page - 1) . '" href="' . $this->getUrl(null,
					array(
						':merge' => true,
						'page' => $this->pageInfo->page - 1
					)) . '">上一页</a>';
		} else {
			$html[] = '<b class="no">上一页</b>';
		}

		$count = 0;
		for ($i = 1; $i <= $this->pageInfo->page_total; $i ++) {
			$prePool = $this->pageInfo->page - $poolSizeAvg;
			$nextPool = $this->pageInfo->page + $poolSizeAvg;
			$iprePool = $prePool;
			$inectPool = $nextPool;
			$iprePool -= ($nextPool > $this->pageInfo->page_total ? ($nextPool - $this->pageInfo->page_total) : 0);
			$inectPool += ($prePool < 0 ? - $prePool : 0);

			if ($i >= $iprePool && $i <= $inectPool) {
				if ($count == 0 && $i > 1) {
					$html[] = '<a page="1" href="' . $this->getUrl(null, array(
						':merge' => true,
						'page' => 1
					)) . '">1</a>' . ($i > 2 ? '<span>...</span>' : '');
				}

				if ($i != $this->pageInfo->page) {
					$html[] = '<a page="' . $i . '" href="' . $this->getUrl(null, array(
						':merge' => true,
						'page' => $i
					)) . '">' . $i . '</a>';
				} else {
					$html[] = '<b id="page">' . $i . '</b> ';
				}
				$count = $i;
			}
		}

		if ($count != $this->pageInfo->page_total) {
			$html[] = ($count < $this->pageInfo->page_total - 1 ? '<span>...</span>' : '') . '<a page="' . $this->pageInfo->page_total . '" href="' . $this->getUrl(null,
					array(
						':merge' => true,
						'page' => $this->pageInfo->page_total
					)) . '">' . $this->pageInfo->page_total . '</a>';
		}

		if ($this->pageInfo->page < $this->pageInfo->page_total) {
			$html[] = '<a page="' . ($this->pageInfo->page + 1) . '" href="' . $this->getUrl(null,
					array(
						':merge' => true,
						'page' => $this->pageInfo->page + 1
					)) . '">下一页</a>';
		} else {
			$html[] = '<b class="no">下一页</b>';
		}

		$html[] = '共 ' . $this->pageInfo->total . ' 条';
		$html[] = '</div>';
		return implode(' ', $html);
	}

	public function __toString() {
		return $this->result();
	}

	public function getUrl($op, $params = array()) {
		if (is_object($this->engine)) {
			return $this->engine->base->getUrl($op, $params);
		} else {
			unset($params[':merge']);
			return $this->requestBaseUrl . '?' . http_build_query(array_merge($this->requestParams, $params));
		}
	}
}

