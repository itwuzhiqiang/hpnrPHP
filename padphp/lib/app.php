<?php

class PadLib_App {

	static public function mvcStart ($bootDir, PadMvcRequest $request, PadMvcResponse $response) {
		$controller = new PadLib_App_Controller();

		$projectConfigFile = $bootDir . '/../padproject.json';
		$appConfigFile = $bootDir . '/padapp.json';
		$appConfig = json_decode(file_get_contents($appConfigFile), true);

		if (PAD_ENVIRONMENT == 'dev') {
			$projectConfig = json_decode(file_get_contents($projectConfigFile), true);

			$request->setParam('projectKey', $projectConfig['projectKey']);
			$request->setParam('appKey', $appConfig['appKey']);

			$response->set('projectKey', $projectConfig['projectKey']);
			$response->set('appKey', $appConfig['appKey']);
			$response->set('userKey', $_SERVER['PADDEV_USER']);
		}

		$response->set('appPlatform', $appConfig['platform']);

		$controller->doApp($request, $response);
	}
}
