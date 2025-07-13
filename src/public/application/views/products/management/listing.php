<style>
    .action-buttons-header {
        padding-top: 20px;
        padding-right: 0px;
        padding-left: 0px;
    }

    .action-buttons-header > * {
        margin-left: 5px;
    }

    .btn-outline {
        background-color: transparent;
        color: inherit;
        transition: all .5s;
    }

    .btn-primary.btn-outline {

    }

    .btn-primary.btn-outline:hover {
        color: #fff;
    }

    #collapseFilter .row > div[class^="col-"] {
        padding-right: 0px;
        padding-left: 0px;
    }

    #collapseFilter .action-filter > div[class^="col-"] {
        padding-right: 0px;
        padding-left: 0px;
    }

    select option:disabled {
        color: #d2d6de;
    }
    .bootstrap-select > .dropdown-toggle.bs-placeholder {
        border-color: #d2d6de;
        border-radius: 0;
    }
</style>

<div class="content-wrapper">

    <?php
    $data['pageinfo'] = "";
    $data['page_now'] = "registered_products";
    $this->load->view('templates/content_header', $data);
    ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="row">
                    <div id="messages"></div>
                    <?php if ($this->session->flashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <?php echo $this->session->flashdata('success'); ?>
                        </div>
                    <?php elseif ($this->session->flashdata('warning')): ?>
                        <div class="alert alert-warning alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <?php echo $this->session->flashdata('warning'); ?>
                        </div>
                    <?php elseif ($this->session->flashdata('error')): ?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <?php echo $this->session->flashdata('error'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="box-body action-buttons-header">
                            <a class="pull-left btn btn-primary btn-outline-primary collapse-out" id="buttonCollapseFilter"
                               role="button" data-toggle="collapse" href="#collapseFilter" aria-expanded="false"
                               aria-controls="collapseFilter" data-collapse-in="<?= lang('application_hide_filters');?>"
                               data-collapse-out="<?= lang('application_display_filters'); ?>">
                                <span class="glyphicon glyphicon-filter" aria-hidden="true"></span>
                                <?= lang('application_hide_filters'); ?>
                            </a>
                            <?php if (in_array('createProduct', $user_permission)): ?>
                                <a href="<?php echo base_url('productsKit/create') ?>"
                                   class="pull-right btn btn-primary btn-outline-primary">
                                    <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                                    <?= lang('application_add_product_kit'); ?>
                                </a>
                                <a href="<?php echo base_url('products/create') ?>"
                                   class="pull-right btn btn-primary btn-outline-primary">
                                    <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                                    <?= lang('application_add_product'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-primary collapse in" id="collapseFilter">
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h4 class="col-md-12"><?= lang('application_filters'); ?> <?= $sellercenter_name ?></h4>
                                        <div class="col-md-2 form-group">
                                            <label for="sku"><?= lang('application_label_code_sku'); ?></label>
                                            <input type="search" name="sku" id="sku" class="form-control"
                                                   placeholder="<?= lang('application_label_code_sku'); ?>" aria-label="Search"
                                                   aria-describedby="basic-addon1" onchange="personalizedSearch()">
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="product"><?= lang('application_label_product_name'); ?></label>
                                            <input type="search" name="product" id="product" class="form-control"
                                                   placeholder="<?= lang('application_label_product_name'); ?>" aria-label="Search"
                                                   aria-describedby="basic-addon1" onchange="personalizedSearch()">
                                        </div>
                                        <div class="col-md-2 form-group">
                                            <label for="status"><?= lang('application_label_availability'); ?></label>
                                            <select class="form-control" name="status" id="status" onchange="personalizedSearch()">
                                                <option value="0"><?= lang('application_product_status') ?></option>
                                                <option value="1" <?= (isset($products_complete) && $products_complete == 1 ? 'selected' : '') ?> ><?= lang('application_active') ?></option>
                                                <option value="2"><?= lang('application_inactive') ?></option>
                                                <option value="4"><?= lang('application_under_analysis') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 form-group">
                                            <label for="stock"><?= lang('application_label_stock'); ?></label>
                                            <select class="form-control" name="with_stock" id="stock" onchange="personalizedSearch()">
                                                <option value=""><?= lang('application_all') . ' ' . lang('application_promotion_type_stock') ?></option>
                                                <option value="1" <?= (isset($products_without_stock) && $products_without_stock == 1 ? 'selected' : '') ?>><?= lang('application_with_stock') ?></option>
                                                <option value="0" <?= (isset($products_without_stock) && $products_without_stock == 2 ? 'selected' : '') ?>><?= lang('application_no_stock') ?></option>
                                            </select>
                                        </div>

                                        <div class="col-md-2 form-group">
                                            <label for="kit">Kit</label>
                                            <select class="form-control" name="is_kit" id="kit" onchange="personalizedSearch()">
                                                <option value=""><?= lang('application_with_or_without_kit'); ?></option>
                                                <option value="1" <?= (isset($products_kit) && $products_kit == 1 ? 'selected' : '') ?>><?= lang('application_products_kit') ?></option>
                                                <option value="0" <?= (isset($products_kit) && $products_kit == 2 ? 'selected' : '') ?>><?= lang('application_no_products_kit') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="col-md-3 form-group"
                                             style="<?= (count($stores_filter) > 1) ? "" : "display: none;" ?>">
                                            <label for="stores"><?= lang('application_label_store'); ?></label>
                                            <select class="form-control selectpicker show-tick" name="store[]"
                                                    id="stores" data-live-search="true" data-actions-box="true"
                                                    multiple="multiple" data-style="btn-link" 
                                                    data-selected-text-format="count > 1"
                                                    title="<?= lang('application_search_for_store'); ?>" onchange="personalizedSearch()">
                                                <?php foreach ((array)$stores_filter as $store_filter) { ?>
                                                    <option value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4 class="col-md-12"><?= lang('application_filters'); ?> Marketplace</h4>
                                        <div class="col-md-5 form-group">
                                            <label for="marketplaces">Marketplace</label>
                                            <select title="Selecione o marketplace"
                                                    class="form-control selectpicker show-tick"
                                                    name="marketplace[]" id="marketplaces" data-live-search="true"
                                                    data-actions-box="true" multiple="multiple" data-style="btn-link"
                                                    data-selected-text-format="count > 1" onchange="personalizedSearch()">
                                                <?php
                                                if (isset($activeIntegrations)) {
                                                    foreach ((array)$activeIntegrations as $key => $activeIntegration) { ?>
                                                        <option value=<?= $activeIntegration['int_to'] ?>><?= $nameOfIntegrations[$activeIntegration['int_to']] ?></option>
                                                        <?php
                                                    }
                                                } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-5 form-group">
                                            <label for="status_integration"><?= lang('application_label_status_integration'); ?></label>
                                            <select class="form-control" name="status_integration"
                                                    id="status_integration" onchange="personalizedSearch()">
                                                <option value=""><?= lang('application_integration_status') ?></option>
                                                <option value="not_published"><?= lang('application_not_published') ?></option>
                                                <option value="0" data-status-mkt="disabled"
                                                        disabled><?= lang('application_product_in_analysis') ?></option>
                                                <option value="1" data-status-mkt="disabled"
                                                        disabled><?= lang('application_product_waiting_to_be_sent') ?></option>
                                                <option value="2" data-status-mkt="disabled"
                                                        disabled><?= lang('application_product_sent') ?></option>
                                                <option value="11" data-status-mkt="disabled"
                                                        disabled><?= lang('application_product_higher_price') ?></option>
                                                <option value="14" data-status-mkt="disabled"
                                                        disabled><?= lang('application_product_release') ?></option>
                                                <option value="20" data-status-mkt="disabled"
                                                        disabled><?= lang('application_in_registration') ?></option>
                                                <option value="30" data-status-mkt="disabled"
                                                        disabled><?= lang('application_errors_tranformation') ?></option>
                                                <option value="40" data-status-mkt="disabled"
                                                        disabled><?= lang('application_published') ?></option>
                                                <option value="90" data-status-mkt="disabled"
                                                        disabled><?= lang('application_inactive') ?></option>
                                                <option value="91" data-status-mkt="disabled"
                                                        disabled><?= lang('application_no_logistics') ?></option>
                                                <option value="99" data-status-mkt="disabled"
                                                        disabled><?= lang('application_out_stock') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 form-group" style="display: none;">
                                            <label for="collections"><?= $this->lang->line('application_collections'); ?></label>
                                            <select class="form-control selectpicker show-tick"
                                                    id="collections"
                                                    name="collections[]"
                                                    data-live-search="true"
                                                    data-actions-box="true"
                                                    data-style="btn-link"
                                                    data-selected-text-format="count > 1"
                                                    title="<?= lang('application_collections_select'); ?>" onchange="personalizedSearch()">
                                                <option value=""><?= lang('application_all'); ?></option>
                                                <?php if (!empty($collections)) {
                                                    foreach ($collections as $collection) { ?>
                                                        <option value="<?= $collection['id']?>"><?=$collection['name'] ?></option>
                                                    <?php }
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h4 class="col-md-12"><?= lang('application_pendences_filters'); ?></h4>
                                        <div class="col-md-4 form-group">
                                            <label for="situation"><?= lang('application_label_situation'); ?></label>
                                            <select name="situation" id="situation" class="form-control"
                                                    data-toggle="tooltip" data-html="true" data-placement="top"
                                                    title="<b>Completo</b>: o cadastro do produto está completo e pronto para ser publicado;<p /> <b>Incompleto</b>: faltam campos que precisam ser preenchidos." onchange="personalizedSearch()">
                                                <option value="0"><?= lang('application_product_situation') ?></option>
                                                <option value="2" <?= (isset($products_incomplete) && $products_incomplete == 2 ? 'selected' : '') ?>><?= lang('application_complete') ?></option>
                                                <option value="1" <?= (isset($products_incomplete) && $products_incomplete == 1 ? 'selected' : '') ?>><?= lang('application_incomplete') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-8 action-filter d-flex justify-content-end">
                                            <div class="col-md-4">
                                                <a onclick="clearFilters()" class="btn btn-primary col-md-12"
                                                   style="margin-top: 25px;">
                                                    <i class="fa fa-eraser"></i>
                                                    <?= lang('application_clear'); ?>
                                                </a>
                                            </div>
                                            <div class="col-md-4">
                                                <a onclick="personalizedSearch()"
                                                   class="btn btn-primary btn-outline-primary col-md-12 ml-1"
                                                   style="margin-top: 25px;">
                                                    <span class="glyphicon glyphicon-search" aria-hidden="true"></span>
                                                    <?= lang('application_search') ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-primary">
                            <div class="box-header">
                                <div class="products-actions">
                                    <div class="pull-right">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default dropdown-toggle"
                                                    data-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false" id="actionsButton">
                                                <?= lang('application_actions') ?>
                                                <span class="caret"></span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                                <li class="dropdown-item"
                                                    data-toggle="modal"
                                                    data-view="list"
                                                    data-type="status"
                                                    data-status='<?= json_encode([
                                                        'code' => Model_products::ACTIVE_PRODUCT,
                                                        'alias' => 'active',
                                                        'description' => lang('application_active')
                                                    ]) ?>'
                                                >
                                                    <a>
                                                        <i class="fa fa-check-circle-o text-success"></i>
                                                        <?= lang('application_activate') ?>
                                                    </a>
                                                </li>
                                                <li class="dropdown-item"
                                                    data-toggle="modal"
                                                    data-view="list"
                                                    data-type="status"
                                                    data-status='<?= json_encode([
                                                        'code' => Model_products::INACTIVE_PRODUCT,
                                                        'alias' => 'inactive',
                                                        'description' => lang('application_inactive')
                                                    ]) ?>'
                                                >
                                                    <a>
                                                        <i class="fa fa-stop-circle-o text-warning"></i>
                                                        <?= lang('application_deactivate') ?>
                                                    </a>
                                                </li>
                                                <li class="divider" role="separator"></li>
                                                <li class="dropdown-item<?php echo in_array('moveProdTrash', $this->permission) ? '' : ' disabled'; ?>"
                                                    data-toggle="modal"
                                                    data-view="list"
                                                    data-type="trash"
                                                    <?php echo in_array('moveProdTrash', $this->permission) ? '' : 'data-disabled=true'; ?>
                                                >
                                                    <a>
                                                        <i class="fa fa-trash-o text-danger"></i>
                                                        <?= lang('application_delete') ?>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="btn-group">
                                            <button class="btn btn-primary dropdown-toggle" type="button"
                                                    data-toggle="dropdown">
                                                <i class="fa fa-file-excel-o"></i>Excel
                                                <span class="caret"></span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                                <li>
                                                    <a class="dropdown-item"
                                                       href="<?php echo(base_url('export/productXlsNew')) ?>"
                                                       id="exportProductsOnly"><?= lang('application_only_product'); ?></a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                       href="<?php echo(base_url('export/productXlsNew')) ?>"
                                                       id="exportProducts"><?= lang('application_variation_product'); ?></a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="box-body">
                                <table id="manageTable" class="table table-striped table-hover display table-condensed"
                                       cellspacing="0" style="border-collapse: collapse; width: 99%;">
                                    <thead>
                                    <tr>
                                        <th><input type="checkbox" name="select_all" value="1"
                                                   id="manageTable-select-all"></th>
                                        <th><?= lang('application_image'); ?></th>
                                        <th><?= lang('application_sku'); ?></th>
                                        <th><?= lang('application_name'); ?></th>
                                        <th><?= lang('application_price'); ?></th>
                                        <th><?= lang('application_qty'); ?></th>
                                        <th><?= lang('application_store'); ?></th>
                                        <th><?= lang('application_id'); ?></th>
                                        <th><?= lang('application_status'); ?></th>
                                        <th><?= lang('application_situation'); ?></th>
                                        <th><?= lang('application_platform'); ?></th>
                                        <!--th><?= lang('application_collections'); ?></th-->
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php if (in_array('deleteProduct', $user_permission)): ?>
    <!-- remove brand modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?= lang('application_delete_product'); ?><span
                                id="deleteproductname"></span></h4>
                </div>

                <form role="form" action="<?php echo base_url('products/remove') ?>" method="post" id="removeForm">
                    <div class="modal-body">
                        <p><?= lang('messages_delete_message_confirm'); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default"
                                data-dismiss="modal"><?= lang('application_close'); ?></button>
                        <button type="submit"
                                class="btn btn-primary"><?= lang('application_confirm'); ?></button>
                    </div>
                </form>

            </div>
        </div>
    </div>
<?php endif; ?>
<?php
include_once APPPATH . 'views/products/components/popup.update.status.product.php';
?>
<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script src="<?php echo base_url('assets/dist/js/components/products/product.base.update.js') ?>"></script>
<script src="<?php echo base_url('assets/dist/js/components/products/product.update.status.js') ?>"></script>
<script src="<?php echo base_url('assets/dist/js/components/products/product.move.trash.js') ?>"></script>

<script>
    var manageTable = null;
    var base_url = "<?php echo base_url(); ?>";
    var MAX_SELECT_STORES = 200;

    function isValidStoreSelection() {
        const selected = $('#stores').val() || [];
        return selected.length <= MAX_SELECT_STORES;
    }

    $(document).ready(function () {
        $('#stores').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
            var selected = $(this).val() || [];
            var isValidStoreSelection = selected.length <= MAX_SELECT_STORES
            if (isValidStoreSelection) {
                return;
            }
            $(this).selectpicker('val', previousValue);
            Swal.fire({
            title: 'Atenção!',
            html: `<h2>Você pode selecionar no máximo ${MAX_SELECT_STORES} lojas.</h2>`,
            icon: 'warning',
            showCancelButton: false,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Ok',
            })
        });

        $('#buttonCollapseFilter').bind('click', function (e) {
            $(this).hasClass('collapse-in') ? $(this).removeClass('collapse-in') : $(this).addClass('collapse-in');
            var text = $(this).hasClass('collapse-in') ? $(this).data('collapse-out') : $(this).data('collapse-in');
            var html = $('<span>', {
                class: "glyphicon glyphicon-filter",
                "aria-hidden": true
            });
            $('#buttonCollapseFilter').html($(html).get(0).outerHTML + text);
            return true;
        });

        $('#marketplaces').bind('change', function () {
            if ($(this).val().length > 0) {
                $('#status_integration option[data-status-mkt="disabled"]').removeAttr('disabled');
                return true;
            }
            $('#status_integration').val('');
            $('#status_integration option[data-status-mkt="disabled"]').attr('disabled', 'disabled');
        });

        $('#collapseFilter input, #collapseFilter select').on('change', function () {
            reloadFiltersExport({});
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function () {
        manageTable = personalizedSearch();

        $('#manageTable').on('draw.dt', function () {
            $('#manageTable [data-toggle="tootip"]').tooltip();
        });

        $('#manageTable').on('preXhr.dt', function (e, settings, data) {
            //reloadFiltersExport({columns: data.columns, order: data.order});
        });

        $('body').tooltip({
            selector: '[data-toggle="tooltip"]'
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $($.fn.dataTable.tables(true)).DataTable()
                .columns.adjust()
                .responsive.recalc();
        });
        $('input[type="checkbox"].minimal').iCheck({
            checkboxClass: 'icheckbox_minimal-blue',
            radioClass: 'iradio_minimal-blue'
        });

        $('#actionsButton').prop('disabled', true);
        // Handle click on "Select all" control
        $('#manageTable-select-all').on('click', function () {
            // Get all rows with search applied
            var rows = manageTable.rows({'search': 'applied'}).nodes();
            // Check/uncheck checkboxes for all rows in the table
            $('input[type="checkbox"]', rows).prop('checked', this.checked).trigger('change');
        });

        $('#manageTable tbody').on('change', 'input[type="checkbox"]', function () {
            if (!this.checked) {
                var el = $('#manageTable-select-all').get(0);
                if (el && el.checked && ('indeterminate' in el)) {
                    el.indeterminate = true;
                }
            }
            if ($('#manageTable tbody input[type="checkbox"]:checked').length > 0) {
                $('#actionsButton').prop('disabled', false);
            } else {
                $('#actionsButton').prop('disabled', true);
            }
        });

        $('li[data-toggle="modal"]').off('click').on('click', function () {
            if ($(this).data('disabled')) {
                return false;
            }
            var modal = (new ChangeProductStatusModal({
                view: $(this).data('view'),
                type: $(this).data('type'),
            }));
            if ($(this).data('status')) {
                modal.setStatus($(this).data('status'));
            }
            modal.setCount(
                $('#manageTable tbody input[type="checkbox"]:checked').length
            );
            modal.init().then(function (args) {
                var obj = null;
                if (args.type && args.type == 'trash') {
                    obj = new ProductMoveTrash({
                        baseUrl: base_url,
                        endpoint: 'products'
                    });
                } else if (args.type && args.type == 'status') {
                    obj = new ProductUpdateStatus({
                        baseUrl: base_url,
                        endpoint: 'products'
                    });
                }
                if (obj) {
                    $('#manageTable tbody input[type="checkbox"]:checked').each(function () {
                        var prod = {id: $(this).val()};
                        if (args.status && args.status['code']) {
                            $.extend(prod, {status: args.status['code']});
                        }
                        obj.addProduct(prod);
                    });
                    obj.send().then(function (response) {
                        var res = JSON.parse(response);
                        Toast.fire({
                            icon: 'success',
                            title: res['message']
                        });
                    }).fail(function (e) {
                        var msg = e.responseText.length > 0 ? JSON.parse(e.responseText)['errors'] : [e.statusText];
                        var alerts = [];
                        $.each(msg ?? [], function (k, m) {
                            alerts.push({
                                icon: 'warning',
                                title: m
                            });
                        });
                        if (alerts.length > 0) {
                            Toast.queue(alerts);
                        }
                    }).always(function () {
                        $('#manageTable-select-all').prop('checked', false);
                        $('#actionsButton').prop('disabled', true);
                        personalizedSearch();
                    });
                }
            });
        });
        reloadFiltersExport({});
    });

    function personalizedSearch() {
        if (!isValidStoreSelection()) {
            return;
        }

        if (manageTable !== null) {
            manageTable.destroy();
        }
        manageTable = $('#manageTable').DataTable({
            "language": {"url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "order": [[0, 'desc']],
            "serverMethod": "post",
            'columnDefs': [{
                'targets': 0,
                'searchable': false,
                'orderable': false,
                'className': 'dt-body-center',
                'render': function (data, type, full, meta) {
                    return '<input type="checkbox" class="productsselect" name="id[]" value="' + $('<div/>').text(data).html() + '">';
                }
            }],
            'columns': [
                {data: 'id', orderable: false, name: 'id'},
                {data: 'image', orderable: false},
                {data: 'sku', name: 'sku'},
                {data: 'name', name: 'name'},
                {data: 'price', name: 'price'},
                {data: 'stock', name: 'qty'},
                {data: 'store_name', orderable: false},
                {data: 'id', name: 'id'},
                {data: 'status', orderable: false},
                {data: 'situation', orderable: false},
                {data: 'marketplaces', orderable: false}//,
               /* {data: 'collections', orderable: false}*/
            ],
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'products/fetchProductDataNew',
                data: getFormDataFilter({}),
                pages: 2, // number of pages to cache
            }),
            "createdRow": function (row, data, dataIndex) {
                $(row).find('td:eq(3)').addClass('d-flex align-items-center');
            },
            "initComplete": function (settings, json) {
                $('#manageTable [data-toggle="tootip"]').tooltip();
                $('#manageTable_filter input[type="search"]').on('keypress keydown keyup paste', function () {
                    if ($(this).val().length == 0) return;
                    reloadFiltersExport({
                        search: {
                            value: $(this).val()
                        }
                    });
                });
            }
        });
        reloadFiltersExport({});
        return manageTable;
    }

    function clearFilters() {
        clearFormFilter();
        personalizedSearch();
    }

    const reloadFiltersExport = (addFilters) => {
        $.extend(addFilters, {variation: false});
        setHrelButtom('exportProductsOnly', addFilters);
        $.extend(addFilters, {variation: true});
        setHrelButtom('exportProducts', addFilters);
    }
    const setHrelButtom = (id, addParams) => {
        const href = $('#' + id).attr('href');
        const splitHref = href.split('?');
        var filters = getFormDataFilter(addParams);
        let newHref = splitHref[0] + '?' + $.param(filters);
        $('#' + id).attr('href', newHref);
    }

    function clearFormFilter() {
        $('#collapseFilter input').each(function () {
            $(this).val('');
        });
        $('#collapseFilter select').each(function () {
            $(this).val('0');
            if ($(this).attr('name') == 'status_integration'
                || $(this).attr('name') == 'with_stock'
                || $(this).attr('name') == 'is_kit'
                || $(this).attr('id') == 'collections'
            ) {
                $(this).val('');
            }
            if ($(this).hasClass('selectpicker')) {
                $(this).trigger("change");
            }
        });
    }

    function getFormDataFilter(addParams) {
        let filters = {};
        $('#collapseFilter input').each(function () {
            if (typeof $(this).attr('id') !== "undefined" && typeof $(this).val() !== "undefined" && $(this).val() != '') {
                var filter = {};
                if ($(this).attr('name').length > 0) {
                    filter[$(this).attr('name')] = $(this).val();
                } else {
                    filter[$(this).attr('id')] = $(this).val();
                }
                $.extend(filters, filter);
            }
        });
        $('#collapseFilter select').each(function () {
            if (typeof $(this).attr('id') !== "undefined" && typeof $(this).val() !== "undefined") {
                var filter = {};
                if ($(this).attr('name').length > 0) {
                    if ($(this).val() == '0'
                        && (
                            !($(this).attr('name') == 'status_integration')
                            && !($(this).attr('name') == 'with_stock')
                            && !($(this).attr('name') == 'is_kit')
                        )
                    ) return;
                    if ($(this).attr('id') == 'stores') {
                        filter['stores'] = $(this).val();
                    } else if ($(this).attr('id') == 'marketplaces') {
                        filter['marketplaces'] = $(this).val();
                    } else if ($(this).attr('id') == 'collections') {
                        filter['collections'] = Array.isArray($(this).val()) ? $(this).val() : [$(this).val()];
                    } else {
                        filter[$(this).attr('name')] = $(this).val();
                    }
                } else {
                    filter[$(this).attr('id')] = $(this).val();
                }
                $.extend(filters, filter);
            }
        });
        $.extend(filters, addParams);

        $.each(filters, function (filter, value) {
            return $.extend(filters, {
                [filter]: typeof value === 'string' ? encodeURIComponent(value) : value
            });
        });
        return filters;
    }
</script>

<script>

    function changeQty(id, old_qty, new_qty) {
        $.ajax({
            url: base_url + "products/updateQty",
            type: 'POST',
            data: {id: id, old_qty: old_qty, new_qty: new_qty},
            async: true,
            dataType: 'json'
        });
    }

    function changePrice(id, old_price, new_price, elementHtml) {
        var priceFloat = parseFloat(new_price);
        var priceFormated = priceFloat.toLocaleString('pt-BR');

        $.ajax({
            url: base_url + "products/updatePrice",
            type: 'POST',
            data: {id: id, old_price: old_price, new_price: new_price},
            async: true,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $(elementHtml).val(priceFormated);
                    $(elementHtml).attr("onfocus", `this.value=${priceFormated.replace(',', '.')}`);
                }
            }
        });

        return priceFormated;
    }

    function formatPrice(value) {
        return value.replace(/[^0-9.]/g, "");
    }

    function removeFunc(id, name) {
        if (id) {
            document.getElementById("deleteproductname").innerHTML = ': ' + name;
            $("#removeForm").on('submit', function () {
                var form = $(this);
                $(".text-danger").remove();
                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: {product_id: id},
                    dataType: 'json',
                    success: function (response) {
                        manageTable.ajax.reload(null, false);
                        if (response.success === true) {
                            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + response.messages +
                                '</div>');

                            // hide the modal
                            $("#removeModal").modal('hide');

                        } else {
                            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + response.messages +
                                '</div>');
                        }
                    }
                });
                return false;
            });
        }
    }
</script>
