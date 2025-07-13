<?php

$userPermission = $user_permission ?? [];

$priceQtyByMarketplace = $Preco_Quantidade_Por_Marketplace ?? 0;

$productManagement = [
    'updateProduct', 'viewProduct', 'deleteProduct', 'updateProductsMarketplace', 'viewTrash'
];
$productAddManagement = [
    'createProduct', 'b2b_integration_via', 'updateProduct'
];
$publicationManagement = [
    'doProductsPublish', 'createPublicationManagement', 'updatePublicationManagement',
    'viewPublicationManagement', 'deletePublicationManagement', 'viewCuration', 'doProductsApproval'
];

$attributeManagement = [
    'updateProduct'
];

if (!empty(array_intersect(array_merge($productManagement, $productAddManagement, $publicationManagement, $attributeManagement), $userPermission))) : ?>
    <li class="treeview activateable-item">
        <a class="menuhref" href="#">
            <i class="fa fa-shopping-cart"></i>
            <span><?= $this->lang->line('application_products'); ?></span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
        </a>
        <ul class="treeview-menu">
            <?php if (!empty(array_intersect($productManagement, $userPermission))) : ?>
                <li class="treeview activateable-item">
                    <a class="menuhref" href="#">
                        <span><?= $this->lang->line('application_manage_products'); ?></span>
                        <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                    </a>
                    <ul class="treeview-menu">
                        <li
                                data-path="/products"
                                data-linked-paths="/catalogProducts/updateFromCatalog"
                        >
                            <a class="menuhref" href="<?php echo base_url('products') ?>">
                                <?= $this->lang->line('application_list_products'); ?>
                            </a>
                        </li>
                        <?php
                        if (($priceQtyByMarketplace == 1) && in_array('updateProductsMarketplace', $userPermission)): ?>
                            <li
                                    data-path="/productsMarketplace"
                            >
                                <a class="menuhref" href="<?php echo base_url('productsMarketplace') ?>">
                                    <?= $this->lang->line('application_productsmarketplace'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (in_array('viewTrash', $userPermission)) : ?>
                            <li
                                    data-path=/products/trash
                            >
                                <a class="menuhref" href="<?php echo base_url('products/trash') ?>">
                                    <?= $this->lang->line('application_trash'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>
            <?php if (!empty(array_intersect($productAddManagement, $userPermission))) : ?>
                <li class="treeview activateable-item">
                    <a class="menuhref" href="#">
                        <span><?= $this->lang->line('application_add_products'); ?></span>
                        <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                    </a>
                    <ul class="treeview-menu">
                        <?php if (in_array('createProduct', $userPermission)): ?>
                            <li
                                    data-path="/products/create"
                                    data-linked-paths="/products/copy"
                            >
                                <a class="menuhref" href="<?php echo base_url('products/create') ?>">
                                    <?= $this->lang->line('application_add_product'); ?>
                                </a>
                            </li>
                            <li
                                    data-path="/productsKit/create"
                            >
                                <a class="menuhref" href="<?php echo base_url('productsKit/create') ?>">
                                    <?= $this->lang->line('application_add_product_kit'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty(array_intersect(array_merge($productAddManagement, $productManagement), $userPermission))) : ?>
                            <li
                                    data-path="/ProductsLoadByCSV"
                                    data-linked-paths="/billet/creatediscountworksheet|/payment/createnfiscal"
                            >
                                <a class="menuhref" href="<?php echo base_url('ProductsLoadByCSV/index') ?>">
                                    <?= $this->lang->line('application_upload_products'); ?>
                                </a>
                            </li>
                            <?php if (!in_array('createProduct', $userPermission) && in_array('disablePrice', $userPermission)): ?>
                            <li
                                    data-path="/ProductsLoadByCSV/CatalogProduct"
                            >
                                <a class="menuhref" href="<?php echo base_url('ProductsLoadByCSV/CatalogProduct') ?>">
                                    Carga de catálogo por marketplace
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (in_array('b2b_integration_via', $userPermission)): ?>
                                <li
                                        data-path="/LoadProductsB2BVia"
                                >
                                    <a class="menuhref" href="<?php echo base_url('LoadProductsB2BVia') ?>">
                                        <?= $this->lang->line('application_load_products_via_b2b'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (in_array('syncPublishedSku', $userPermission)): ?>
                                <li
                                        data-path="/ProductsLoadByCSV/SyncPublishedSku"
                                >
                                    <a class="menuhref" href="<?php echo base_url('ProductsLoadByCSV/SyncPublishedSku') ?>">
                                        <?= $this->lang->line('application_load_marketplace_sku'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (in_array('groupSimpleSku', $userPermission)): ?>
                                <li
                                        data-path="/ProductsLoadByCSV/GroupSimpleSku"
                                >
                                    <a class="menuhref" href="<?php echo base_url('ProductsLoadByCSV/GroupSimpleSku') ?>">
                                        <?= $this->lang->line('application_group_simple_sku'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (in_array('importRDskus', $userPermission)): ?>
                                <li
                                        data-path="/importRDSku"
                                >
                                    <a class="menuhref" href="<?php echo base_url('importRDSku/index') ?>">
                                        <?= $this->lang->line('application_import_rd_skus'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (in_array('addOn', $userPermission)): ?>
                                <li
                                        data-path="/ProductsLoadByCSV/AddOn"
                                >
                                    <a class="menuhref" href="<?php echo base_url('ProductsLoadByCSV/AddOn') ?>">
                                        <?= $this->lang->line('application_import_addon_skus'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li
                                    data-path="/FileProcess/product_load"
                            >
                                <a class="menuhref" href="<?php echo base_url('FileProcess/product_load') ?>">
                                    <?= $this->lang->line('application_file_process'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>
            <?php if (!empty(array_intersect($publicationManagement, $userPermission))) : ?>
                <li class="treeview activateable-item">
                    <a class="menuhref" href="#">
                        <span><?= $this->lang->line('application_manage_publication'); ?></span>
                        <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                    </a>
                    <ul class="treeview-menu">
                        <?php if (!empty(array_intersect(['doProductsPublish'], $userPermission))) : ?>
                            <li
                                    data-path="/productsPublish"
                            >
                                <a class="menuhref" href="<?php echo base_url('productsPublish/index') ?>">
                                    <?= $this->lang->line('application_products_publish'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty(array_intersect(['marketplaces_integrations'], $userPermission))) : ?>
                            <li
                                    data-path="/IntegrationsConfiguration"
                            >
                                <a class="menuhref" href="<?php echo base_url('IntegrationsConfiguration/index') ?>">
                                    <?= $this->lang->line('application_integrationsconfiguration'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (in_array('createPublicationManagement', $userPermission)) : ?>
                            <li
                                    data-path="/PublicationManagement"
                            >
                                <a class="menuhref" href="<?php echo base_url('PublicationManagement') ?>">
                                    <?= $this->lang->line('application_unpublished_products'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty(array_intersect(['viewCuration', 'doProductsApproval'], $userPermission))) { ?>
                            <li class="treeview activateable-item">
                                <a class="menuhref" href="#">
                                    <span><?= $this->lang->line('application_curatorship'); ?></span>
                                    <span class="pull-right-container"><i
                                                class="fa fa-angle-left pull-right"></i></span>
                                </a>
                                <ul class="treeview-menu">
                                    <?php if (in_array('viewCuration', $userPermission)) : ?>
                                        <li
                                                data-path="/BlacklistWords"
                                        >
                                            <a class="menuhref" href="<?php echo base_url('BlacklistWords/index') ?>">
                                                <?= $this->lang->line('application_blacklistwords') ?>
                                            </a>
                                        </li>
                                        <li
                                                data-path="/Whitelist"
                                        >
                                            <a class="menuhref" href="<?php echo base_url('Whitelist/index') ?>">
                                                <?= $this->lang->line('application_whitelist') ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (in_array('doProductsApproval', $userPermission)) : ?>
                                        <li
                                                data-path="/products/productsApprove"
                                        >
                                            <a class="menuhref"
                                               href="<?php echo base_url('products/productsApprove/') ?>">
                                                <?= $this->lang->line('application_products_approval'); ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php } ?>
                        <?php if (!empty(array_intersect(['marketplaces_integrations'], $userPermission))) : ?>
                            <li data-path="/errorsTransformation">
                            <a class="menuhref" href="<?php echo base_url('errorsTransformation/index') ?>">
                                <?= $this->lang->line('application_errors_tranformation'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>
            <?php if (!empty(array_intersect($attributeManagement, $userPermission))) : ?>
                <li class="treeview activateable-item">
                    <a class="menuhref" href="#">
                        <span><?= $this->lang->line('application_products_attributes'); ?></span>
                        <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                    </a>
                    <ul class="treeview-menu">
                        <?php if (in_array('updateProduct', $userPermission)) : ?>
                            <li
                                    data-path="/products/importAttributes"
                            >
                                <a class="menuhref" href="<?php echo base_url('products/importAttributes') ?>">
                                    <?= $this->lang->line('application_import_attributes'); ?>
                                </a>
                            </li>
                            <?php if($collection_occ == "1"): ?>
                                <li data-path="/products/importCollections">
                                    <a class="menuhref" href="<?php echo base_url('products/importCollections') ?>">
                                        <?= $this->lang->line('application_navigation_upload'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>
            <!--li class="treeview activateable-item">
                <a class="menuhref">
                    <span><?= $this->lang->line('application_flowchart_product_publication_process'); ?></span>
                </a>
            </li-->
        </ul>
    </li>
<?php endif; ?>

<style>
    li {
        white-space: initial;
    }

    li.activateable-item > ul.treeview-menu {
        padding-left: 0px;
    }

    li.activateable-item > ul.treeview-menu > li > a {
        padding-left: 40px;
    }

    li.activateable-item > ul.treeview-menu > li > ul.treeview-menu > li > a {
        padding-left: 60px;
    }

    li.activateable-item > ul.treeview-menu > li > ul.treeview-menu > li > ul.treeview-menu > li > a {
        padding-left: 80px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        var currentLocationPath = window.location.pathname.toLowerCase();
        var menuItems = $('li[data-path]');
        menuItems.sort(function (a, b) {
            return $(a).data('path').length > $(b).data('path').length ? -1 : 1;
        });
        var menuItem = menuItems.filter(function (i, item) {
            var pathList = [$(item).data('path'), ...($(item).data('linked-paths') ? $(item).data('linked-paths').split('|') : [])];
            pathList.sort(function (a, b) {
                return a.length > b.length ? -1 : 1;
            });
            var pathCheck = pathList.filter(function (p) {
                return (currentLocationPath.indexOf(p.toLowerCase())) > -1;
            });
            return pathCheck.length > 0;
        });
        if (menuItem.length ?? 0) {
            var nodes = [];
            var element = $(menuItem[0]).addClass('active').get(0);
            nodes.push(element);
            while (element.parentNode) {
                nodes.unshift(element.parentNode);
                element = element.parentNode;
                if ($(element).hasClass('activateable-item')) {
                    $(element).addClass('active menu-open');
                }
            }
        }
    });
</script>
