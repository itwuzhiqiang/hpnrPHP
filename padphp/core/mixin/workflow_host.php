<?php

trait PadMixin_WorkflowHost {

	/**
	 * 设置token
	 * @param $tokens
	 */
	public function wfSetState ($tokens, $context = array()) {
		$this->set('wf_state', json_encode(array(
			'tokens' => $tokens,
			'context' => $context,
		)));
		$this->set('wf_status', implode(',', array_keys($tokens)));
		$this->flush();
	}

	/**
	 * 获取当前的token
	 * @return mixed
	 */
	public function wfGetTokens () {
		if (!$this->model()->fieldExists('wf_state')) {
			throw new PadErrorException('wf_state字段不存在');
		}

		if (!$this->wf_state || $this->wf_state === 'NULL') {
			return 'NULL';
		}

		$state = json_decode($this->wf_state, true);
		return array($state['tokens'], $state['context']);
	}
}

