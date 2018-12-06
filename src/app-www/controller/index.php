<?php

class Controller_Index extends Controller_Abstract {

	public function doIndex(PadMvcRequest $request, PadMvcResponse $response) {
		$page = $request->param('page', 'home');
		$title = '快易赔';

		if (!$this->police) {
			echo "<script>alert('参数错误')</script>";
			exit;
		}

		$page = str_replace('.', '/', $page);
		$response->set('appPage', $page);
		$response->set('title', $title);
		$response->set('appParams', $request->params);
		$response->setDisplayParam('layout_name', null);
		$response->template('index.index');
	}

	public function doPdf(PadMvcRequest $request, PadMvcResponse $response) {
		$response->setDisplayParam('layout_name', null);
		$pdfUrl = $request->param('pdfUrl', 0);
		$response->set('pdfUrl', $pdfUrl);
		$response->template('index.goods');
	}

}






