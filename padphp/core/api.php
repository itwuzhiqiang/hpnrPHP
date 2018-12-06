<?php

class PadApi {

	static public function padinx () {
		static $padinx;
		if (!isset($padinx)) {
			$padinx = new PadApiPadinx();
		}
		return $padinx;
	}
}
