<?php

class PadLib_Pfs {
	static public $gOptions;
	
	/**
	 * 强制注册文件管理的实体
	 */
	static public function initOrm ($options = array()) {
		self::$gOptions = array_merge(array(
			'filedata_tbname' => 'filedata',
			'filedata_ref_tbname' => 'filedataRef',
			'watermark' => null,
		), $options);
		
		$GLOBALS['pad_core']->orm->registerEntityModel('filedata', 'PadLib_Pfs_EntityFiledata', 'PadLib_Pfs_EntityLoaderFiledata');
		$GLOBALS['pad_core']->orm->registerEntityModel('filedata_ref', 'PadLib_Pfs_EntityFiledataRef');
	}
	
	public function __construct($options = array()) {
		$options = array_merge(array(
			'watermark' => null,
		), $options);
		$this->options = $options;
	}
	
	/**
	 * 管理运行
	 */
	public function managerExecute($request, $response){
		$manager = new PadLib_Pfs_Manager($this);
		$manager->execute($request, $response);
	}
	
	/**
	 * 根据请求的URL，获得图片资源
	 */
	public function httpOutput(){
		// 获得当前请求的URI
		$requestUri = substr($_SERVER['REQUEST_URI'], 1);
		$extName = null;
		if (($pos = strpos($requestUri, '.')) !== false) {
			$extName = substr($requestUri, $pos + 1);
			$requestUri = substr($requestUri, 0, $pos);
		}
		$requestUri = str_replace('pfs/', '', $requestUri);
		$tmp = explode('/', $requestUri);
		
		$id = $tmp[0];
		$thumbSize = null;
		$thumbParams = array(
			'full' => false,
		);
		if (count($tmp) > 1) {
			$thumbSize = explode('x', $tmp[1]);
			if (isset($thumbSize[2]) && $thumbSize[2] == 'f') {
				$thumbParams['full'] = true;
			} else if (isset($thumbSize[2]) && $thumbSize[2] == 'wf') {
				$thumbParams['full'] = true;
				$thumbParams['watermark'] = true;
			}
		}
		
		$fContent = null;
		$fExtName = null;
		
		$filedata = $GLOBALS['pad_core']->orm->getEntityModel('filedata')->get($id);
		if ($filedata->isnull) {
			$filedata = $GLOBALS['pad_core']->orm->getEntityModel('filedata_ref')->get($id);
			if (!$filedata->isnull) {
				// 如果数据来源是引用
				$pcurl = new PadLib_Pcurl();
				$url = $filedata->url;
				list($fContent, $info) = $pcurl->get($url);
				$fExtName = $filedata->extName;
			}
		} else {
			$fContent = $filedata->content;
			$fExtName = $filedata->extName;
		}
		
		// 文件类型
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mineType = finfo_buffer($finfo, $fContent);
		
		// 文件不存在
		if (!$fContent) {
			if (strpos($mineType, 'image') !== false) {
				header('Content-Type:' . $mineType);
				$content = file_get_contents(__DIR__.'/404.jpg');
				if ($thumbSize) {
					$content = $this->thumbImage($content, $thumbSize[0], $thumbSize[1], $thumbParams);
				}
				echo $content;
			} else {
				header("http/1.1 404 not found"); 
				header("status: 404 not found"); 
			}
			echo '404';
			exit;
		}
		
		// 文件存在
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mineType = finfo_buffer($finfo, $fContent);
		header('Content-type:' . $mineType);
		if ($thumbSize && strpos($mineType, 'image') !== false) {
			$fContent = $this->thumbImage($fContent, $thumbSize[0], $thumbSize[1], $thumbParams);
		} else if (strpos($mineType, 'image') !== false) {
			// 原图始终显示水印
			$thumbParams['watermark'] = true;
		}

		if ($this->options['watermark'] && isset($thumbParams['watermark']) && $thumbParams['watermark']) {
			$textColor = isset($this->options['watermark']['text-color']) ? $this->options['watermark']['text-color'] : '#EEEEEE';
			$font = isset($this->options['watermark']['text-font']) ? $this->options['watermark']['text-font'] : PAD_RC_DIR . '/font/arial.ttf';
			$text = $this->options['watermark']['text'];
			$fontSize = $this->options['watermark']['text-size'];
			
			$imagick = new Imagick();
			$imagick->readImageBlob($fContent);
			
			$draw = new ImagickDraw();
			$draw->setFillColor($textColor);
			$draw->setFont($font);
			$draw->setFontSize($fontSize);
			$draw->setFillOpacity(0.8);
			
			$boxInfo = $imagick->queryFontMetrics($draw, $text);
			$left = $imagick->getImageWidth() - $boxInfo['textWidth'] - 10;
			$top = $imagick->getImageHeight() - 10;
			$imagick->annotateImage($draw, $left, $top, 0, $text);
			
			$fContent = $imagick->getImageBlob();
		}
		echo $fContent;
	}
	
	/**
	 * 根据图片流，生成缩略图
	 * @param unknown $content
	 * @param unknown $width
	 * @param unknown $height
	 * @param unknown $params
	 * @return string
	 */
	public function thumbImage($content, $width, $height, $params = array()){
		$params = array_merge(array(
			'full' => false,
		), $params);
	
		$blob = null;
		$imagick = new Imagick();
		$imagick->readImageBlob($content);
		$iwidth = $imagick->getImageWidth();
		$iheight = $imagick->getImageHeight();

		if ($params['full']) {
			if ($width > 0 && $height > 0) {
				$sc = ($width/$iwidth < $height/$iheight ? $height/$iheight : $width/$iwidth);
				$imagick->resizeImage($iwidth*$sc, $iheight*$sc, Imagick::FILTER_CATROM, 1, true);
			} else if ($width == 0) {
				$imagick->resizeImage($iwidth*$height/$iheight, $height, Imagick::FILTER_CATROM, 1, true);
			} else if ($height ==  0) {
				$imagick->resizeImage($width, $iheight*$width/$iwidth, Imagick::FILTER_CATROM, 1, true);
			}
		} else {
			$imagick->resizeImage($width, $height, Imagick::FILTER_CATROM, 1, true);
		}
		
		$newwidth = $imagick->getImageWidth();
		$newheight = $imagick->getImageHeight();
		
		$newimagick = new Imagick();
		$colorTransparent = new ImagickPixel('#ffffff');
		if ($width == 0 || $height == 0) {
			$newimagick->newImage($newwidth, $newheight, $colorTransparent, strtolower($imagick->getImageFormat()));
			$newimagick->compositeImage($imagick, Imagick::COMPOSITE_OVER, 0, 0);
		} else {
			$newimagick->newImage($width, $height, $colorTransparent, strtolower($imagick->getImageFormat()));
			$newimagick->compositeImage($imagick, Imagick::COMPOSITE_OVER, ($width - $newwidth)/2, ($height - $newheight)/2);
		}
		$blob = $newimagick->getImageBlob();
		$newimagick->clear();
		$newimagick->destroy();
		
		$imagick->clear();
		$imagick->destroy();
		return $blob;
	}
}