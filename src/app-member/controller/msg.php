<?php

class Controller_Msg extends Controller_Abstract {
	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 用户事件消息
	 */
	public function doEventMsg(PadMvcRequest $request, PadMvcResponse $response) {
		$token = $this->memberToken;
		$pageNum = $request->param('pageNum', 1);
		$pageSize = $request->param('pageSize', 10);
		$requestType = 'get';
		$url = config('domain.rpapi') . '/personal/v1/app/message/system/queryByCase?accountToken=' . $token . '&pageNum=' . $pageNum . '&pageSize=' . $pageSize;
		$data = $response->helper->urlRequest($requestType, $url)['data'];
		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 获取有效的顶级系统消息栏目，及栏目下最新的消息
	 */
	public function doQueryByParentId(PadMvcRequest $request, PadMvcResponse $response) {
		$token = $this->memberToken;
		$requestType = 'get';
		$url = config('domain.rpapi') . '/personal/v1/app/message/system/queryByParentId?accountToken=' . $token . '&clientType=3';
		$data = $response->helper->urlRequest($requestType, $url)['data'];
		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 获取消息详情
	 */
	public function doQueryByDetils(PadMvcRequest $request, PadMvcResponse $response) {
		$token = $this->memberToken;
		$msgId = $request->param('msgId');
		$requestType = 'get';
		$url = config('domain.rpapi') . '/personal/v1/app/message/system/queryByDetils?accountToken=' . $token . '&msgId=' . $msgId;
		$data = $response->helper->urlRequest($requestType, $url)['data'];
		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 根据消息类型获取消息
	 */
	public function doQueryByType(PadMvcRequest $request, PadMvcResponse $response) {
		$token = $this->memberToken;
		$pageNum = $request->param('pageNum', 1);
		$pageSize = $request->param('pageSize', 10);
		$typeId = $request->param('typeId');
		$requestType = 'get';
		$url = config('domain.rpapi') . '/personal/v1/app/message/system/queryByType?accountToken=' . $token
			. '&typeId=' . $typeId . '&pageNum=' . $pageNum . '&pageSize=' . $pageSize . '&clientType=3&platfromType=2';
		$data = $response->helper->urlRequest($requestType, $url)['data'];
		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

}






