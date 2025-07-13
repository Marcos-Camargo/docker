<?php
    $data['pageinfo'] = "application_import";
    $this->load->view('templates/content_header',$data);
?>
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
        <div class="row">
          <div class="col-md-12 col-xs-12">
            <?php if(in_array('createProduct', $user_permission)): ?>
            <div class="box box-primary">
              <div class="box-body">
                <div class="row">
                  <div id="showActions" class="col-md-12">
                    <a class="pull-right btn btn-primary col-md-2" href="<?=base_url('export/categoriesxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_categories_export')?></a>
                    <span class ="pull-right">&nbsp</span>
                    <a class="pull-right btn btn-primary col-md-2" href="<?=base_url('export/fabricantesxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_brands_export')?></a>
                    <span class ="pull-right">&nbsp</span>
                    <a class="pull-right btn btn-primary col-md-3" href="<?=base_url('export/origemxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_origem_export')?></a>
                    <span class ="pull-right">&nbsp</span>
                    <a class="pull-right btn btn-primary col-md-2" href="<?=base_url('export/lojaxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_store_export')?></a>
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>

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

            <?php if(!isset($validate_finish)): ?>
            <div class="box box-primary">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="callout callout-warning">
                                <h3 class="no-margin font-weight-bold"><?=$this->lang->line('application_warning')?>!</h3>
                                <h4><?=$this->lang->line('messages_read_import_rules_carefully')?> <button type="button" class="btn btn-warning ml-3" data-toggle="collapse" data-target="#collapseRules" aria-expanded="false" aria-controls="collapseRules"><?=$this->lang->line('application_view_rulese')?></button></h4>
                                <div class="collapse" id="collapseRules">
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
                                                <td>Obrigatório caso o usuário gerencie mais que uma loja <a class="btn btn-warning font-weight-bold pt-0 pb-0 ml-3" href="<?=base_url('export/lojaxls') ?>"><?=$this->lang->line('application_store_export')?></a></td>
                                            </tr>
                                            <tr>
                                                <td>Sku do Parceiro</td>
                                                <td>Obrigatório</td>
                                            </tr>
                                            <tr>
                                                <td>Nome do Item</td>
                                                <td>Obrigatório</td>
                                            </tr>
                                            <tr>
                                                <td>Preco de Venda</td>
                                                <td>Obrigatório - Maior que 0(zero)</td>
                                            </tr>
                                            <tr>
                                                <td>Quantidade em estoque</td>
                                                <td>Opcional - Maior ou igual a zero, não informado será definido como 0(zero)</td>
                                            </tr>
                                            <tr>
                                                <td>Fabricante</td>
                                                <td>Obrigatório <a class="btn btn-warning font-weight-bold pt-0 pb-0 ml-3" href="<?=base_url('export/fabricantesxls') ?>"><?=$this->lang->line('application_brands_export')?></a></td>
                                            </tr>
                                            <tr>
                                                <td>SKU no fabricante</td>
                                                <td>Opcional</td>
                                            </tr>
                                            <tr>
                                                <td>Categoria</td>
                                                <td>Obrigatório <a class="btn btn-warning font-weight-bold pt-0 pb-0 ml-3" href="<?=base_url('export/categoriesxls') ?>"><?=$this->lang->line('application_categories_export')?></a></td>
                                            </tr>
                                            <tr>
                                                <td>EAN</td>
                                                <td>Opcional - Se informado será validado para verificar a existência</td>
                                            </tr>
                                            <tr>
                                                <td>Peso líquido em kgs</td>
                                                <td>Obrigatório - Maior que 0(zero)</td>
                                            </tr>
                                            <tr>
                                                <td>Peso Bruto em kgs</td>
                                                <td>Obrigatório - Maior que 0(zero)</td>
                                            </tr>
                                            <tr>
                                                <td>Largura em cm</td>
                                                <td>Obrigatório - Maior que 11(onze)</td>
                                            </tr>
                                            <tr>
                                                <td>Altura em cm</td>
                                                <td>Obrigatório - Maior que 2(dois)</td>
                                            </tr>
                                            <tr>
                                                <td>Profundidade em cm</td>
                                                <td>Obrigatório - Maior que 16(dezesseis)</td>
                                            </tr>
                                            <tr>
                                                <td>NCM</td>
                                                <td>Opcional - Se informado será validado para a existência de 8(oito) números</td>
                                            </tr>
                                            <tr>
                                                <td>Origem do Produto _ Nacional ou Estrangeiro</td>
                                                <td>Obrigatório - Baixe as origens de produtos <a class="btn btn-warning font-weight-bold pt-0 pb-0 ml-3" href="<?=base_url('export/origemxls') ?>"><?=$this->lang->line('application_origem_export')?></a></td>
                                            </tr>
                                            <tr>
                                                <td>Garantia em meses</td>
                                                <td>Opcional - Não informado será definido como 0(zero)</td>
                                            </tr>
                                            <tr>
                                                <td>Prazo Operacional em dias</td>
                                                <td>Opcional - Não informado será definido como 0(zero)</td>
                                            </tr>
                                            <tr>
                                                <td>Produtos por embalagem</td>
                                                <td>Opcional - Não informado será definido como 1(um)</td>
                                            </tr>
                                            <tr>
                                                <td>Descricao do Item _ Informacoes do Produto</td>
                                                <td>Obrigatório - Tags permitidas: p, br, h1, h2, h3, h4, h5, h6, strong, b, em, i, u, small, ul, ol, li</td>
                                            </tr>
                                            <tr>
                                                <td>Imagens</td>
                                                <td>Opcional - Máximo 4(quatro) imagens para novos produtos - URLs de imagens devem ser separadas por vírgula( <strong>,</strong> )</td>
                                            </tr>
                                            <tr>
                                                <td>Status(1=Ativo|2=Inativo|3=Lixeira)</td>
                                                <td>Obrigatório - 1=Ativo - 2=Inativo - 3=Lixeira</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-center">Processamento de no máximo 200 produtos por carga, mais que isso poderá levar o próprio navegador a cancelar a operaçao.</h4>
                                    <h4 class="text-center"><?=$this->lang->line('messages_import_products_rules_1')?> <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#example-import-update-product"><?=$this->lang->line('application_view_example')?></button></h4>
                                    <h4 class="text-center"><?=$this->lang->line('messages_import_products_rules_2')?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="kv-avatar form-group col-md-8">
                                <form role="form" action="<?php base_url('products/load') ?>" method="post" enctype="multipart/form-data" id="upload-file">
                                    <label for="product_upload"><?=$this->lang->line('messages_upload_file');?></label>
                                    <div class="file-loading">
                                        <input id="product_upload" name="product_upload" type="file" required>
                                    </div>
                                    <input type="hidden" value="0" name="typeImport" required>
                                </form>
                                <div class="content-block-screen-upload">
                                    <h4><?=$this->lang->line('messages_wait_data_reading')?> <i class="fa fa-spin fa-spinner"></i></h4>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="product_upload">&nbsp;</label>
                                <a download="sample_products.csv" href="<?=base_url('assets/files/sample_products.csv') ?>" class="btn btn-primary col-md-12" style="white-space: normal;"><i class="fa fa-download"></i> <br> <?=$this->lang->line('application_sample_product_file');?></a>
                                <a download="sample_products_stock.csv" href="<?=base_url('assets/files/sample_products_stock.csv') ?>" class="btn btn-primary col-md-12 mt-2" style="white-space: normal;"><i class="fa fa-download"></i> <br> <?=$this->lang->line('application_sample_product_file_stock_price');?></a>
                            </div>
                        </div>
                    </div>
                    <div class="row inputSubmit">
                        <div class="form-group col-md-12">
                            <div class="col-md-4 pull-right">
                                <button type="submit" class="btn btn-success col-md-12" name="import" ><?=$this->lang->line('application_validate_file');?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif ?>
            <?php if(isset($validate_finish)):
                $existError = false;
            ?>
            <div class="box box-primary" id="validation">
              <div class="box-header">
                  <h3 class="box-title"><?=$tipo_importacao == 0 ? $this->lang->line('application_validation') : $this->lang->line('application_importation')?></h3>
              </div>
              <div class="box-body">
                  <div class="row">
                      <div class="col-md-12">
                          <div class="col-md-12 no-padding">
                              <?=$tipo_importacao == 0 ? "<h4 class='text-danger'>".$this->lang->line('application_products_errors').": <strong>{$qty_errors}</strong></h4>" : ''?>
                              <?=$tipo_importacao == 0 ? "<h4 class='text-success'>".$this->lang->line('application_products_complete').": <strong>".(count($validate_finish) - $qty_errors)."</strong></h4>" : ''?>
                          </div>
                          <div class="col-md-12 no-padding">
                              <?php if($tipo_importacao == 2): ?>
                                  <h4 class="text-center"><?=$this->lang->line('messages_import_successfully_download_file_errors')?></h4>
                              <?php endif;?>
                              <?php if (count($validate_finish) || ($tipo_importacao == 0 || $tipo_importacao == 2)): ?>
                              <table class="table table-validate">
                                  <thead>
                                      <tr>
                                          <th><?=$this->lang->line('application_line')?></th>
                                          <th><?=$this->lang->line('application_sku')?></th>
                                          <th><?=$this->lang->line('application_situation')?></th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                    <?php
                                    $countNewLineProdErrors = 1;
                                    foreach ($validate_finish as $row => $status):
                                        $colorBg = empty($status) ? '#39ab39' : '#b52e2e';
                                        if (empty($status) && $tipo_importacao == 0 && $validate_finish_prd_ext[$row]) $colorBg = '#4040a3';

                                        if ($tipo_importacao == 2 && $colorBg != '#b52e2e') continue; // mostrar apenas linhas com erro para exportar
                                        elseif($tipo_importacao == 2) $row = ++$countNewLineProdErrors;

                                        if ($validate_finish_var_ext[$row] && $colorBg != '#b52e2e') $colorBg = '#4040a3';

                                        if (!empty($status) && $tipo_importacao == 0) $existError = true; // definir apenas se for validação

                                        echo "<tr style='color:#fff;background-color: {$colorBg}'><td>{$row}</td><td>{$validate_finish_skus[$row]}</td>";

                                        if(empty($status) && $tipo_importacao == 0 && $validate_finish_prd_ext[$row] && !$validate_finish_var_ext[$row]) {
                                            if ($colorBg == '#4040a3' && $validate_finish_inte_ext[$row])
                                                echo "<td><ul><li>{$this->lang->line('messages_import_already_exists_complet_integrate')}</li></ul></td>";
                                            else
                                                echo "<td><ul><li>{$this->lang->line('messages_import_already_exists_complet')}</li></ul></td>";
                                        }

                                        elseif(empty($status) && $tipo_importacao == 0 && !$validate_finish_prd_ext[$row] && $validate_finish_var_ext[$row])
                                            echo "<td><ul><li>".$this->lang->line('messages_import_already_exists_complet_var')."</li></ul></td>";

                                        elseif(empty($status)) echo "<td><ul><li>{$this->lang->line('messages_import_new_product_complet')}</li></ul></td>";

                                        else echo "<td><ul><li>".implode('</li><li>', $status)."</li></ul></td>";

                                        echo "</tr>";
                                    endforeach;
                                    ?>
                                  </tbody>
                              </table>
                              <div class="content-scroll">
                                  <div class="icon-scroll">
                                      <div class="mouse">
                                          <div class="wheel"></div>
                                      </div>
                                      <div class="icon-arrows">
                                          <span></span>
                                      </div>
                                  </div>
                              </div>
                              <?php else: ?>
                                <h4 class="text-center"><?=$tipo_importacao == 2 ? $this->lang->line('messages_import_successfully_download_file_errors') : $this->lang->line('messages_import_successfully_completed')?></h4>
                              <?php endif?>
                          </div>
                      </div>
                  </div>
              </div>
              <div class="box-footer">
                  <div class="row">
                      <div class="col-md-12 d-flex justify-content-between">
                          <?php if ($existError && $qty_errors < count($validate_finish)): ?>
                          <form role="form" action="<?php base_url('products/load') ?>" method="post" enctype="multipart/form-data" class="col-md-3 no-padding" id="import-complets">
                              <button class="btn btn-success col-md-12" name="import"><i class="fas fa-upload"></i> <?=$this->lang->line('application_import_complete_products_onlys');?></button>
                              <input type="hidden" value="1" name="typeImport" required>
                              <input type="hidden" name="validate_file" value="<?=$upload_file?>">
                          </form>
                          <form role="form" action="<?php base_url('products/load') ?>" method="post" enctype="multipart/form-data" class="col-md-4 no-padding" id="import-complets-download-errors">
                              <button class="btn btn-warning col-md-12" name="import"><i class="fas fa-upload"></i> <?=$this->lang->line('application_import_products_download_errors');?></button>
                              <input type="hidden" value="2" name="typeImport" required>
                              <input type="hidden" name="validate_file" value="<?=$upload_file?>">
                          </form>
                          <?php elseif(!$existError && $tipo_importacao == 0): ?>
                              <form role="form" action="<?php base_url('products/load') ?>" method="post" enctype="multipart/form-data" class="col-md-3 no-padding" id="imports-no-errors">
                                  <button class="btn btn-success col-md-12" name="import"><i class="fas fa-upload"></i> <?=$this->lang->line('application_import_products');?></button>
                                  <input type="hidden" value="1" name="typeImport" required>
                                  <input type="hidden" name="validate_file" value="<?=$upload_file?>">
                              </form>
                          <?php endif ?>
                          <?php if ($tipo_importacao == 0): ?>
                              <a href="<?=base_url('products/load')?>" class="btn btn-danger col-md-3"><i class="fas fa-power-off"></i> <?=$this->lang->line('application_cancel_import');?></a>
                          <?php elseif ($tipo_importacao == 2): ?>
                              <form role="form" action="<?php base_url('products/load') ?>" method="post" enctype="multipart/form-data" class="col-md-3 no-padding">
                                  <button class="btn btn-warning col-md-12 animate__animated animate__shakeX" name="import"><i class="fas fa-download"></i> <?=$this->lang->line('application_download_products_with_errors');?></button>
                                  <input type="hidden" value="3" name="typeImport" required>
                                  <input type="hidden" name="validate_file" value="<?=$upload_file?>">
                              </form>
                              <a href="<?=base_url('products/load')?>" class="btn btn-success col-md-3"><i class="fas fa-power-off"></i> <?=$this->lang->line('application_finish');?></a>
                          <?php else: ?>
                              <a href="<?=base_url('products/load')?>" class="btn btn-success col-md-3"><i class="fas fa-power-off"></i> <?=$this->lang->line('application_finish');?></a>
                          <?php endif ?>
                      </div>
                  </div>
              </div>
              <div class="content-block-screen-import">
                <h4 class="text-center"><?=$this->lang->line('messages_wait_import_products')?></h4>
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
        background: #0066CC;
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