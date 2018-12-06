<?php

class PadBaseGmtask {

	public $configs = array();

	public $dbhandler;

	public $workerFunctions = array();

	public $bufferContext = array();

	public function __construct($configs = array()) {
		$this->configs = array_merge(
				array(
					'gearman_servers' => array(
						'127.0.0.1:4730'
					),
					'db_name' => 'default',
					'db_table_prefix' => 'gmtask',
					'php_exec' => '/software/php/bin/php',
					'worker_dir' => null,
					'worker_class_prefix' => 'GmTask_',
					'worker_exec_count' => 10
				), $configs);
		$this->dbhandler = $GLOBALS['pad_core']->database->getPerform($this->configs['db_name']);
		
		/**
		 * 获得可以执行的functions
		 */
		$dir = dir($this->configs['worker_dir']);
		PadCore::autoload($this->configs['worker_class_prefix'], $this->configs['worker_dir']);
		
		while (false !== ($entry = $dir->read())) {
			if (strpos($entry, '.') !== 0) {
				$name = str_replace('.php', '', $entry);
				$models[] = str_replace('.php', '', $entry);
			}
		}
		$dir->close();
		
		foreach ($models as $name) {
			$className = $this->configs['worker_class_prefix'] . PadBaseString::padStrtoupper($name);
			if (PadAutoload::classExists($className)) {
				$methods = get_class_methods($className);
				foreach ($methods as $method) {
					if (strpos($method, '_do_') === 0) {
						$this->workerFunctions[$name . '::' . substr($method, 4)] = array(
							$className,
							substr($method, 4)
						);
					}
				}
			}
		}
	}

	/**
	 * 获得client
	 */
	public function getClient() {
		static $obj;
		if (! isset($obj)) {
			$obj = new GearmanClient();
			foreach ($this->configs['gearman_servers'] as $server) {
				list ($host, $port) = explode(':', $server);
				$obj->addServer($host, $port);
			}
		}
		return $obj;
	}

	/**
	 * 获得worker
	 */
	public function getWorker() {
		static $obj;
		if (! isset($obj)) {
			$obj = new GearmanWorker();
			foreach ($this->configs['gearman_servers'] as $server) {
				list ($host, $port) = explode(':', $server);
				$obj->addServer($host, $port);
			}
		}
		return $obj;
	}

	/**
	 * 添加执行对象
	 */
	public function add($gmFunction, $function, $params = array()) {
		if (! isset($this->workerFunctions[$function])) {
			$GLOBALS['pad_core']->error(E_ERROR, 'not found worker function "%s"', $function);
		}
		
		$uniqid = md5(uniqid());
		$this->dbhandler->insert($this->configs['db_table_prefix'], 
				array(
					'id' => $uniqid,
					'function' => $function,
					'params' => serialize($params),
					'create_time' => time(),
					'status' => 1
				));
		
		$client = $this->getClient();
		$params = $uniqid . serialize($params);
		
		if ($gmFunction == 'do') {
			$return = $client->do($function, $params);
			return unserialize($return);
		} elseif ($gmFunction == 'do_background') {
			$client->doBackground($function, $params);
			return true;
		} else {
			$GLOBALS['pad_core']->error(E_ERROR, 'gmFunction error "%s"', $gmFunction);
		}
	}

	/**
	 * worker运行
	 */
	public function workerRun($bootfile) {
		global $argv;
		if (in_array('@worker_execute@', $argv)) {
			$this->workerExecute();
		} else {
			$commend = $bootfile;
			if ($argv) {
				$commend .= ' ' . implode(' ', $argv);
			}
			if (0) {
				exec($this->configs['php_exec'] . ' ' . $commend . ' @worker_execute@', $output);
				$this->dbhandler->insert($this->configs['db_table_prefix'] . '_worker', 
						array(
							'commend' => $commend,
							'output' => implode("\n", $output),
							'create_time' => time()
						));
			} else {
				system($this->configs['php_exec'] . ' ' . $commend . ' @worker_execute@');
			}
		}
	}

