<?php

class PadLib_Pcurl {
	private $_yOptions;
	private $_options;
	private $_jobIsClear = false;
	private $_jobList = array();
	private $_curlMulti;
	private $_result = array();

	static public function request ($url, $params = array()) {
		$curl = new self();
		return call_user_func_array(array($curl, 'get'), func_get_args());
	}
	
	public function __construct($options = array()){
		$this->_yOptions = $options;
		$this->_options = array_merge(array(
			// 同时进行的任务数
			'workerMaxNum' => 20,
			// select的超时时间
			'workerSelectTimeout' => 200,
			// 默认的获取到的代理(callback)
			'getProxy' => null,
			// 默认的拦截器([cbk_before, cbk_after])
			'intercept' => null,
			// worker默认的参数
			'workerOptions' => array(
				// 超时时间
				'timeout' => 3000,
				// 默认的代理地址
				'proxy' => array(),
				// 重试次数
				'retryNum' => 5,
				// 使用中转代理
				'proxyConfig' => null,
				/*'proxyConfig' => array(
					'type' => 'agent',
					'agent' => 'http://127.0.0.1:8061/',
				),*/
			),
		), $options);
		$this->_options['workerOptions'] = array_merge(array(
			'timeout' => 6000,
			'retryNum' => 5,
			'proxy' => array(),
			'proxyConfig' => null,
		), $this->_options['workerOptions']);
		$this->_curlMulti = curl_multi_init();
	}
	
	public function withCookie ($cookieData, $params = array()) {
		$defaultParams = array(
			'type' => 'net_header',
		);
		$params = array_merge($defaultParams, $params);
		
		$cookieList = array();
		if ($params['type'] == 'net_header') {
			$list = explode('; ', $cookieData);
			foreach ($list as $line) {
				list($k, $v) = explode('=', $line, 2);
				$cookieList[] = array(
					'name' => $k,
					'value' => $v
				);
			}
		}
		
		$tempFile = new PadLib_TempFile();
		$cookieFile = $tempFile->getPath();
		foreach ($cookieList as $item) {
			$domain = (isset($params['domain']) ? $params['domain'] : '.none.com');
			$path = (isset($item['path']) ? $item['path'] : '/');
			$expireTime = (isset($item['expire_time']) ? $item['expire_time'] : 0);
			$rows = array($domain, 'TRUE', $path, 'FALSE', ceil($expireTime), $item['name'], $item['value']);
			file_put_contents($cookieFile, implode("\t", $rows)."\n", FILE_APPEND);
		}
		$this->_options['workerOptions']['withCookie'] = $cookieFile;
	}
	
	public function newly(){
		return new PadLib_Pcurl($this->_yOptions);
	}
	
	public function get($url, $options = array()){
		$return = null;
		$returnInfo = null;
		$this->add($url, function($data, $content, $info) use (&$return, &$returnInfo) {
			$return = $content;
			$returnInfo = $info;
		}, $options);
		$this->exec();
		return array($return, $returnInfo);
	}
	
	public function getJson ($url, $options = array()) {
		return $this->getWith($url, function ($data, $content, $info) {
			if (!$content) {
				return $data['statusRetry'];
			}
			
			$content = trim($content,chr(239).chr(187).chr(191));
			$json = @json_decode($content, true);
			if (json_last_error()) {
				return $data['statusRetry'];
			}
			
			return $json;
		}, $options);
	}
	
	public function getWith($url, $callback, $options = array()){
		$return = null;
		$this->add($url, function($data, $content, $info) use (&$return, &$callback) {
			$return = $callback($data, $content, $info);
		}, $options);
		$this->exec();
		return $return;
	}
	
	public function add($url, $callback, $options = array()){
		$id = uniqid();
		$index = count($this->_jobList);
		$options = array_merge(array(
			'index' => $index,
			'url' => $url,
			'callback' => $callback,
		), $this->_options['workerOptions'], $options);
		
		// 代理判断
		$options['proxy'] = (array) $options['proxy'];
		
		$this->_jobList[] = $options;
	}
	
	private function _curlMultiExec(&$stillRunning) {
		static $startTime;
		if (!isset($startTime)) {
			$startTime = microtime(true) * 1000;
		}
		
		do {
			$rv = curl_multi_exec($this->_curlMulti, $stillRunning);
			usleep(10 * 1000);
		} while ($rv == CURLM_CALL_MULTI_PERFORM);
		return $rv;
	}
	
	/**
	 * 前置拦截器
	 * @param unknown $url
	 * @return multitype:boolean |mixed
	 */
	private function interceptBefore ($url) {
		if (!$this->_options['intercept'] || !isset($this->_options['intercept'][0]) || !$this->_options['intercept'][0]) {
			return array(false, false);
		}
		$intercept = $this->_options['intercept'][0];
		$res = call_user_func_array($intercept, array($url));
		if (is_array($res)) {
			return $res;
		} else {
			return array(false, false);
		}
	}

