<?php 
	$helper instanceof PadMvcHelper;
	$entityDir = PadAutoload::$configs['Entity_'];
	
	$entityNames = array();
	foreach (glob($entityDir.'/*.php') as $filename) {
		$entityName = str_replace('.php', '', basename($filename));
		if ($entityName != 'abstract') {
			$entityNames[] = $entityName;
		}
	}
	
	$entityNamesItemKeys = array();
	foreach ($entityNames as $key) {
		$entityNamesItemKeys['index.'.$key] = $key.' 列表';
		$entityNamesItemKeys['item.'.$key] = $key.' 编辑';
	}

	$selectValues = array(
		array(
			'name' => '系统模板',
			'items' => array(
				'blank' => '空白模板',
			),
		),
		array(
			'name' => '实体模板',
			'items' => $entityNamesItemKeys,
		),
	);
?>
<div style="padding:20px;">
	<form action="<?= url('Crud,AutoBuildPost') ?>" method="post" onsubmit="return ppAutoBuilderCheckform(this);">
		<?= $helper->form->hidden('filepath', $filepath) ?>
		创建命令：<?= $helper->form->select('command', $selectValues) ?>
		<input type="submit" value="自动创建" />
	</form>
</div>
<script type="text/javascript">
function ppAutoBuilderCheckform(formObj){
	var msg = '';
	var command = document.getElementById('ppAutoBuildCommand').value;
	if (command.indexOf('blank') > -1) {
		msg = '将要创建一个空白的模版文件';
	} else if (command.indexOf('entity') > -1 && command.indexOf('index') > -1) {
		msg = '将要创建一个实体的管理列表页面';
	} else if (command.indexOf('entity') > -1 && command.indexOf('item') > -1) {
		msg = '将要创建一个实体的管理内容页面';
	} else {
		alert('命令"' + command + '"不被识别');
		return false;
	}
	
	if (confirm(msg)) {
		return true;
	} else {
		return false;
	}
}
</script>
