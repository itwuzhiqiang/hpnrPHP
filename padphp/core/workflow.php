<?php

class PadWorkflow {
	private $config = array();
	private $flowList = array();

	public function __construct($bootFile) {
		$this->config = include($bootFile);
	}

	private function initModel ($name) {
		if (!isset($this->flowList[$name])) {
			$cfg = $this->config[$name];
			$xmlString = file_get_contents($cfg['config']);

			$builder = new PadLib_Petrinet_Builder();
			$builder->loadXml($xmlString);
			$net = $builder->getPetrinet();
			$this->flowList[$name] = new PadWorkflowModel($name, $net, $cfg);
		}
	}

	/**
	 * 返回工作流模型
	 * @param $name
	 * @return PadWorkflowModel
	 */
	public function get ($name) {
		$this->initModel($name);
		return $this->flowList[$name];
	}
}

class PadWorkflowModel {
	private $name;
	private $network;
	private $config;

	private $userType;
	private $userId;

	public function __construct($name, $network, $config) {
		$this->name = $name;
		$this->network = $network;
		$this->config = $config;
	}

	public function setUser ($userType, $userId = null) {
		$this->userType = $userType;
		$this->userId = $userId;
	}

	private function getHost ($action, $mix) {
		$entitySign = $this->network->config('entity');
		$entityName = null;
		$model = null;
		if (strpos($entitySign, 'entity::') === 0) {
			$entityName = substr($entitySign, strlen('entity::'));
		}

		if ($action == 'get') {
			$return = ety($entityName)->get($mix);
			vcheck($return, '!ety_null', 'Host对象不能为空实体');
			return $return;
		}
	}

	public function getNetwork () {
		return $this->network;
	}

	/*
	 * 执行超时的AutoFire任务
	 */
	public function fireTimeoutFire () {
		$entitySign = $this->network->config('entity');
		if (strpos($entitySign, 'entity::') === 0) {
			$entityName = substr($entitySign, strlen('entity::'));
		}

		$network = $this->getNetwork();
		foreach ($network->getAutoFireWithTimeoutTransitions() as $fireInfo) {
			// 暂时就处理一个place的情况
			// @todo 多个place是合并分支的情况,需要取最新的一个时间
			$place = array_shift($fireInfo['place']);
			$entityList = ety($entityName)->loader()
				->query('where wf_status = ?', $place)
				->getDataList();
			foreach ($entityList as $entity) {
				$latestTime = database('default')->getOne('
					select create_time from workflow_place_log 
					where `name` = ? and `place` = ? and `host_id` = ?
					order by create_time desc limit 1',
					$this->name, $place, $entity->id
				);

				// 超时的时候,自动发送
				if ($latestTime && $latestTime + $fireInfo['timeout'] < time()) {
					workflow($this->name)->get($entity->id)->fire($fireInfo['transition']);
				}
			}
		}
	}

	/**
	 * 获取一个绑定的工作流
	 * @param $name
	 * @param $id
	 * @return PadWorkflowModelState
	 */
	public function get ($id) {
		$host = $this->getHost('get', $id);
		$state = $this->network->newState($host, $this->name);
		$state->setUser($this->userType, $this->userId);
		foreach ($this->config as $key => $val) {
			if (strpos($key, 'on') === 0) {
				$state->addEvent(substr($key, 2), $val);
			}
		}
		return new PadWorkflowModelState($this, $state);
	}
}

class PadWorkflowModelState {
	public $model;

	/**
	 * @var PadLib_Petrinet_PetrinetState
	 */
	public $state;

	public function __construct($model, $state) {
		$this->model = $model;
		$this->state = $state;
	}

	public function setUser ($userType, $userId = null) {
		return $this->state->setUser($userType, $userId);
	}

	public function getStatus () {
		return $this->state->getStatus();
	}

	/**
	 * 获取当前状态的描述
	 */
	public function getStatusNamed () {
		return $this->state->getNamed();
	}

	public function getDotSvg () {
		return $this->state->getDotSvg();
	}

	/**
	 * 设置状态
	 * @param $tokens
	 * @param array $context
	 */
	public function setState ($tokens, $context = array()) {
		$this->state->getHost()->wfSetState($tokens, $context);
	}

	/**
	 * 是不是允许激活
	 * @param $trans
	 * @return mixed
	 */
	public function isAllowFire ($trans) {
		return $this->state->isEnabled($trans);
	}

	public function checkAllowFire ($trans, $params = array()) {
		$isAllow = $this->isAllowFire($trans);
		if (!$isAllow) {
			$entity = $this->state->getEntity();
			$message = '不允许的操作(6011)';
			if ($entity && method_exists($entity, 'getWFErrorMessage')) {
				$getMessage = call_user_func_array(array($entity, 'getWFErrorMessage'), $params);
				if ($getMessage) {
					$message = $getMessage;
				}
			}
			throw new PadErrorException($message);
		}
	}

	/**
	 * 获得允许fire的列表
	 */
	public function getAllowFireList () {
		return $this->state->getAllowFireList();
	}

	/**
	 * 激活
	 * @param $trans
	 * @return mixed
	 */
	public function fire () {
		return call_user_func_array(array($this->state, 'fire'), func_get_args());
	}
}


