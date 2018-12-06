<?php 

class PadLib_Curl {
	protected $_handler;
	protected $_url;
	protected $_urlParse;
	protected $_options = array();
	protected $_headers = array();
	
	static public function get($url, $options = array()){
		return (new self($url, $options))->execute();
	}
	
	public function __construct($url, $options = array()){
		$this->_url = $url;
		$this->_urlParse = parse_url($url);
		if (isset($this->_urlParse['port']) && $this->_urlParse['port'] != '80') {
			$this->_urlParse['host'] .= ':'.$this->_urlParse['port'];
		}
		
		$this->_options = $options;
		$this->_headers = array(
			'Accept-Encoding' => 'gzip,deflate',
			'Host' => $this->_urlParse['host'],
			'PRAGMA' => 'no-cache',
			'Cache-Control' => 'max-age=0',
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11',
			'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/' . '*' . ';q=0.8',
			'Accept-Charset' => 'gb2312,utf-8;q=0.7,*;q=0.7',
			'Accept-Language' => 'zh-cn,zh;q=0.5'
		);
	}
	
	/**
	 * 设置一个提交的header
	 * @param unknown_type $key
	 * @param unknown_type $value
	 * @return PadLib_Curl
	 */
	public function setHeader($key, $value){
		$this->_headers[$key] = $value;
		return $this;
	}
	
	public function setHeaders($headers){
		$this->_headers = array_merge($this->_headers, $headers);
		return $this;
	}
	
	public function setOptions($options){
		$this->_options = array_merge($this->_options, $options);
		return $this;
	}
	
	/**
	 * 获得一个配置
	 * @param unknown_type $key
	 * @return Ambigous <NULL, multitype:>
	 */
	public function getOption($key, $default = null){
		return isset($this->_options[$key]) ? $this->_options[$key] : $default;
	}
	
	/**
	 * 获得header的字符串连接
	 * @return string
	 */
	private function _getHeaderString(){
		$lines = array();
		foreach($this->_headers as $k => $v){
			if ($v !== null && $v !== false) {
				$lines[] = $k.': '.$v;
			}
		}
		return $lines;
	}
	
	public function execute(){
		$this->_handler = curl_init();
		
		curl_setopt($this->_handler, CURLOPT_URL, $this->_url);
		curl_setopt($this->_handler, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->_handler, CURLOPT_ENCODING, 'gzip');
		
		// https
		if ($this->_urlParse['scheme'] == 'https') {
			curl_setopt($this->_handler, CURLOPT_SSL_VERIFYPEER, false);
		}
		
		// cookieFile
		$cookieFile = $this->getOption('cookieFile');
		if ($cookieFile) {
			if (file_exists($cookieFile)) {
				curl_setopt($this->_handler, CURLOPT_COOKIEFILE, $cookieFile);
			}
			curl_setopt($this->_handler, CURLOPT_COOKIEJAR, $cookieFile);
		}
		
		// POST数据
		$postData = $this->getOption('postData');
		if ($postData) {
			curl_setopt($this->_handler, CURLOPT_POST, true);
			if (is_array($postData)) {
				$postData = http_build_query($postData);
			}
			curl_setopt($this->_handler, CURLOPT_POSTFIELDS, $postData);
		}
		
		// header设置
		curl_setopt($this->_handler, CURLOPT_HTTPHEADER, $this->_getHeaderString());
		curl_setopt($this->_handler, CURLOPT_REFERER, 'http://' . $this->_urlParse['host'] . '/');
		
		// 重试次数
		$tryCount = $this->getOption('retryCount', 1);
		curl_setopt($this->_handler, CURLOPT_TIMEOUT_MS, $this->getOption('timeout', 100000) / $tryCount);
		
		$responseContent = null;
		$responseInfo = null;
		$localProxy = $this->getOption('localProxy');
		$bodyMatchString = $this->getOption('bodyMatchString');
		while ($tryCount-- > 0) {
			// 代理地址
			$proxy = $this->getOption('proxy');
			$useProxy = null;
			if ($proxy) {
				$useProxy = (is_array($proxy) ? $proxy[array_rand($proxy)] : $proxy);
			}
			
			// 使用本地代理来连接代理服务器
			if ($useProxy) {
				if ($localProxy) {
					curl_setopt($this->_handler, CURLOPT_PROXY, $localProxy);
					$this->setHeader('Efproxy', $useProxy);
					curl_setopt($this->_handler, CURLOPT_HTTPHEADER, $this->_getHeaderString());
				} else {
					curl_setopt($this->_handler, CURLOPT_PROXY, $useProxy);
				}
			}
			
			$responseContent = curl_exec($this->_handler);
			$responseInfo = curl_getinfo($this->_handler);
			
			if ($responseContent === false) {
				continue;
			} else if ($responseContent !== false && $bodyMatchString && strpos($responseContent, $bodyMatchString) === false) {
				continue;
			}
			break;
		}
		curl_close($this->_handler);
		
		// 返回的数据结构
		$return = array(
			'http_code' => $responseInfo['http_code'],
			'info' => $responseInfo,
			'content' => $responseContent,
		);
		
		if ($responseContent !== false && $bodyMatchString && strpos($responseContent, $bodyMatchString) === false) {
			$responseContent = false;
			return $return;
		}
		
		// 返回
		return $this->_contentCharset($return);
	}
	
	/**
	 * 处理返回结果，UTF8编码等
	 * @param unknown_type $return
	 * @return string
	 */
	private function _contentCharset($return){
		// 指定要转换成的编码
		$contentCharset = $this->getOption('contentCharset');
		if (!$return['content'] || !$contentCharset) {
			return $return;
		}
		
		// mb和html中的编码关系映射
		$mbHtmlCharsetMapping = array(
			'utf8' => 'utf-8',
			'gbk' => 'gbk',
		);
		$htmlMbCharsetMapping = array_flip($mbHtmlCharsetMapping);
		
		// 替换返回的HTML
		$replaceContentMeta = function ($fromCharset, $toCharset) use ($mbHtmlCharsetMapping, $return) {
			$return['content'] = mb_convert_encoding($return['content'], $toCharset, $fromCharset);
			$htmlCharset = $mbHtmlCharsetMapping[$toCharset];
			$return['content'] = preg_replace('/charset=[a-zA-Z"\']+/', 'charset="'.$htmlCharset.'"', $return['content'], 1);
			return $return;
		};
		
		// 从返回的header或者body内容识别编码，并进行替换
		if (isset($return['info']['content_type']) && $return['info']['content_type']) {
			$contentType = strtolower($return['info']['content_type']);
			if ($contentType && preg_match('/charset=(.*?)$/', $contentType, $matches)) {
				$ctCharset = (isset($htmlMbCharsetMapping[$matches[1]]) ? $htmlMbCharsetMapping[$matches[1]] : $matches[1]);
				if ($ctCharset != $contentCharset) {
					$return = $replaceContentMeta($ctCharset, $contentCharset);
				}
			}
		}
	
		return $return;
	}
}



