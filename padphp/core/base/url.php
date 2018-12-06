<?php

class PadBaseUrl {

	static public function getMainHost($url) {
		$domain = $url;
		if (strpos($url, 'http') === 0) {
			$parseUrl = parse_url($url);
			$domain = $parseUrl['host'];
		}
		
		if (! preg_match('/((?:(?:25[0-5]|2[0-4]\d|[01]?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d?\d))/', $domain)) {
			$tmpdomain = explode('.', $domain);
			$pos = 2;
			if (substr($domain, - 7) == '.com.cn') {
				$pos = 3;
			}
			return '.' . implode('.', array_slice($tmpdomain, count($tmpdomain) - $pos));
		} else {
			return $domain;
		}
	}

	static public function getBrowserByAgent($userAgent) {
		$userAgent = strtolower($userAgent);
		if (strpos($userAgent, 'firefox') !== false) {
			return 'firefox';
		} elseif (strpos($userAgent, 'msie 8.0') !== false) {
			return 'ie8';
		} elseif (strpos($userAgent, 'msie 7.0') !== false) {
			return 'ie7';
		} elseif (strpos($userAgent, 'msie 6.0') !== false) {
			return 'ie6';
		} else {
			return 'unknown';
		}
	}

	static public function getContent($url, $params = array()) {
		$userAgent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
		
		$parseUrl = parse_url($url);
		$headers = array(
			'Accept-Encoding: gzip,deflate',
			'Host: ' . $parseUrl['host'],
			'PRAGMA: no-cache',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:2.0.0.16) Gecko/2009120208 Firefox/3.1.16',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9',
			'Accept-Charset: gb2312,utf-8;q=0.7,*;q=0.7',
			'Accept-Language: zh-cn,zh;q=0.5'
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		if (strpos($url, 'https:/'.'/') === 0) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/shm/curl.' . md5(__file__));
		curl_setopt($ch, CURLOPT_COOKIEJAR, '/dev/shm/curl.' . md5(__file__));
		$result = curl_exec($ch);
		
		if (isset($params['drop_htmlspchar'])) {
			$result = preg_replace('/&.*?;/', '', $result);
		}
		
		if (isset($params['charset']) && $params['charset'] != 'utf-8') {
			return mb_convert_encoding($result, 'utf-8', $params['charset']);
		} else {
			return $result;
		}
	}

	static public function getDom($url, $params = array()) {
		$body = self::getContent($url, $params);
		if (preg_match('/<body.*?>(.*)<\/body>/is', $body, $match)) {
			$body = $match[1];
		}
		$body = mb_convert_encoding($body, 'HTML-ENTITIES', "UTF-8");
		
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->preserveWhiteSpace = false;
		$dom->strictErrorChecking = false;
		@$dom->loadHTML('<html><body>' . $body . '</body></html>');
		
		return $dom;
	}
}



