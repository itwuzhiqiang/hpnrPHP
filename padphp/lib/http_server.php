<?php

/**
 * Created by PhpStorm.
 * User: macmini
 * Date: 16/2/10
 * Time: 下午12:27
 */
class PadLib_HttpServer {
	private $bootfile;
	private $server;
	private $options = array();

	public function __construct($bootFile, $options = array()) {
		$this->bootfile = $bootFile;
		$this->bootdir = dirname($bootFile);

		$configFile = dirname($this->bootfile).'/server-config.php';
		if (file_exists($configFile)) {
			$this->options = include($configFile);
		}
		$this->options = array_merge(array(
			'serverHost' => '0.0.0.0',
			'serverPort' => '8333',
			'reconfig' => array(),
		), $this->options, $options);

		// 覆盖当前环境的配置
		foreach ($this->options['reconfig'] as $k => $v) {
			$GLOBALS['pad_core']->configs[$k] = $v;
		}

		$this->server = new swoole_http_server($this->options['serverHost'], $this->options['serverPort']);
		$this->server->set(array(
			'worker_num' => $this->getOption('workerNum', 5),
			'daemonize' => $this->getOption('daemonize', 0),
			'max_request' => $this->getOption('maxRequest', 500),
			'package_max_length' => 1024*1024*30,
			'buffer_output_size' => 1024*1024*30,
		));

		pprint('Listen: ' . $this->options['serverHost'] . ':' . $this->options['serverPort']);

		$this->server->on('WorkerStart', array($this, 'onWorkerStart'));
		$this->server->on('Request', array($this, 'onRequest'));
		$this->server->start();
	}

	private function getOption($key, $default = null) {
		return isset($this->options[$key]) ? $this->options[$key] : $default;
	}

	public function onWorkerStart () {
		register_shutdown_function(function () {
			$lastError = error_get_last();
			$output = array();
			foreach ($lastError as $key => $message) {
				$output[] = $key . ': ' . $message;
			}
			$this->server->lastResponse->end(implode("<br>\n", $output));
		});
	}

	public function onRequest ($request, $response) {
		//pprint('request:', $request->server['request_uri']);

		foreach ($this->options['location'] as $location => $newDir) {
			if (strpos($request->server['request_uri'], $location) === 0) {
				$this->locationOther($request, $response, $location, $newDir);
				return;
			}
		}

		$this->server->lastRequest = $request;
		$this->server->lastResponse = $response;

		$_SERVER['REMOTE_ADDR'] = $request->server['remote_addr'];
		$_SERVER['HTTP_HOST'] = $request->header['host'];
		$_SERVER['REQUEST_URI'] = $request->server['request_uri'];

		$headerList = array();
		$mvc = new PadMvc($this->bootfile, null, array(
			'gGet' => (isset($request->get) ? $request->get : array()),
			'gPost' => (isset($request->post) ? $request->post : array()),
			'gFiles' => (isset($request->files) ? $request->files : array()),
			'headerCallback' => function ($string, $replace, $code) use (&$headerList) {
				$headerList[] = array($string, $replace, $code);
			},
		));
		$GLOBALS['pad_core']->mvc = $mvc;

		ob_start();
		$mvc->process();
		$content = ob_get_clean();

		foreach ($headerList as $item) {
			list($string, $replace, $code) = $item;
			if (strpos($string, ':') !== false) {
				list($k, $v) = explode(': ', $string, 2);
				$response->header($k, $v);
			} else if (strpos($string, 'HTTP') === 0) {
				list($httpCode, $statusCode, $msg) = explode(' ', $string, 3);
				$response->status($statusCode);
			}
		}
		$response->end($content);
	}

	private function locationOther ($request, $response, $location, $toLocation) {
		$filePath = $toLocation . str_replace($location, '', $request->server['request_uri']);
		$filePath = realpath($filePath);

		if (is_dir($filePath)) {
			$response->status(403);
			$response->end('<h1>Is Dir</h1>');
		} else if (!file_exists($filePath)) {
			$response->status(404);
			$response->end('<h1>File Not Found</h1>');
		} else {
			$mimeType = $this->getMimeType($filePath);
			$response->header('Content-Type', $mimeType);
			$response->end(file_get_contents($filePath));
		}
	}

	private function getMimeType ($filePath) {
		$ext = $filePath;
		if (strpos($ext, '.') !== false) {
			$ext = pathinfo($filePath, PATHINFO_EXTENSION);
		}

		$extList = array(
			'js' => 'application/x-javascript; charset=utf-8',
			'css' => 'text/css; charset=utf-8',
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'html' => 'text/html; charset=utf-8',
			'bin' => 'application/octet-stream',
			'woff' => 'application/font-woff',
			'woff2' => 'application/font-woff',
		);
		return isset($extList[$ext]) ? $extList[$ext] : $extList['bin'];
	}
}


