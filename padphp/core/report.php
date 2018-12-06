<?php

class PadReport {

	static public function getModel ($name) {
		// 加载一下cpt的报表引擎
		if (strpos($name, 'cpt/') === 0) {
			return cpt('report')->model('buildin/' . substr($name, 4));
		} else {
			$appClassName = 'Report_' . PadBaseString::padStrtoupper($name);
			return new $appClassName();
		}
	}
}



