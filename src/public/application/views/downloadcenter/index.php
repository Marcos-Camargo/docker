<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $data['page_now'] = 'download_center';
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?= $this->session->flashdata('success') ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?= $this->session->flashdata('error') ?>
                    </div>
                <?php endif ?>

                <div class="box box-primary" id="collapseFilter">
                    <div class="box-body">
                        <h4 class="mt-0">Filtro</h4>
                        <div class="col-md-3 form-group no-padding" style="<?= (count($users_filter) > 1) ? "" : "display: none;" ?>">
                            <select class="form-control selectpicker show-tick" id="buscaUsers" name="user[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?= $this->lang->line('application_search_for_users'); ?>">
                                <?php foreach ((array)$users_filter as $user_filter) { ?>
                                    <option value="<?= $user_filter['id'] ?>" <?= set_select('user', $user_filter['id'], $user_filter['id'] == $user_id) ?>><?= "{$user_filter['email']} ({$user_filter['firstname']} {$user_filter['lastname']})" ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 col-xs-12 no-padding" id="content_file_process">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#products_tab" id="products_btn" data-toggle="tab"><?= $this->lang->line('application_products') ?></a></li>
                            <?php if ($catalog_product_marketplace_permission): ?><li><a href="#catalog_product_marketplace_tab" id="catalog_product_marketplace_btn" data-toggle="tab"><?= $this->lang->line('application_catalogs') ?></a></li><?php endif; ?>
                            <?php if ($catalog_product_marketplace_permission): ?><li><a href="#catalog_product_marketplace_store_tab" id="catalog_product_marketplace_store_btn" data-toggle="tab"><?= $this->lang->line('application_catalogs_store') ?></a></li><?php endif; ?>                        </ul>
                        <div class="tab-content col-md-12">
                            <div class="tab-pane active" id="products_tab">
                                <table id="manageTableProducts" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th><?= $this->lang->line('application_id'); ?></th>
                                            <th><?= $this->lang->line('application_file'); ?></th>
                                            <th><?= $this->lang->line('application_status'); ?></th>
                                            <th><?= $this->lang->line('application_user'); ?></th>
                                            <th><?= $this->lang->line('application_date_create'); ?></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <?php if ($catalog_product_marketplace_permission): ?>
                                <div class="tab-pane" id="catalog_product_marketplace_tab">
                                    <table id="manageTableCatalogProductMarketplace" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th><?= $this->lang->line('application_id'); ?></th>
                                                <th><?= $this->lang->line('application_file'); ?></th>
                                                <th><?= $this->lang->line('application_status'); ?></th>
                                                <th><?= $this->lang->line('application_user'); ?></th>
                                                <th><?= $this->lang->line('application_date_create'); ?></th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            <?php endif; ?>
                            <?php if ($catalog_product_marketplace_permission): ?>
                                <div class="tab-pane" id="catalog_product_marketplace_store_tab">
                                    <table id="manageTableCatalogProductMarketplaceStore" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th><?= $this->lang->line('application_id'); ?></th>
                                                <th><?= $this->lang->line('application_file'); ?></th>
                                                <th><?= $this->lang->line('application_status'); ?></th>
                                                <th><?= $this->lang->line('application_user'); ?></th>
                                                <th><?= $this->lang->line('application_date_create'); ?></th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewStatusFile">
    <div class="modal-dialog" role="document">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?= $this->lang->line('application_status_file_process'); ?><span id="deletecategoryname"></span></h4>
            </div>
            <div class="modal-body text-center"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?= HOMEPATH ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
    let manageTableProducts, manageTableCatalogProductMarketplace, manageTableCatalogProductMarketplaceStore;
    let base_url = "<?= base_url() ?>";

    $(document).ready(function() {
        $("#mainProductNav").addClass('active');
        $("#navFileProcessProductsLoad").addClass('active');
        getTable('products');
    });

    $('#buscaUsers').on('change', function() {
        const btn_type = $('#content_file_process ul.nav-tabs li.active a').attr('id').replace('_btn', '');
        getTable(btn_type);
    });

    $('a[id*="_btn"]').on('show.bs.tab', function() {
        const btn_type = $(this).attr('id').replace('_btn', '');
        getTable(btn_type);
    });

    const getTable = type_load => {
        let type = '';
        let content_table = '';
        let users = $('#buscaUsers').val();

        if (type_load === 'products') {
            if (typeof manageTableProducts !== 'undefined') {
                manageTableProducts.destroy();
            }
            content_table = 'manageTableProducts';
            type = 'Product';
        } else if (type_load === 'catalog_product_marketplace') {
            if (typeof manageTableCatalogProductMarketplace !== 'undefined') {
                manageTableCatalogProductMarketplace.destroy();
            }
            content_table = 'manageTableCatalogProductMarketplace';
            type = 'ProductCatalog';
        } else if (type_load === 'catalog_product_marketplace_store') {
            if (typeof manageTableCatalogProductMarketplaceStore !== 'undefined') {
                manageTableCatalogProductMarketplaceStore.destroy();
            }
            content_table = 'manageTableCatalogProductMarketplaceStore';
            type = 'ProductCatalogStore';
        }

        if (!(users.length)) {
            users = '';
        }

        // initialize the datatable
        let manageTable = $(`#${content_table}`).DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?= ucfirst($this->input->cookie('swlanguage')) ?>.lang'
            },
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'DownloadCenter/fetchDownloads',
                data: {
                    users,
                    type
                },
                pages: 2
            }),
            "order": [
                [0, 'desc']
            ]
        });

        if (type_load === 'products') {
            manageTableProducts = manageTable;
        } else if (type_load === 'catalog_product_marketplace') {
            manageTableCatalogProductMarketplace = manageTable;
        } else if (type_load === 'catalog_product_marketplace_store') {
            manageTableCatalogProductMarketplaceStore = manageTable;
        }
    }
</script>