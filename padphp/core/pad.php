<?php

class PadCore {

	public $database;

	public $cache;
	public $orm;
	public $cpt;
	public $mvc;
	public $debug;
	public $instanceKey;
	public $hasException = false;

	public $envConfigs = array();

	public $envArgv = array();

	public $configs = array();

	public $onceException = false;

	public $_headerHandler = 'header';
	public $_outputHandler = 'sprintf';
	public $_errorHandler = 'trigger_error';
	public $cptLoadedList = array();

	public $envDefaultConfigs = array(
		'boot_file' => null,
		'boot_dir' => null,
		'tmp_dir' => null,
		'config_environment' => '',
		'error_environment' => 'dev',
		'error_log' => null,
		'config_dir' => null,
		'config_tags' => array(),
	);

	/**
	 * 初始化
	 *
	 * @param string $bootFile 启动文件路径
	 * @param array $configs 相关配置
	 * @return PadCore
	 */
	static public function init($bootFile, $configs = array()) {
		$GLOBALS['pad_core'] = new PadCore($bootFile, $configs);
		$GLOBALS['pad_core']->instanceKey = md5($bootFile);
		$GLOBALS['pad_core']->database = new PadDatabase();
		$GLOBALS['pad_core']->cache = new PadCache();

		$GLOBALS['pad_core']->loadConfig();
		$GLOBALS['pad_core']->envInit();

		// shell运行，自动调用destruct
		$envcfgs = $GLOBALS['pad_core']->envConfigs;
		if ($envcfgs['is_shell_run']) {
			register_shutdown_function('PadCore::destruct');
		}

		// 是否开启debug
		if (isset($GLOBALS['pad_core']->envArgv['--pad-debug'])) {
			$GLOBALS['pad_core']->debug = new PadDebug($GLOBALS['pad_core']->envArgv['--pad-debug']);
			$GLOBALS['pad_core']->debug->startXhprof();
			assert_options(ASSERT_ACTIVE, 1);
			register_shutdown_function(array(
				$GLOBALS['pad_core']->debug,
				'output'
			));
		} else {
			$GLOBALS['pad_core']->debug = new PadOrmEntityNull();
			assert_options(ASSERT_ACTIVE, 0);
		}

		// 加载cpt
		if (isset($GLOBALS['pad_core']->envConfigs['cpt_dir'])) {
			$GLOBALS['pad_core']->cpt = new PadCpt();
			$GLOBALS['pad_core']->cpt->lookupConfig($GLOBALS['pad_core']->envConfigs['boot_dir']);
		}

		return $GLOBALS['pad_core'];
	}

	static public function free() {
		unset($GLOBALS['pad_core']);
		spl_autoload_unregister();
	}

	/**
	 * 无
	 */
	static public function destruct() {
		$error = error_get_last();
		if (isset($error['type']) && ($error['type'] == E_USER_ERROR || $error['type'] == E_ERROR)) {
		} else if ($GLOBALS['pad_core']->orm !== null) {
			if ($GLOBALS['pad_core']->hasException) {
				foreach ($GLOBALS['pad_core']->database->connects as $database) {
					$database->rollback();
				}
			} else {
				// 没有捕捉到严重错误才提交ORM，警告错误依然提交
				$GLOBALS['pad_core']->orm->flush();
				foreach ($GLOBALS['pad_core']->database->connects as $database) {
					$database->commit();
				}
			}
		}
	}

	/**
	 * 自动加载一个类
	 *
	 * @param string $prefix 类名
	 * @param string $path 路径
	 */
	static public function autoload($prefix, $path) {
		PadAutoload::register($prefix, $path);
	}

	/**
	 * 初始化ORM
	 *
	 * @param unknown $bootFile
	 * @param unknown $configs
	 */
	static public function initOrm($bootFile, $configs = array()) {
		$GLOBALS['pad_core']->orm = new PadOrm($bootFile, $configs);
		return $GLOBALS['pad_core']->orm;
	}

	/**
	 * 初始化工作流
	 * @param $bootFile
	 * @param array $configs
	 * @return mixed
	 */
	static public function initWorkflow($bootFile, $configs = array()) {
		$GLOBALS['pad_core']->workflow = new PadWorkflow($bootFile, $configs);
		return $GLOBALS['pad_core']->workflow;
	}

	/**
	 * 初始化MVC
	 *
	 * @param unknown $bootFile
	 */
	static public function initMvc($bootFile, $options = array()) {
		if ($GLOBALS['pad_core']->cpt) {
			$GLOBALS['pad_core']->cpt->lookupConfig($bootFile);
		}

		if (PAD_ENVIRONMENT == 'dev') {
			$dever = $GLOBALS['pad_core']->getConfig('dever.class');
			if ($dever) {
				$dever->initApp(dirname($bootFile));
			}
		}

		if (isset($GLOBALS['pad_core']->envArgv['--server'])) {
			include(__DIR__ . '/../padinx/boot.php');
			$server = new Padinx_Server(array_merge(array(
				'bootFile' => $bootFile,
			), $options));
			$server->start();
		} else {
			$GLOBALS['pad_core']->mvc = new PadMvc($bootFile);
			$GLOBALS['pad_core']->mvc->process();

			return $GLOBALS['pad_core']->mvc;
		}
	}

