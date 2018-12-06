<?php 
	$json = $this->jsonTaskListAll($request);
	$pager = new PadMvcHelperPager(array(
		'total' => $json['total'],
		'page' => $json['page'],
		'page_size' => $json['pageSize'],
	), 'tasklist.php', isset($request->get) ? $request->get : array());
	
	$statusMap = array(
		PadLib_Pworker_Server_Jobhandler::JobStatusNew => '新建',
		PadLib_Pworker_Server_Jobhandler::JobStatusRuning => '运行中',
		PadLib_Pworker_Server_Jobhandler::JobStatusFinish => '完成',
	);
?>
<table>
<tr>
	<th style="width:100px;">ID</th>
	<th style="width:160px;">创建时间</th>
	<th style="width:160px;">更新时间</th>
	<th style="width:80px;">运行状态</th>
	<th style="width:80px;">运行结果</th>
	<th>用户标签</th>
	<th style="width:100px;"></th>
</tr>
<?php foreach ($json['list'] as $item) { ?>
<tr>
	<td><?= $item['id'] ?></td>
	<td><?= $item['createTime'] ?></td>
	<td><?= $item['upTime'] ?></td>
	<td><?= $statusMap[$item['runStatus']] ?></td>
	<td><?= isset($item['returnCode']) ? $item['returnCode'] : '-' ?></td>
	<td><?= $item['userTags'] ?></td>
	<td>
		<a href="javascript:popDetailJson('/json/taskinfo?id=<?= $item['id'] ?>')" target="_blank">详细</a>
		<?php if ($item['runStatus'] != PadLib_Pworker_Server_Jobhandler::JobStatusFinish) { ?>
		<a href="javascript:killTask('<?= $item['id'] ?>');" onclick="return confirm('确定终止任务')" target="_blank">终止</a>
		<a href="javascript:popWatch('<?= $item['id'] ?>', '<?= uniqid() ?>')">观测</a>
		<?php } ?>
	</td>
</tr>
<?php } ?>
</table>
<div class="pager">
	<?= strval($pager) ?>
</div>
<script>
function killTask (id) {
	$.getJSON('/json/taskkill?id=' + id, function (json) {
		if (json.succ) {
			alert('任务结束成功');
		} else {
			alert('任务结束失败');
		}
	});
}

function popupDiv (html, params) {
	params = params || {};
	var ndiv = $('<div class="popup popupdevtag"><div class="close"><a href="javascript:">关闭</a></div><div class="content"></div></div>');
	ndiv.find('.content').html(html);
	ndiv.find('.close a').click(function () {
		ndiv.remove();
		if ($(document.body).find('.popupdevtag').length == 0) {
			$(document.body).css({
				'overflow-y': 'auto',
				'padding-right': '0px'
			});
		}
		if (params.afterClose) {
			params.afterClose();
		}
	});
	$(document.body).append(ndiv);
	$(document.body).css({
		'overflow-y': 'hidden',
		'padding-right': '17px'
	});
	var nowpopnum = $('.popupdevtag').length - 1;
	ndiv.css({
		'top': ($(window).height() - ndiv.height()) / 2 + nowpopnum * 10,
		'left': ($(window).width() - ndiv.width()) / 2 + nowpopnum * 10,
		'visibility': 'visible'
	});
	return ndiv;
}

function nl2br (str) {
	var breakTag = '<br>';
	
	var div = document.createElement("div");
	var text = document.createTextNode(str);
	div.appendChild(text);
	str = div.innerHTML;

	str = (str + '').replace(/( +)/g, ' ');
	str = str.replace(/(\t)/g, '  ');
	return str;
	return str.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}

function popDetailJson (jsonUrl) {
	$.getJSON(jsonUrl, function (json) {
		var html = new Array();

		html.push('<b>Function(' + json.data.params.join(', ') + '):</b>');
		html.push('<div class="ctt-item">' + nl2br(json.data.function) + '</div>');

		if (json.return && json.return.info) {
			html.push('<b>Output:</b>');
			html.push('<div class="ctt-item">' + nl2br(json.return.info.output) + '</div>');
		}

		if (json.return) {
			html.push('<b>Return:</b>');
			html.push('<div class="ctt-item">' + JSON.stringify(json.return.res) + '</div>');
		}
		
		popupDiv('<div class="showbox"><div>' + html.join('</div><div>') + '</div></div>');
	});
}

function popWatch (id, key) {
	var isClose = false;
	var ndiv = popupDiv('<b>Output:</b><div class="ctt-item"></div>', {
		'afterClose': function () {
			isClose = true;
		}
	});
	function refreshLog () {
		$.getJSON('/json/taskwatch?id=' + id + '&key=' + key, function (json) {
			if (json.res) {
				$.each (json.res, function () {
					ndiv.find('.content .ctt-item').append(this + '<br>');
				});
			}
			if (json.status > 0) {
				ndiv.find('.content .ctt-item').append('--任务完成--');
			} else if (!isClose) {
				setTimeout(function () {
					refreshLog();
				}, 1000);
			}
		});
	}
	refreshLog();
}
</script>

