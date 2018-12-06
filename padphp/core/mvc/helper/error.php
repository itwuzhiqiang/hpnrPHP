<?php

class PadMvcHelperError {

	public $referErrors = array();

	public $errors = array();

	/**
	 * 压入一条错误
	 */
	public function message($key, $message) {
		if (!isset($this->errors[$key])) {
			$this->errors[$key] = $message;
		} else {
			$this->errors[$key] .= "\n".$message;
		}
	}

	public function getInfo($key, $subKey = null){
		$message = null;
		if (isset($this->errors[$key])) {
			$message = $this->errors[$key];
		} elseif (isset($this->referErrors[$key])) {
			$message = $this->referErrors[$key];
		} else {
			$message = null;
		}
		
		$code = 100;
		if (strpos($message, '::') !== false) {
			list($code, $message) = explode('::', $message);
		}
		$return = array(
			'code' => $code,
			'status' => $message !== null ? 'error' : 'ok',
			'message' => $message,
		);
		return $subKey ? $return[$subKey] : $return;
	}
	
	public function errorTemplate($key, $template = null){
		$info = $this->getInfo($key);
		if ($template === null) {
			$template = $GLOBALS['pad_core']->mvc->errorTemplate;
		}
		if ($template === null) {
			$template = '<span data-error="{key}" class="e-{status}">{message}</span>';
		}
		$template = str_replace('{key}', $key, $template);
		$template = str_replace('{status}', $info['status'], $template);
		$template = str_replace('{message}', $info['message'], $template);
		return $template;
	}

	/**
	 * 获得错误信息
	 */
	public function getMessage($key, $template = null) {
		$message = null;
		if (isset($this->errors[$key])) {
			$message = $this->errors[$key];
		} elseif (isset($this->referErrors[$key])) {
			$message = $this->referErrors[$key];
		} else {
			$message = null;
		}
		
		$return = null;
		if ($message && $template == null) {
			$return = '<b>' . $message . '</b>';
		} elseif ($message && $template) {
			$return = '<b>' . str_replace('###', $message, $template) . '</b>';
		} else {
			$return = null;
		}
		
		return '<span class="error" errkey="' . $key . '">' . $return . '</span>';
	}

	/**
	 * 是否有来源错误
	 */
	public function hasReferError() {
		return count($this->referErrors) > 0;
	}

	/**
	 * 是否有错误
	 */
	public function hasError() {
		return count($this->errors) > 0;
	}

	public function execute($type = null) {
		$activeRequest = $GLOBALS['pad_core']->mvc->activeRequest;
		$activeResponse = $GLOBALS['pad_core']->mvc->activeResponse;
		
		/**
		 * 传入check参数，始终返回true，以便不执行实际的代码
		 */
		if (isset($activeRequest->params['_forcheckrequest'])) {
			$errors = $this->errors;
			foreach ($errors as $key => $str) {
				if ($type == 'json') {
					$errors[$key] = $this->getInfo($key);
				} else {
					$errors[$key] = $this->getMessage($key);
				}
			}
			$activeResponse->json($errors);
			return true;
		}
		
		/**
		 * 没传入实际check参数
		 */
		if ($this->hasError()) {
			if (! isset($activeRequest->params['_padreferdata'])) {
				print_r($this->errors);
			} else {
				$padreferdata = unserialize(base64_decode($activeRequest->params['_padreferdata']));
				list ($controller, $action, $params) = $padreferdata;
				$activeResponse->jump($controller, $action, $params);
				return true;
			}
		}
		
		return false;
	}
}

