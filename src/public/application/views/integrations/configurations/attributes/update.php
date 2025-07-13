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
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header">
                            <h1>
                                <?= sprintf(lang('application_edit_attribute_mapping'), "<b>{$attr['name']}</b>") ?>
                            </h1>
                        </div>
                        <div class="box-body">
                            <div class="col-md-12">
                                <div class="row">
                                    <button class='btn btn-primary btn-add-value-attribute'
                                            data-toggle='tooltip'
                                            data-placement='top'
                                            data-attribute-id='<?= $attr['id'] ?>'
                                            data-store-id='<?= $attr['store_id'] ?>'
                                            data-company-id='<?= $attr['company_id'] ?>'
                                            data-attribute-name='<?= $attr['name'] ?>'
                                            title='<?= $this->lang->line('application_add_attribute_map') ?>'
                                    >
                                        <i class='fa fa-plus'></i>&nbsp;&nbsp;<?= $this->lang->line('application_add_attribute_map') ?>
                                    </button>
                                </div>
                                <div class="row" style="margin-top: 15px">
                                    <table id="manageTable"
                                           class="table table-striped table-hover display table-condensed"
                                           cellspacing="0" style="border-collapse: collapse; width: 99%;">
                                        <thead>
                                        <tr>
                                            <th><?= $this->lang->line('application_name'); ?></th>
                                            <th style="min-width: 320px"><?= $this->lang->line('application_actions'); ?></th>
                                        </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="row" style="margin-top: 15px">
                                    <button class='btn btn-default btn-back'><?= lang('application_back') ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php
include_once APPPATH . 'views/integrations/configurations/attributes/components/popup.add.variation.map.php';
?>
<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
    var manageTable = null;
    var baseUrl = "<?php echo base_url(); ?>";

    function getBaseUrl() {
        return baseUrl;
    };

    function getFullEndpoint() {
        return getBaseUrl() + 'integrations/configuration/attributes';
    };

    $(document).ready(function () {
        $("#mainIntegrationApiNav").addClass('active');
        $("#integrationAttributes").addClass('active');

        personalizedSearch();

        $('.btn-back').on('click', function () {
            window.location.href = getFullEndpoint();
        });
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
        $('.btn-add-value-attribute').on('click', function () {
            addUpdateAttrMapBtn($(this));
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
                $('.btn-del-attribute').off('click').on('click', function () {
                    deleteBtn($(this));
                });
                $('.btn-edit-attribute').off('click').on('click', function () {
                    addUpdateAttrMapBtn($(this));
                });
            },
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "serverMethod": "POST",
            "ajax": $.fn.dataTable.pipeline({
                url: getFullEndpoint() + '/fetchAttributeMappedValues/' + '<?=$attr['id']?>',
                data: getFilters(),
                pages: 2, // number of pages to cache
            }),
            'columns': [
                {data: 'value'},
                {data: 'actions'}
            ]
        });
    }

    function addUpdateAttrMapBtn(el) {
        var attrTitle = '<?=lang('application_add_attribute_map') . ": "?>' + $(el).data('attribute-name');
        var attrId = $(el).data('attribute-id');
        var mappedId = $(el).data('mapped-id') ?? '';
        var storeId = $(el).data('store-id');
        var companyId = $(el).data('company-id');

        var modal = (new AddUpdateVariationMapModal({popupTitle: attrTitle, data: $(el).data()}));

        modal.init().then(function (args) {
            var url = getFullEndpoint().concat('/addUpdateAttrMap/').concat(mappedId);
            var formData = {
                'attributeId': attrId,
                'storeId': storeId,
                'companyId': companyId
            };
            $.extend(formData, modal.getFormValues());
            return $.post(url, {
                data: JSON.stringify(formData),
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
    }

    function deleteBtn(el) {
        var mappedId = $(el).data('mapped-id') ?? '';
        var url = getFullEndpoint().concat('/deleteAttrMap')
            .concat('/' + mappedId);
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
    }

    function getFilters() {
        var filters = {};
        return filters;
    }

    function clearFilters() {
        $('#filter_stores').val('');
        $('#filter_stores').selectpicker('val', '');

        personalizedSearch();
    }

</script>