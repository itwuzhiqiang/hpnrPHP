<?php

class PadLib_Dataql_Request {
	private $mvcRequest;

	public function param ($key, $default = null) {
		return $this->mvcRequest->param($key, $default);
	}
}

