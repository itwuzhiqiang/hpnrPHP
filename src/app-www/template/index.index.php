<html>
<head>
	<title><?= $title ?></title>
	<meta http-equiv="content-type" content="application/pdf"/>
	<meta name="viewport" content="width=device-width, initial-scale=1 ,maximum-scale=1.0, user-scalable=0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<script src="https://cdn.bootcss.com/jquery/3.2.1/jquery.js"></script>
	<script src="http://cdn.padkeji.com/paho/mqtt/mqttws31.js"></script>
	<link rel="stylesheet" type="text/css" href="/static/iconfont/iconfont.css"/>
	<link rel="stylesheet" type="text/css" href="/static/css/DateSelector.css"/>

	<script src="http://padcdn.oss-cn-beijing.aliyuncs.com/layer/mobile2.0/layer.js"></script>

	<script src="http://cdn.padkeji.com/swiper/js/swiper.jquery.min.js"></script>
	<script src="/static/js/laydate/laydate.js"></script>

	<link rel="stylesheet" href="http://cdn.padkeji.com/layui/1.0.9/css/layui.css">
	<link rel="stylesheet" href="http://cdn.padkeji.com/swiper/css/swiper.min.css">
	<script type="text/javascript" src="http://webapi.amap.com/maps?v=1.4.3&key=f17cf22f26b8937cb7c84ec3723e9cd4"></script>
	<script src="http://api.map.baidu.com/api?v=2.0&ak=9111654c94324102ef8d63b242868319"></script>
</head>
<body>
<div id="application">
	应用加载中
</div>
<script>
	$(function () {
		PADAPP_START('<?= PAD_ENVIRONMENT ?>', '<?= $appPage ?>', <?= json_encode($appParams) ?>);
	});
</script>
<script src="/bundle/app.js"></script>
<script type="text/javascript" src="/static/js/DateSelector.js"></script>
</body>
</html>