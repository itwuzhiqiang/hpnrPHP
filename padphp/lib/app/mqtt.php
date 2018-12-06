<?php

class PadLib_App_Mqtt {

	static public function publish ($key, $data) {
		$pCurl = new PadLib_Pcurl();

		$baseUrl = 'http://padinx:3000';
		$params = array(
			'key' => $key,
			'data' => json_encode($data)
		);

		if ($_SERVER['PAD_ENVIRONMENT'] == 'dev' && $_SERVER['PADDEV_USER']) {
			$baseUrl = 'http://dever_' . $_SERVER['PADDEV_USER'] . ':3000';
		}

		$baseUrl = $baseUrl . '/mqtt/publish?';
		$pCurl->get($baseUrl . http_build_query($params));

		return true;
	}
}

