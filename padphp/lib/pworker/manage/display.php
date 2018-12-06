<?php

class PadLib_Pworker_Manage_Display {
	
	static public function getHeader () {
		return '<!DOCTYPE html><head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<script src="http://libs.baidu.com/jquery/1.9.0/jquery.js"></script>
			<style>
				html, body { font-size:12px; }
				.main { width:1100px; margin:0px auto; }
				table {width:100%;}
			</style>
			</head><body><div class="main">';
	}
	
	static public function getFooter () {
		return '</div></body></html>';
	}
	
	static public function list2table ($array) {
		$return = array();
		$return[] = '<table>';
		foreach ($array as $item) {
			$return[] = '<tr><td>'.implode('</td><td>', $item).'</td></tr>';
		}
		$return[] = '</table>';
		return implode('', $return);
	}
}