	/**
	 * 后置拦截器
	 * @param unknown $status
	 * @param unknown $url
	 * @param unknown $content
	 * @param unknown $info
	 * @return multitype:boolean
	 */
	private function interceptAfter ($status, $url, $content, $info) {
		if (!$this->_options['intercept'] || !isset($this->_options['intercept'][1]) || !$this->_options['intercept'][1]) {
			return array(false, false);
		}
		$intercept = $this->_options['intercept'][1];
		call_user_func_array($intercept, array($status, $url, $content, $info));
	}
	
	public function exec() {
		$activeCurlNum = null;
		$curlList = array();
		
		$funcPushJob = function ($newJob) use (&$curlList) {
			$url = $newJob['url'];
			$isIntercepted = false;
			list ($itContent, $itHttpInfo) = $this->interceptBefore($url);
			if ($itContent !== false) {
				$isIntercepted = true;
			}
			
			list($curl, $curlReturn) = $this->newCurl($newJob['url'], $newJob);
			if (!$isIntercepted) {
				curl_multi_add_handle($this->_curlMulti, $curl);
			}
			$newJob['data']['curlReturn'] = $curlReturn;
			$data = array(
				'statusRetry' => 'STATUS::CONTENT_ERROR',
				'url' => $newJob['url'],
				'pcurl' => $this,
				'curl' => $curl,
			);
			if (isset($newJob['data'])) {
				$data = array_merge($data, $newJob['data']);
			}
			
			$return = array(
				'handler' => $curl,
				'callback' => isset($newJob['callback']) ? $newJob['callback'] : function () {},
				'data' => $data,
				'jobOptions' => $newJob,
			);
			if (!$isIntercepted) {
				$curlList[intval($curl)] = $return;
				array_shift($curlList[intval($curl)]['jobOptions']['proxy']);
			} else {
				$this->execCallback($return['callback'], $return['data'], $itContent, $itHttpInfo, true);
			}
			return $return;
		};
		
		$preExecTime = microtime(true)*10000;
		while (count($this->_jobList) > 0) {
			$curlList = array();	
			do {
				$popJobMax = $this->_options['workerMaxNum'] - intval($activeCurlNum);
				while ($popJobMax-- > 0) {
					$newJob = array_shift($this->_jobList);
					if ($newJob) {
						$funcPushJob($newJob);
					}
				}
				
				$selected = null;
				$execRv = null;
				if ($activeCurlNum !== null) {
					$selected = curl_multi_select($this->_curlMulti, 0.5);
				}
				$execRv = $this->_curlMultiExec($activeCurlNum);
				
				$content = false;
				while (($selected > 0 || $selected === null) && ($info = curl_multi_info_read($this->_curlMulti)) !== false) {
					$content = false;
					if ($info['result'] == CURLE_OK) {
						$content = curl_multi_getcontent($info['handle']);
					}
					$httpInfo = curl_getinfo($info['handle']);
					curl_multi_remove_handle($this->_curlMulti, $info['handle']);
					$index = intval($info['handle']);
					// 有些是之前的线程任务
					if (!isset($curlList[$index])) {
						continue;
					}
					
					$jobInfo = $curlList[$index];
					$res = null;
					if ($content !== false && $content !== null) {
						if (preg_match('/(text\/html|text\/plain)/', $httpInfo['content_type'])) {
							$content = $this->_contentCharset($content, $httpInfo);
						}
						$res = $this->execCallback($jobInfo['callback'], $jobInfo['data'], $content, $httpInfo);
					}
					
					
					if ($content === false || $content === null || $res == 'STATUS::CONTENT_ERROR') {
						if ($this->_jobIsClear) {
							$this->execCallback($jobInfo['callback'], $jobInfo['data'], false, false);
						} else {
							if ($jobInfo['jobOptions']['retryNum'] > 1) {
								$jobInfo['jobOptions']['retryNum']--;
								$this->_jobList[] = $jobInfo['jobOptions'];
							} else {
								$this->execCallback($jobInfo['callback'], $jobInfo['data'], false, false);
							}
						}
					}
					unset($curlList[$index]);
				}
			} while ($activeCurlNum);
			
			// 有些任务是直接退出的，select不到
			foreach ($curlList as $index => $jobInfo) {
				if ($jobInfo['jobOptions']['retryNum'] > 1 && !$this->_jobIsClear) {
					$jobInfo['jobOptions']['retryNum']--;
					$this->_jobList[] = $jobInfo['jobOptions'];
				} else {
					$this->execCallback($jobInfo['callback'], $jobInfo['data'], false, false);
				}
				curl_multi_remove_handle($this->_curlMulti, $jobInfo['handler']);
				unset($curlList[$index]);
			}
			
			// 执行时间
			$preExecTime = microtime(true)*10000;
		}
		$return = $this->_result;
		$this->reset();
		return $return;
	}
	
	/**
	 * 清楚所有未运行的任务
	 */
	public function clearAll(){
		$this->_jobIsClear = true;
		$jobList = $this->_jobList;
		$this->_jobList = array();
		$this->_result = array();
		
		foreach ($jobList as $jobInfo) {
			$data = array(
				'statusRetry' => 'STATUS::CONTENT_ERROR',
				'url' => $jobInfo['url'],
				'pcurl' => $this,
				'curl' => null,
			);
			$this->execCallback($jobInfo['callback'], $data, false, false);
		}
		$this->_result = array();
	}
	
