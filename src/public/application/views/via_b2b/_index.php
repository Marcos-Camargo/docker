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
                <div class="box box-primary">
                    <div class="box-header">
                    </div>
                    <div class="box-body">
                        <h4 class="mt-0"><?= $this->lang->line('application_file_upload_via_b2b_title') ?></h4>
                        <div class="alert alert-warning alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <?= $this->lang->line('application_file_upload_via_b2b_warning') ?>
                        </div>
                        <div class="row">
                            <div class="col-md-12">

                            </div>
                            <div class="col-md-12">
                                <div class="kv-avatar form-group col-md-12">
                                    <form role="form" action="<?php echo base_url('LoadProductsB2BVia/uploadFile') ?>"
                                          method="post" enctype="multipart/form-data" id="upload-file">
                                        <label for="file_upload"><?= $this->lang->line('application_upload_file_label'); ?>:</label>
                                        <div class="file-loading">
                                            <input id="file_upload" name="file_upload" type="file" required>
                                        </div>
                                        <input type="hidden" value="0" name="typeImport" required>
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
                </br>
            </div>
        </div>
    </section>
</div>
<style>
    .table-warning.table-striped>tbody>tr:nth-of-type(odd){
        background: #c87f0a;
    }
    .content-block-screen-upload {
        width: 100%;
        position: absolute;
        top: 0;
        left: 0;
        height: 105%;
        background: rgba(0,0,0,.6);
        display: none;
        justify-content: center;
        align-items: center;
        margin-top: -1%;
        color: #fff;
        font-weight: bold;
        border-radius: 5px;
    }

    .table-validate ul{
        margin-bottom: 0px;
        padding-left: 20px;
    }

    .icon-arrows span {
        display: block;
        width: 0.315em;
        height: 0.315em;
        border-right: 1px solid rgba(255,255,255,.8);
        border-bottom: 1px solid rgba(255,255,255,.8);
        margin: 0 0 0.125em 0.315em;
        transform: rotate(45deg);
        animation: mouse-scroll 1s infinite;
        animation-direction: alternate;
    }

    .icon-scroll span {
        animation-delay: .2s;
    }

    .icon-scroll {
        height: 1.375em;
        width: .875em;
        border: 1px solid rgba(255,255,255,.8);
        border-radius: 2em;
    }

    .icon-scroll {
        position: relative;
        display: block;
        height: 0.1875em;
        width: 0.1875em;
        margin: 0.1875em auto 0;
        background: rgba(255,255,255,.8);
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
        0%   { opacity: 0; }
        50%  { opacity: .5; }
        100% { opacity: 1; }
    }
</style>

<script type="text/javascript">
    var manageTable;
    var base_url = "<?=base_url()?>";
    $('form#upload-file').on('submit', function (){
        $('.content-block-screen-upload').css('display', 'flex')
    });
    $('form#import-complets, form#import-complets-download-errors, form#imports-no-errors').on('submit', function (){
        $('.content-block-screen-import').css('display', 'flex')
    });

    $(document).ready(function() {
        $("#file_upload").fileinput({
            overwriteInitial: true,
            maxFileSize: 10000000,
            showClose: false,
            showCaption: false,
            browseLabel: '',
            removeLabel: '',
            language:'pt-BR',
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
    });
</script>