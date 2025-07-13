<div class="content-wrapper">
    <?php
    $data['pageinfo'] = "application_import_product_csv";
    $this->load->view('templates/content_header', $data);
    ?>
    <section class="content">
        <div class="box box-primary" id="validation">
            <div class="box-header">
                <div class="col-md-9 no-padding">
                    <h3 class="box-title"><?= $this->lang->line('application_validation_csv') ?></h3>
                </div>
                <div class="col-md-3 no-padding">
                    <?= "<span class='text-danger ' > <i class='fa fa-times-circle'></i> " . $this->lang->line('application_products_errors') . ": <strong>" . $qtd_line_erros_global . "</strong></span> " ?> &nbsp;&nbsp;
                    <?= "<span class='text-success'>  <i class='fa fa-check-circle'></i> " . $this->lang->line('application_products_complete') . ": <strong>" . (count($lines_verifications) - $qtd_line_erros_global) . "</strong></span>" ?>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        
                        <div class="col-md-12 no-padding">
                            <table class="table table-validate">
                                <thead>
                                    <tr>
                                        <th><?= $this->lang->line('application_line') ?></th>
                                        <th><?= $this->lang->line('application_sku') ?></th>
                                        <th><?= $this->lang->line('application_situation') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($lines_verifications as $row => $status) :
                                        $status['delete'] = $status['delete'] ?? false;
                                        $color = $status['qtdErros'] != 0 ? '#F8D7DA' : '#98f598';
                                        $color = $status['type'] == 'variant' ? '#c8f7c8' : $color;
                                        $color = $status['delete'] ? '#f5f073' : $color;
                                    ?>
                                        <tr style='color:#000;background-color: <?= $color ?>'>
                                            <td><?= ($row + 1) ?></td>
                                            <td>
                                                <?php
                                                foreach ($status['lines_msgs'] as $row_msg => $text_msg) :
                                                ?>
                                                    <ul>
                                                        <li>
                                                            <?= $text_msg ?>
                                                        </li>
                                                    </ul>
                                                <?php endforeach ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $descProd = $status['type'] == 'variant' ? $this->lang->line('application_variations') : $this->lang->line('application_products');
                                                    $descStatus = $status['qtdErros'] != 0 ? $this->lang->line('application_error') : $this->lang->line('application_ok');
                                                    $descStatus = $status['delete'] ? $this->lang->line('application_warning') : $descStatus;
                                                    echo "{$descProd} {$descStatus}";
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                    
                                    <?php if (isset($block)) { ?>
                                    <tr style='color:#000;background-color: rgb(255, 200, 0)'>
                                        <td> <i class="glyphicon glyphicon-alert"></i> </td>
                                        <td><ul><li><?php echo $this->lang->line('application_information')?></li></ul></td>
                                        <td><?php echo $block ?></td>
                                        </tr>
                                    <?php } ?>    

                                    <?php if (isset($excecao)) { ?>
                                    <tr style='color:#000;background-color: rgb(255, 200, 0)'>
                                        <td> <i class="glyphicon glyphicon-alert"></i> </td>
                                        <td><ul><li><?php echo $this->lang->line('application_information')?></li></ul></td>
                                        <td><?php echo $excecao ?></td>
                                        </tr>
                                    <?php } ?>    


                                </tbody>
                            </table>
                        </div>
                        <?php if ($qtd_line_erros_global + $line_brank <$total_line) : ?>
                            <div class="col-md-12">
                                <br>
                                <h3 class="text-success"><i class="fa fa-check-circle"></i>  <?=sprintf($this->lang->line('application_total_lines_errors'), $total_line)?></h3>
                                <p class="text-body"><?=$this->lang->line('application_click_button_import');?></p>
                            </div>
                            <div class="col-md-12 d-flex justify-content-between">
                                <form role="form" action="<?= base_url('ProductsLoadByCSV/import') ?>" method="post" enctype="multipart/form-data" class="no-padding" id="import-complets">
                                    <div class="container-fluid">
                                        <br><a href="<?= base_url('ProductsLoadByCSV') ?>" class="btn btn-default text-info"><i class="fa fa-times-circle"></i> <?= $this->lang->line('application_cancel_import'); ?></a>
                                        <button class="btn btn-success" name="import"><i class="fas fa-upload"></i> <?= $this->lang->line('application_import_complete_products'); ?></button>                                        
                                    </div>
                                    <input type="hidden" name="validate_file" value="<?= $upload_file ?>">
                                </form>
                            </div>
                        <?php else : ?>
                            <div class="col-md-12 d-flex justify-content-between">
                                <form role="form" action="<?= base_url('ProductsLoadByCSV/import') ?>" method="post" enctype="multipart/form-data" class="no-padding" id="import-complets">
                                    <br><a href="<?= base_url('ProductsLoadByCSV') ?>" class="btn btn-default text-info"><i class="fa fa-times-circle"></i> <?= $this->lang->line('application_cancel_import'); ?></a>
                                    <input type="hidden" name="validate_file" value="<?= $upload_file ?>">
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="row">
                        <div class="col-md-12 d-flex justify-content-between">
                        </div>
                    </div>
                </div>
                <div class="content-block-screen-import">
                    <h4 class="text-center"><?= $this->lang->line('messages_wait_import_products') ?></h4>
                </div>
            </div>
        </div>
    </section>
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

<?php
include_once APPPATH . 'views/products/components/popup.update.status.product.php';
?>

<script type="text/javascript">
    var manageTable;
    var base_url = "<?= base_url() ?>";

    var lines = <?= json_encode($lines_verifications) ?>;
    var deleteValidationTxt = '<?=$this->lang->line('application_confirm_txt_delete_products')?>';
    var labelConfirmationTxt = '<?=$this->lang->line('application_label_text_confirmation')?>';

    $('form#upload-file').on('submit', function() {
        $('.content-block-screen-upload').css('display', 'flex')
    });
    $('form#import-complets, form#import-complets-download-errors, form#imports-no-errors').on('submit', function() {
        $('.content-block-screen-import').css('display', 'flex')
    });

    function validateProductsDeletion() {
        var deleted = 0;
        $.each(lines, function (i, line) {
            if (line['delete']) {
                deleted++;
            }
        });
        if (deleted > 0) {
            $('#import-complets').append($('<input>', {name: 'confirmDeleteText', type: 'hidden'}));
            $('button[name="import"]').prop('disabled', true);
            var lbl = labelConfirmationTxt.replace('{text}', deleteValidationTxt);
            var div = $('<div>', {class: 'form-group col-md-12 col-xs-12 '});
            var label = $('<label>', {for: 'input_confirm_deletion'}).text(lbl);
            var input = $('<input>', {placeholder: deleteValidationTxt, class: 'form-control', type: 'text'});
            $(div).append(label);
            $(div).append(input);
            console.log(div);
            var modal = (new ChangeProductStatusModal({
                view: 'list',
                type: 'csv',
            }));

            $(input).on('keyup', function () {
                var deferredForm = $.Deferred();
                if ($(this).val().toLowerCase() == deleteValidationTxt.toLowerCase()) {
                    deferredForm.resolve({});
                } else {
                    deferredForm.reject({});
                }
                modal.setDeferredForm(deferredForm.promise());
            });

            modal.setCount(deleted);
            modal.setForm(div);
            modal.init().then(function (args) {
                $('button[name="import"]').prop('disabled', false);
                $('input[name="confirmDeleteText"]').val($(input).val());
            });
        }
    };

    $(document).ready(function() {

        validateProductsDeletion();

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

        $("#product_upload").fileinput({
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
            allowedFileExtensions: ["csv", "txt"]
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