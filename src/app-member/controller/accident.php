<?php

class Controller_Accident extends Controller_Abstract {

	/**
	 * @param PadMvcRequest $request
	 * @param PadMvcResponse $response
	 * 修改密码
	 */
	public function doHistoryProcessed(PadMvcRequest $request, PadMvcResponse $response) {
		$data["list"] = array();
		$accountToken = $this->memberToken;
		$start = $request->param('start') ?: 1;
		$length = $request->param('length') ?: 20;
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/app/accident/history/processed?accountToken=' .
			$accountToken . '&start=' . $start . '&length=' . $length;
		$data["list"] = $response->helper->urlRequest($requestType, $url);
		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	public function doHistoryGetAll(PadMvcRequest $request, PadMvcResponse $response) {
		$data["list"] = array();
		$accountToken = $this->memberToken;
		//var_dump($accountToken);
		$start = $request->param('start') ?: 1;
		$length = $request->param('length') ?: 20;
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/app/accident/history/getAll?accountToken=' .
			$accountToken . '&start=' . $start . '&length=' . $length;
		$datalist['list'] = $response->helper->urlRequest($requestType, $url);
		//var_dump($data);
		$response->json(array(
			'code' => 0,
			'res' => $datalist,
		));
	}

	public function doCondition(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->memberToken;
		$rpaid = $request->param('rpaid');
		$jd = $request->param('lontitude');
		$wd = $request->param('latiturde');
		$requestType = 'post';
		$url = config('domain.rpapi') . '/accident/v1/app/accident/history/condition?accountToken=' .
			$accountToken . '&sgbh=' . $rpaid . '&jd=' . $jd . '&wd=' . $wd;
		$data = $response->helper->urlRequest($requestType, $url);
		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

	public function doSearchList(PadMvcRequest $request, PadMvcResponse $response) {

		$accountToken = $this->memberToken;
		//var_dump($accountToken);
		$start = $request->param('page') ?: 1;
		$length = $request->param('page_size') ?: 20;
		$type = $request->param('type') ?: 1;
		$requestType = 'get';
		if ($type == 1) { //全部
			$url = config('domain.rpapi') . '/accident/v1/app/accident/allProcessed?accountToken=' .
				$accountToken . '&start=' . $start . '&length=' . $length;
		} elseif ($type == 2) { //待处理
			$url = config('domain.rpapi') . '/accident/v1/app/accident/inProcessedAccidentOfUser?accountToken=' .
				$accountToken . '&start=' . $start . '&length=' . $length;
		} elseif ($type == 3) { //处理完
			$url = config('domain.rpapi') . '/accident/v1/app/accident/processedAccidentOfUser?accountToken=' .
				$accountToken . '&start=' . $start . '&length=' . $length;
		}
		$data = $response->helper->urlRequest($requestType, $url)['data'];

		$pageList['list'] = $data;
		$response->json(array(
			'code' => 0,
			'res' => $pageList,
		));
	}

	public function doObtain(PadMvcRequest $request, PadMvcResponse $response) {
		$accountToken = $this->memberToken;
		$rpaid = $request->param('rpaid');
		$requestType = 'get';
		$url = config('domain.rpapi') . '/accident/v1/app/accident/obtain?accountToken=' .
			$accountToken . '&rpaId=' . $rpaid;
		$data = $response->helper->urlRequest($requestType, $url);
		$response->json(array(
			'code' => 0,
			'res' => $data,
		));
	}

}






