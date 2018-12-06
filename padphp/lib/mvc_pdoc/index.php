<style>
	html, body {
		margin: 0px;
		padding: 0px;
		height: 100%;
		font-size: 14px;
	}

	.body {
		display: flex;
		flex-direction: column;
		height: 100%;
		overflow: hidden;
	}

	.main {
		display: flex;
		flex: 1;
		flex-direction: row;
		height: 100%;
	}

	.left {
		width: 150px;
		background: #EEEEEE;
		height: 100%;
	}

	.right {
		flex: 1;
		background: #000;
		height: 100%;
	}

	.iframe {
		width: 100%;
		height: 100%;
		margin: 0px;
		padding: 0px;
		background: #FFFFFF;
	}

	.menu {
		line-height: 20px;
		padding: 0 0 0 10px;
		display: block;
		margin: 0 0 0 0;
		font-size: 14px;
		font-weight: bold;
	}

	.menu.head {
		font-size: 14px;
		font-weight: bold;
	}

	b.menu {
		padding: 10px 0 0 10px;
	}
</style>
<div class="body">
	<div class="main">
		<div class="left">
			<b class="menu" style="font-size: 12px; color: #AAA;">接口文档</b>
			<?php foreach ($componentList as $cpt) { ?>
				<div>
					<div class="menu head">
						<span style="color:#000;text-decoration: none; display: block;line-height: 25px;">
							<?= $cpt['component'] ?>
							<input type="hidden" class="menuinput" value="1">
						</span>
					</div>
					<?php foreach ($cpt['controllers'] as $ctrl) { ?>
						<a class="menu"
							href="<?= url('Pdoc,Controller', 'component=' . $cpt['component'] . ',controller=' . $ctrl) ?>"
							target="content"><?= $ctrl ?></a>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
		<div class="right">
			<iframe id="content" src="about:blank" frameborder="0" class="iframe" name="content"></iframe>
		</div>
	</div>
</div>
<script>

</script>
