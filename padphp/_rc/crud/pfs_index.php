<?php include(__DIR__.'/css.php'); ?>

<div class="botton">
<form action="<?= url('', 'action=newFilePost') ?>" enctype="multipart/form-data" method="post">
	<input type="file" name="file" />
	<input type="submit" value="上传文件" />
</form>
</div>
<div class="pager">
<?= strval(new PadMvcHelperPager($datalistPageInfo)); ?>
</div>
<div class="cdatalist">
	<table class="datalist" cellpadding="0" cellspacing="0" border="1">
		<tr>
	<th>文件路径</th>
	<th>上传名称</th>
	<th>文件类型</th>
	<th>文件大小</th>
	<th></th>
		</tr>
	<?php foreach ($datalist as $item) { ?>
	<tr>
		<td><?= $item->id.'.'.$item->extName ?></td>
		<td><?= $item->uploadName ?></td>
		<td><?= $item->mineType ?></td>
		<td><?= $item->size ?></td>
		<td>
			<a href="" onclick="alert('暂不至此');">删除</a>
		</td>
	</tr>
	<?php } ?>
</table>
</div>
<div class="pager w100">
<?= strval(new PadMvcHelperPager($datalistPageInfo)); ?>
</div>

