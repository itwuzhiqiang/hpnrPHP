<?php

class Helper_Helper {
	public function urlRequest($type, $url, $arr = array()) {
		$pcurl = new PadLib_Pcurl();
		if ($type == 'post') {
			list($content, $info) = $pcurl->get($url, array(
				'post' => array()
			));
		} else {
			list($content, $info) = $pcurl->get($url);
		}
		$arr = json_decode($content, true);
		$data = array();
		if ($arr) {
//			var_dump($arr);exit();
			$code = $arr['code'];
			$msg = $arr['msg'];
			if ($code != 1) {
				throw new PadBizException($msg);
			} else {
				$data = $arr['data'];
			}
		} else {
			throw new PadBizException('连接失败请联系管理员');//事故接口经常挂
		}

		return $data;
	}
}