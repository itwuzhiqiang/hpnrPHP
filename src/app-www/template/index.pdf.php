<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta http-equiv="content-type" content="application/pdf"/>
	<meta name="viewport" content="initial-scale=1, maximum-scale=1">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta content="telephone=no" name="format-detection"/>
	<style>
		* {
			margin: 0;
			padding: 0;
		}

		body {
			width: 375px;
		}

		#asd img {
			width: 375px !important;
			overflow: hidden;
			height: auto;
			padding: 0;
			margin: 0;
		}

		#asd div {
			width: 375px !important;
			overflow: hidden;
			height: auto;
			padding: 0;
			margin: 0;
		}

		#asd {
			width: 375px !important;
			overflow: hidden;
			text-align: center;
		}
	</style>
</head>
<body>
<div id="asd">
	<span>565646</span>
<!--	<iframe src="--><?//= $pdfUrl ?><!--"></iframe>-->
	<a href="<?= $pdfUrl ?>" type="application/pdf">pdf</a>
	<embed width="100%" height="100%" name="plugin" id="plugin" src="<?= $pdfUrl ?>" type="application/pdf">
</div>
</body>
</html>