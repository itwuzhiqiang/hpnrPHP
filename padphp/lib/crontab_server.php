<?php

class PadLib_CrontabServer {
	private $options = array();
	private $server;
	private $crontabAtomics = array();

	public function __construct ($options = array()) {
		$this->initOptions($options);

		$this->server = new swoole_http_server($this->options['host'], $this->options['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
		$this->server->set(array(
			'worker_num' => 5,
			'task_worker_num' => 2,
			'dispatch_mode' => 4,
			'max_request' => 50,
			'daemonize' => ($this->options['daemonize'] ? true : false),
		));

		for ($i = 0; $i < 32; $i++) {
			$this->crontabAtomics[] = new swoole_atomic(0);
		}

		$this->server->on('WorkerStart', array($this, 'onWorkerStart'));
		$this->server->on('Task', array($this, 'onTask'));
		$this->server->on('Finish', array($this, 'onFinish'));
		$this->server->on('Request', array($this, 'onRequest'));
	}

	public function onWorkerStart($server, $workerId){
		if (!$server->taskworker) {
		} else {
			$server->tick(1000, function ($id) use ($server) {
				$this->executeCrontab();
			});
		}
	}

	public function onTask($server, $taskId){

	}

	public function onFinish($server, $taskId){

	}

	public function onRequest ($request, $response) {
		$requestUri = $request->server['request_uri'];

		if ($requestUri == '/status') {
			$response->end(json_encode(array(
				'crontabs' => $this->options['crontabs'],
			)));
		} else {
			$response->end(json_encode(array(
				'status' => 'error',
				'message' => 'not method',
			)));
		}
	}

	public function executeCrontab () {
		$crontabs = array();
		if (isset($this->options['config'])) {
			$fOptions = include($this->options['config']);
			$crontabs = $fOptions['crontabs'];
		}

		foreach ($crontabs as $index => $crontab) {
			$second = 0;
			$minute = 0;
			$hour = 0;
			$hit = false;

			if (preg_match('/(\d+)s/', $crontab[0], $match)) {
				$second = $match[1];
			} else if (preg_match('/(\d+)i/', $crontab[0], $match)) {
				$minute = $match[1];
			} else if (preg_match('/(\d+)h/', $crontab[0], $match)) {
				$hour = $match[1];
			}

			if ($hour > 0 && date('h') % $hour == 0 && date('i') == 1 && date('s') == 1) {
				$hit = true;
			} else if ($minute > 0 && date('i') % $minute == 0 && date('s') == 1) {
				$hit = true;
			} else if ($second > 0 && date('s') % $second == 0) {
				$hit = true;
			}

			if (!$hit) {
				continue;
			}

			// 有在运行的任务
			$number = $this->crontabAtomics[$index]->add(1);
			if ($number > 1) {
				$this->crontabAtomics[$index]->sub(1);
				continue;
			}

			ob_start();
			system($_SERVER['_'] . ' ' . $crontab[2]);
			$output = ob_get_clean();

			$logLine = array();
			$logLine[] = date('Y-m-d H:i:s') . ' PadCrontab:' . $_SERVER['_'] . ' ' . $crontab[2];
			$logLine[] = "\n";
			$logLine[] = $output;

			if ($this->options['logDir']) {
				$file = $this->options['logDir'] . DIRECTORY_SEPARATOR . date('YmdH') . '.log';
				file_put_contents($file, implode('', $logLine), FILE_APPEND);
			} else {
				pprint(implode('', $logLine));
			}

			// 任务数量减1
			$this->crontabAtomics[$index]->sub(1);

			pprint(date('Y-m-d H:i:s'), 'Task', $crontab[1].' '.$crontab[2], 'Finish');
		}
	}

	public function initOptions ($options) {
		if (isset($options['config'])) {
			$fOptions = include($options['config']);
			$options = array_merge($fOptions, $options);
		}
		$options = array_merge(array(
			'host' => '0.0.0.0',
			'port' => '6533',
			'daemonize' => false,
			'crontabs' => array(),
			'logDir' => false,
		), $options);
		$this->options = $options;
	}

	public function start () {
		$this->server->start();
	}
}
