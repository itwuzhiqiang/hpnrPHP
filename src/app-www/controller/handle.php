<?php

class Controller_Handle extends Controller_Abstract {

	/***
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 事故信息获取
	 */
	public function doAccident(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->police['sign'];
		$rpaId = $request->param('rpaId');

		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/app/accident/access?accountToken=' . $accountToken . '&rpaId=' . $rpaId;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 基础数据填报
	 */
	public function doReport(PadMvcRequest $request, PadMvcResponse $response) {

		$accountToken = $this->police['sign'];
		$type = $request->param('type', 2);
		$points = json_decode($request->param('points'));

//		var_dump($points);

		$requestType = 'post';
		$url = config('domain.rpapi') . '/accident/v1/app/accident/report?accountToken=' .
			$accountToken . '&rpaType=' . $type . '&coordsys=baidu&rpaLongitude=' . $points->lng . '&rpaLatitude=' . $points->lat;

//		var_dump($url);

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 事故模版获取
	 */
	public function doTemplateGet(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->police['sign'];
		$templateType = $request->param('templateType', 'negotiation');//identified:认定;negotiation:协商
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/app/template/get?templateType=' . $templateType . '&accountToken=' . $accountToken;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 事故证据提交
	 */
	public function doSubmitevidence(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->police['sign'];
		$rpaId = $request->param('rpaId');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/accident/v1/app/accident/submitevidence?rpaId=' . $rpaId . '&accountToken=' . $accountToken;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 挪车
	 */
	public function doMoveCare(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->police['sign'];
		$rpaId = $request->param('rpaId');
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/app/accident/moveCar?rpaId=' . $rpaId . '&accountToken=' . $accountToken;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 逐个录入当事人信息
	 */
	public function doSaveParty(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->police['sign'];
		$rpaId = $request->param('rpaId');
		$apiLp = $request->param('apiLp');
		$apiVlp = $request->param('apiVlp');
		$apiName = $request->param('apiName');
		$apiNo = $request->param('apiNo');
		$apiFn = $request->param('apiFn', '');
		$apiPhone = $request->param('apiPhone');
		$apiPn = urlencode($request->param('apiPn'));
		$apiVas = $request->param('apiVas', '');
		$apiPt = $request->param('apiPt', '');
		$apiPtText = $request->param('apiPtText', '');
		$apiIc = $request->param('apiIc', '');
		$apiIcName = $request->param('apiIcName', '');
		$apiIno = $request->param('apiIno', '');
		$apiOrder = $request->param('apiOrder', '');//当事人顺序

		$requestType = 'post';

		$url = config('domain.rpapi') . '/accident/v2/app/accident/saveParty?rpaId=' . $rpaId . '&accountToken=' . $accountToken
			. '&apiLp=' . urlencode($apiLp) . '&apiPn=' . $apiPn . '&apiName=' . urlencode($apiName) . '&apiNo=' . $apiNo
			. '&apiPhone=' . $apiPhone . '&apiVlp=' . urlencode($apiVlp) . '&apiPt=' . $apiPt . '&apiPtText=' . urlencode($apiPtText)
			. '&apiOrder=' . $apiOrder . '&apiIc=' . $apiIc . '&apiIcName=' . urlencode($apiIcName);
//		var_dump($url);

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 移除事故当事人
	 */
	public function doRemoveParty(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->police['sign'];
		$rpaId = $request->param('rpaId');
		$apId = $request->param('apId');//驾驶人id
		$aviId = $request->param('aviId');//车辆id
		$requestType = 'post';
		$url = config('domain.rpapi') . '/accident/v1/app/accident/removeParty?rpaId=' . $rpaId . '&accountToken=' . $accountToken
			. '&apId=' . $apId . '&aviId=' . $aviId;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 获取事故当事人列表
	 */
	public function doConfirmConsultation(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->police['sign'];
		$rpaId = $request->param('rpaId');
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/app/accident/confirmConsultation?rpaId=' . $rpaId . '&accountToken=' . $accountToken;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 事故定责
	 */
	public function doCognizance(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->police['sign'];
		$rpaId = $request->param('rpaId');
		$accident = json_decode($request->param('accident'));
		$rpaResponsibility = $request->param('rpaResponsibility');
		$rapConciliation = $request->param('rapConciliation');
		$partyInputs = json_decode($request->param('partyInputs'));
		$arr['rpaId'] = $rpaId;
		$arr['rpaReasonCode'] = $accident->data->atCode;
		$arr['rpaResponsibility'] = $rpaResponsibility;
		$arr['rapConciliation'] = $rapConciliation;
		$arr['partyInputs'] = $partyInputs;
		$arr['rpaMangerInstructions'] = $accident->data->atRegulations;
		$arr['rpaManger'] = $accident->data->atId;
		$arr['rpaMangerText'] = $accident->data->atName;

		$url = config('domain.rpapi') . '/accident/v1/app/accident/cognizance?accountToken=' . $accountToken;

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode($arr),
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache",
				"content-type: application/json",
			),
		));

		$result = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

//		var_dump(json_encode($arr));

		if ($result === false) {
			throw new PadBizException($err);
		} else {
			$result = json_decode($result);
			if ($result) {
				if ($result->code != 0) {
					throw new PadBizException($result->msg);
				} else {
					$data = $result->data;
				}
			} else {
				throw new PadBizException('接口挂了');
			}

		}

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 推送事故给当事人
	 */
	public function doSendToParty(PadMvcRequest $request, PadMvcResponse $response) {
		$rpaId = $request->param('rpaId');
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/that/show/sendToParty?rpaId=' . $rpaId;

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
	public function doSendOnly(PadMvcRequest $request, PadMvcResponse $response) {
		$phone = $request->param('phone');
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/that/show/sendOnly?phone==' . $phone;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 给事故发送验证码
	 */
	public function doSend(PadMvcRequest $request, PadMvcResponse $response) {
		$phone = $request->param('phone');
		$rpaId = $request->param('rpaId');
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/that/show/send?phone=' . $phone . '&rpaId=' . $rpaId;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 验证码比对
	 */
	public function doVerificAndPhone(PadMvcRequest $request, PadMvcResponse $response) {
		$phone = $request->param('phone');
		$verific = $request->param('verific');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/verification/v1/app/verification/verificAndPhone?phone=' . $phone . '&verific=' . $verific;
//var_dump($url);
		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 生成事故责任书PDF
	 */
	public function doResponsibilityPDF(PadMvcRequest $request, PadMvcResponse $response) {
		$rpaId = $request->param('rpaId');
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/PDF/responsibilityPDF?rpaId=' . $rpaId;
		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 事故情形
	 */
	public function doAccidentType(PadMvcRequest $request, PadMvcResponse $response) {
		$atFatherId = $request->param('atFatherId', 0);
		$state = $request->param('state', 1);
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/general/accidentType?state=' . $state;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 驾驶证的状态
	 */
	public function doDriverLicenseState(PadMvcRequest $request, PadMvcResponse $response) {
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/general/driverLicenseState';

		$data = $response->helper->urlRequest($requestType, $url);
		//这里应该还需要将data操作成key value的数组

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
		$url = config('domain.rpapi') . '/accident/v1/general/plateType?state=1';

		$data = $response->helper->urlRequest($requestType, $url);

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
		$accountToken = $this->police['sign'];
		$requestType = 'get';
		$url = config('domain.rpapi') . '/personal/v1/app/insurance/company/queryAll?accountToken=' . $accountToken;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/***
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 根据车牌号获取车辆信息
	 */
	public function doCarInfo(PadMvcRequest $request, PadMvcResponse $response) {
		$vlpNo = $request->param('vlp_no', '渝CS8975');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/external/v2/car/info?vlp_no=' . urlencode($vlpNo);

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));

	}

	/***
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 根据身份证获取信息
	 */
	public function doDriverInfo(PadMvcRequest $request, PadMvcResponse $response) {
		$partyIdnum = $request->param('party_idnum', '510211197001010342');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/external/v2/user/driver?party_idnum=' . $partyIdnum;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 用户拒绝
	 */
	public function doRefused(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->police['sign'];
		$phone = $request->param('phone');
		$rpaId = $request->param('rpaId');
		$acApId = $request->param('ac_apId');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/accident/v1/that/show/refused?rpaId=' . $rpaId . '&phone=' . $phone
			. '&ac_apId=' . $acApId . '&isAgreed=false';

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 用户确认
	 */
	public function doComfirm(PadMvcRequest $request, PadMvcResponse $response) {
		$phone = $request->param('phone');
		$rpaId = $request->param('rpaId');
		$acApId = $request->param('ac_apId');
		$code = $request->param('code');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/accident/v1/that/show/comfirm?rpaId=' . $rpaId . '&phone=' . $phone
			. '&ac_apId=' . $acApId . '&code=' . $code;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 事故当事人确认
	 */
	public function doPartyComfirm(PadMvcRequest $request, PadMvcResponse $response) {
		$rpaId = $request->param('rpaId');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/accident/v1/that/show/partyComfirm?rpaId=' . $rpaId;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 校验驾驶人
	 */
	public function doCheckDriver(PadMvcRequest $request, PadMvcResponse $response) {
		$partyIdnum = $request->param('party_idnum');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/external/v2/user/driver?party_idnum=' . $partyIdnum;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 校验驾驶人
	 */
	public function doCheckCar(PadMvcRequest $request, PadMvcResponse $response) {
		$vlpNo = $request->param('vlp_no');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/external/v2/car/info?vlp_no=' . $vlpNo;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 根据警察获取高速公路路段
	 */
	public function doRoad(PadMvcRequest $request, PadMvcResponse $response) {
		$accountId = $this->police['uid'];
		$requestType = 'post';
		$url = config('domain.rpapi') . '/external/v2/user/road?user_id=' . $accountId;

		$data = $response->helper->urlRequest($requestType, $url);

		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}



}






