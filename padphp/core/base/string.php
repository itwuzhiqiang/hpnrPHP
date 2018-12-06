<?php

class PadBaseString {

	/**
	 * 特有方法，将 "Word1Word2_Hello" 替换成 "word1_word2/hello"
	 */
	static public function padStrtolower($string) {
		$string = str_replace('\\', '$$$', $string);
		$string = preg_replace('/([A-Z])/', '_$1', $string);
		$string = str_replace('__', '/', $string);
		$return = ltrim(strtolower($string), '_');
		$return = str_replace('$$$', '\\', $return);
		return $return;
	}

	/**
	 * 特有方法，将 "word1_word2" 替换成 "Word1Word2"
	 */
	static public function padStrtoupper($string) {
		$string = str_replace('\\', '$$$ ', $string);
		$string = preg_replace('/([A-Z])/', '_$1', $string);
		$string = str_replace(array(
			'/',
			'__'
		), '/_', $string);
		$string = trim($string, '_');
		$string = str_replace('_', ' ', $string);
		$return = str_replace(array(
			' ',
			'/'
		), array(
			'',
			'_'
		), ucwords($string));
		return str_replace('$$$', '\\', $return);
	}

	static public function truncate($string, $length, $postfix = '..') {
		if (mb_strlen($string, 'utf8') > $length) {
			return mb_substr($string, 0, $length, 'utf-8') . $postfix;
		} else {
			return $string;
		}
	}
}

