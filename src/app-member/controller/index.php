<?php

class Controller_Index extends Controller_Abstract {

	public function doIndex(PadMvcRequest $request, PadMvcResponse $response) {
		$page = $request->param('page', 'home');
		$title = '快易赔';
		if (!$this->memberToken) {
			$page = 'login';
		}
		$page = str_replace('.', '/', $page);
		$response->set('appPage', $page);
		$response->set('title', $title);
		$response->set('appParams', $request->params);
		$response->setDisplayParam('layout_name', null);
		$response->template('index.index');
	}

}