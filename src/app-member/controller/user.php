<?php

class Controller_User extends Controller_Abstract {

	public function doInfo(PadMvcRequest $request, PadMvcResponse $response) {
		$info = array();
		$token = $this->memberToken;
		if ($token) {
			$requestType = 'get';
			$url = config('domain.rpapi') . '/personal/v1/app/user/driver/queryId?accountToken=' . $token;
			$arr = $response->helper->urlRequest1($requestType, $url);

			if ($arr['code'] == -1) {
				$info['code'] = -1;
				Controller_Abstract::getPassport()->setLogout();
			} else {
				$info = $arr['data'];
				$info['code'] = 1;
			}
		} else {
			$response->template('login.login');
		}

		$info['account_token'] = $token;

		$response->json(array(
			'code' => 0,
			'res' => $info,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 修改个人信息
	 */
	public function doUpdate(PadMvcRequest $request, PadMvcResponse $response) {
		$name = $request->param('name');
		$sex = $request->param('sex');
		$idCard = $request->param('idCard');
		$pic = $request->param('pic');
		$token = $this->memberToken;
		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/user/driver/edit?accountToken=' . $token;
		if ($name) {
			$url .= '&name=' . urlencode($name);
		}

		if ($sex) {
			$url .= '&sex=' . $sex;
		}

		if ($idCard) {
			$url .= '&idCard=' . $idCard;
		}

		if ($pic) {
			$url .= '&pic=' . $pic;
		}

		$data = $response->helper->urlRequest($requestType, $url)['data'];

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 修改密码
	 */
	public function doUpdatePassword(PadMvcRequest $request, PadMvcResponse $response) {
		$password = $request->param('password');
		$duAccount = $request->param('duAccount');
		$newPassword = $request->param('newPassword');
		$token = $this->memberToken;
		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/user/driver/editByPhoneAndPwd?accountToken=' . $token . '&duAccount=' . $duAccount . '&password=' . $password . '&newPpassword=' . $newPassword;
		$data = $response->helper->urlRequest($requestType, $url)['data'];

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/***
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 *  用户反馈
	 */
	public function doFeedback(PadMvcRequest $request, PadMvcResponse $response) {
		$ufMsg = urlencode($request->param('ufMsg'));
		$token = $this->memberToken;
		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/user/feedback/save?ufMsg=' . $ufMsg . '&accountToken=' . $token;
		$data = $response->helper->urlRequest($requestType, $url)['data'];

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

}






