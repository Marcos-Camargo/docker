<div class="content-wrapper">
    <?php
    $data['pageinfo'] = "application_import_categories_comission_rules";
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
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 col-xs-12">
                <div class="box box-alert alert alert-warning ">
                    <div class="box-body">
                        <?= $this->lang->line('application_import_categories_comission_rules_1') ?>
                    </div>
                </div>
            </div>
            <div class="col-md-9 col-xs-12">
                <div class="box box">
                    <div class="box-body">
                        <div class="col-md-3 col-xs-12">
                            <?= $this->lang->line('application_import_categories_comission_step_1') ?>
                            <hr>
                            <a download="sample_change_product_category.csv" href="<?=base_url('stores/downloadComissionsCategory') ?>"><i class="fa fa-download"></i>  <?=$this->lang->line('application_sample_categories_comission_rules');?></a><br><br>
                        </div>
                        <div class="col-md-9 col-xs-12">
                            <?= $this->lang->line('application_import_categories_comission_step_2') ?>
                            <hr>
                            <form role="form" action="<?php echo base_url('Category/onlyVerify') ?>" method="post" enctype="multipart/form-data" id="upload-file">
                                <div class="file-loading">
                                    <input id="product_upload" name="product_upload" type="file" required>
                                </div>
                                <input type="hidden" value="0" name="typeImport" required>
                            </form>
                            <div class="content-block-screen-upload">
                                <h4><?=$this->lang->line('messages_wait_data_reading')?> <i class="fa fa-spin fa-spinner"></i></h4>
                            </div>
                        </div>
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
                                    <td><?= $this->lang->line('application_old_code') ?></td>
                                    <td><?= $this->lang->line('application_required') ?></td>
                                </tr>
                                <tr>
                                    <td><?= $this->lang->line('application_new_code') ?></td>
                                    <td><?= $this->lang->line('application_required') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </section>
</div>

<style>
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
</style>

<script type="text/javascript">
    var manageTable;
    var base_url = "<?=base_url()?>";

    $(document).ready(function() {
        $("#mainCategoryNav").addClass('active');
        $("#changeProductCategoryNav").addClass('active');

        $("#product_upload").fileinput({
            maxFileSize: 100000,
            showCaption: false,
            browseLabel: '',
            removeLabel: '',
            language:'pt-BR',
            browseIcon: '<?=$this->lang->line('application_select')?> &nbsp;&nbsp;&nbsp;<i class="glyphicon glyphicon-folder-open"></i>',
            removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
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