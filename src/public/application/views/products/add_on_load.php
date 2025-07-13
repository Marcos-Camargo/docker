<div class="content-wrapper">
<?php
    $data['pageinfo'] = "application_import_product_csv";
    $this->load->view('templates/content_header',$data);
?>
  <section class="content">
    <div class="row">
        <div class="col-md-12 col-xs-12">
            <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <?=$this->session->flashdata('success')?>
            </div>
            <?php elseif($this->session->flashdata('error')): ?>
            <div class="alert alert-error alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <?=$this->session->flashdata('error')?>
            </div>
            <?php endif ?>
            <?php if($this->session->flashdata('warning')): ?>
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?=$this->session->flashdata('warning')?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 col-xs-12">
            <div class="box box-alert alert alert-warning ">
                <div class="box-body">
                    <?= $this->lang->line('application_import_rules_addon_sku') ?>
                    <center><button type="button" class="btn  btn-primary" data-toggle="collapse" data-target="#collapseRules" aria-expanded="false" aria-controls="collapseRules"><?=$this->lang->line('application_view_rulese')?></button></center>
                </div>
            </div>
        </div>
        <div class="col-md-9 col-xs-12">
        <div class="box box">
            <div class="box-body">
                <div class="col-md-3 col-xs-12">
                    <?= $this->lang->line('application_import_addon_sku_step_1') ?>
                    <hr>
                    <a download="sample_addon_sku.csv" href="<?=base_url('assets/files/sample_addon_sku.csv') ?>"><i class="fa fa-download"></i>  <?=$this->lang->line('application_sample_product_file2');?></a><br><br>
                </div>
                <div class="col-md-3 col-xs-12">
                    <?= $this->lang->line('application_import_product_step_2') ?>
                    <hr>
                    <?php if(in_array('createProduct', $user_permission)): ?>
                        <a href="<?=base_url('export/lojaxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_store_export')?></a><br><br>
                    <?php endif; ?>
                </div>
                <?php if(!isset($validate_finish)): ?>
                    <div class="col-md-6 col-xs-12">
                        <?= $this->lang->line('application_import_addon_sku_step_3') ?>
                        <hr>
                        <form role="form" action="<?php echo base_url('ProductsLoadByCSV/onlyVerifyAddOn') ?>" method="post" enctype="multipart/form-data" id="upload-file">
                            <div class="file-loading">
                                <input id="product_upload" name="product_upload" type="file" required>
                            </div>
                            <input type="hidden" value="0" name="typeImport" required>
                        </form>
                        <div class="content-block-screen-upload">
                            <h4><?=$this->lang->line('messages_wait_data_reading')?> <i class="fa fa-spin fa-spinner"></i></h4>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="collapse " id="collapseRules">
        <div class=" col-md-12 col-xs-12 "  >
            <div class="box box-defaut box-body alert alert-warning alert-dismissible">
                <hr>
                <h5 class="font-weight-bold"><?=$this->lang->line('messages_completed_fields_and_rules')?></h5>
                <table class="table table-striped table-warning table-bordered">
                    <thead>
                        <tr>
                            <th><?=$this->lang->line('application_field')?></th>
                            <th><?=$this->lang->line('application_rule')?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ID da Loja</td>
                            <td><?= $this->lang->line('messages_field_stores') ?> <a class="btn btn-warning font-weight-bold pt-0 pb-0 ml-3" href="<?=base_url('export/lojaxls') ?>"><?=$this->lang->line('application_store_export')?></a></td>
                        </tr>
                        <tr>
                            <td>Sku do Parceiro</td>
                            <td><?= $this->lang->line('application_required') ?></td>
                        </tr>
                        <tr>
                            <td>Sku do Add-On</td>
                            <td><?= $this->lang->line('application_required') ?></td>
                        </tr>
                    </tbody>
                </table>
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
                <h4 class="modal-title"><?=$this->lang->line('messages_example_update_products_import')?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 text-center form-group">
                        <h4 class="mb-3"><?=$this->lang->line('messages_example_update_products_import_modal')?></h4>
                    </div>
                    <div class="col-md-12 text-center">
                        <img width="400" src="<?=base_url('assets/images/system/example-import-update-product.png')?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table-warning{
        background: #f39c12;
    }
    .table-warning.table-striped>tbody>tr:nth-of-type(odd){
        background: #c87f0a;
    }
    .content-block-screen-upload,
    .content-block-screen-import {
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
    .dataTables_scrollBody{
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
        background: #0d0d0d;
    }
    .table-validate ul{
        margin-bottom: 0px;
        padding-left: 20px;
    }
    .content-scroll{
        width: 100%;
        position: absolute;
        top: 45px;
        background: rgba(0,0,0,.6);
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
        border-right: 1px solid rgba(255,255,255,.8);
        border-bottom: 1px solid rgba(255,255,255,.8);
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
        border: 1px solid rgba(255,255,255,.8);
        border-radius: 2em;
    }

    .icon-scroll .wheel {
        position: relative;
        display: block;
        height: 0.1875em;
        width: 0.1875em;
        margin: 0.1875em auto 0;
        background: rgba(255,255,255,.8);
        animation: mouse-wheel 1.2s ease infinite;
        border-radius: 50%;
    }

    .circulo {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        font-size: 20px;
        font-weight: bold;
        color: #fff;
        background: darkblue;
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

    .dataTables_scrollBody {
        height: auto;
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
        $('.table-validate').DataTable( {
            "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
            "processing": true,
            "responsive": false,
            "sortable": false,
            "paging": false,
            "scrollY": "400px",
            "scrollCollapse": true,
            "fixedHeader": true,
            "searching": false,
            "ordering": false,
            "bAutoHeight" : true,
            "initComplete": function( settings, json ) {
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

        $("#product_upload").fileinput({
            overwriteInitial: true,
            maxFileSize: 100000,
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
            // defaultPreviewContent: '<img src="/uploads/default_avatar_male.jpg" alt="Your Avatar">',
            layoutTemplates: {main2: '{preview} {remove} {browse}'},
            allowedFileExtensions: ["csv", "txt"]
        });

        if ($('.file-input.file-input-ajax-new').length) {
          $('.file-input.file-input-ajax-new').append(`
              <div class="col-md-4 col-xs-12 pull-right no-padding">
                <button type="submit" class="btn btn-success col-md-12" name="import" ><?=$this->lang->line('application_validate_file');?></button>
              </div>
          `);
          $('.btn-file').addClass('col-md-4');
          $('.inputSubmit').addClass('d-none');
        }

    });
</script>