<?php

class PadLib_Kue {

	static public function execute ($type, $data = null) {
		$kueUrl = 'http://padinx:3000';
		
		$opts = array(
			'http'=>array(
				'method' => 'POST',
				'header' => 'Content-type: application/json',
				'content' => json_encode(array(
					'type' => $type,
					'data' => $data,
				)),
			)
		);

		$cxContext = stream_context_create($opts);
		$job = file_get_contents($kueUrl . '/job', false, $cxContext);
		$job = json_decode($job, true);

		$timeout = 1000 * 10 + microtime(true) * 1000;
		$tryCount = 1;
		while (true) {
			$info = file_get_contents($kueUrl . '/job/' . $job['id']);
			$info = json_decode($info, true);

			if ($info['state'] == 'complete') {
				return $info;
			} else if (microtime(true) * 1000 > $timeout) {
				return array(
					'id' => $info['id'],
					'state' => 'timeout',
				);
			} else {
				usleep(1000 * 100 * $tryCount);
				$tryCount++;
			}
		}
	}
}




