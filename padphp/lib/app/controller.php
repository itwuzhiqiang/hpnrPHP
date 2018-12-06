<?php

class PadLib_App_Controller {

	public function doApp (PadMvcRequest $request, PadMvcResponse $response) {
		$page = $request->param('page');
		$params = $request->params;

		$appPage = str_replace('.', '/', $page);
		$appParams = $params;

		$response->set('appPage', $appPage);
		$response->set('appParams', $appParams);

		$response->templateLayout(null);
		$response->template('&' . __DIR__ . '/tpl.app_index.php');
	}
}



