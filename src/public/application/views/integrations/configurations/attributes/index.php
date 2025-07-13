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
        .bootstrap-select > .dropdown-toggle {
            background-color: #fff;
        }

        /* Small devices (landscape phones, 576px and up)*/
        @media (min-width: 576px) {
            #publishAllProducts .modal-dialog {
                width: 90% !important;
            }
        }

        /* Medium devices (tablets, 768px and up)*/
        @media (min-width: 768px) {
            #publishAllProducts .modal-dialog {
                width: 80% !important;
            }
        }

        /* Large devices (desktops, 992px and up)*/
        @media (min-width: 992px) {
            #publishAllProducts .modal-dialog {
                width: 70% !important;
            }
        }

        /* Extra large devices (large desktops, 1200px and up)*/
        @media (min-width: 1200px) {
            #publishAllProducts .modal-dialog {
                width: 70% !important;
            }
        }
    </style>

    <?php
    $data = array_merge($data ?? [], (array)($this->data ?? []));
    $this->load->view('templates/content_header', $data);
    ?>

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
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#nav-products_variation"
                               data-toggle="tab"><?= $this->lang->line('application_variations') ?>
                            </a>
                        </li>
                        <li>
                            <a href="#nav-products_attribute"
                               data-toggle="tab"><?= $this->lang->line('application_attributes') ?>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content col-md-12 no-padding">
                    <div class="tab-pane active" id="nav-products_variation"
                         data-module="<?= \libraries\Attributes\Application\Resources\CustomAttribute::PRODUCT_VARIATION_MODULE ?>">
                        <?php if (!empty($stores)) { ?>
                            <div class="box box-primary">
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-info">
                                                <h4 style="text-align: center"><?=lang('application_data_normalization_info')?></h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 form-group">
                                            <label for="filter_stores"><?= $this->lang->line('application_stores') ?></label>
                                            <select class="form-control selectpicker show-tick" id="filter_stores"
                                                    name="stores"
                                                    onchange="personalizedVariationSearch()" data-live-search="true"
                                                    data-actions-box="true"
                                                    multiple="multiple" data-style="btn-blue"
                                                    data-selected-text-format="count > 1"
                                                    title="<?= $this->lang->line('application_search_for_store'); ?>">
                                                <?php foreach ($stores as $store) { ?>
                                                    <option value="<?= $store['id'] ?>"><?= $store['name'] ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="box box-primary">
                            <div class="box-header">

                            </div>
                            <div class="box-body">
                                <table id="manageTable" class="table table-striped table-hover display table-condensed"
                                       cellspacing="0" style="border-collapse: collapse; width: 99%;">
                                    <thead>
                                    <tr>
                                        <th><?= $this->lang->line('application_name'); ?></th>
                                        <th><?= $this->lang->line('application_type'); ?></th>
                                        <th><?= $this->lang->line('application_mapped_values'); ?></th>
                                        <th><?= $this->lang->line('application_status'); ?></th>
                                        <th><?= $this->lang->line('application_store'); ?></th>
                                        <th style="min-width: 320px"><?= $this->lang->line('application_actions'); ?></th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="nav-products_attribute"
                         data-module="<?= \libraries\Attributes\Application\Resources\CustomAttribute::PRODUCT_ATTRIBUTE_MODULE ?>">
                        <div class="nav-tabs-custom">
                            <div class="row">
                                <div class="col-md-12" style="background-color: #ecf0f5">
                                    <div class="alert alert-info">
                                        <h4 style="text-align: center"><?=lang('application_data_normalization_info')?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="box box-primary">
                                <div class="box-header">

                                </div>
                                <div class="box-body">
                                    <div class="row" id="nav-products_attribute_key">
                                        <div class="col-md-4 form-group <?=count($stores) == 1 ? 'd-none' : ''?>">
                                            <label for="filter_stores_attribute"><?= $this->lang->line('application_stores') ?></label>
                                            <select class="form-control selectpicker show-tick" id="filter_stores_attribute"
                                                    name="stores"
                                                    data-live-search="true"
                                                    data-style="btn-blue"
                                                    title="<?= $this->lang->line('application_search_for_store'); ?>">
                                                <?php foreach ($stores as $store) { ?>
                                                    <option value="<?= $store['id'] ?>" <?=count($stores) == 1 ? 'selected' : ''?>><?= $store['name'] ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-5">
                                            <label for="filter_categories"><?= $this->lang->line('application_categories') ?></label>
                                            <select class="form-control selectpicker show-tick"
                                                    id="filter_categories"
                                                    name="categories"
                                                    data-live-search="true"
                                                    data-style="btn-blue"
                                                    title="<?= $this->lang->line('application_search_for_categories'); ?>"
                                                    disabled
                                            ></select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="marketplace"><?= $this->lang->line('application_marketplace') ?></label>
                                            <select class="form-control selectpicker show-tick"
                                                    id="marketplace"
                                                    name="marketplace"
                                                    data-live-search="true"
                                                    data-style="btn-blue"
                                                    title="<?= $this->lang->line('application_search_for_marketplaces'); ?>"
                                                    disabled
                                            ></select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="attributes"><?= $this->lang->line('application_attributes') ?></label>

                                            <div class="input-group">
                                                <select class="form-control select2 show-tick"
                                                        id="attributes"
                                                        name="attributes"
                                                        data-live-search="true"
                                                        data-style="btn-blue"
                                                        title="<?= $this->lang->line('application_search_for_attributes'); ?>"
                                                        disabled
                                                >
                                                    <option value=""><?=$this->lang->line('application_select')?></option>
                                                </select>
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-primary btn-flat" id="addAttributeMarketplace" disabled><i class="fa fa-plus"></i> <?=$this->lang->line('application_add')?></button>
                                                </span>
                                            </div>
                                            <button class="btn btn-link" id="add-required-attributes" disabled><i class="fa fa-plus-circle"></i> Adicionar todos os atributos obrigatórios</button>
                                        </div>
                                    </div>

                                    <div id="head_list_attributes">
                                        <div class="pt-3 mb-2 content_attributes" style="max-height: 500px; overflow-x: hidden;border-top: 2px dashed;border-bottom: 2px dashed">
                                            <div class="row mt-1">
                                                <div class="form-group col-md-12 no-margin">
                                                    <div class="col-md-3 no-padding">
                                                        <label>Atributo Marketplace</label>
                                                    </div>
                                                    <div class="col-md-1">&nbsp;</div>
                                                    <div class="col-md-5">
                                                        <label>Atributo do produto</label>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label></label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <form id="formSellerAttributes">
                                                    <div class="form-group col-md-12" id="list_attributes">

                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col-md-12 d-flex justify-content-end">
                                                <button class="btn btn-success col-md-3" id="btnSaveSellerAttributes"><?=$this->lang->line('application_update_changes')?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="publishAllProducts">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span>Vínculo de Valores de Atributos</span></h4>
            </div>
            <div class="modal-body" id="nav-products_attribute_value">
                <div class="row">
                    <div class="col-md-5 form-group <?=count($stores) == 1 ? 'd-none' : ''?>">
                        <label for="filter_stores_attribute"><?= $this->lang->line('application_stores') ?></label>
                        <select class="form-control selectpicker show-tick" id="filter_stores_attribute_attribute_value"
                            name="stores"
                            data-live-search="true"
                            data-style="btn-blue"
                            title="<?= $this->lang->line('application_search_for_store'); ?>"
                            disabled
                        >
                            <?php foreach ($stores as $store) { ?>
                                <option value="<?= $store['id'] ?>" <?=count($stores) == 1 ? 'selected' : ''?>><?= $store['name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-5">
                        <label for="filter_categories_attribute_value"><?= $this->lang->line('application_categories') ?></label>
                        <select class="form-control selectpicker show-tick"
                            id="filter_categories_attribute_value"
                            name="filter_categories_attribute_value"
                            data-live-search="true"
                            data-style="btn-blue"
                            title="<?= $this->lang->line('application_search_for_categories'); ?>"
                            disabled
                        ></select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="marketplace_attribute_value"><?= $this->lang->line('application_marketplace') ?></label>
                        <select class="form-control selectpicker show-tick"
                            id="marketplace_attribute_value"
                            name="marketplace_attribute_value"
                            data-live-search="true"
                            data-style="btn-blue"
                            title="<?= $this->lang->line('application_search_for_marketplaces'); ?>"
                            disabled
                        ></select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="attributes_attribute_value"><?= $this->lang->line('application_attributes') ?></label>
                        <select class="form-control select2 show-tick"
                            id="attributes_attribute_value"
                            name="attributes_attribute_value"
                            data-live-search="true"
                            data-style="btn-blue"
                            title="<?= $this->lang->line('application_search_for_attributes'); ?>"
                            disabled
                        >
                            <option value=""><?=$this->lang->line('application_select')?></option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-5 d-none">
                        <label for="values_attribute_value"><?= $this->lang->line('application_values') ?></label>
                        <div class="input-group">
                            <select class="form-control select2 show-tick"
                                id="values_attribute_value"
                                name="values_attribute_value"
                                data-live-search="true"
                                data-style="btn-blue"
                                title="<?= $this->lang->line('application_search_for_values'); ?>"
                                disabled
                            >
                                <option value=""><?=$this->lang->line('application_select')?></option>
                            </select>
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-primary btn-flat" id="addAttributeMarketplace_attribute_value" disabled><i class="fa fa-plus"></i> <?=$this->lang->line('application_add')?></button>
                            </span>
                        </div>
                    </div>
                </div>

                <div id="head_list_attributes_attribute_value">
                    <div class="pt-3 mb-2 content_attributes" style="max-height: 500px; overflow-x: hidden;border-top: 2px dashed;border-bottom: 2px dashed">
                        <div class="row mt-1">
                            <div class="form-group col-md-12 no-margin">
                                <div class="col-md-5 no-padding">
                                    <label>Valor do Atributo no Marketplace</label>
                                </div>
                                <div class="col-md-1">&nbsp;</div>
                                <div class="col-md-6">
                                    <label>Valor do Atributo no Produto</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <form id="formSellerAttributes_attribute_value">
                                <div class="form-group col-md-12" id="list_attributes_attribute_value">

                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-default col-md-3" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                <button class="btn btn-success col-md-3" id="btnSaveSellerAttributes_attribute_value"><?=$this->lang->line('application_update_changes')?></button>
            </div>
        </div>
    </div>
</div>


<?php
include_once APPPATH . 'views/integrations/configurations/attributes/components/popup.add.attribute.map.php';
include_once APPPATH . 'views/integrations/configurations/attributes/components/popup.add.variation.map.php';
?>
<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
    var manageTable = null;
    var manageTableAttribute = null;
    var baseUrl = "<?php echo base_url(); ?>";
    var attribute_custom = [];
    var attribute_custom_in_use = [];
    var attribute_value_custom_in_use = [];
    var datalist_attribute_custom = '';

    function getBaseUrl() {
        return baseUrl;
    }

    function getFullEndpoint() {
        return getBaseUrl() + 'integrations/configuration/attributes';
    }

    $(document).ready(function () {
        $("#mainIntegrationApiNav, #integrationAttributes").addClass('active');
        $('#attributes, #attributes_attribute_value, #values_attribute_value').select2();
        $('#head_list_attributes, #head_list_attributes_attribute_value').hide();

        personalizedVariationSearch();

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

        setTimeout(() => {
            $('#nav-products_attribute #filter_stores_attribute').trigger('click');
            $('#nav-products_attribute #filter_stores_attribute, #nav-products_attribute #filter_stores_attribute_attribute_value').trigger('change');
        }, 500);
    });

    $('#publishAllProducts').on('show.bs.modal', function (event) {
        $('#publishAllProducts #marketplace_attribute_value').val($('#nav-products_attribute #marketplace').val()).selectpicker('refresh');
        $('#publishAllProducts #filter_categories_attribute_value').val($('#nav-products_attribute #filter_categories').val()).selectpicker('refresh');
        $('#publishAllProducts #attributes_attribute_value').val(($(event.relatedTarget).data('attribute') ?? '').split('_')[0] ?? '').trigger('change')
    })


    function personalizedVariationSearch() {
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
                $('#manageTable .btn-del-attribute').off('click').on('click', function () {
                    deleteBtn($(this));
                });
                $('#manageTable .btn-edit-attribute').off('click').on('click', function () {
                    var attrId = $(this).data('attribute-id');
                    if (parseInt(attrId) > 0) {
                        window.location.href = getFullEndpoint() + '/edit/' + attrId;
                    }
                });
                $('#manageTable .btn-add-value-attribute').off('click').on('click', function () {
                    addUpdateVariationMapBtn($(this));
                });
            },
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "serverMethod": "POST",
            "ajax": $.fn.dataTable.pipeline({
                url: "<?=base_url('integrationAttributeMap/fetchVariationData')?>",
                data: getFilters('#nav-products_variation'),
                pages: 2, // number of pages to cache
            }),
            'columns': [
                {data: 'name'},
                {data: 'type'},
                {data: 'mapped_values'},
                {data: 'status'},
                {data: 'store_name'},
                {data: 'actions'}
            ]
        });
    }

    function addUpdateVariationMapBtn(el) {
        const attrTitle = '<?=lang('application_add_attribute_map') . ": "?>' + $(el).data('attribute-name');
        const modal = (new AddUpdateVariationMapModal({popupTitle: attrTitle}));

        const attrId = $(el).data('attribute-id');
        const storeId = $(el).data('store-id');
        const companyId = $(el).data('company-id');
        modal.init().then(function (args) {
            const url = getFullEndpoint().concat('/addUpdateAttrMap');
            const formData = {
                'attributeId': attrId,
                'storeId': storeId,
                'companyId': companyId
            };
            $.extend(formData, modal.getFormValues());
            return $.post(url, {
                data: JSON.stringify(formData),
            }).then(function (response) {
                const res = JSON.parse(response);
                Toast.fire({
                    icon: 'success',
                    title: res['messages'].join("\n")
                });
            }).fail(function (e) {
                const msg = e.responseText.length > 0 ? JSON.parse(e.responseText)['errors'] : [e.statusText];
                let alerts = [];
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
                personalizedVariationSearch();
            });
        });
    }

    function deleteBtn(el) {
    }

    function getFilters(el) {
        var filters = {};

        $(el).find('[id^="filter_"]').each(function () {
            if ($(this).attr('name').length > 0) {
                $.extend(filters, {
                    [$(this).attr('name')]: $(this).val()
                });
            }
        });
        if ($('.tab-pane.active').length > 0) {
            $.extend(filters, {
                'module': $('.tab-pane.active').data('module')
            });
        }
        return filters;
    }

    function clearFilters(el) {
        $(el).find('#filter_stores').val('');
        $(el).find('#filter_stores').selectpicker('val', '');

        personalizedVariationSearch();
    }

    const adjustHeightAttributeContent = attributeContent => {
        const attributes_nums = attributeContent.find('.values .attribute').length;
        const input_pixel = 3.9;

        attributeContent.find('.marketplace_attribute p').attr("style", "height: " + (attributes_nums * input_pixel) + "rem");
        attributeContent.find('.attribute-arrow i').attr("style", "display: flex;height: " + (attributes_nums * input_pixel) + "rem");
        attributeContent.find('.attribute-btn-new-value').attr("style", "display: flex;height: " + (attributes_nums * input_pixel) + "rem");
    }

    const inputNewAttribute = (attribute, attribute_value = '', suffix_input_name = '') => {
        let input_attribute = datalist_attribute_custom;

        if (suffix_input_name !== '') {
            input_attribute = '<input type="text" autocomplete="off" class="form-control col-md-10" name="replace_id_custom" list="replace_id_custom" value="attribute_value_replace"><datalist id="replace_id_custom"></datalist>';
        }

        input_attribute = input_attribute
            .replace('id="replace_id_custom"', `id="${attribute}"`)
            .replace('list="replace_id_custom"', `list="${attribute}"`)
            .replace('name="replace_id_custom"', `name="${attribute}"`)
            .replace('attribute_value_replace', attribute_value);

        return `<div class="attribute d-flex mt-2">${input_attribute}<button class="btn btn-flat btn-danger col-md-2 no-padding del_attribute_value${suffix_input_name}"><i class="fa fa-trash"></i></button></div>`;
    }

    const updateSelectAttributes = (suffix_input_name = '') => {
        const id_el = suffix_input_name ? 'values_attribute_value' : 'attributes';
        const nav_products_attribute = suffix_input_name ? '#nav-products_attribute_value ' : '#nav-products_attribute ';

        $(`${nav_products_attribute}#${id_el}`).select2('destroy').select2({
            templateResult: function(option) {
                let attributes_custom = suffix_input_name ? attribute_value_custom_in_use : attribute_custom_in_use;

                if (attributes_custom.includes($(option.element).val())) {
                    return null;
                }
                return option.text;
            }
        }).val('').trigger('change.select2');
    }

    const checkFiltersSelectedToAddAttribute = (suffix_input_name = '') => {
        const nav_products_attribute = suffix_input_name ? '#nav-products_attribute_value ' : '#nav-products_attribute ';

        const store = $(`${nav_products_attribute}#filter_stores_attribute${suffix_input_name}`).val();
        const category = $(`${nav_products_attribute}#filter_categories${suffix_input_name}`).val();
        const marketplace = $(`${nav_products_attribute}#marketplace${suffix_input_name}`).val();

        if (!store.length || marketplace == '' || category == '') {
            return false;
        }

        return true;
    }

    const createNewAttributeList = (attributes, attribute_seller_value = [], suffix_input_name = '', show_after_save = false, trigger_new_input = true) => {
        const nav_products_attribute = suffix_input_name ? '#nav-products_attribute_value ' : '#nav-products_attribute ';
        if (!$(`${nav_products_attribute}#list_attributes${suffix_input_name} .attribute-content`).length) {
            $(`${nav_products_attribute}#head_list_attributes${suffix_input_name}`).show();
        }

        let attribute_name = '';
        if (suffix_input_name) {
            attribute_name = $(`${nav_products_attribute}#values_attribute_value option[value="${attributes}"]`).text();
        } else {
            attribute_name = $(`${nav_products_attribute}#attributes option[value="${attributes}"]`).text();
        }

        if (attribute_name == '') {
            return false;
        }

        if (!suffix_input_name) {
            let show_btn_async = attributes.split('_').at(-1) === 'list' ? '' : 'd-none';
            let text_btn_async = '<i class="fa fa-plus-circle"></i> Realizar vínculo de valores de atributo';
            let data_saved = true;
            let disabled_btn_async = '';
            if (show_after_save) {
                text_btn_async = '<i class="fa fa-plus-circle"></i> Salve as alterações para fazer o vínculo';
                data_saved = false;
                disabled_btn_async = 'disabled';
            }

            if (show_btn_async === 'd-none') {
                text_btn_async = '<i class="fa fa-ban"></i> Atributo do tipo texto. Não há valores para realizar vínculo';
                disabled_btn_async = 'disabled';
                data_saved = true;
                show_btn_async = '';
            }

            $(`${nav_products_attribute}#list_attributes${suffix_input_name}`).append(`
                <div class="row mt-1 mb-3 attribute-content" data-attribute="${attributes}">
                    <div class="col-md-3 marketplace_attribute">
                        <p class="form-control d-flex align-items-center bg-gray">${attribute_name}</p>
                    </div>
                    <div class="col-md-1 text-center attribute-arrow">
                        <i class="fa fa-arrow-right d-flex align-items-center justify-content-center"></i>
                    </div>
                    <div class="col-md-5">
                        <div class="values"></div>
                        <button type="button" class="btn btn-link new_attribute_value"><i class="fa fa-plus-circle"></i> Novo Valor</button>
                    </div>
                    <div class="col-md-3">
                        <div class="w-100 d-flex align-items-center attribute-btn-new-value">
                            <button type="button" class="btn btn-link ${show_btn_async}" data-toggle="modal" data-target="#publishAllProducts" data-attribute="${attributes}" data-saved="${data_saved}" ${disabled_btn_async}>${text_btn_async}</button>
                        </div>
                    </div>
                    <input type="hidden" name="attribute[]" value="${attributes}">
                </div>
            `);
        } else {
            $(`${nav_products_attribute}#list_attributes${suffix_input_name}`).append(`
                <div class="row mt-1 mb-3 attribute-content" data-attribute="${attributes}">
                    <div class="col-md-5 marketplace_attribute">
                        <p class="form-control d-flex align-items-center bg-gray">${attribute_name}</p>
                    </div>
                    <div class="col-md-1 text-center attribute-arrow">
                        <i class="fa fa-arrow-right d-flex align-items-center justify-content-center"></i>
                    </div>
                    <div class="col-md-6">
                        <div class="values"></div>
                        <button type="button" class="btn btn-link new_attribute_value"><i class="fa fa-plus-circle"></i> Novo Valor</button>
                    </div>
                    <input type="hidden" name="attribute[]" value="${attributes}">
                </div>
            `);
        }

        const attributeContent = $(`${nav_products_attribute}#list_attributes${suffix_input_name} .attribute-content input[name="attribute[]"][value="${attributes}"]`).closest('.attribute-content');
        if (attribute_seller_value.length) {
            $(attribute_seller_value).each(function(k, attribute){
                createInputsAttributes(attributeContent, attributes, attribute, suffix_input_name);
            })
        } else {
            if (trigger_new_input) {
                $(`${nav_products_attribute}#list_attributes${suffix_input_name} [data-attribute="${attributes}"] .new_attribute_value`).trigger('click');
                $(`${nav_products_attribute}.content_attributes`).prop("scrollTop", $('.content_attributes').prop("scrollHeight"));
            }
        }
    }

    const createInputsAttributes = (attributeContent, attribute, attribute_value = '', suffix_input_name = '') => {
        if (suffix_input_name) {
            if (!attribute_value_custom_in_use.includes(attribute.toString())) {
                attribute_value_custom_in_use.push(attribute.toString());
            }
        } else {
            if (!attribute_custom_in_use.includes(attribute.toString())) {
                attribute_custom_in_use.push(attribute.toString());
            }
        }

        attributeContent.find('.values').append(inputNewAttribute(attribute, attribute_value, suffix_input_name));

        adjustHeightAttributeContent(attributeContent);
        updateSelectAttributes(suffix_input_name);
    }

    $('#nav-products_attribute #filter_stores_attribute, #nav-products_attribute_value #filter_stores_attribute_attribute_value').on('change', function(){
        const select_store = $(this);

        const is_values = select_store.closest('#publishAllProducts').length === 1;
        const suffix_input_name = is_values ? '_attribute_value' : '';
        const nav_products_attribute = is_values ? '#nav-products_attribute_value ' : '#nav-products_attribute ';

        if (!is_values) {
            $('#publishAllProducts #filter_stores_attribute_attribute_value').val($('#nav-products_attribute #filter_stores_attribute').val()).selectpicker('refresh').trigger('change');
        }

        const store_id = $(this).val();
        const select_attribute_map = $(`${nav_products_attribute}#filter_categories${suffix_input_name}`);
        const select_marketplace = $(`${nav_products_attribute}#marketplace${suffix_input_name}`);
        const select_attributes = $(`${nav_products_attribute}#attributes${suffix_input_name}`);
        const btn_add_attribute = $(`${nav_products_attribute}#addAttributeMarketplace${suffix_input_name}`);
        const btn_add_all_required_attribute = $(`${nav_products_attribute}#add-required-attributes${suffix_input_name}`);
        const url_marketplaces_to_attribute_map = "<?=base_url('Products/getCategoriesByStoreProduct/')?>" + store_id;
        const url_marketplaces = "<?=base_url('Integrations/getIntegrationsByStore/')?>" + store_id;

        if (store_id == '') {
            return false;
        }

        if (!is_values) {
            select_attributes.val('').trigger('change.select2').attr('disabled', true);
            btn_add_attribute.attr('disabled', true);
            btn_add_all_required_attribute.attr('disabled', true);
            select_attribute_map.empty().attr('disabled', true).selectpicker('refresh');
            select_marketplace.empty().attr('disabled', true).selectpicker('refresh');
            select_store.attr('disabled', true).selectpicker('refresh');
        }

        $.get("<?=base_url('IntegrationAttributeMap/getAttributesCustomByStore/')?>" + store_id, response => {
            datalist_attribute_custom = '';
            attribute_custom = response;

            datalist_attribute_custom = '<input type="text" autocomplete="off" class="form-control col-md-10" name="replace_id_custom" list="replace_id_custom" value="attribute_value_replace"><datalist id="replace_id_custom">';
            $(response).each(function(k, value) {
                datalist_attribute_custom += `<option value='${value.name}'></option>`;
            });
            datalist_attribute_custom += '</datalist>';
        });

        $.get(url_marketplaces_to_attribute_map, response => {
            let selected = response.length === 1 ? 'selected' : '';

            $(response).each(function (k, value) {
                select_attribute_map.append(`<option value="${value.id}" ${selected}>${value.name}</option>`);
            });

            if (!is_values) {
                select_attribute_map.attr('disabled', false).selectpicker('refresh');
                select_store.attr('disabled', false).selectpicker('refresh');
            }
            select_attribute_map.trigger('change');

            if (!response.length) {
                AlertSweet.fire({
                    icon: 'warning',
                    title: 'Loja não possui produto com categoria.'
                });
            }
        });

        $.get(url_marketplaces, response => {
            let selected = response.length === 1 ? 'selected' : '';

            $(response).each(function (k, value) {
                select_marketplace.append(`<option value="${value.int_to}" ${selected}>${value.name}</option>`);
            });

            if (!is_values) {
                select_marketplace.attr('disabled', false).selectpicker('refresh');
                select_store.attr('disabled', false).selectpicker('refresh');
            }

            select_marketplace.trigger('change');

            if (!response.length) {
                AlertSweet.fire({
                    icon: 'warning',
                    title: 'Loja não possui integração com marketplace.'
                });
            }
        });
    });

    $('#nav-products_attribute #addAttributeMarketplace, #nav-products_attribute_value #addAttributeMarketplace_attribute_value').on('click', function(){

        const is_values = $(this).closest('#publishAllProducts').length === 1;
        const suffix_input_name = is_values ? '_attribute_value' : '';
        const nav_products_attribute = is_values ? '#nav-products_attribute_value ' : '#nav-products_attribute ';

        const category = $(`${nav_products_attribute}#filter_categories${suffix_input_name}`).val();
        const marketplace = $(`${nav_products_attribute}#marketplace${suffix_input_name}`).val();
        const attributes = $(`${nav_products_attribute}#attributes${suffix_input_name}`).val();
        const values_attribute = $(`${nav_products_attribute}#values${suffix_input_name}`).val();

        if (marketplace == '' || category == '' || attributes == '' || (is_values && values_attribute == '')) {
            AlertSweet.fire({
                icon: 'warning',
                title: 'Seleciona todos os campos para adicionar.'
            });
            return false;
        }

        if ($(`${nav_products_attribute}#list_attributes${suffix_input_name} input[name="attribute[]"][value="${attributes}"]`).length) {
            AlertSweet.fire({
                icon: 'warning',
                title: 'Atributo já existente. Na listagem abaixo, procure o atributo e clique em "Novo Valor" para adicionar um novo valor.'
            });
            return false;
        }

        createNewAttributeList(suffix_input_name ? values_attribute : attributes, [], suffix_input_name, true);
    });

    $('#btnSaveSellerAttributes, #btnSaveSellerAttributes_attribute_value').on('click', function(){
        const is_values = $(this).closest('#publishAllProducts').length === 1;
        const suffix_input_name = is_values ? '_attribute_value' : '';
        const nav_products_attribute = is_values ? '#publishAllProducts ' : '#nav-products_attribute ';

        const category = $(`${nav_products_attribute}#filter_categories${suffix_input_name}`).val();
        const marketplace = $(`${nav_products_attribute}#marketplace${suffix_input_name}`).val();
        const store = $(`${nav_products_attribute}#filter_stores_attribute${suffix_input_name}`).val();
        const attributes_values = $(`#formSellerAttributes${suffix_input_name}`).serializeArray();
        const attributes_keys = $(`#formSellerAttributes${suffix_input_name} [name="attribute[]"]`).map(function(idx, elem) {
            return $(elem).val();
        }).get();

        if (!is_values) {
            $(`${nav_products_attribute}input, ${nav_products_attribute}button:not([data-target="#publishAllProducts"]), ${nav_products_attribute}select`).attr('disabled', true);
        } else {
            $(`${nav_products_attribute}input, ${nav_products_attribute}button:not([data-target="#publishAllProducts"]), ${nav_products_attribute}#values_attribute_value`).attr('disabled', true);
        }

        const url_save_attributes = is_values ? "<?=base_url('IntegrationAttributeMap/saveSellerValuesAttribute')?>" : "<?=base_url('IntegrationAttributeMap/saveSellerAttributes')?>";
        let data = {
            category,
            marketplace,
            store,
            attributes_values,
            attributes_keys
        }

        if (is_values) {
            data['attribute'] = $(`#attributes_attribute_value`).val();
        }

        $.post(url_save_attributes, data, response => {
            $(`#formSellerAttributes${suffix_input_name} .attribute input[type="text"]`).each(function(idx, elem) {
                if ($(elem).val() == '') {
                    $(elem).css('border', '2px solid #d73925')
                } else {
                    $(elem).removeAttr('style');
                }
            });

            Swal.fire({
                icon: response.success ? 'success' : 'warning',
                html: response.message
            });

            if (response.success && !is_values) {
                $('#publishAllProducts #marketplace_attribute_value').trigger('change');
                setTimeout(() => {
                    $(`[data-target="#publishAllProducts"][data-saved="false"]`).attr('disabled', false).data('saved', true).html('<i class="fa fa-plus-circle"></i> Realizar vínculo de valores de atributo');
                }, 1000);
            }

            if (!is_values) {
                $(`${nav_products_attribute}input, ${nav_products_attribute}button:not([data-target="#publishAllProducts"]), ${nav_products_attribute}select`).attr('disabled', false);
            } else {
                $(`${nav_products_attribute}input, ${nav_products_attribute}button:not([data-target="#publishAllProducts"]), ${nav_products_attribute}#values_attribute_value`).attr('disabled', false);
            }
        });

    });

    $(` #nav-products_attribute #marketplace,
        #nav-products_attribute #filter_categories,
        #nav-products_attribute_value #marketplace_attribute_value,
        #nav-products_attribute_value #filter_categories_attribute_value`
    ).on('changed.bs.select', function() {

        const is_values = $(this).closest('#publishAllProducts').length === 1;
        const suffix_input_name = is_values ? '_attribute_value' : '';
        const nav_products_attribute = is_values ? '#nav-products_attribute_value ' : '#nav-products_attribute ';

        const select_category = $(`${nav_products_attribute}#filter_categories${suffix_input_name}`);
        const select_marketplace = $(`${nav_products_attribute}#marketplace${suffix_input_name}`);
        const select_store = $(`${nav_products_attribute}#filter_stores_attribute${suffix_input_name}`);
        const select_attributes = $(`${nav_products_attribute}#attributes${suffix_input_name}`);
        const btn_add_attribute = $(`${nav_products_attribute}#addAttributeMarketplace${suffix_input_name}`);
        const btn_add_all_required_attribute = $(`${nav_products_attribute}#add-required-attributes${suffix_input_name}`);
        const head_list_attributes = $(`${nav_products_attribute}#head_list_attributes${suffix_input_name}`);
        const list_attributes = $(`${nav_products_attribute}#list_attributes${suffix_input_name}`);
        const select_values_attributes = $(`${nav_products_attribute}#values${suffix_input_name}`);
        const category = select_category.val();
        const marketplace = select_marketplace.val();
        const store = select_store.val();
        const first_option_select = `<option value=""><?=$this->lang->line('application_select')?></option>`;
        let required_attribute = '';

        list_attributes.empty();
        head_list_attributes.hide();
        if (!is_values) {
            attribute_custom_in_use = [];
        }

        if (!is_values) {
            btn_add_attribute.attr('disabled', true);
            btn_add_all_required_attribute.attr('disabled', true);
        }

        if (!checkFiltersSelectedToAddAttribute(suffix_input_name)) {
            if (!is_values) {
                select_attributes.val('').trigger('change.select2').attr('disabled', true);
            }
            return false;
        }

        if (!is_values) {
            $('#publishAllProducts #marketplace_attribute_value').val($('#nav-products_attribute #marketplace').val()).selectpicker('refresh');
            $('#publishAllProducts #filter_categories_attribute_value').val($('#nav-products_attribute #filter_categories').val()).selectpicker('refresh').trigger('change');
        }

        select_attributes.select2("destroy");
        select_values_attributes.select2("destroy");

        if (!is_values) {
            select_attributes.attr('disabled', true);
            select_values_attributes.empty().append(first_option_select).attr('disabled', true);
            select_category.attr('disabled', true).selectpicker('refresh');
            select_marketplace.attr('disabled', true).selectpicker('refresh');
            select_store.attr('disabled', true).selectpicker('refresh');
        }

        let url_get_attributes = "<?=base_url('IntegrationAttributeMap/getAttributesCategoryMarketplace/')?>" + category + '/' + marketplace;
        let url_get_attributes_store = "<?=base_url('IntegrationAttributeMap/getAttributesStoreCategoryMarketplace/')?>" + store + '/' + category + '/' + marketplace;

        if (is_values) {
            url_get_attributes = url_get_attributes_store + '/1';
            url_get_attributes_store = false;
        }

        $.get(url_get_attributes, response => {
            let option_required = '';
            let option_value = '';
            let option_name = '';
            select_attributes.empty();
            select_attributes.append(first_option_select);
            $(response).each(function (k, value) {
                option_required = is_values ? 0 : value.obrigatorio;
                option_value    = is_values ? value.attribute_marketplace_id : `${value.id_atributo}_${value.id_categoria}_${value.int_to}_${value.tipo}`;
                option_name     = is_values ? value.attribute_seller_value : value.nome;
                required_attribute = parseInt(option_required) === 1 ? 'required-attribute' : '';

                select_attributes.append(`<option value="${option_value}" ${required_attribute}>${option_name}</option>`);
            });

            if (!is_values) {
                select_attributes.attr('disabled', false);
                btn_add_attribute.attr('disabled', false);
                btn_add_all_required_attribute.attr('disabled', false);
                select_category.attr('disabled', false).selectpicker('refresh');
                select_marketplace.attr('disabled', false).selectpicker('refresh');
                select_store.attr('disabled', false).selectpicker('refresh');
                select_values_attributes.empty().append(first_option_select).attr('disabled', false);
            }
            select_attributes.select2();
            select_values_attributes.select2();

            if (!response.length && !is_values) {
                AlertSweet.fire({
                    icon: 'warning',
                    title: 'A categoria do marketplace não possui atributos.'
                });
                return false;
            }

            if (!is_values) {
                head_list_attributes.show();
            }
            list_attributes.append('<div class="form-group col-md-12 text-center mt-5"><h4><?=$this->lang->line('application_loading')?></h4></div>');

            // Não precisa ler os atributos já realizados o de para, para a loja.
            if (url_get_attributes_store) {
                $.get(url_get_attributes_store, response => {
                    let attributes = [];
                    let key_attribute = '';
                    list_attributes.empty();

                    if (!response.length) {
                        head_list_attributes.hide();
                    }

                    $(response).each(function (k, value) {
                        key_attribute = `${value.attribute_marketplace_id}_${value.category_marketplace_id}_${value.int_to}_${value.tipo}`;
                        if (typeof attributes[key_attribute] === "undefined") {
                            attributes[key_attribute] = [];
                        }

                        attributes[key_attribute].push(value.attribute_seller_value);
                    });

                    for (let [attribute, attribute_seller_value] of Object.entries(attributes)) {
                        createNewAttributeList(attribute, attribute_seller_value);
                    }
                });
            }
        });
    });

    $('#nav-products_attribute_value #attributes_attribute_value').on('change', function() {
        const select_category = $(`#nav-products_attribute_value #filter_categories_attribute_value`);
        const select_marketplace = $(`#nav-products_attribute_value #marketplace_attribute_value`);
        const select_store = $(`#nav-products_attribute_value #filter_stores_attribute_attribute_value`);
        const select_attributes = $(`#nav-products_attribute_value #attributes_attribute_value`);
        const btn_add_attribute = $(`#nav-products_attribute_value #addAttributeMarketplace_attribute_value`);
        const head_list_attributes = $(`#nav-products_attribute_value #head_list_attributes_attribute_value`);
        const list_attributes = $(`#nav-products_attribute_value #list_attributes_attribute_value`);
        const select_values_attributes = $(`#nav-products_attribute_value #values_attribute_value`);
        const category = select_category.val();
        const marketplace = select_marketplace.val();
        const attribute = select_attributes.val();
        const first_option_select = `<option value=""><?=$this->lang->line('application_select')?></option>`;
        const btnSaveAsync = $('#btnSaveSellerAttributes_attribute_value');

        list_attributes.empty();
        head_list_attributes.hide();
        attribute_value_custom_in_use = [];

        select_values_attributes.val("").attr('disabled', true);
        btn_add_attribute.attr('disabled', true);
        btnSaveAsync.attr('disabled', true);

        if (!checkFiltersSelectedToAddAttribute('_attribute_value')) {
            return false;
        }

        head_list_attributes.show();
        list_attributes.append('<div class="form-group col-md-12 text-center mt-5"><h4><?=$this->lang->line('application_loading')?></h4></div>');
        //select_attributes.attr('disabled', true);
        //select_category.attr('disabled', true).selectpicker('refresh');
        //select_marketplace.attr('disabled', true).selectpicker('refresh');
        //select_store.attr('disabled', true).selectpicker('refresh');

        let url_get_values_attribute_seller = "<?=base_url('IntegrationAttributeMap/getValuesAttributeCategoryMarketplaceAttribute/')?>" + category + '/' + marketplace + '/' + attribute;
        let url_get_values_attribute_marketplace = "<?=base_url('IntegrationAttributeMap/getValuesAttributeSellerCategoryMarketplaceAttribute/')?>" + category + '/' + marketplace + '/' + attribute;

        $.get(url_get_values_attribute_seller, response => {
            select_values_attributes.empty();
            select_values_attributes.append(first_option_select);

            if (response && response.valor) {
                $(JSON.parse(response.valor)).each(function (k, value) {
                    // somente ativos
                    if (value.IsActive) {
                        select_values_attributes.append(`<option value="${value.FieldValueId}">${value.Value}</option>`);
                    }
                });
            }

            $.get(url_get_values_attribute_marketplace, response => {
                let attributes = [];
                let key_attribute = '';
                list_attributes.empty();

                select_values_attributes.attr('disabled', false);

                let values_in_use = [];
                $(response).each(function (k, value) {
                    key_attribute = `${value.attribute_value_marketplace_id}`;
                    values_in_use.push(key_attribute);
                    if (typeof attributes[key_attribute] === "undefined") {
                        attributes[key_attribute] = [];
                    }

                    attributes[key_attribute].push(value.attribute_value_seller_name);

                });

                for (let [attribute, attribute_seller_value] of Object.entries(attributes)) {
                    createNewAttributeList(attribute, attribute_seller_value, '_attribute_value');
                }

                setTimeout(() => {
                    $('#values_attribute_value option').each(function(){
                        if ($(this).val() !== '' && !values_in_use.includes($(this).val().toString())) {
                            createNewAttributeList($(this).val(), [], '_attribute_value', false, false);

                            if (!attribute_value_custom_in_use.includes($(this).val().toString())) {
                                attribute_value_custom_in_use.push($(this).val().toString());
                            }
                        }
                        updateSelectAttributes('_attribute_value');
                    });
                }, 250);


                if (!response.length && $('#values_attribute_value option').length === 1 && $('#values_attribute_value option').val() === '') {
                    AlertSweet.fire({
                        icon: 'warning',
                        title: 'O atributo não possui valores no marketplace.'
                    });
                } else {
                    btnSaveAsync.attr('disabled', false);
                }
                btn_add_attribute.attr('disabled', false);
                //select_attributes.attr('disabled', false);
                //select_category.attr('disabled', false).selectpicker('refresh');
                //select_marketplace.attr('disabled', false).selectpicker('refresh');
                //select_store.attr('disabled', false).selectpicker('refresh');
            });
        });
    });

    $(document).on('click', '#nav-products_attribute .del_attribute_value, #nav-products_attribute_value .del_attribute_value_attribute_value', async function () {
        const attributeContent = $(this).closest('.attribute-content');
        const attribute = attributeContent.data('attribute').toString();
        const is_values = $(this).closest('#publishAllProducts').length === 1;
        const suffix_input_name = is_values ? '_attribute_value' : '';
        const nav_products_attribute = is_values ? '#nav-products_attribute_value ' : '#nav-products_attribute ';

        $(this).closest('.attribute').remove();

        if (attributeContent.find('.values .attribute').length === 0) {
            if (!is_values) {
                attributeContent.remove();
                if (is_values) {
                    attribute_value_custom_in_use = attribute_value_custom_in_use.filter(item => item !== attribute);
                } else {
                    attribute_custom_in_use = attribute_custom_in_use.filter(item => item !== attribute);
                }
                updateSelectAttributes(suffix_input_name);
            }
        }

        if (!is_values) {
            if (!$(`${nav_products_attribute}#list_attributes${suffix_input_name} .attribute-content`).length) {
                $(`${nav_products_attribute}#head_list_attributes${suffix_input_name}`).hide();
            }
        }

        if (!is_values || attributeContent.find('.values .attribute').length !== 0) {
            adjustHeightAttributeContent(attributeContent);
        }
    });

    $(document).on('click', '#nav-products_attribute .new_attribute_value, #nav-products_attribute_value .new_attribute_value', function() {
        const attributeContent = $(this).closest('.attribute-content');
        const attribute = attributeContent.data('attribute');
        const is_values = $(this).closest('#publishAllProducts').length === 1;
        const suffix_input_name = is_values ? '_attribute_value' : '';
        const nav_products_attribute = is_values ? '#nav-products_attribute_value ' : '#nav-products_attribute ';
        let stop = false;

        $(`${nav_products_attribute}[name="${attribute}"]`).each(function(){
            if ($(this).val() == '') {
                stop = true;
                return false;
            }
        });

        if (stop) {
            AlertSweet.fire({
                icon: 'warning',
                title: 'Existem valores em branco, preencha antes de adicionar um novo.'
            });
            return false;
        }

        createInputsAttributes(attributeContent, attribute, '', suffix_input_name);
    });

    $(document).on('click', '#nav-products_attribute #add-required-attributes', function() {
        let attribute_not_found = true;

        if ($('#attributes option[required-attribute]').length === 0) {
            AlertSweet.fire({
                icon: 'warning',
                title: 'Não existem atributos obrigatórios.'
            });
            return false;
        }

        $('#attributes option[required-attribute]').each(function(){
            const attribute = $(this).val();
            if (!attribute_custom_in_use.includes(attribute)) {
                createNewAttributeList(attribute, [], '', true);
                attribute_not_found = false;
            }
        });

        if (!checkFiltersSelectedToAddAttribute()) {
            AlertSweet.fire({
                icon: 'warning',
                title: 'Seleciona a categoria e marketplace.'
            });
            return false;
        }

        if (attribute_not_found) {
            AlertSweet.fire({
                icon: 'warning',
                title: 'Todos os atributos obrigatórios já estão listados abaixo.'
            });
        }
    });
</script>