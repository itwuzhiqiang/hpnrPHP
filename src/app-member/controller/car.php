<?php

class Controller_Car extends Controller_Abstract {

	public function doCarList(PadMvcRequest $request, PadMvcResponse $response) {
		$token = $this->memberToken;
		$requestType = 'get';
		$url = config('domain.rpapi') . '/personal/v1/app/user/vehicle/queryUsId?accountToken=' . $token;
		$data['list'] = $response->helper->urlRequest($requestType, $url)['data'];

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 事故车牌类型
	 */
	public function doPlateType(PadMvcRequest $request, PadMvcResponse $response) {
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/general/plateType';

		$data = $response->helper->urlRequest($requestType, $url)['data'];

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 获取所有的保险公司
	 */
	public function doQueryAll(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->memberToken;
		$requestType = 'get';
		$url = config('domain.rpapi') . '/personal/v1/app/insurance/company/queryAll?accountToken=' . $accountToken;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	public function doBindCar(PadMvcRequest $request, PadMvcResponse $response) {
		//不知道调取哪一个接口
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 绑定驾照
	 */
	public function doBindLicense(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->memberToken;
		$certificateNumber = $request->param('certificateNumber');
		$fileNumber = $request->param('fileNumber');

		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/driver/license/bindingLicense?accountToken=' . $accountToken . '&certificateNumber=' . $certificateNumber . '&fileNumber=' . $fileNumber;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 驾驶证
	 */
	public function doLicenseList(PadMvcRequest $request, PadMvcResponse $response) {
		$token = $this->memberToken;
		$requestType = 'get';
		$url = config('domain.rpapi') . '/personal/v1/app/driver/license/queryByUserID?accountToken=' . $token;
		$data['list'] = $response->helper->urlRequest($requestType, $url)['data'];

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/***
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 绑定车辆
	 */
	public function doBindingVehicle(PadMvcRequest $request, PadMvcResponse $response) {
		$token = $this->memberToken;
		$viVehicleNumber = $request->param('viVehicleNumber');
		$plateType = $request->param('plateType');
		$viVehicleIdentificationCode = $request->param('viVehicleIdentificationCode');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/user/vehicle/bindingVehicle?accountToken=' . $token
			. '&plateType=' . $plateType . '&viVehicleNumber=' . $viVehicleNumber . '&viVehicleIdentificationCode=' . $viVehicleIdentificationCode;
		$data['list'] = $response->helper->urlRequest($requestType, $url)['data'];

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 设为默认
	 */
	public function doEditById(PadMvcRequest $request, PadMvcResponse $response) {
		$token = $this->memberToken;
		$dvViId = $request->param('dvViId');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/personal/v1/app/driver/license/queryByUserID?accountToken=' . $token . '&dvViId=' . $dvViId;
		$data['list'] = $response->helper->urlRequest($requestType, $url)['data'];

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}
}






