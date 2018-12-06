<?php include(__DIR__.'/css.php'); ?>
<div class="botton">
	<a href="<?= url('@refer', url('Index', 'entity='.$_entity)) ?>">返回列表</a>
</div>
<form
	action="<?= $GLOBALS['pad_core']->mvc->getUrl(($ety->isnull ? 'New' : 'Update').'Post', 'entity='.$_entity) ?>"
	method="post">
	<?= $helper->form->referdata() ?>
	<table class="datalist" cellpadding="0" cellspacing="0" border="1"
		style="margin: 10px;">
		<tr>
			<td width="100">字段</td>
			<td>值</td>
		</tr>
<?php foreach($entityConfig->fieldsInfo as $field => $info){ ?>
<?php
	if (strpos('|id|is_delete|create_time|update_time|isDelete|createTime|updateTime|', '|'.$field.'|') !== false) {
		continue;
	}
?>
<?php if (isset($belongtoArray[$field])) { ?>
<?php

		$config = $belongtoArray[$field];
		$ecfg = $GLOBALS['pad_core']->orm->getEntityConfig($config['entity_name']);
		$pairList = ety($config['entity_name'])->getList();
		$pair = array(
			'0' => '请选择...'
		) + PadBaseArray::array2pair($pairList, $ecfg->pkField, isset($ecfg->fields['name']) ? $ecfg->fields['name'] : $ecfg->pkField);
		?>
	<tr>
			<td><?= $field ?></td>
			<td><?= helper('form')->select('vars['.$config['link_field'].']', $pair, $ety->$field) ?></td>
		</tr>
<?php } elseif ($info['is_big']) { ?>
	<tr>
			<td><?= $field ?></td>
			<td><textarea cols="120" rows="6" name="vars[<?= $field ?>]"><?= $ety->$field ?></textarea></td>
		</tr>
<?php } else { ?>
	<tr>
			<td><?= $field ?></td>
			<td><input type="text" name="vars[<?= $field ?>]" size="66"
				value="<?= $ety->$field ?>" /></td>
		</tr>
<?php } ?>
<?php } ?>
<tr>
			<td></td>
			<td><input type="hidden" name="id" value="<?= $ety->$pkField ?>" /> <input
				type="submit" value="确定" />
			</td>
		</tr>
	</table>
</form>