	/**
	 * worker执行
	 */
	public function workerExecute() {
		$worker = $this->getWorker();
		
		$classMaps = array();
		foreach ($this->workerFunctions as $functionName => $item) {
			$className = $item[0];
			$classMaps[$item[0]] = new $className();
			$classMaps[$item[0]]->gmworker = $this;
		}
		
		foreach ($this->workerFunctions as $functionName => $item) {
			$worker->addFunction($functionName, array(
				$classMaps[$item[0]],
				$item[1]
			));
		}
		
		/**
		 * 执行规定的次数
		 */
		while ($this->configs['worker_exec_count'] -- > 0) {
			$worker->work();
		}
	}

	public function console() {
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$pageSize = 30;
		$tasklist = $this->dbhandler->getAll(
				'
			select * from ' . $this->configs['db_table_prefix'] .
						 ' order by status asc, create_time desc ' . 'limit ' . $pageSize . ' offset ' . ($page - 1) * $pageSize . '
		');
		$pageInfoString = strval(
				new PadMvcHelperPager(
						array(
							'page' => $page,
							'page_size' => $pageSize,
							'total' => $this->dbhandler->getOne('select count(*) from ' . $this->configs['db_table_prefix'])
						)));
		
		echo '<html>
		<meta http-equiv="content-type" content="text/html;charset=utf-8">
		<title>GmTask Console</title>
		<style>
		html, body, table {padding:0px; margin:0px; font:12px normal Verdana,Arial,sans-serif;}
		table {padding:0px; margin:0px; width:100%; border:1px solid #888; border-left:0px; border-bottom:0px;}
		td, th {padding:2px; margin:0px; border:1px solid #888; border-top:0px; border-right:0px;}
		th {text-align:left;}
		.pager {height:26px; text-align:right; line-height:26px; font-size:12px; color:#888;}
		.pager a{border:1px solid #888; font-size:12px; padding:0 3px 0 3px; color:#888; text-decoration:none;}
		.pager a:hover{border:1px solid #FF6600;}
		</style>
		<body><center><div style="margin:0px auto; text-align:left; width:960px;">';
		echo '<div style="height:28px; background:#333; padding: 0 0 0 5px; color:#FFF; line-height:28px;"><b>GmTask List:</b></div>';
		
		echo '<div class="pager">', $pageInfoString, '</div>';
		echo '<table cellpadding="0" cellspacing="0" border="1">';
		echo '<tr>
		<th>ID</th>
		<th>Function</th>
		<th>Start Time</th>
		<th>Exec Time</th>
		<th>Status</th>
		<th>Console</th>
		</tr>';
		$statusArray = array(
			'0' => 'process',
			'1' => 'wait',
			'2' => 'finish'
		);
		foreach ($tasklist as $row) {
			echo '<tr>';
			echo '<td width="60">' . $row['id'] . '</td>';
			echo '<td width="360">' . $row['function'] . '</td>';
			echo '<td width="100">' . date('m-d H:i:s', $row['start_time']) . '</td>';
			echo '<td width="80">' . $row['exec_time'] . '</td>';
			echo '<td width="60">' . $statusArray[$row['status']] . '</td>';
			echo '<td>&nbsp;</td>';
			echo '</tr>';
		}
		echo '</table>';
		echo '<div class="pager">', $pageInfoString, '</div>';
		
		echo '</div></center></body></html>';
	}
}

/**
 * worker的基类
 */
class PadBaseGmtaskWorker {

	public $gmworker;

	public function __call($function, $params) {
		$rfunction = '_do_' . $function;
		$job = $params[0];
		
		$workload = $job->workload();
		$id = substr($workload, 0, 32);
		$params = unserialize(substr($workload, 32));
		
		/**
		 * 添加任务
		 */
		$className = get_class($this);
		$this->gmworker->dbhandler->update($this->gmworker->configs['db_table_prefix'], array(
			'start_time' => time(),
			'status' => 0
		), 'id=\'' . $id . '\'');
		$pid = $this->gmworker->dbhandler->getInsertId();
		
		/**
		 * 执行任务
		 */
		$startTime = microtime(true) * 1000;
		$return = call_user_func_array(array(
			$this,
			$rfunction
		), array(
			$params
		));
		$execTime = microtime(true) * 1000 - $startTime;
		
		/**
		 * 更新任务状态
		 */
		$this->gmworker->dbhandler->update($this->gmworker->configs['db_table_prefix'], array(
			'exec_time' => $execTime,
			'status' => 2
		), 'id=\'' . $id . '\'');
		
		return serialize($return);
	}
}


