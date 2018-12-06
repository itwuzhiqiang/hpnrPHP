<?php

/**
 *
 * @property PadMvcHelperForm $form
 * @method PadMvcHelperPager pager()
 */
class PadMvcHelper {

	public function __get($name) {
		if ($name == 'form') {
			return new PadMvcHelperForm();
		} else {
			$className = 'Helper_' . PadBaseString::padStrtoupper($name);
			return new $className();
		}
	}

	public function __call($name, $argvs) {
		if ($name == 'pager') {
			return new PadMvcHelperPager($argvs);
		} else {
			$className = 'Helper_Helper';
			$class = new $className();
			return call_user_func_array(array(
				$class,
				$name
			), $argvs);
		}
	}
}


