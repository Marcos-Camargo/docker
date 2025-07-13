<li class="nav-item active">
    <a class="nav-link" @click="settings.category = 'all', search = '', getSettingByTab('all')" id="custom-tabs-all" data-toggle="pill" href="#custom-tabs-content-all" role="tab" aria-controls="custom-tabs-all" aria-selected="false">
        <img src="<?= base_url('assets/dist/img/icons_parameters/personalizado.png') ?>" width="51px" height="auto">
        <p>Todos</p>
    </a>
</li>
<?php
    foreach($categories as $category){
?>
        <li class="nav-item">
            <transition name="fade">
            <a v-if="!showLoading" class="nav-link" @click="settings.catName = '<?= detectUTF8($category['name']) ?>', settings.category = sanitizeTitle('<?= detectUTF8($category['name']) ?>'), search = '', getSettingByTab(settings.category)" id="custom-tabs-<?= $category['id'] ?>" data-toggle="pill" href="#custom-tabs-content-<?= $category['id'] ?>" role="tab" aria-controls="custom-tabs-<?= $category['id'] ?>" aria-selected="false">
                <img src="<?= base_url('assets/dist/img/icons_parameters/' . $category['icon'] . '.png') ?>" height="auto">
                <p><?= $category['name'] ?></p>
            </a>
            </transition>
        </li>
<?php
    }
?>
