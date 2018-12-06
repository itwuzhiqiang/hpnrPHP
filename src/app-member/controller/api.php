<?php

class Controller_Api {

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 用户登陆
	 */
	public function doLogin(PadMvcRequest $request, PadMvcResponse $response) {
		$phone = $request->param('phone');
		$password = $request->param('password');

		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/user/driver/login?duAccount=' . $phone . '&password=' . $password . '&lrClientType=PC&imei=PC';
		$data = $response->helper->urlRequest($requestType, $url);
		Controller_Abstract::getPassport()->setLogin(json_encode($data['data']['account_token']));

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	public function doRegister(PadMvcRequest $request, PadMvcResponse $response) {
		$phone = $request->param('phone');
		$password = $request->param('password');
		$code = $request->param('code');

		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/user/driver/register?phoneNumber=' . $phone . '&password=' . $password . '&validation=' . $code;
		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 发送验证码
	 */
	public function doSendCode(PadMvcRequest $request, PadMvcResponse $response) {
		$phone = $request->param('phone');
		$isThereAre = $request->param('isThereAre', true);

		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/user/driver/sendSMSValidat?phoneNumber=' . $phone . '&isThereAre=' . $isThereAre;
//		var_dump($url);exit;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 用户登出
	 */
	public function doLogout(PadMvcRequest $req, PadMvcResponse $res) {
		Controller_Abstract::getPassport()->setLogout();
		$res->json(array(
			'code' => 0,
			'res' => array('status' => 'success'),
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 忘记密码
	 */
	public function doForget(PadMvcRequest $request, PadMvcResponse $response) {
		$password = $request->param('password');
		$duAccount = $request->param('duAccount');
		$verification = $request->param('verification');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/user/driver/editByPhone?duAccount=' . $duAccount . '&password=' . $password . '&verification=' . $verification;
		$data = $response->helper->urlRequest($requestType, $url)['data'];

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	public function doTest(PadMvcRequest $request, PadMvcResponse $response) {
//		//获取事故车辆颜色
//		$pcurl = new PadLib_Pcurl();
//		$url = 'http://test.fastepay.cn/accident/v1/general/color';
//		list($content, $info) = $pcurl->get($url);
//		$arr = json_decode($content, true);
//		$response->json(array(
//			'code' => 0,
//			'res' => $arr['data'],
//		));

//		//登录
//		$pcurl = new PadLib_Pcurl();
//		$url = "http://test.fastepay.cn/personal/v1/app/user/police/login?policeNumber=010140&password=010140&lrClientType=PC&imei=123";
//		list($content, $info) = $pcurl->get($url,array(
//			'post'=> array(
//			)
//		));
//		$arr = json_decode($content, true);
//		var_dump($arr);
		$response->json(array(
			'code' => 0,
			'res' => '123',
		));
	}

	public function doGetHistory(PadMvcRequest $req, PadMvcResponse $res) {
		$pcurl = new PadLib_Pcurl();
		$url = "http://test.fastepay.cn/accident/v1/app/accident/history/getAll?accountToken=B093998E4053C1906D8AE8F9D29BC057&start=1&length=10";
		list($content, $info) = $pcurl->get($url);
		$arr = json_decode($content, true);
		$res->json(array(
			'code' => 0,
			'res' => '123',
		));
	}
}