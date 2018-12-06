<?php

class PadOrmLoaderSimple {

	public function loadByIds($ids) {
		$return = array();
		$pg_orm = $GLOBALS['pad_core']->orm;
		
		if ($pg_orm->isUseMemoryCache) {
			foreach ($ids as $idx => $id) {
				if (! isset($pg_orm->entityLoadedList[$this->entityName][$id])) {
					$pg_orm->entityLoadedList[$this->entityName][$id] = $pg_orm->entityNull;
					$return[$id] = $pg_orm->entityNull;
				} else {
					$return[$id] = $pg_orm->entityLoadedList[$this->entityName][$id];
					unset($ids[$idx]);
				}
			}
		} else {
			foreach ($ids as $idx => $id) {
				$return[$id] = $pg_orm->entityNull;
			}
		}
		
		if (empty($ids)) {
			return $return;
		}
		
		$list = $this->_loadByIds($ids);
		$this->createEntityList($list);
		
		return $return;
	}

	public function createEntityList($list, $pkFiled = 'id') {
		$return = array();
		$pg_orm = $GLOBALS['pad_core']->orm;
		
		foreach ($list as $id => $row) {
			$class = clone $this->etyConfig->entityBaseMatrix;
			foreach ($row as $field => $val) {
				$class->$field = $val;
			}
			$pg_orm->entityLoadedList[$this->entityName][$row[$pkFiled]] = $class;
			$return[] = $class;
		}
		return $return;
	}
}

