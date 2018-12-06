<?php

class PadBasePassport {
	public $activeDomain;
	public $sessionId;
	public $sessionIsInit = false;
	public $des;
	public $configs;

	public $cookies = array();

	static public function getInstance($configs = array()) {
		static $instance;
		if (! isset($instance)) {
			$instance = new PadBasePassport($configs);
		}
		return $instance;
	}

	public function __construct($configs = array()) {
		$baseConfig = array(
			'cookie_name' => '__padcookie',
			'identity_cookie_name' => '__padgid',
			'des_key' => null,
			'expire_time' => 31536000,
			'verification_code_chars' => '0123456789',
			'verification_code_font' => 'arial'
		);
		$this->configs = array_merge($baseConfig, $configs);
		
		// cookie名称
		if (! isset($this->configs['cookie_name'])) {
			$GLOBALS['pad_core']->error(E_ERROR, 'passport must set cookie_name');
		}
		
		// 反加密的des_key
		if (! isset($this->configs['des_key'])) {
			$GLOBALS['pad_core']->error(E_ERROR, 'passport must set des_key');
		}
		
		// gid的cookie名称，永不过期，不设置，用cookieName加后缀
		if (! isset($this->configs['identity_cookie_name'])) {
			$this->configs['identity_cookie_name'] = $this->configs['cookie_name'] . '_identity';
		}
		
		$this->des = new PadBaseDes($this->configs['des_key']);
		$this->activeDomain = $this->_getActiveDomain();
		
		$cookieValue = null;
		if (isset($_COOKIE[$this->configs['cookie_name']])) {
			$cookieValue = $_COOKIE[$this->configs['cookie_name']];
		}
		
		if (!$cookieValue && isset($_GET[$this->configs['cookie_name']])) {
			$cookieValue = $_GET[$this->configs['cookie_name']];
		}

		if ($cookieValue) {
			$this->sessionId = $this->des->decrypt($cookieValue);
		}
	}

	/**
	 * 获得唯一的cookie名字
	 * @return string
	 */
	public function identity() {
		$cfg = $this->configs;
		if (! isset($_COOKIE[$cfg['identity_cookie_name']])) {
			$gid = uniqid();
			$_COOKIE[$cfg['identity_cookie_name']] = $gid;
			setcookie($cfg['identity_cookie_name'], $gid, time() + 3600 * 24 * 1000, '/');
		}
		return $_COOKIE[$cfg['identity_cookie_name']];
	}

	/**
	 * 设置为登录状态
	 * @param string $value
	 * @param integer $expire
	 * @param $options $array
	 */
	public function setLogin($value, $expire = -1, $options = array()) {
		$cfg = $this->configs;
		if ($expire == - 1) {
			if ($cfg['expire_time']) {
				$expire = time() + $cfg['expire_time'];
			} else {
				$expire = 0;
			}
		}

		setcookie($cfg['cookie_name'], $this->des->encrypt($value), $expire, '/');
		if (isset($options['domains'])) {
			foreach ($options['domains'] as $domain) {
				setcookie($cfg['cookie_name'], $this->des->encrypt($value), $expire, '/', $domain);
			}
		}

		$_COOKIE[$cfg['cookie_name']] = $this->des->encrypt($value);
		$this->sessionId = $value;
	}

	/**
	 * 设置为登出
	 */
	public function setLogout() {
		setcookie($this->configs['cookie_name'], 0, 0, '/');
		$_COOKIE[$this->configs['cookie_name']] = null;
	}

	public function getCookieKey() {
		return $this->sessionId;
	}

	/**
	 * 检测是否已经登录
	 * @return boolean
	 */
	public function getIsLogin() {
		return $this->sessionId ? true : false;
	}

	/**
	 * 设置session
	 * @param unknown $name
	 * @param unknown $value
	 */
	public function setSession($name, $value) {
		if ($this->sessionId !== false) {
			$this->_checkSessionInit();
			if ($value !== null) {
				$_SESSION[$name] = $value;
			} else {
				unset($_SESSION[$name]);
			}
		}
	}

	/**
	 * 获得session
	 * @param unknown $name
	 * @return Ambigous <NULL, unknown>|NULL
	 */
	public function getSession($name) {
		if ($this->sessionId !== false) {
			$this->_checkSessionInit();
			return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
		}
		return null;
	}

	/**
	 * 设置Cookie
	 * @param unknown $name
	 * @param unknown $value
	 * @param number $expire
	 */
	public function setCookie($name, $value, $expire = 0) {
		$name = $this->configs['cookie_name'] . '_' . $name;
		$this->cookies[$name] = $value;
		setcookie($name, $value, $expire > 0 ? $expire + time() : 24 * 3600 + time(), '/');
	}

