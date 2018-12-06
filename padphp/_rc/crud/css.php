<div class="menu-list">
<div class="menu-content">
<a>PADPHP-CRUD v1.0</a>
<?php foreach ($menuList as $menu) { ?>
<a href="<?= call_user_func_array('url', $menu['url']) ?>"><?= $menu['text'] ?></a>
<?php } ?>
<a href="<?= url('Pfs') ?>">文件管理</a>
</div>
</div>

