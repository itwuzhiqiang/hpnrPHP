<?php

class PadOrmFlush {

	public $cacheVersionKeys = array();

	public $cacheDataKeys = array();

	public function execute() {
		$orm = $GLOBALS['pad_core']->orm;
		foreach ($orm->entityChangedList as $entityName => $list) {
			foreach ($list as $entity) {
				$entity->flush();
			}
		}
		foreach ($orm->entityNewList as $entityName => $list) {
			foreach ($list as $entity) {
				$entity->flush();
			}
		}
	}

	/**
	 * 实体更改时，更新数据缓存
	 */
	public function refreshCacheEntity($entity, $fdatas = array()) {
		$entityName = $entity->_pvtVars->etyConfig->entityName;
		$nPkField = $entity->_pvtVars->etyConfig->pkField;
		$cacheRedis = $GLOBALS['pad_core']->orm->cacheRedis;
		if ($cacheRedis) {
			/**
			 * 单条的缓存
			 */
			if (0) {
				$res = $cacheRedis->hDel('ety.item@' . $entityName, $entity->$nPkField);
			}
			
			/**
			 * 实体自身的所有缓存
			 */
			$keys = $cacheRedis->sMembers('ety.ids@' . $entityName);
			if ($keys) {
				array_push($keys, 'ety.ids@' . $entityName);
				$cacheRedis->delete($keys);
			}
			
			/**
			 * 实体对关系的所有缓存
			 */
			$etyRltKeys = $cacheRedis->sMembers('ety.rlt@' . $entityName);
			if ($etyRltKeys) {
				foreach ($etyRltKeys as $rltStr) {
					$isFail = false;
					$tmp = explode('.', $rltStr);
					$rEntityName = $entityName;
					$rEntityId = $entity;
					foreach ($tmp as $rk) {
						$etyCfg = $GLOBALS['pad_core']->orm->getEntityConfig($rEntityName);
						if (! isset($etyCfg->relations[$rk])) {
							/**
							 * 没有发现关系KEY，删除这个
							 */
							$isFail = true;
							$cacheRedis->sRem('ety.rlt@' . $entityName, $rltStr);
							break;
						}
						
						$rlc = $etyCfg->relations[$rk];
						$rEntityName = $rlc['entity_name'];
						$rEntityId = $rEntityId->$rk;
					}
					
					if (! $isFail && ! $rEntityId->isnull) {
						$pkField = $rEntityId->_pvtVars->etyConfig->pkField;
						$rEntityId = $rEntityId->{$pkField};
						$keys = $cacheRedis->sMembers('ety.ids@' . $entityName . '.' . $rEntityName . '.' . $rEntityId);
						if ($keys) {
							array_push($keys, 'ety.ids@' . $entityName . '.' . $rEntityName . '.' . $rEntityId);
							$cacheRedis->delete($keys);
						}
					}
				}
			}
			
			/**
			 * 如果是更改的实体，需要刷新更新之前的缓存
			 */
			if ($fdatas) {
				$etyCfg = $GLOBALS['pad_core']->orm->getEntityConfig($entityName);
				$clone = clone $entity;
				$clone->_pvtVars->fversion = 0;
				foreach ($etyCfg->relations as $k => $null) {
					unset($clone->$k);
				}
				$clone->sets($fdatas);
				$this->refreshCacheEntity($clone);
				unset($clone);
			}
		}
	}
}

