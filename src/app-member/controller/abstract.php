<?php

class Controller_Abstract {
	public static $passportObject;
	public $passport;
	public $memberToken;

	/**
	 * 通行证类111
	 * @return PadBasePassport
	 */
	static public function getPassport() {
		if (!self::$passportObject) {
			self::$passportObject = new PadBasePassport(
				array(
					'cookie_name' => 'seller',
					'identity_cookie_name' => 'sellerid',
					'expire_time' => 0,
					'des_key' => '3d69deab-6661-4f53-8f0d-61e23aa4fd6fs'
				));
		}
		return self::$passportObject;
	}

	public function __construct(PadMvcRequest $request, PadMvcResponse $response) {
		$page = $request->param('page');
		$memberToken = '';
		$passport = self::getPassport();
		$this->passport = $passport;
		$cookieKey = $passport->getCookieKey();
		if ($cookieKey) {
			$memberToken = json_decode($cookieKey, true);
		}else{
			$response->template('index.index');
		}
		$this->memberToken = $memberToken;
	}

}


