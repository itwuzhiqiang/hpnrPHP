<?php

class PadLib_Grant {

	static public function verification ($verification) {
		$verification = strtoupper($verification);

		ob_start();
		system('ifconfig');
		$content = ob_get_clean();

		preg_match_all('/(\S+\:\S+\:\S+\:\S+\:\S+\:\S+)/', $content, $matches);
		foreach ($matches[1] as $mac) {
			$mac = strtoupper($mac);
			$code = strtoupper(md5(md5($mac) . md5($mac)));

			if ($code === $verification) {
				return true;
			}
		}

		return false;
	}
}

