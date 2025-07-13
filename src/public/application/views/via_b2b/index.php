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
    $data['pageinfo'] = "application_importation";
    $data['page_now'] = "load_products_via_b2b";
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
                <br/>
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <?php $flagClass = ''; ?>
                        <?php foreach ($integrations ?? [] as $k => $integration): ?>
                            <?php $flagClass = $integration['link']; ?>
                            <li <?= $k == 0 ? "class=\"active {$flagClass}\"" : "class=\"{$flagClass}\"" ?>>
                                <a href="#nav-<?= $integration['link'] ?>"
                                   data-toggle="tab"><?= $integration['title'] ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="tab-content col-md-12">
                        <?php foreach ($integrations ?? [] as $k => $integration): ?>
                            <?php $flagClass = $integration['link']; ?>
                            <div class="tab-pane <?= $k == 0 ? 'active' : '' ?>" id="nav-<?= $integration['link'] ?>">
                                <div class="row" style="margin-left: 0px; margin-right: 0px">
                                    <h3 class="mt-0 <?= $flagClass ?>"><?= "<b>{$integration['store_name']} - {$integration['title']}</b>" ?></h3>
                                    <h4 class="mt-0 <?= $flagClass ?>"><?= $this->lang->line('application_load_files_via_b2b_title') ?></h4>
                                    <div class="alert alert-warning alert-dismissible" role="alert">
                                        <button type="button" class="close" data-dismiss="alert"
                                                aria-label="Close"><span
                                                    aria-hidden="true">&times;</span></button>
                                        <?= $this->lang->line('application_load_files_via_b2b_warning') ?>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <?php foreach ($integration['download_file_types'] ?? [] as $d => $document): ?>
                                                <div class="panel panel-primary <?= $flagClass ?>">
                                                    <div class="panel-heading">
                                                        <?= $document['title'] ?>
                                                    </div>
                                                    <div class="panel-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <button
                                                                        id="btn_import_<?= $integration['flag'] ?>_<?= $d ?>"
                                                                        flag="<?= $integration['flag'] ?>"
                                                                        type_name="<?= $d ?>"
                                                                        store_id="<?= $integration['store_id'] ?>"
                                                                        company_id="<?= $integration['company_id'] ?>"
                                                                        class="btn btn-success col-md-4"
                                                                        style="float: right"
                                                                >
                                                                    <span class="glyphicon glyphicon-import"
                                                                          aria-hidden="true"></span>
                                                                    <i class="fa fa-circle-o-notch fa-spin loading-icon"
                                                                       style="display: none;"></i>
                                                                    <span class="btn-import-txt"><?= $this->lang->line('application_import') ?></span>
                                                                    <span class="btn-loading-txt" style="display: none;"><?= $this->lang->line('application_loading') ?></span>
                                                                </button>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <button id="download_<?= "{$integration['flag']}_{$d}" ?>"
                                                                        class="btn btn-default col-md-4"
                                                                        disabled>
                                                                <span class="glyphicon glyphicon-download-alt"
                                                                      aria-hidden="true"></span>
                                                                    <?= $this->lang->line('application_download_file') ?>
                                                                </button>
                                                                <a role="button" href="#" style="display: none;"></a>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="alert alert-dismissible"
                                                                     role="alert"
                                                                     style="margin-bottom: 0px; margin-top: 10px; display: none;">
                                                                    <button type="button" class="close"
                                                                            data-dismiss="alert"
                                                                            aria-label="Close"><span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                    <span id="msg_<?= "{$integration['flag']}_{$d}" ?>"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row imp-info"
                                                             id="impInfo_<?= "{$integration['flag']}_{$d}" ?>"
                                                            <?= !isset($document['lastImportationDate']) ? 'style="display: none;"' : '' ?>
                                                        >
                                                            <div class="col-md-3">
                                                                <div class="panel panel-default">
                                                                    <div class="panel-heading">Última Importação</div>
                                                                    <div class="panel-body" id="lastImportationDate_<?= "{$integration['flag']}_{$d}" ?>">
                                                                        <?= $document['lastImportationDate'] ?? ''?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="panel panel-default">
                                                                    <div class="panel-heading">Data dos Arquivos</div>
                                                                    <div class="panel-body" id="filesCreationDate_<?= "{$integration['flag']}_{$d}" ?>">
                                                                        <?= $document['filesCreationDate'] ?? ''?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="panel panel-default">
                                                                    <div class="panel-heading">Total de Arquivos</div>
                                                                    <div class="panel-body" id="totalFiles_<?= "{$integration['flag']}_{$d}" ?>">
                                                                        <?= $document['totalFiles'] ?? ''?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="panel panel-default">
                                                                    <div class="panel-heading">Em Processamento...
                                                                        <i
                                                                                class="fa fa-circle-o-notch fa-spin loading-icon"
                                                                                style="float: right; margin-top:3px; <?=$document['pendingFiles'] > 0 ? '': 'display:none;'?>"
                                                                        >
                                                                        </i>
                                                                    </div>
                                                                    <div class="panel-body" id="pendingFiles_<?= "{$integration['flag']}_{$d}" ?>">
                                                                        <?= $document['pendingFiles'] ?? ''?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="row <?= $flagClass ?>" style="margin-left: 0px; margin-right: 0px;">
                                    <h4 class="mt-0 <?= $flagClass ?>"><?= $this->lang->line('application_file_upload_via_b2b_title') ?></h4>
                                    <div class="alert alert-warning alert-dismissible" role="alert">
                                        <button type="button" class="close" data-dismiss="alert"
                                                aria-label="Close"><span
                                                    aria-hidden="true">&times;</span></button>
                                        <?= $this->lang->line('application_file_upload_via_b2b_warning') ?>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">

                                        </div>
                                        <div class="col-md-12">
                                            <div class="kv-avatar form-group col-md-12">
                                                <form role="form"
                                                      action="<?php echo base_url("LoadProductsB2BVia/uploadFile/{$integration['flag']}") ?>"
                                                      method="post" enctype="multipart/form-data"
                                                      id="upload-file-<?= $integration['link'] ?>">
                                                    <label class="<?= $flagClass ?>"
                                                           for="file_upload"><?= $this->lang->line('application_upload_file_label'); ?>
                                                        :</label>
                                                    <div class="file-loading">
                                                        <input id="file_upload_<?= $integration['link'] ?>"
                                                               name="file_upload" type="file"
                                                               required>
                                                    </div>
                                                    <input type="hidden" value="0" name="typeImport" required>
                                                    <input type="hidden" value="<?= $integration['company_id'] ?>"
                                                           name="company_id" required>
                                                    <input type="hidden" value="<?= $integration['store_id'] ?>"
                                                           name="store_id" required>
                                                </form>
                                                <div class="content-block-screen-upload">
                                                    <h4><?= $this->lang->line('messages_wait_data_reading') ?> <i
                                                                class="fa fa-spin fa-spinner"></i></h4>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-4">

                                            </div>
                                        </div>
                                    </div>
                                    <div class="row inputSubmit">
                                        <div class="form-group col-md-12">
                                            <div class="col-md-4 pull-right">
                                                <button type="submit" class="btn btn-success col-md-12"
                                                        name="import"><?= $this->lang->line('application_upload_file_action_batch'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    </br>
                </div>
            </div>
    </section>
</div>
<style>

    .nav-tabs-custom > .nav-tabs > li.casasbahia > a {
        color: #337ab7 !important;
    }

    .nav-tabs-custom > .nav-tabs > li.active.casasbahia {
        border-top-color: #337ab7 !important;
    }

    label.casasbahia,
    h3.casasbahia,
    h4.casasbahia {
        color: #337ab7 !important;
    }

    .casasbahia div.btn-file,
    div.panel.casasbahia .panel-heading {
        background-color: #337ab7 !important;
        border-color: #337ab7 !important;
    }

    div.panel.casasbahia {
        border-color: #337ab7 !important;
    }

    .nav-tabs-custom > .nav-tabs > li.extra > a {
        color: #f1636c !important;
    }

    .nav-tabs-custom > .nav-tabs > li.active.extra {
        border-top-color: #f1636c !important;
    }

    label.extra,
    h3.extra,
    h4.extra {
        color: #f1636c !important;
    }

    .extra div.btn-file,
    div.panel.extra .panel-heading {
        background-color: #f1636c !important;
        border-color: #f1636c !important;
    }

    div.panel.extra {
        border-color: #f1636c !important;
    }

    .nav-tabs-custom > .nav-tabs > li.active.pontofrio {
        border-top-color: #3c3c3c !important;
    }

    .nav-tabs-custom > .nav-tabs > li.pontofrio > a {
        color: #3c3c3c !important;
    }

    label.pontofrio,
    h3.pontofrio,
    h4.pontofrio {
        color: #3c3c3c !important;
    }

    .pontofrio div.btn-file,
    div.panel.pontofrio .panel-heading {
        background-color: #3c3c3c !important;
        border-color: #3c3c3c !important;
    }

    div.panel.pontofrio {
        border-color: #3c3c3c !important;
    }

    div.imp-info {
        padding-top: 10px;
    }

    div.imp-info div.panel-default div.panel-heading {
        background-color: #f4f4f4 !important;
        border-color: #ddd !important;
    }

    div.imp-info div.panel-default div.panel-body {
        text-align: center;
        font-weight: bold;
        font-size: 18px;
    }

    .casasbahia div.imp-info div.panel-default div.panel-body {
        color: #337ab7 !important;
    }

    .extra div.imp-info div.panel-default div.panel-body {
        color: #f1636c !important;
    }

    .pontofrio div.imp-info div.panel-default div.panel-body {
        color: #3c3c3c !important;
    }

    .content-block-screen-upload {
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

</style>

<script type="text/javascript">
    var manageTable;
    var base_url = "<?=base_url()?>";

    $(document).ready(function () {
        $('form[id^="upload-file"]').on('submit', function () {
            $('.content-block-screen-upload').css('display', 'flex')
        });
        $('form#import-complets, form#import-complets-download-errors, form#imports-no-errors').on('submit', function () {
            $('.content-block-screen-import').css('display', 'flex')
        });

        $('[id^="file_upload"]').fileinput({
            overwriteInitial: true,
            maxFileSize: 10000000,
            showClose: false,
            showCaption: false,
            browseLabel: '',
            removeLabel: '',
            language: 'pt-BR',
            browseIcon: '<?=$this->lang->line('application_select')?> &nbsp;&nbsp;&nbsp;<i class="glyphicon glyphicon-folder-open"></i>',
            removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
            removeTitle: 'Cancel or reset changes',
            elErrorContainer: '#kv-avatar-errors-1',
            msgErrorClass: 'alert alert-block alert-danger',
            layoutTemplates: {main2: '{preview} {remove} {browse}'},
            allowedFileTypes: null,
            allowedFileExtensions: ['zip'],
            allowedPreviewTypes: null,
            allowedPreviewExtensions: ['zip'],
            disabledPreviewExtensions: null
        });

        if ($('.file-input.file-input-ajax-new').length) {
            $('.file-input.file-input-ajax-new').append(`
              <div class="col-md-4 col-xs-12 pull-right no-padding">
                <button type="submit" class="btn btn-success col-md-12" name="import" ><?=$this->lang->line('application_upload_file_action_batch');?></button>
              </div>
          `);
            $('.btn-file').addClass('col-md-4');
            $('.inputSubmit').addClass('d-none');
        }

        $('button[id^="btn_import_"]').each(function (i, btn) {
            $(btn).prop('disabled', true).children('.glyphicon').hide();
            $(btn).children('.loading-icon').show();
            $(btn).children('span.btn-import-txt').hide();
            $(btn).children('span.btn-loading-txt').show();
            var flag = $(btn).attr('flag');
            var typeName = $(btn).attr('type_name');
            var storeId = $(btn).attr('store_id');
            var companyId = $(btn).attr('company_id');
            var url = base_url + "LoadProductsB2BVia/getLinkFileDownload/" + flag + "/" + typeName + "/" + storeId + "/" + companyId + "?t=" + new Date().getTime();
            $.ajax({
                type: "GET",
                url: url,
                datatype: "json",
                contentType: "application/json"
            }).done(function (data) {
                var response = JSON.parse(data);
                if (response.hasOwnProperty('file_url')) {
                    $('#download_' + flag + '_' + typeName).prop('disabled', false).parent().children('a').attr('href', response.file_url);
                    $('#download_' + flag + '_' + typeName).unbind('click').bind('click', function () {
                        $(this).parent().children('a')[0].click();
                    })
                }
            }.bind(btn)).always(function () {
                $(btn).children('.loading-icon').hide();
                $(btn).prop('disabled', false).children('.glyphicon').show();
                $(btn).children('span.btn-import-txt').show();
                $(btn).children('span.btn-loading-txt').hide();
            }.bind(btn));
        });

        $('button[id^="btn_import_"]').unbind('click').bind('click', function (e) {
            e.preventDefault();
            var elm = e.target;
            if($(e.target).prop("tagName").toLowerCase() == 'span') {
                elm = $(e.target).parent();
            }
            runImportation(e, elm);
            return;
        });
    });

    function runImportation(e, btn) {
        e.preventDefault();
        var flag = $(btn).attr('flag');
        var typeName = $(btn).attr('type_name');
        var storeId = $(btn).attr('store_id');
        var companyId = $(btn).attr('company_id');
        $(btn).prop('disabled', true).children('.glyphicon').hide();
        $(btn).children('.loading-icon').show();
        $('#msg_' + flag + '_' + typeName).parent().hide();
        var url = base_url + "LoadProductsB2BVia/runImportation/" + flag + "/" + typeName + "?t=" + new Date().getTime();
        $.ajax({
            beforeSend: function (request) {
                request.setRequestHeader("Access-Control-Allow-Origin", '*');
                request.setRequestHeader("Access-Control-Allow-Methods", 'POST, GET, PUT, OPTIONS');
                request.setRequestHeader("Connection", 'keep-alive');
            },
            type: "POST",
            url: url,
            data: JSON.stringify({store_id: storeId, company_id: companyId}),
            datatype: "json",
            contentType: "application/json",
            cache: false,
            timeout: 60000000,
            async: true
        }).done(function (data) {
            var response = JSON.parse(data);
            var classAlert = 'alert-success';
            var messages = response.hasOwnProperty('messages') ? response.messages : [];
            if (response.hasOwnProperty('errors')) {
                classAlert = 'alert-error';
                messages = response.errors ?? [];
            }
            $('#msg_' + flag + '_' + typeName).html($('<ul>').append(
                messages.map(msg => $('<li>').append('<a>').html(msg.msg))
            ));
            $('#msg_' + flag + '_' + typeName).parent().removeClass('alert-success alert-error')
            $('#msg_' + flag + '_' + typeName).parent().addClass(classAlert).show();
            if (response.hasOwnProperty('file_url')) {
                $('#download_' + flag + '_' + typeName).prop('disabled', false).parent().children('a').attr('href', response.file_url);
                $('#download_' + flag + '_' + typeName).unbind('click').bind('click', function () {
                    $(this).parent().children('a')[0].click();
                });
            }
            if (response.hasOwnProperty('info')) {
                var info = response.info;
                $('#lastImportationDate_' + flag + '_' + typeName).html(info.lastImportationDate);
                $('#filesCreationDate_' + flag + '_' + typeName).html(info.filesCreationDate);
                $('#totalFiles_' + flag + '_' + typeName).html(info.totalFiles);
                $('#pendingFiles_' + flag + '_' + typeName).html(info.pendingFiles);
                $('#impInfo_' + flag + '_' + typeName).show();
            }
        }.bind(btn)).always(function () {
            $(btn).children('.loading-icon').hide();
            $(btn).prop('disabled', false).children('.glyphicon').show();
        }.bind(btn));
        return true;
    }
</script>