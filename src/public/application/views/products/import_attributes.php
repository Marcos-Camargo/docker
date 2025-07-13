<?php
$data['pageinfo'] = "application_import_attributes";
$this->load->view('templates/content_header', $data);
?>
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 col-xs-12">
                    <?php if ($this->session->flashdata('success')) : ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?= $this->session->flashdata('success') ?>
                        </div>
                    <?php elseif ($this->session->flashdata('error')) : ?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?= $this->session->flashdata('error') ?>
                        </div>
                    <?php endif ?>

                    <?php if (!isset($validate_finish)) : ?>
                        <div class="box box-primary">
                            <div class="box-body">
                                <div class="alert alert-warning alert-dismissible" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <?= $this->lang->line('messages_worning_xls') ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-12" style="display: none">
                                        <div class="callout callout-warning">
                                            <h3 class="no-margin font-weight-bold"><?= $this->lang->line('application_warning') ?>!</h3>
                                            <h4><?= $this->lang->line('messages_read_import_rules_carefully') ?> <button type="button" class="btn btn-warning ml-3" data-toggle="collapse" data-target="#collapseRules" aria-expanded="false" aria-controls="collapseRules"><?= $this->lang->line('application_view_rulese') ?></button></h4>
                                            <div class="collapse" id="collapseRules">
                                                <hr>
                                                <h5 class="font-weight-bold"><?= $this->lang->line('messages_completed_fields_and_rules') ?></h5>
                                                <table class="table table-striped table-warning table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th><?= $this->lang->line('application_field') ?></th>
                                                            <th><?= $this->lang->line('application_rule') ?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>ID da loja</td>
                                                            <td><?= $this->lang->line('messages_field_stores') ?> <a class="btn btn-warning font-weight-bold pt-0 pb-0 ml-3" href="<?= base_url('export/lojaxls') ?>"><?= $this->lang->line('application_store_export') ?></a></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Fase da loja</td>
                                                            <td><?= $this->lang->line('application_required') ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Responsavel</td>
                                                            <td><?= $this->lang->line('application_required') ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Meta</td>
                                                            <td><?= $this->lang->line('application_required') ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="category"><?= $this->lang->line('application_categories'); ?>(*)</label>
                                        <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="category" name="category[]" title="<?= $this->lang->line('application_select'); ?>">
                                            <option value=""><?= $this->lang->line('application_select'); ?></option>
                                            <?php foreach ($category as $k => $v) { ?>
                                                <option value="<?php echo $v['id'] ?>"><?php echo $v['name'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="kv-avatar form-group col-md-8">
                                            <form role="form" action="<?php echo base_url('products/upload_attributes_file') ?>" method="post" enctype="multipart/form-data" id="upload-file">
                                                <label for="phase_upload"><?= $this->lang->line('messages_upload_file'); ?></label>
                                                <div class="file-loading">
                                                    <input id="phase_upload" name="phase_upload" type="file" required>
                                                </div>
                                                <input type="hidden" value="0" name="typeImport" required>
                                            </form>
                                            <div class="content-block-screen-upload">
                                                <h4><?= $this->lang->line('messages_wait_data_reading') ?> <i class="fa fa-spin fa-spinner"></i></h4>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="product_upload">&nbsp;</label>
                                            <a id="download" class="btn btn-primary col-md-12" style="white-space: normal;"><i class="fa fa-download"></i> <br> <?= $this->lang->line('application_download_attributes'); ?></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="row inputSubmit">
                                    <div class="form-group col-md-12">
                                        <div class="col-md-4 pull-right">
                                            <button type="submit" class="btn btn-success col-md-12" name="import"><?= $this->lang->line('application_validate_file'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="example-import-update-product">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?= $this->lang->line('messages_example_update_products_import') ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 text-center form-group">
                        <h4 class="mb-3"><?= $this->lang->line('messages_example_update_products_import_modal') ?></h4>
                    </div>
                    <div class="col-md-12 text-center">
                        <img width="400" src="<?= base_url('assets/images/system/example-import-update-product.png') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table-warning {
        background: #f39c12;
    }

    .table-warning.table-striped>tbody>tr:nth-of-type(odd) {
        background: #c87f0a;
    }

    .content-block-screen-upload,
    .content-block-screen-import {
        width: 100%;
        position: absolute;
        top: 0;
        left: 0;
        height: 105%;
        background: rgba(0, 0, 0, .6);
        display: none;
        justify-content: center;
        align-items: center;
        margin-top: -1%;
        color: #fff;
        font-weight: bold;
        border-radius: 5px;
    }

    .dataTables_scrollBody {
        height: 400px;
        overflow: scroll
    }

    .dataTables_scrollBody::-webkit-scrollbar-track {
        background-color: #F4F4F4;
    }

    .dataTables_scrollBody::-webkit-scrollbar {
        width: 13px;
        background: #F4F4F4;
    }

    .dataTables_scrollBody::-webkit-scrollbar-thumb {
        background: #0066CC;
    }

    .table-validate ul {
        margin-bottom: 0px;
        padding-left: 20px;
    }

    .content-scroll {
        width: 100%;
        position: absolute;
        top: 45px;
        background: rgba(0, 0, 0, .6);
        display: none;
    }

    .icon-scroll {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 1em;
        height: 3.125em;
        transform: translateX(-50%) scale(2);
        z-index: 99999;
    }

    .icon-arrows::after,
    .icon-arrows::before {
        content: '';
    }

    .icon-arrows span,
    .icon-arrows::after,
    .icon-arrows::before {
        display: block;
        width: 0.315em;
        height: 0.315em;
        border-right: 1px solid rgba(255, 255, 255, .8);
        border-bottom: 1px solid rgba(255, 255, 255, .8);
        margin: 0 0 0.125em 0.315em;
        transform: rotate(45deg);
        animation: mouse-scroll 1s infinite;
        animation-direction: alternate;
    }

    .icon-arrows::before {
        margin-top: 0.315em;
        animation-delay: .1s;
    }

    .icon-scroll span {
        animation-delay: .2s;
    }

    .icon-arrows::after {
        animation-delay: .3s;
    }

    .icon-scroll .mouse {
        height: 1.375em;
        width: .875em;
        border: 1px solid rgba(255, 255, 255, .8);
        border-radius: 2em;
    }

    .icon-scroll .wheel {
        position: relative;
        display: block;
        height: 0.1875em;
        width: 0.1875em;
        margin: 0.1875em auto 0;
        background: rgba(255, 255, 255, .8);
        animation: mouse-wheel 1.2s ease infinite;
        border-radius: 50%;
    }

    @keyframes mouse-wheel {
        0% {
            opacity: 1;
            transform: translateY(0);
        }

        100% {
            opacity: 0;
            transform: translateY(.375em);
        }
    }

    @keyframes mouse-scroll {
        0% {
            opacity: 0;
        }

        50% {
            opacity: .5;
        }

        100% {
            opacity: 1;
        }
    }

    .dataTables_scrollBody {
        height: auto;
    }
</style>

<script type="text/javascript">
    var manageTable;
    var base_url = "<?= base_url() ?>";
    $('form#upload-file').on('submit', function() {
        $('.content-block-screen-upload').css('display', 'flex')
    });
    $('form#import-complets, form#import-complets-download-errors, form#imports-no-errors').on('submit', function() {
        $('.content-block-screen-import').css('display', 'flex')
    });

    $(document).ready(function() {
        $('#download').attr('disabled', true);
        $('.table-validate').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?= ucfirst($this->input->cookie('swlanguage')) ?>.lang'
            },
            "processing": true,
            "responsive": false,
            "sortable": false,
            "paging": false,
            "scrollY": "400px",
            "scrollCollapse": true,
            "fixedHeader": true,
            "searching": false,
            "ordering": false,
            "bAutoHeight": true,
            "initComplete": function(settings, json) {
                if ($('.dataTables_scrollBody').height() == 400) {
                    $('.content-scroll').height($('.dataTables_scrollBody').height()).show();
                }
            }
        });
        $('.content-scroll').on('mousewheel', function(event) {
            $('.content-scroll').fadeOut(500);
            $([document.documentElement, document.body]).animate({
                scrollTop: $("#validation").offset().top
            }, 1000);
        });

        $('#category').on('change', function(event) {
            if (this.value == '') {
                $('#download').attr('disabled', true);
                $('#download').attr('href', '');
            }
            else {
                $('#download').attr('disabled', false);
                $('#download').attr('href', "<?php echo base_url('products/generateCsvAttribute').'/' ?>"+ this.value);
            }
        })

        $("#phase_upload").fileinput({
            overwriteInitial: true,
            maxFileSize: 100000,
            showClose: false,
            showCaption: false,
            browseLabel: '',
            removeLabel: '',
            language: 'pt-BR',
            browseIcon: '<?= $this->lang->line('application_select') ?> &nbsp;&nbsp;&nbsp;<i class="glyphicon glyphicon-folder-open"></i>',
            removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
            removeTitle: 'Cancel or reset changes',
            elErrorContainer: '#kv-avatar-errors-1',
            msgErrorClass: 'alert alert-block alert-danger',
            // defaultPreviewContent: '<img src="/uploads/default_avatar_male.jpg" alt="Your Avatar">',
            layoutTemplates: {
                main2: '{preview} {remove} {browse}'
            },
            allowedFileExtensions: ["xlsx"]
        });

        if ($('.file-input.file-input-ajax-new').length) {
            $('.file-input.file-input-ajax-new').append(`
              <div class="col-md-4 col-xs-12 pull-right no-padding">
                <button type="submit" class="btn btn-success col-md-12" name="import" ><?= $this->lang->line('application_validate_file'); ?></button>
              </div>
          `);
            $('.btn-file').addClass('col-md-4');
            $('.inputSubmit').addClass('d-none');
        }

    });
</script>