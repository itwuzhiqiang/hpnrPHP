<?php

class Controller_Api {

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 用户登陆
	 */
	public function doLogin(PadMvcRequest $request, PadMvcResponse $response) {
		$policeNumber = $request->param('userName');
		$password = $request->param('password');

		$curl = curl_init();
		$url = config('domain.rpapi') . "/personal/v1/app/user/police/login?policeNumber=" . $policeNumber
			. "&password=" . $password . "&lrClientType=PC&imei=123";
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_HTTPHEADER => array(
				"Accept: application/json",
				"Content-Type: application/json;charset=utf-8",
			),
		));

		$result = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($result === false) {
			throw new PadBizException($err);
		} else {
			$result = json_decode($result);
			if ($result) {
				if ($result->code == 0) {
					throw new PadBizException($result->msg);
				} else {
					foreach ($result->data as $key => $value) {
						$array[$key] = $value;
					}
					$data = json_encode($array);
					Controller_Abstract::getPassport()->setLogin($data);
				}
			} else {
				throw new PadBizException('接口挂了');
			}

		}

		$response->json(array(
			'code' => 0,
			'res' => $result->code == 0 ? array() : array(
				'data' => $result->data,
			),
		));
	}

	public function doLogout(PadMvcRequest $req, PadMvcResponse $res) {
		Controller_Abstract::getPassport()->setLogout();
		$res->json(array(
			'code' => 0,
			'res' => array('status' => 'success'),
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
		$url = config('domain.rpapi') . "/accident/v1/app/accident/history/getAll?accountToken=B093998E4053C1906D8AE8F9D29BC057&start=1&length=10";
		list($content, $info) = $pcurl->get($url);
		$arr = json_decode($content, true);

		$res->json(array(
			'code' => 0,
			'res' => '123',
		));
	}

}