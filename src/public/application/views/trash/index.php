<!--
SW Serviços de Informática 2019

Listar Produtos

Obs:
cada usuario so pode ver produtos da sua empresa.
Agencias podem ver todos os produtos das suas empresas
Admin pode ver produtos detodas as empresas e agencias

-->
<!-- Content Wrapper. Contains page content -->

<div class="content-wrapper">
    <style>
        .dropdown.bootstrap-select.show-tick.form-control {
            display: block;
            width: 100%;
            color: #555;
            background-color: #fff;
            background-image: none;
            border: 1px solid #ccc;
        }

        .bootstrap-select > .dropdown-toggle.bs-placeholder {
            padding: 5px 12px;
        }

        .bootstrap-select .dropdown-toggle .filter-option {
            background-color: white !important;
        }

        .bootstrap-select .dropdown-menu li a {
            border: 1px solid gray;
        }

        .input-group-addon {
            cursor: pointer;
        }
    </style>

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div id="messages"></div>
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>
                <div class="box box-primary" id="collapseFilter">
                    <div class="box-body">

                        <h4 class="mt-0">Filtros</h4>
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label for="filter_sku"><?= $this->lang->line('application_sku') ?></label>
                                <div class="input-group">
                                    <input type="search" id="filter_sku" name="sku" class="form-control"
                                           placeholder="Código SKU" aria-label="Search" aria-describedby="basic-addon1"
                                           onchange="personalizedSearch()" maxlength="60"
                                           onKeyUp="characterLimit(this);"
                                    >
                                    <span class="input-group-addon " id="">
                                        <i class="fas fa-search text-grey" aria-hidden="true"></i>
                                    </span>
                                </div>
                                <span id="char_filter_sku"></span>
                            </div>

                            <div class="col-md-4 form-group">
                                <label for="filter_product"><?= $this->lang->line('application_product') ?></label>
                                <div class="input-group">
                                    <input type="search" id="filter_product" name="product" class="form-control"
                                           placeholder="Nome do Produto" aria-label="Search"
                                           aria-describedby="basic-addon1"
                                           onchange="personalizedSearch()">
                                    <span class="input-group-addon " id="">
                                    <i class="fas fa-search text-grey" aria-hidden="true"></i>
                                </span>
                                </div>
                            </div>

                            <?php if (!empty($stores)) { ?>
                                <div class="col-md-3 form-group">
                                    <label for="filter_stores"><?= $this->lang->line('application_stores') ?></label>
                                    <select class="form-control selectpicker show-tick" id="filter_stores" name="stores"
                                            onchange="personalizedSearch()" data-live-search="true"
                                            data-actions-box="true"
                                            multiple="multiple" data-style="btn-blue"
                                            data-selected-text-format="count > 1"
                                            title="<?= $this->lang->line('application_search_for_store'); ?>">
                                        <?php foreach ($stores as $store) { ?>
                                            <option value="<?= $store['id'] ?>"><?= $store['name'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            <?php } ?>
                            <div class="col-md-2">
                                <label for="filter_synchronized"><?= $this->lang->line('application_synchronized') ?></label>
                                <select class="form-control" id="filter_synchronized" name="synchronized"
                                        onchange="personalizedSearch()">
                                    <option value=""><?= $this->lang->line('application_all') ?></option>
                                    <option value="0"><?= $this->lang->line('application_not_synchronized') ?></option>
                                    <option value="1"><?= $this->lang->line('application_synchronized') ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2 form-group" style="float: right;">
                                <button type="button" onclick="clearFilters()" class="pull-right btn btn-primary">
                                    <i class="fa fa-eraser"></i> Limpar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box box-primary">
                    <div class="box-header">
                        <div class="pull-right dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown"><i
                                        class="fa fa-file-excel-o"></i>Excel
                                <span class="caret"></span></button>
                            <ul class="dropdown-menu">
                                <li><a href="#">
                                        <a class="dropdown-item"
                                           href="<?php echo(base_url('export/trashProductsExport') . "") ?>"
                                           id="exportProductsOnly">Somente Produtos</a></a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo(base_url('export/trashProductsExport') . "") ?>"
                                       id="exportProducts">Produtos com Variação</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="box-body">
                        <table id="manageTable" class="table table-striped table-hover display table-condensed"
                               cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <thead>
                            <tr>
                                <th><?= $this->lang->line('application_id'); ?></th>
                                <th><?= $this->lang->line('application_image'); ?></th>
                                <th><?= $this->lang->line('application_sku'); ?></th>
                                <th><?= $this->lang->line('application_name'); ?></th>
                                <th><?= $this->lang->line('application_store'); ?></th>
                                <th><?= $this->lang->line('application_marketplace'); ?></th>
                                <th style="min-width: 120px"><?= $this->lang->line('application_actions'); ?></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php
include_once APPPATH . 'views/products/components/popup.update.status.product.php';
?>
<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
    var manageTable = null;
    var baseUrl = "<?php echo base_url(); ?>";

    function getBaseUrl() {
        return baseUrl;
    };

    function getFullEndpoint() {
        return getBaseUrl() + 'products/trash';
    };

    $(document).ready(function () {

        personalizedSearch();

        $('.selectpicker').on('show.bs.select', function () {
            var $dropdownMenu = $(this).nextAll('div.dropdown-menu').first();
            if ($dropdownMenu.length > 0) {
                $dropdownMenu.css('min-width', '').css('max-width', '100%');
                var $inner = $dropdownMenu.find('div.inner');
                if ($inner.length > 0) {
                    $inner.css('overflow-x', 'hidden');
                }
            }
            $('.dropdown-menu .bs-searchbox input', $(this).parent()).attr('maxlength', 60);
        });
    });

    function personalizedSearch() {
        if (manageTable !== null) {
            manageTable.destroy();
        }

        manageTable = $('#manageTable').DataTable({
            "language": {
                "url": getBaseUrl() + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'
            },
            drawCallback: function () {
                $('button[data-toggle="tooltip"]', $('#manageTable tr:not(:first-child')).tooltip();
                $('button[data-toggle="tooltip"]', $('#manageTable tr:first-child')).tooltip({
                    placement: 'bottom'
                });
                $('.btn-del-product').off('click').on('click', function () {
                    deleteBtn($(this));
                });
                $('.btn-cp-product').off('click').on('click', function () {
                    var prodId = $(this).data('product-id');
                    if (parseInt(prodId) > 0) {
                        window.open(getFullEndpoint() + '/copy/' + prodId);
                    }
                });
                $('.btn-view-product').off('click').on('click', function () {
                    var prodId = $(this).data('product-id');
                    if (parseInt(prodId) > 0) {
                        window.open(getFullEndpoint() + '/view/' + prodId);
                    }
                });
            },
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline({
                url: getFullEndpoint() + '/fetchProductData',
                data: getFilters(),
                pages: 2, // number of pages to cache
            }),
            'columns': [
                {data: 'id'},
                {data: 'image'},
                {data: 'sku'},
                {data: 'name'},
                {data: 'store_name'},
                {data: 'marketplaces'},
                {data: 'actions'}
            ]
        });
        reloadFiltersExport({});
    }

    function deleteBtn(el) {
        var modal = (new ChangeProductStatusModal({
            view: $(el).data('view'),
            type: $(el).data('type'),
        }));

        var idProduct = $(el).data('product-id');
        modal.init().then(function (args) {
            var url = getFullEndpoint().concat('/deletePermanently')
                .concat('/' + idProduct);
            return $.ajax({
                url: url,
                type: 'DELETE'
            }).then(function (response) {
                var res = JSON.parse(response);
                Toast.fire({
                    icon: 'success',
                    title: res['messages'].join("\n")
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
                personalizedSearch();
            });
        });
    };

    function getFilters() {
        var filters = {};

        $('[id^="filter_"]').each(function () {
            if ($(this).attr('name').length > 0) {
                $.extend(filters, {
                    [$(this).attr('name')]: $(this).val()
                });
            }
        });

        return filters;
    }

    function clearFilters() {
        $('#filter_sku').val('');
        $('#filter_product').val('');
        $('#filter_stores').val('');
        $('#filter_synchronized').val('');
        $('#filter_stores').selectpicker('val', '');

        personalizedSearch();
    }

    const reloadFiltersExport = (addFilters) => {
        $.extend(addFilters, {variation: false});
        setHrelButtom('exportProductsOnly', addFilters);
        $.extend(addFilters, {variation: true});
        setHrelButtom('exportProducts', addFilters);
    }
    const setHrelButtom = (id, adicional_param) => {
        const href = $('#' + id).attr('href');

        const splitHref = href.split('?');
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
                    if ($(this).val() == '0' && !($(this).attr('name') == 'synchronized')) return;

                    if ($(this).attr('id') == 'filter_stores') {
                        filter['stores'] = $(this).val();
                    } else {
                        filter[$(this).attr('name')] = $(this).val();
                    }
                } else {
                    filter[$(this).attr('id')] = $(this).val();
                }
                $.extend(filters, filter);
            }
        });
        $.extend(filters, adicional_param);

        $.each(filters, function (filter, value) {
            return $.extend(filters, {
                [filter]: typeof value === 'string' ? encodeURIComponent(value) : value
            });
        });
        let new_href = splitHref[0] + '?' + $.param(filters);
        console.log(id, new_href);
        $('#' + id).attr('href', new_href);
    }

    function characterLimit(object) {
        var limit = object.getAttribute('maxlength');
        var attribute = object.getAttribute('id');

        if (attribute == 'description') {
            // var quantity = $(".note-editable").text().length;
            var quantity = $(".note-editable").html().length;
        } else {
            var quantity = object.value.length;
        }

        $('#char_' + attribute).text(`<?= $this->lang->line('application_type_char'); ?>${quantity}/${limit}`);
    }
</script>
