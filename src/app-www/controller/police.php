<?php

class Controller_Police extends Controller_Abstract {

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 修改密码
	 */
	public function doUpdatePassword(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->police['sign'];
		$policeNumber = $this->police['policeNumber'];
		$password = $request->param('password');
		$newPassword = $request->param('newPassword');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/user/police/editByPoliceNumber?accountToken=' .
			$accountToken . '&policeNumber=' . $policeNumber . '&password=' . $password . '&newPassword=' . $newPassword;

		$data = $response->helper->urlRequest($requestType, $url);

		Controller_Abstract::getPassport()->setLogout(); //退出登录

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 警察个人信息
	 */
	public function doPoliceInfo(PadMvcRequest $request, PadMvcResponse $response) {

		if (!$this->police) {
//			throw new PadBizException('登录失效 请重新登录');
			$info = array();
		} else {
			$info = $this->police;
		}

		$response->json(array(
			'code' => 0,
			'res' => $info,
		));
	}

}






