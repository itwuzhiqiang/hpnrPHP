<html>
<head>
	<title>...</title>

	<?php if ($appPlatform == 'web/mobile10') { ?>
		<meta name="viewport" content="width=device-width, initial-scale=1 ,maximum-scale=1.0, user-scalable=0">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<?php } ?>

	<script src="https://cdn.bootcss.com/jquery/3.2.1/jquery.js"></script>
	<script src="http://cdn.padkeji.com/paho/mqtt/mqttws31.js"></script>

	<?php if ($appPlatform == 'web/mobile10') { ?>
		<script src="http://padcdn.oss-cn-beijing.aliyuncs.com/layer/mobile2.0/layer.js"></script>
	<?php } else if ($appPlatform == 'web/admin10') { ?>
		<script src="http://cdn.padkeji.com/layui/1.0.9/lay/dest/layui.all.js"></script>
		<script>
			window.UEDITOR_CONFIG_URL  = '/padapi/system/ueditor_config';
		</script>
		<script charset="utf-8" src="/ueditor/ueditor.config.js"></script>
		<script charset="utf-8" src="/ueditor/ueditor.all.min.js"> </script>
		<script charset="utf-8" src="/ueditor/lang/zh-cn/zh-cn.js"></script>
	<?php } ?>
	<style>
		.app-loading-box {
			text-align: center;
			position: absolute;
			top: 45%;
			left: 0;
			right: 0;
			bottom: 0;
		}

		.app-loading{
			width: 150px;
			height: 15px;
			margin: 0 auto;
		}
		.app-loading span{
			display: inline-block;
			width: 15px;
			height: 100%;
			margin-right: 5px;
			border-radius: 50%;
			background: #CCCCCC;
			-webkit-animation: load 1.04s ease infinite;
		}
		.app-loading span:last-child{
			margin-right: 0px;
		}
		@-webkit-keyframes load{
			0%{
				opacity: 1;
			}
			100%{
				opacity: 0;
			}
		}
		.app-loading span:nth-child(1){
			-webkit-animation-delay:0.13s;
		}
		.app-loading span:nth-child(2){
			-webkit-animation-delay:0.26s;
		}
		.app-loading span:nth-child(3){
			-webkit-animation-delay:0.39s;
		}
		.app-loading span:nth-child(4){
			-webkit-animation-delay:0.52s;
		}
		.app-loading span:nth-child(5){
			-webkit-animation-delay:0.65s;
		}
	</style>
</head>
<body>
<div id="application">
	<div class="app-loading-box">
		<div class="app-loading">
			<span></span>
			<span></span>
			<span></span>
			<span></span>
			<span></span>
		</div>
	</div>
</div>
<?php if (PAD_ENVIRONMENT == 'dev') { ?>
	<script src="http://<?= $appKey ?>.<?= $projectKey ?>.<?= $userKey ?>.padeex.cn:7780/app.js"></script>
<?php } else { ?>
	<script src="/public/app.js"></script>
<?php } ?>
<script>
	PADAPP_START('dev', '<?= $appPage ?>', <?= json_encode($appParams) ?>);
</script>
</body>
</html>


