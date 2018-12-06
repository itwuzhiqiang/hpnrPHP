<?php

class PadLib_Dever {
	public $devName;

	public function __construct($bootFile, $options = array()) {
		if (isset($_SERVER['PADAPP_ACCOUNT'])) {
			$this->configDir = dirname($bootFile);
			$this->devName = $_SERVER['PADAPP_ACCOUNT'];
			$this->devIp = '127.0.0.1';
			return;
		}

		if (isset($_SERVER['PADDEV_USER'])) {
			$this->configDir = dirname($bootFile);
			$this->devName = $_SERVER['PADDEV_USER'];
			$this->devIp = '127.0.0.1';
			return;
		}

		if (isset($_SERVER['PAD_DEV_USER']) && $_SERVER['PAD_DEV_USER']) {
			$this->configDir = dirname($bootFile);
			$this->devName = $_SERVER['PAD_DEV_USER'];
			$this->devIp = '127.0.0.1';
			return;
		}

		$options = array_merge(array(
			'ipmap' => '/mnt/dev/ipmap.conf',
		), $options);
		$this->configDir = dirname($bootFile);

		$deverNameFile = '/mnt/dev/dev.conf';
		$defJs = __DIR__ . '/../../dev.js';
		if (defined('PAD_DEV_NAME')) {
			$this->devName = PAD_DEV_NAME;
		} else if (file_exists($deverNameFile)) {
			$deverContent = file_get_contents($deverNameFile);
			$this->devName = trim($deverContent);
		} else if (file_exists($defJs)) {
			$deverContent = file_get_contents($defJs);
			preg_match('/\'(.*?)\'/', $deverContent, $match);
			$this->devName = trim($match[1]);
		} else {
			$user = '.';
			if (preg_match('/\/mnt\/(dev|hgfs)\/(.*?)\//', __FILE__, $match)) {
				$user = $match[2];
			}
			$this->devName = $user;
		}

		$ipMap = array();
		$ipmapLines = file($options['ipmap']);
		foreach ($ipmapLines as $line) {
			$line = trim($line);
			list($k, $v) = explode("\t", $line);
			$ipMap[$k] = $v;
		}
		$this->devIp = $ipMap[$this->devName];
	}

	public function initApp ($dirname) {
		if (file_exists($dirname . '/padreact.web.default.js')) {
			$jsContent = file_get_contents($dirname . '/padreact.web.default.js');
			if (preg_match('/devServerPort\:\s+(\d+)/', $jsContent, $match)) {
				$GLOBALS['pad_core']->configs['dever.app.web.js.port'] = $match[1];
			}
		}
	}

	public function mergeConfig ($config) {
		$oconfig = $config;

		$oconfig['dever.class'] = $this;
		$oconfig['dever.ip'] = $this->devIp;

		$deverConfigFile = $this->configDir . '/dever/' . $this->devName . '.php';
		if (file_exists($deverConfigFile)) {
			include($deverConfigFile);
			if (isset($config) && is_array($config)) {
				$oconfig = array_merge($oconfig, $config);
			}
		}
		return $oconfig;
	}
}





