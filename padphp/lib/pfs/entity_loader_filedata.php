<?php

class PadLib_Pfs_EntityLoaderFiledata extends PadOrmLoader {
	
	public function setRef($url){
		$temp = explode('?', $url);
		$temp = $temp[0];
		$temp = explode('.', $temp);
		$fExtName = strtolower($temp[count($temp) - 1]);
		if (strlen($fExtName) > 32) {
			$fExtName = '';
		}
	
		$id = md5($url);
		$ref = $GLOBALS['pad_core']->orm->getEntityModel('filedata_ref')->createByUniqueField('id', array(
			'id' => $id,
			'url' => $url,
			'extName' => $fExtName,
			'createTime' => time(),
			'updateTime' => time(),
		));
		$ref->flush();
		return $ref;
	}
	
	public function createByFile($file, $options = array()){
		$id = md5(uniqid() . rand(0, 100000));
		$tmpName = explode('/', $file);
		$uploadName = $tmpName[count($tmpName) - 1];
		$uploadName = explode('?', $uploadName);
		$uploadName = $uploadName[0];
		$extName = explode('.', $uploadName);
		if (strlen($extName[1]) > 32) {
			$extName[1] = '';
		}
		
		$content = file_get_contents($file);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mineType = finfo_file($finfo, $file);
	
		$entity = $GLOBALS['pad_core']->orm->getEntityModel('filedata')->create();
		$entity->sets(array(
			'id' => $id,
			'uploadName' => $uploadName,
			'extName' => $extName[1],
			'mineType' => $mineType,
			'content' => $content,
			'size' => strlen($content),
			'createTime' => time(),
			'updateTime' => time(),
		));
		$entity->flush();
		return $entity;
	}
	
	public function createByUrl($url, $options = array()){
		$id = md5(uniqid() . rand(0, 100000));
		$tmpName = explode('/', $url);
		$uploadName = $tmpName[count($tmpName) - 1];
		$uploadName = explode('?', $uploadName);
		$uploadName = $uploadName[0];
		$extName = explode('.', $uploadName);
	
		$pcurl = new PadLib_Pcurl();
		list($content, $info) = $pcurl->get($url, $options);
		$mineType = $info['content_type'];
		if (PadBaseFile::getSuffixByMineType($mineType) !== null) {
			$extName = PadBaseFile::getSuffixByMineType($mineType);
		} else {
			$extName = isset($extName[1]) ? $extName[1] : 'binary';
		}
		
		$content = $content;
		if (!$mineType || !$content || (isset($options['type']) && strpos($mineType, $options['type']) === false)) {
			return false;
		}
	
		$entity = $GLOBALS['pad_core']->orm->getEntityModel('filedata')->create();
		$entity->sets(array(
			'id' => $id,
			'uploadName' => $uploadName,
			'extName' => $extName,
			'mineType' => $mineType,
			'content' => $content,
			'size' => strlen($content),
			'createTime' => time(),
			'updateTime' => time(),
		));
		$entity->flush();
		return $entity;
	}
	
	public function createByCurlData($url, $content, $info){
		$id = md5(uniqid() . rand(0, 100000));
		$tmpName = explode('/', $url);
		$uploadName = $tmpName[count($tmpName) - 1];
		$uploadName = explode('?', $uploadName);
		$uploadName = $uploadName[0];
		$extName = explode('.', $uploadName);
		if (strlen($extName[1]) > 32) {
			$extName[1] = '';
		}
		$extName = $extName[1];
		
		if (strpos($info['content_type'], ';') !== false) {
			list($info['content_type'], $null) = explode(';', $info['content_type']);
		}
		$mineType = $info['content_type'];

		if (PadBaseFile::getSuffixByMineType($mineType) !== null) {
			$extName = PadBaseFile::getSuffixByMineType($mineType);
		}
	
		$entity = $GLOBALS['pad_core']->orm->getEntityModel('filedata')->create();
		$entity->sets(array(
			'id' => $id,
			'uploadName' => $uploadName,
			'extName' => $extName,
			'mineType' => $mineType,
			'content' => $content,
			'size' => strlen($content),
			'createTime' => time(),
			'updateTime' => time(),
		));
		$entity->flush();
		return $entity;
	}
	
	/**
	 * 创建一个新文件
	 * @param unknown $data
	 */
	public function createByData($data){
		$entity = $GLOBALS['pad_core']->orm->getEntityModel('filedata')->create();
		$id = md5(uniqid() . rand(0, 100000));
		$tmpName = explode('.', $data['uploadName']);
		$extName = $tmpName[count($tmpName) - 1];
		if (strlen($extName) > 32) {
			$extName = '';
		}
		
		if (PadBaseFile::getSuffixByMineType($data['mineType']) !== null) {
			$extName = PadBaseFile::getSuffixByMineType($data['mineType']);
		}
		$entity->sets(array(
			'id' => $id,
			'uploadName' => $data['uploadName'],
			'extName' => $extName,
			'mineType' => $data['mineType'],
			'content' => $data['content'],
			'size' => strlen($data['content']),
			'createTime' => time(),
			'updateTime' => time(),
		));
		$entity->flush();
		return $entity;
	}
	
	/**
	 * 根据上传的原始数据，写入一个文件
	 * @param unknown $fileRaw
	 */
	public function createByFileRaw($fileRaw, $userId = 0){
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mineType = finfo_file($finfo, $fileRaw['tmp_name']);
		
		return $this->createByData(array(
			'uploadName' => $fileRaw['name'],
			'mineType' => $mineType,
			'userId' => $userId,
			'content' => file_get_contents($fileRaw['tmp_name']),
		));
	}
}


