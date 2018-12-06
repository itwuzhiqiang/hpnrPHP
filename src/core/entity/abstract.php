<?php

/**
 * Created by PhpStorm.
 * User: macmini
 * Date: 16/2/25
 * Time: 下午3:55
 */
class Entity_Abstract extends PadOrmEntity {

	static public function config() {
		return array();
	}

	/** flush同时进行的赋值 */
	public function onFlush(){
		if ($this->_pvtVars->isCreate) {
			if ($this->create_time === null) {
				$this->set('create_time', time());
			}
			if ($this->update_time === null) {
				$this->set('update_time', time());
			}
		}

		if ($this->_pvtVars->isUpdate) {
			if (!isset($this->_pvtVars->formerDatas['update_time'])) {
				$this->set('update_time', time());
			}
		}
	}
}