	/**
	 * 初始化完成，开始运行
	 */
	static public function initFinish () {
		if (!php_sapi_name() === "cli") {
			return;
		}

		// 获取参数
		$getopt = getopt('', array(
			'config::',
			'pkgconfig::',
			'format::'
		));

		// 输出所有配置
		if (isset($getopt['config'])) {
			$jsonConfigs = array();
			foreach ($GLOBALS['pad_core']->configs as $key => $v) {
				$jsonConfigs[$key] = $v;
			}

			if (isset($getopt['format']) && $getopt['format'] == 'php') {
				echo '<?php return ' . var_export($jsonConfigs, true) . ';';
			} else {
				echo json_encode($jsonConfigs);
			}
			exit;
		}

		// 输出模块配置
		if (isset($getopt['pkgconfig']) && $getopt['pkgconfig']) {
			$pkgName = $getopt['pkgconfig'];
			$pkgPrefix = 'pkg.' . $pkgName . '.';
			$pkgPrefixLen = strlen($pkgPrefix);

			$jsonConfigs = array();
			foreach ($GLOBALS['pad_core']->configs as $key => $v) {
				if (strpos($key, $pkgPrefix) === 0) {
					$jsonConfigs[substr($key, $pkgPrefixLen)] = $v;
				}
			}

			if (isset($getopt['format']) && $getopt['format'] == 'php') {
				echo '<?php return ' . var_export($jsonConfigs, true) . ';';
			} else {
				echo json_encode($jsonConfigs);
			}
			exit;
		}
	}

	static public function clearMemory() {
		$GLOBALS['pad_core']->orm->entityLoadedList = array();
		$GLOBALS['pad_core']->orm->entityChangedList = array();
		$GLOBALS['pad_core']->orm->entityNewList = array();
	}

	/**
	 * 加载一个组件
	 * @param unknown $name
	 */
	static public function loadCpt($name) {
		$GLOBALS['pad_core']->cpt->load($name);
	}

	/**
	 * 构造函数
	 *
	 * @param unknown $bootFile
	 * @param unknown $configs
	 */
	public function __construct($bootFile, $configs = array()) {
		$this->envDefaultConfigs['tmp_dir'] = sys_get_temp_dir() . '/padphp-' . md5($bootFile);
		$this->envConfigs = array_merge($this->envDefaultConfigs, array(
			'padphp_dir' => __DIR__ . '/..',
		), $configs);
		$this->envConfigs['boot_file'] = $bootFile;
		$this->envConfigs['boot_dir'] = dirname($bootFile);
		$this->envConfigs['is_shell_run'] = !isset($_SERVER['REQUEST_URI']);

		/**
		 * 尝试创建临时目录
		 */
		if (!is_dir($this->envDefaultConfigs['tmp_dir'])) {
			mkdir($this->envDefaultConfigs['tmp_dir']);
		}

		if ($this->envConfigs['config_dir'] == null) {
			$this->envConfigs['config_dir'] = $this->envConfigs['boot_dir'] . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'configs';
		}

		if ($this->envConfigs['error_log']) {
		} else {
		}

		// 计划任务目录
		PadAutoload::register('PadCrontab_', $this->envConfigs['boot_dir'] . DIRECTORY_SEPARATOR . 'crontab');
	}

	/**
	 * 加载配置
	 */
	public function loadConfig() {
		$configs = array();

		// 模块配置,项目的方式运行才加载配置
		if (!defined('PADPKG_NAME')) {
			$nmDir = '/project/src/node_modules/@padkeji';
			if (file_exists($nmDir)) {
				foreach (glob($nmDir . '/padpkg-*') as $path) {
					$pkgName = substr(basename($path), 7);
					if (!file_exists($path . '/config.php')) {
						continue;
					}
					$mConfig = include $path . '/config.php';
					foreach ($mConfig as $k => $v) {
						$configs["pkg.${pkgName}.${k}"] = $v;
					}
				}
			}
		}

		// boot文件写入的配置
		$dConfigs = (isset($this->envConfigs['configs']) ? $this->envConfigs['configs'] : array());
		$configs = array_merge($dConfigs, $configs);

		// 配置文件的配置
		foreach ($this->envConfigs['config_tags'] as $tag) {
			$config = array();
			require($this->envConfigs['config_dir'] . DIRECTORY_SEPARATOR . $tag . '.php');
			$configs = array_merge($configs, $config);
			if ($this->envConfigs['config_environment'] == $tag) {
				break;
			}
		}

		// 运行状态生成的配置
		$jsonPath = '/data/config/runtime-' . PAD_ENVIRONMENT . '.json';
		if (file_exists($jsonPath)) {
			$uConfigs = json_decode(file_get_contents($jsonPath), true);
			$configs = array_merge($configs, $uConfigs);
		}

		// padpkg配置KEY的处理
		$keyPrefix = '';
		$keyPrefixLen = 0;
		if (defined('PADPKG_NAME')) {
			$keyPrefix = 'pkg.' . PADPKG_NAME . '.';
			$keyPrefixLen = strlen($keyPrefix);
		}

		foreach ($configs as $key => $val) {
			$newval = $val;
			if (is_string($val) && strpos($val, '&') === 0 && isset($config[substr($val, 1)])) {
				$newval = $config[substr($val, 1)];
			}

			if (strpos($key, $keyPrefix . 'database.') === 0) {
				$this->database->configs[substr($key, 9 + $keyPrefixLen)] = $newval;
			} else if (strpos($key, $keyPrefix . 'cache.') === 0) {
				$this->cache->configs[substr($key, 6 + $keyPrefixLen)] = $newval;
			}

			if (defined('PADPKG_NAME')) {
				$this->configs[substr($key, $keyPrefixLen)] = $newval;
			} else {
				$this->configs[$key] = $newval;
			}
		}
	}

