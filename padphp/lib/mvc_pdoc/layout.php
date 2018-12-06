<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>PADDEV控制台</title>
	<script src="http://cdn.padkeji.com/layer/3.0.3/layer.js"></script>
	<script src="http://cdn.padkeji.com/jquery/jquery-1.8.3.js"></script>
	<style>
		html, body {
			margin: 0px;
			padding: 0px;
			height: 100%;
			font-size: 14px;
		}
		a {
			color: #2D93CA;
		}
		.content {
			padding: 10px 20px;
		}
		input, button {
			padding: 3px;
			border: 1px solid #AAA;
			border-radius: 3px;
			background: #F8F8F8;
		}
		input {
			padding: 4px;
			background: #FFFFFF;
		}

		table {
			border: 1px solid #AAA;
			border-collapse: collapse;
		}

		table td {
			border: 1px solid #AAA;
			padding: 5px;
		}
		.ft12 {
			font-size: 12px;
		}
		.title {
			padding: 5px 0;
			font-weight: bold;
			font-size: 14px;
		}

		.item {
			padding: 10px 0;
		}
		.item span {
			display: inline-block;
			width: 160px;
			text-align: right;
		}
		.item .help {
			padding: 10px 0 0 160px;
		}

		select {
			padding: 5px;
		}
	</style>
</head>

<body>
<?php $layout->displayBlock('@content'); ?>
</body>
</html>
