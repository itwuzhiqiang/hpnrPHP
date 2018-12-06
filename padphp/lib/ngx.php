<?php

class PadLib_Ngx {

	/**
	 * 请求ngx接口
	 * @param $url
	 * @param array $params
	 * @return mixed
	 */
	static public function request ($url, $params = array()) {

		return json_decode(file_get_contents());
	}

	/**
	 * 开始运行一个任务
	 * @return PadLib_Ngx_Task
	 */
	static public function startTask () {
		return new PadLib_Ngx_Task();
	}
}

class PadLib_Ngx_Task {

	public function __construct() {
	}

	/**
	 * 添加日志
	 * @param $log
	 */
	public function setLog ($log) {
		echo $log . "\n";
		flush();
	}

	/**
	 * 添加状态字
	 * @param $data
	 */
	public function setContext ($data) {
		echo '##TASKCONTEXT##' . json_encode($data) . "\n";
		flush();
	}
}


