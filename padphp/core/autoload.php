<?php

/**
 * 自动加载的类
 * @author huchunhui
 *
 */
class PadAutoload {
	public static $configs = array();
	public static $configsParams = array();
	public static $autoloadClass = array();

	/**
	 * 初始化
	 */
	static public function initialize() {
		$functions = spl_autoload_functions();
		if (is_array($functions)) {
			foreach ($functions as $func) {
				spl_autoload_unregister($func);
			}
		}
		
		spl_autoload_register(array(
			'PadAutoload',
			'autoload'
		));
	}

	/**
	 * 自动加载一个类
	 *
	 * @param string $loadclass
	 */
	static public function autoload($loadclass) {
		self::classExists($loadclass);
	}

	/**
	 * 注册一个加载对象(只允许一个目录)
	 * @param unknown $prefix
	 * @param unknown $path
	 */
	static public function register($prefix, $path, $params = array()){
		PadAutoload::$configs[$prefix] = array($path);
		PadAutoload::$configsParams[$prefix] = array($params);
	}
	
	/**
	 * 注册一个加载对象(允许多个目录)
	 * @param unknown $prefix
	 * @param unknown $path
	 */
	static public function registerMulti($prefix, $path, $params = array()){
		if (!isset(PadAutoload::$configs[$prefix])) {
			PadAutoload::$configs[$prefix] = array();
			PadAutoload::$configsParams[$prefix] = array();
		}
		
		if (!in_array($path, PadAutoload::$configs)) {
			PadAutoload::$configs[$prefix][] = $path;
			PadAutoload::$configsParams[$prefix][] = $params;
		}
	}
	
	/**
	 * 注册一个三方自动加载类插件
	 *
	 * @param callbackClass $callback
	 */
	static public function registerAutoloadClass($callback) {
		self::$autoloadClass[] = $callback;
	}

	/**
	 * 检查类是否存在，同时自动加载
	 *
	 * @param string $loadclass
	 * @return boolean
	 */
	static public function classExists($loadclass) {
		if (class_exists($loadclass, false)) {
			return true;
		}

		if (strpos($loadclass, '\\') !== false && $loadclass[0] !== '\\') {
			$loadclass = '\\' . $loadclass;
		}

		$isHit = false;
		if (isset(self::$configs[$loadclass])) {
			// 如果是类名直接命中，必须制定一个路径，不支持多个目录
			$file = self::$configs[$loadclass];
			$files = (is_array($file) ? $file : array($file));
			foreach ($files as $file) {
				if (file_exists($file)) {
					include ($file);
					$isHit = true;
					unset(self::$configs[$loadclass]);
					break;
				}
			}
		} else {
			foreach (self::$configs as $prefix => $icdir) {
				// 前缀必须能匹配上
				if (strpos($loadclass, $prefix) === 0) {
					$icdirs = self::$configs[$prefix];
					$loadParams = self::$configsParams[$prefix];

					foreach ($icdirs as $idx => $icdir) {
						$loadClassName = $loadclass;
						$params = $loadParams[$idx];
						if (isset($params['classPrefix'])) {
							$loadClassName = str_replace($prefix.$params['classPrefix'], $prefix, $loadclass);
						}

						$classpath = null;
						if (substr($icdir, - 1, 1) == '*') {
							$classpath = substr($icdir, 0, -1) . DIRECTORY_SEPARATOR . PadBaseString::padStrtolower($loadClassName) . '.php';
						} else if ($prefix[0] == '\\') {
							$paths = explode('\\', substr($loadClassName, strlen($prefix) + 1));
							$filePaths = array();
							foreach ($paths as $item) {
								$filePaths[] = PadBaseString::padStrtolower($item);
							}
							$classpath = $icdir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $filePaths) . '.php';
						} else {
							$classpath = $icdir . DIRECTORY_SEPARATOR . PadBaseString::padStrtolower(substr($loadClassName, strlen($prefix))) . '.php';
						}

						// 文件是否存在
						if (file_exists($classpath)) {
							$isHit = true;
							include ($classpath);
							break 2;
						}
					}
				}
			}
		}
		
		// 挂接其他的autoload函数
		if (!$isHit) {
			foreach (self::$autoloadClass as $callback) {
				call_user_func_array($callback, array(
					$loadclass
				));
			}
		}
		
		return class_exists($loadclass, false) || interface_exists($loadclass, false);
	}
}







