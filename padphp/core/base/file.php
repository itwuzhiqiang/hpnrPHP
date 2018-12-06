<?php

class PadBaseFile {

	public static $mineTypes = array(
		'chm' => 'application/octet-stream',
		'ppt' => 'application/vnd.ms-powerpoint',
		'xls' => 'application/vnd.ms-excel',
		'doc' => 'application/msword',
		'exe' => 'application/octet-stream',
		'rar' => 'application/octet-stream',
		'js' => "javascript/js",
		'css' => "text/css",
		'hqx' => "application/mac-binhex40",
		'bin' => "application/octet-stream",
		'oda' => "application/oda",
		'pdf' => "application/pdf",
		'ai' => "application/postsrcipt",
		'eps' => "application/postsrcipt",
		'es' => "application/postsrcipt",
		'rtf' => "application/rtf",
		'mif' => "application/x-mif",
		'csh' => "application/x-csh",
		'dvi' => "application/x-dvi",
		'hdf' => "application/x-hdf",
		'nc' => "application/x-netcdf",
		'cdf' => "application/x-netcdf",
		'latex' => "application/x-latex",
		'ts' => "application/x-troll-ts",
		'src' => "application/x-wais-source",
		'zip' => "application/zip",
		'bcpio' => "application/x-bcpio",
		'cpio' => "application/x-cpio",
		'gtar' => "application/x-gtar",
		'shar' => "application/x-shar",
		'sv4cpio' => "application/x-sv4cpio",
		'sv4crc' => "application/x-sv4crc",
		'tar' => "application/x-tar",
		'ustar' => "application/x-ustar",
		'man' => "application/x-troff-man",
		'sh' => "application/x-sh",
		'tcl' => "application/x-tcl",
		'tex' => "application/x-tex",
		'texi' => "application/x-texinfo",
		'texinfo' => "application/x-texinfo",
		't' => "application/x-troff",
		'tr' => "application/x-troff",
		'roff' => "application/x-troff",
		'shar' => "application/x-shar",
		'me' => "application/x-troll-me",
		'ts' => "application/x-troll-ts",
		'gif' => "image/gif",
		'jpeg' => "image/pjpeg",
		'jpg' => "image/jpeg",
		'jpe' => "image/pjpeg",
		'ras' => "image/x-cmu-raster",
		'pbm' => "image/x-portable-bitmap",
		'ppm' => "image/x-portable-pixmap",
		'xbm' => "image/x-xbitmap",
		'xwd' => "image/x-xwindowdump",
		'ief' => "image/ief",
		'tif' => "image/tiff",
		'tiff' => "image/tiff",
		'pnm' => "image/x-portable-anymap",
		'pgm' => "image/x-portable-graymap",
		'rgb' => "image/x-rgb",
		'xpm' => "image/x-xpixmap",
		'txt' => "text/plain",
		'c' => "text/plain",
		'cc' => "text/plain",
		'h' => "text/plain",
		'html' => "text/html",
		'htm' => "text/html",
		'htl' => "text/html",
		'rtx' => "text/richtext",
		'etx' => "text/x-setext",
		'tsv' => "text/tab-separated-values",
		'mpeg' => "video/mpeg",
		'mpg' => "video/mpeg",
		'mpe' => "video/mpeg",
		'avi' => "video/x-msvideo",
		'qt' => "video/quicktime",
		'mov' => "video/quicktime",
		'moov' => "video/quicktime",
		'movie' => "video/x-sgi-movie",
		'au' => "audio/basic",
		'snd' => "audio/basic",
		'wav' => "audio/x-wav",
		'aif' => "audio/x-aiff",
		'aiff' => "audio/x-aiff",
		'aifc' => "audio/x-aiff",
		'swf' => "application/x-shockwave-flash",
		'csv' => "application/csv"
	);

	static public function getSuffixByMineType($mineType) {
		$suffixes = array_flip(self::$mineTypes);
		return isset($suffixes[$mineType]) ? $suffixes[$mineType] : null;
	}
	
	static public function getMineTypeBySuffix($suffix) {
		return isset(self::$mineTypes[$suffix]) ? self::$mineTypes[$suffix] : 'binary/binary';
	}

	static public function getSuffix($path) {
		$tmp = explode('.', $path);
		return strtolower($tmp[count($tmp) - 1]);
	}

	static public function scanDir ($openDir, &$files = null) {
		if ($files === null) {
			$files = array();
		}

		if (!is_dir($openDir)) {
			return $files;
		}

		$dir = dir($openDir);
		while ($file = $dir->read()) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			if (is_dir($openDir . DIRECTORY_SEPARATOR .  $file)) {
				self::scanDir($openDir . DIRECTORY_SEPARATOR .  $file, $files);
			} else {
				$files[] = $openDir . DIRECTORY_SEPARATOR .  $file;
			}
		}
		return $files;
	}
}





