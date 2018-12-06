<?php

class PadLib_Pworker_Manage_Abstract {
	public $request;
	public $response;
	
	public $datas;
	public $template;
	
	public function __construct ($request, $response) {
		$this->request = $request;
		$this->response = $response;
	}
	
	public function display () {
		ob_start();
		$content = ob_get_clean();
		$this->response->end($content);
	}
}