	/**
	 * 获得cookie
	 * @param unknown $name
	 * @return Ambigous <NULL, unknown>
	 */
	public function getCookie($name) {
		$name = $this->configs['cookie_name'] . '_' . $name;
		
		if (isset($this->cookies[$name])) {
			return $this->cookies[$name];
		}
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	}

	/**
	 * 验证码验证
	 */
	public function getIsVerifyCode($code) {
		$verificationCode = $this->getSession('verification_code');
		return $verificationCode && strtolower($verificationCode) == strtolower($code);
	}

	/**
	 * 输出验证码(PNG格式)
	 */
	public function showVerifyCodeImage($codeLen = 4, $codeChars = null, $font = null) {
		$baseString = ($codeChars ? $codeChars : $this->configs['verification_code_chars']);
		$baseStringLen = mb_strlen($baseString, 'utf-8');
		
		$width = 0;
		$verificationCodeArray = array();
		for ($count = 0; $count < $codeLen; $count ++) {
			$c = mb_substr($baseString, rand(0, $baseStringLen - 1), 1, 'utf-8');
			if (strlen($c) == 1) {
				$c = strtoupper($c);
				$width += 10;
			} else {
				$width += 20;
			}
			
			$verificationCodeArray[] = $c;
		}
		
		$verificationCode = implode('', $verificationCodeArray);
		$this->setSession('verification_code', $verificationCode);
		
		$code = $verificationCode;
		$height = 20;
		
		$font = PAD_RC_DIR . '/font/' . ($font ? $font : $this->configs['verification_code_font']) . '.ttf';
		
		// 图片尺寸
		$image_x = $width + 16;
		$image_y = $height + 2;
		$im_hdle = imagecreate($image_x, $image_y);
		
		// 写字
		$white = imagecolorallocate($im_hdle, 255, 255, 255);
		foreach ($verificationCodeArray as $idx => $c) {
			$grey = imagecolorallocate($im_hdle, rand(0, 99), rand(0, 99), rand(0, 99));
			imagettftext($im_hdle, 14, 0, $idx * (strlen($c) > 1 ? 20 : 10) + 8, 18 + rand(- 2, 2), $grey, $font, $c);
		}
		
		// 边框
		$lcolor = imagecolorallocate($im_hdle, 0, 0, 0);
		imageline($im_hdle, 0, 0, $image_x - 1, 0, $lcolor);
		imageline($im_hdle, 0, 0, 0, $image_y - 1, $lcolor);
		imageline($im_hdle, $image_x - 1, $image_y - 1, $image_x - 1, 0, $lcolor);
		imageline($im_hdle, $image_x - 1, $image_y - 1, 0, $image_y - 1, $lcolor);
		
		// 干扰线
		for ($i = 0; $i < 30; $i ++) {
			$lcolor = imagecolorallocate($im_hdle, rand(200, 255), rand(200, 255), rand(200, 255));
			$x = rand(1, $image_x - 2);
			$y = rand(1, $image_y - 2);
			imagesetpixel($im_hdle, $x, $y, $lcolor);
		}
		
		// 输出图片
		ob_start();
		ob_implicit_flush(0);
		ImagePNG($im_hdle);
		ImageDestroy($im_hdle);
		$img_contents = ob_get_contents();
		ob_end_clean();
		$img_size = strlen($img_contents);
		@header('Accept-Ranges: bytes');
		@header('Content-Length: ' . $img_size);
		@header('Content-Type: image/png');
		echo $img_contents;
	}

	private function _checkSessionInit() {
		if (! $this->sessionIsInit) {
			$this->sessionIsInit = true;
			session_id(md5($this->identity()));
			session_start();
		}
	}

	private function _getActiveDomain() {
		$domain = $_SERVER['HTTP_HOST'];
		if (preg_match('/\d+\.\d+\.\d+\.\d+\:\d+/', $domain) || preg_match('/\d+\.\d+\.\d+\.\d+/', $domain)) {
			return false;
		}
		
		$tmpdomain = explode('.', $domain);
		$pos = 2;
		$pos3Domain = array('.com.cn', '.gov.cn', '.org.cn');
		foreach ($pos3Domain as $dm) {
			if (strpos($domain, $dm) !== false) {
				$pos = 3;
			}
		}
		$domain = '.' . implode('.', array_slice($tmpdomain, count($tmpdomain) - $pos));
		if (($pos = strpos($domain, ':')) !== false) {
			return substr($domain, 0, $pos);
		} else {
			return $domain;
		}
	}
}