	/**
	 * 初始化环境变量
	 */
	public function envInit() {
		if ($this->envConfigs['is_shell_run']) {
			global $argv;
			if (isset($argv)) {
				array_shift($argv);
				foreach ($argv as $line) {
					$tmp = explode('=', $line);
					$this->envArgv[$tmp[0]] = isset($tmp[1]) ? $tmp[1] : true;
				}
			}
		} elseif (isset($_REQUEST['--pad-debug'])) {
			$this->envArgv = $_GET;
		}
	}

	/**
	 * 获得一个配置
	 *
	 * @param unknown $key
	 * @return Ambigous <NULL, multitype:>
	 */
	public function getConfig($key) {
		return isset($this->configs[$key]) ? $this->configs[$key] : null;
	}

	public function setHeaderHandler($callback) {
		$this->_headerHandler = $callback;
	}

	public function setOutputHandler($callback) {
		$this->_outputHandler = $callback;
	}

	/**
	 * 设置错误输出句柄
	 * @param unknown $callback
	 */
	public function setErrorHandler($callback) {
		$this->_errorHandler = $callback;
	}

	/**
	 * 新的错误处理,统一使用异常
	 * @throws PadException
	 */
	public function error() {
		$argvs = func_get_args();
		$errorType = array_shift($argvs);
		$message = call_user_func_array('sprintf', $argvs);
		throw new PadException($message);
	}

	/**
	 * 旧的错误处理,暂时屏蔽
	 * @throws Exception
	 */
	public function errorPhp() {
		$argvs = func_get_args();
		$errorType = array_shift($argvs);
		$message = call_user_func_array('sprintf', $argvs);

		$errorTypeString = null;
		if ($errorType == E_ERROR) {
			$errorTypeString = 'e_error';
		} else
			if ($errorType == E_NOTICE) {
				$errorTypeString = 'e_notice';
			} else
				if ($errorType == E_WARNING) {
					$errorTypeString = 'e_warning';
				}

		if ($this->onceException && $errorType == E_ERROR) {
			$this->onceException = false;
			throw new Exception($message);
		}

		if ($this->envConfigs['error_environment'] == 'product') {
			if ($errorType == E_ERROR) {
				call_user_func_array($this->_errorHandler, array($message, E_USER_ERROR));
			} elseif ($errorType == E_NOTICE) {
				call_user_func_array($this->_errorHandler, array($message, E_USER_NOTICE));
			} else {
				call_user_func_array($this->_errorHandler, array('not support user error ' . $errorType, E_USER_ERROR));
			}
		} else
			if ($this->envConfigs['error_environment'] == 'dev') {
				$br = ($this->envConfigs['is_shell_run'] ? "\n" : "<br />\n");
				echo call_user_func_array($this->_outputHandler, array($errorTypeString . ', ' . $message . $br));
				foreach (array_reverse(debug_backtrace()) as $index => $trace) {
					$string = "#%s\t%s\t%s\t%s\t%s" . $br;
					$string = sprintf($string, $index++, isset($trace['file']) ? $trace['file'] : '-', isset($trace['code']) ? $trace['code'] : '-',
						isset($trace['line']) ? $trace['line'] : '-', isset($trace['class']) ? $trace['class'] : '-',
						isset($trace['function']) ? $trace['function'] : '-');
					echo call_user_func_array($this->_outputHandler, array($string));
				}

				if ($errorType == E_ERROR) {
					call_user_func_array($this->_errorHandler, array($message, E_USER_ERROR));
				}
			}
	}

	/**
	 *
	 * 业务错误，不会被错误日志捕获
	 */
	public function bizerror($error) {
		if ($this->mvc == null) {
			// 非mvc环境
			$this->error(E_ERROR, $error);
		} else {
			// mvc环境
			$this->mvc->activeResponse->message('@system_biz_error:' . $error);
			$this->mvc->activeResponse->display();
		}
		exit();
	}

	public function executeScript($name, $params = array()) {
		list($className, $method) = explode('.', $name);
		$className = 'PadScript_' . $className;
		$class = new $className();
		call_user_func_array(array($class, $method), array($params));
	}
}




