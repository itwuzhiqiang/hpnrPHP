<?php

class PadLib_Pfs_EntityFiledata extends PadOrmEntity {
	
	static public function config() {
		return array(
			'db_name' => 'default_fs',
			'db_table_name' => PadLib_Pfs::$gOptions['filedata_tbname'],
		);
	}
	
	/**
	 * 获得fs路径
	 * @return string
	 */
	public function getFsPath () {
		return 'fs://'.$this->id.'.'.$this->extName;
	}
	
	public function onFlush(){
		if ($this->_pvtVars->isCreate) {
			if ($this->createTime === null) {
				$this->set('createTime', time());
			}
			if ($this->updateTime === null) {
				$this->set('updateTime', time());
			}
		}
	
		if ($this->_pvtVars->isUpdate) {
			if (!isset($this->_pvtVars->formerDatas['updateTime'])) {
				$this->set('updateTime', time());
			}
		}
	}
}

