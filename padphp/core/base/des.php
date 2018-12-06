<?php

class PadBaseDes {
	public $key;

	public function __construct($key) {
		$this->key = substr(md5($key), 0, 8);
	}

	public function encrypt($pure_string) {
		$encryption_key = $this->key;
		$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($pure_string), MCRYPT_MODE_ECB, $iv);
		return base64_encode($encrypted_string);
	}

	public function decrypt($encrypted_string) {
		$encrypted_string = base64_decode($encrypted_string);
		$encryption_key = $this->key;
		$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, $encrypted_string, MCRYPT_MODE_ECB, $iv);
		return trim($decrypted_string, "\0");
	}
}

