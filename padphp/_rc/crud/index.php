<?php include(__DIR__.'/css.php'); ?>
<div class="botton">
	<a href="<?= url('New', 'entity='.$_entity) ?>">添加记录</a>
</div>
<div class="pager">
<?= strval(new PadMvcHelperPager($datalistPageInfo)); ?>
</div>
<div class="cdatalist">
	<table class="datalist" cellpadding="0" cellspacing="0" border="1">
		<tr>
	<?php foreach($gridFields as $field){ ?>
	<th><?= $field ?></th>
	<?php } ?>
	<th></th>
		</tr>
<?php foreach($datalist as $ety){ ?>
<tr>
	<?php foreach($gridFields as $field){ ?>
	<?php if (strpos('|create_time|update_time|createTime|updateTime|', '|'.$field.'|')) { ?>
	<td><?= date('Ymd/H:i:s', $ety->$field) ?></td>
	<?php } else { ?>
	<td><?= $ety->$field ?></td>
	<?php } ?>
	<?php } ?>
	<td><a href="<?= $GLOBALS['pad_core']->mvc->getUrl('Update', 'id='.$ety->$pkField.',entity='.$_entity) ?>">编辑</a>
		<?php if (isset($entityConfig->fieldsInfo['is_delete']) || isset($entityConfig->fieldsInfo['isDelete'])) { ?>
		<a href="<?= $GLOBALS['pad_core']->mvc->getUrl('DeletePost', 'id='.$ety->$pkField.',entity='.$_entity) ?>" onclick="return confirm('确定删除？')">删除</a>
		<?php } ?>
		<a href="<?= $GLOBALS['pad_core']->mvc->getUrl('DestroyPost', 'id='.$ety->$pkField.',entity='.$_entity) ?>" onclick="return confirm('确定销毁，数据不可恢复？')">销毁</a></td>
		</tr>
<?php } ?>
</table>
</div>
<div class="pager w100">
<?= strval(new PadMvcHelperPager($datalistPageInfo)); ?>
</div>
