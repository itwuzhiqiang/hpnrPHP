<link href="http://cdn.bootcss.com/bootstrap/3.3.1/css/bootstrap.min.css" rel="stylesheet">
<script src="//cdn.bootcss.com/jquery/3.1.1/jquery.min.js"></script>
<script src="//cdn.bootcss.com/markdown.js/0.5.0/markdown.min.js"></script>
<style>
	body {
		padding: 10px;
	}

	h1, .h1, h2, .h2, h3, .h3 {
		margin-top: 0px;
	}

	h4, .h4, h5, .h5, h6, .h6 {
		margin: 5px 0 5px 0;
	}

	h5 {
		font-size: 14px;
	}

	h4 {
		font-size: 16px;
	}

	table {
		max-width: 65em; /*表格最大宽度，避免表格过宽*/
		border: 1px solid #dedede; /*表格外边框设置*/
		margin: 5px 0; /*外边距*/
		border-collapse: collapse; /*使用单一线条的边框*/
		empty-cells: show; /*单元格无内容依旧绘制边框*/
	}

	table th,
	table td {
		height: 30px; /*统一每一行的默认高度*/
		border: 1px solid #dedede; /*内部边框样式*/
		padding: 0 10px; /*内边距*/
	}

	table th {
		font-weight: bold; /*加粗*/
		text-align: center !important; /*内容居中，加上 !important 避免被 Markdown 样式覆盖*/
		background: #EEEEEE; /*背景色*/
	}

	table tbody tr:nth-child(2n) {
		background: rgba(158, 188, 226, 0.12);
	}

	hr {
		margin: 10px 0;
		border-color: #FFF;
	}
</style>

<div style="padding: 0 10px;">
	<div id="contentHtml"></div>
</div>
<?php
	function formatString ($string) {
		$string = str_replace('_', '\_', $string);
		$string = str_replace('|', '\|', $string);
		return $string;
	}
?>
<code id="content" style="display: none;">
	<?php foreach ($methods as $idx => $method) { ?>
		##### <?= ($idx + 1) ?>. <?= $method['description'] ?> ( /<?= formatString($controller) ?>/<?= formatString($method['name']) ?> )

		###### 返回值
		<?= $method['return'] ?>

		<?php if ($method['params']) { ?>
		###### 参数列表

		|<?= implode('|', array('参数', '类型', '默认值', '验证规则', '详细描述')) ?>|
		|:--|:--|:--|:--|:-----------|
		<?php foreach ($method['params'] as $key => $cfg) { ?>
			|<?= implode('|', array(formatString($cfg['key']), formatString($cfg['title']), formatString(json_encode($cfg['default'])), formatString($cfg['vcheck']), formatString($cfg['desc']))) ?>|
		<?php } ?>

		<?php } ?>

		--------------------------------

	<?php } ?>
</code>
<script>
	$(function () {
		var content = $("#content").text().replace(/\t/g, "");
		var doc = $(markdown.toHTML(content, "Maruku"));
		doc.find('a').attr('target', '_blank');
		$("#contentHtml").html(doc);
	});
</script>
