<!DOCTYPE html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script src="http://libs.baidu.com/jquery/1.9.0/jquery.js"></script>
<title>PworkerLogViewer</title>
<style>
	html, body { font-size:12px; font-family: 宋体, simsun; padding:0px; margin:0px;}
	.main { width:100%; margin:0px auto; }
	table {width:100%; border-collapse: collapse; border:0px;}
	td, th {text-align:left; line-height:22px; border: solid #000 1px;}
	.pager {height:32px; text-align:center; line-height:32px; font-size:13px; color:#333; font-family:Arial; padding:10px;}
	.pager a, .main-pager b {display:inline; }
	.pager a{border:1px solid #333; font-size:12px; padding:3px 8px 3px 8px; color:#333; text-decoration:none;}
	.pager a:hover{border:1px solid #0080ff;}
	.pager b {font-weight:normal; border:1px solid #0080ff; color:#0080ff; background:#d6e4fa; padding:3px 8px 3px 8px;}
	.pager b.no {font-weight:normal; border:1px solid #999; color:#999; padding:3px 8px 3px 8px; background:#ffffff; }
	.popup {width:900px; height:500px; position:fixed; z-index:10; left:0px; top:0px; visibility:hidden; background:#FFF;
			border:2px solid #000; color:#000;}
	.popup .showbox b{ display:block; margin:10px 0 0 0; color:#333; }
	.popup .close {height:20px; line-height:20px; padding:0 10px 0 0; background:#333; text-align:right;}
	.popup .close a {color:#FFF;}
	.popup .content {height:480px; padding: 0 5px; overflow-y:auto; word-wrap:break-word; white-space: pre;}
	.popup .content .ctt-item {background:#EEE; border:1px solid #AAA; margin:5px 0; padding:5px;}
</style>
</head>
<body>
<div class="main">

	