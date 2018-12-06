<?php

class PadLib_SimpleMessageServer {
	public $options;
	public $redis;

	private $_eventKey;
	private $_server;
	private $_redis;
	private $_workerData = array();

	/**
	 * 发送一个消息
	 *
	 * @param string $sign 签名 字符串类型
	 * @param string $type 类型 字符串类型
	 * @param mixed $data 数据 任意类型
	 */
	static public function sendMessage ($sign, $type, $data, $config = array()) {
		$config = array_merge(array(
			'redisHost' => '127.0.0.1',
			'redisPort' => '6379',
		), $config);

		vcheck($config, 'key => !empty', 'KEY不存在');
		$key = $config['key'];

		$redis = new Redis();
		$redis->connect($config['redisHost'], $config['redisPort']);
		$redis->publish($key . '.' . $sign, json_encode(array(
			'key' => $type,
			'data' => $data,
		)));
	}

	static public function startServer ($options = array()) {
		$server = new self($options);
		$server->start();
		return $server;
	}

	public function __construct ($options) {
		$this->options = $options;

		$this->_eventKey = $options['key'];
		$this->_server = new swoole_websocket_server($options['host'], $options['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
		$this->_server->set(array(
			'worker_num' => 5,
			'dispatch_mode' => 4,
			'max_request' => 50,
			'daemonize' => $this->options['daemonize'],
		));

		// http 接口
		$this->_server->on('Request', function($request, $response) {
			$requestUri = $request->server['request_uri'];

			if ($requestUri == '/status') {
				$response->end(json_encode(array(
					'activeClientNumber' => count($this->_server->workerData),
				)));
			} else {
				$response->end(json_encode(array(
					'status' => 'error',
					'message' => 'not method',
				)));
			}
		});

		// websocket暴露的接口
		$this->_server->on('WorkerStart', array($this, 'onWorkerStart'));
		$this->_server->on('WorkerStop', array($this, 'onWorkerStop'));
		$this->_server->on('Open', array($this, 'onOpen'));
		$this->_server->on('Message', array($this, 'onMessage'));
		$this->_server->on('Close', array($this, 'onClose'));

		//declare(ticks = 1);
		//pcntl_signal(SIGTERM, array($this, 'signalHandler'));
		//pcntl_signal(SIGINT, array($this, 'signalHandler'));
	}

	public function signalHandler () {
		//swoole_process::wait(false);
		//exit;
	}

	public function onWorkerStart ($server, $workerId) {
		// 保存客户端连接
		$workerData = new swoole_table(1024 * 100);
		$workerData->column('fd', swoole_table::TYPE_INT);
		$workerData->column('worker_id', swoole_table::TYPE_INT);
		$workerData->column('sign', swoole_table::TYPE_STRING, 64);
		$workerData->create();
		$server->workerData = $workerData;

		$server->redis = $this->getRedis();
		if ($workerId >= $server->setting['worker_num']) {
			$this->consoleLog('task worker['.$workerId.'] start');
		} else {
			$this->consoleLog('worker['.$workerId.'] start');
			$this->startRedisEvent($server, $workerId);
		}

		register_shutdown_function(function(){});
	}

	public function onWorkerStop ($server, $workerId) {
	}

	public function startRedisEvent ($server, $workerId) {
		$server->redis = new swoole_redis();
		$server->redis->on('message', function ($client, $result) use ($server, $workerId) {
			if ($result[0] == 'pmessage') {
				list($type, $mkey, $key, $data) = $result;
				$sign = str_replace($this->_eventKey.'.', '', $key);
				foreach ($server->workerData as $id => $row) {
					if ($row['sign'] == $sign && $workerId == $row['worker_id']) {
						$server->push($row['fd'], $data);
					}
				}
			}
		});
		$server->redis->connect($this->options['redisHost'], $this->options['redisPort'], function ($client) {
			$client->pSubscribe($this->_eventKey.'.*');
		});
	}

	public function onOpen($server, $request) {
		$this->consoleLog('client open');
		$server->workerData->set($request->fd, array(
			'fd' => $request->fd,
			'worker_id' => $server->worker_id,
			'sign' => $request->get['sign'],
		));
	}

	public function onMessage($server, $request) {
		$data = json_decode($request->data);
		if (isset($this->options['onClientMessage'])) {
			if (isset($data->event_type) && isset($data->data)) {
				$wdata = $server->workerData->get($request->fd);
				call_user_func_array($this->options['onClientMessage'], array($wdata['sign'], $data->event_type, $data->data));
			}
		}
	}

	public function onClose($server, $fd) {
		$server->workerData->del($fd);
		$this->consoleLog('client close');
	}

	public function getRedis () {
		$redis = new Redis();
		$redis->connect($this->options['redisHost'], $this->options['redisPort']);
		return $redis;
	}

	public function consoleLog () {
		echo implode("\t", func_get_args()) . "\n";
	}

	public function start () {
		$this->_server->start();
	}

	public function __destruct () {
		//swoole_process::wait(true);
	}
}