	/**
	 * 清空执行的结果等
	 */
	public function reset(){
		$this->_jobList = array();
		$this->_result = array();
	}
	
	public function execCallback($callback, $data, $content, $info, $isIntercept = false){
		$setParams = null;
		$ret = null;
		if (gettype($callback) == 'object') {
			$ret = $callback($data, $content, $info);
		} else if (is_array($callback) || is_string($callback)) {
			if (isset($callback[2])) {
				$setParams = array_pop($callback);
			}
			$ret = call_user_func_array($callback, array($data, $content, $info, $setParams));
		}
		$return = $ret;
		
		$statusString = 'PCURL_FETCH';
		if ($isIntercept) {
			$statusString = 'INTERCEPT';
		}
		
		if ($ret == 'STATUS::CONTENT_ERROR') {
			$this->interceptAfter($statusString, $data['url'], false, false);
			$ret = false;
		} else {
			$this->interceptAfter($statusString, $data['url'], $content, $info);
		}
		
		$this->_result[md5($data['url'])] = array($ret, array(
			'setParams' => $setParams,
			'url' => $data['url'],
		));
		return $return;
	}
	
	private function getCurlHeaderString($urlParse, $headers = array()){
		$headers = array_merge(array(
			'Host' => $urlParse['host'],
			'Cache-Control' => 'max-age=0',
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36',
			'Accept' => 'text/html,text/plain,application/xhtml+xml,application/xml;q=0.9,*/' . '*' . ';q=0.8',
			'Accept-Language' => 'zh-CN,en;q=0.8'
		), $headers);
	
		$lines = array();
		foreach($headers as $k => $v){
			if ($v !== null && $v !== false) {
				$lines[] = $k.': '.$v;
			}
		}
		return $lines;
	}
	
	private function newCurl($url, $options = array()) {
		$options = array_merge(array(
			'timeout' => 6000,
			'proxyConfig' => null,
		), $options);
		$urlParse = parse_url($url);
		$curl = curl_init($url);

		// 强制使用IPV4
		curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	
		if ($urlParse['scheme'] == 'https') {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}

		if (isset($options['withCookie'])) {
			$cookieFile = '/tmp/pcurl.cookie.'.md5(__FILE__);
			if (is_file($options['withCookie'])) {
				$cookieFile = $options['withCookie'];
			}
			
			curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);
			curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile);
		}
		
		if (isset($options['post'])) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $options['post']);
		}

		if (isset($options['basicAuth'])) {
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $options['basicAuth']);
		}
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
		if (defined('CURLOPT_TIMEOUT_MS')) {
			curl_setopt($curl, CURLOPT_TIMEOUT_MS, $options['timeout']);
		} else {
			curl_setopt($curl, CURLOPT_TIMEOUT, ceil($options['timeout']/1000));
		}

		if (isset($options['referer'])) {
			curl_setopt($curl, CURLOPT_REFERER, $options['referer']);
		} else {
			curl_setopt($curl, CURLOPT_REFERER, 'http://' . $urlParse['host'] . '/');
		}

		$headersAdd = array();
		if (isset($options['headers'])) {
			$headersAdd = array_merge($headersAdd, $options['headers']);
		}
		
		$proxyReal = null;
		if ((isset($options['proxy']) && $options['proxy']) || $this->_options['getProxy']) {
			$proxyReal = null;
			if ($this->_options['getProxy']) {
				$proxyReal = call_user_func_array($this->_options['getProxy'], array($urlParse));
			} else if ($options['proxy']) {
				$proxyReal = array_shift($options['proxy']);
			}
			
			$isUseAgent = false;
			if (isset($options['proxyConfig']) && is_array($options['proxyConfig'])) {
				$proxyConfig = $options['proxyConfig'];
				if ($proxyConfig['type'] == 'agent') {
					curl_setopt($curl, CURLOPT_PROXY, $proxyConfig['agent']);
					$headersAdd['Efproxy'] = $proxyReal;
					$isUseAgent = true;
				}
			}
			
			if (!$isUseAgent) {
				curl_setopt($curl, CURLOPT_PROXY, $proxyReal);
			}
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getCurlHeaderString($urlParse, $headersAdd));

		// debug信息
		assert('$GLOBALS[\'pad_core\']->debug->write(\'fetch_url\', ($proxyReal ? $proxyReal.\' @ \' : \'\').$url, 0);');
		return array($curl, array(
			'useProxy' => $proxyReal,
		));
	}
	
	private function _contentCharset($content, $httpInfo, $fromCharset = 'ASCII,gbk,utf-8'){
		if (isset($httpInfo['content_type']) && strpos(strtolower($httpInfo['content_type']), 'utf-8') !== false) {
		} else if (strpos($httpInfo['content_type'], 'charset') != false) {
			$content = mb_convert_encoding($content, 'utf-8', $fromCharset);
		}
		if (preg_match('/<body[^>]*>(.*)<\/(body|html)>/mis', $content, $match)) {
			$content = $match[1];
			$content = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>'
			. $content .'</body></html>';
			
		}
		return $content;
	}
}

