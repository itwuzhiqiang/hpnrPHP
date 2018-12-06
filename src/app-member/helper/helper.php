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
			$code = $arr['code'];
			$msg = $arr['msg'];
			if ($code != 1) {
				throw new PadBizException($msg);
			} else {
				$data = $arr;
			}
		} else {
			throw new PadBizException('连接失败请联系管理员111');//事故接口经常挂
		}

		return $data;
	}

	public function urlRequest1($type, $url, $arr = array()) {
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
			$data = $arr;
		} else {
			throw new PadBizException('连接失败请联系管理员111');//事故接口经常挂
		}

		return $data;
	}

}