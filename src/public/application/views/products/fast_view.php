<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
<style>

    @import url('https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css');

    .sortable {
        padding: 0;
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }

    .sortable li {
        float: left;
        width: 120px;
        overflow: hidden;
        text-align: center;
        margin: 5px;
    }

    li.sortable-placeholder {
        border: 1px dashed #CCC;
        background: none;
    }

    .img-thumbnail {
        max-width: 100px;
        width: 100px !important;
        height: 100px !important;
    }

    .dropzone {
        border: none !important;
    }

    div#preview {
        margin-top: -120px;
        padding: 10px;
    }

    .select2-category-error{
        border: 1px solid #dd4b39!important;
        display: flex!important;
        height: 37px;
        border-radius: 5px;
    }
    .description-error{
        border: 1px solid #dd4b39!important;
        display: grid;
        border-radius: 5px;
        height: 365px;
    }
    .spance-1 {
        height: 85px !important;
    }
    .spance-2 {
        margin-bottom: 0;
    }
    .note-editable {
        height: 300px!important;
    }
    #file-dropzone{
        height: 226px;
        font-size: 12px;
    }
    .dz-default.dz-message{
        border: 2px dotted;
        padding: 14px;
        margin-top: -4px!important;
    }
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }
    input:checked + .slider {
        background-color: #04AA6D;
    }
    input:focus + .slider {
        box-shadow: 0 0 1px #04AA6D;
    }
    input:checked + .slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }
    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }
    .slider.round:before {
        border-radius: 50%;
    }
    #editor-container {
        height: 295px;
    }
    .row.ml-0.justify-content-center.align-items-center {
        float: left;
        display: inline-flex;
        padding: 3px;
        margin-top: -150px;
    }
    img.img-fluid.mt-3.rounded {
        width: 100%;
        max-width: 100px;
    }
    .photo {
        padding: 0px;
        padding: 15px;
    }
    .swal2-popup.swal2-modal.swal2-icon-warning.swal2-show {
        width: max-content;
    }
    .icon-color{
        padding-left: 5px;
        color: #dd4b39!important;
    }
    .swal2-actions {
        justify-content: end;
        flex-direction: row-reverse;
    }

</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/dropzone.css"/>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>

                <?php if ($this->session->flashdata('success')): ?>
                    <br>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <br>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>
                <br>
                <div class="alert alert-error alert-dismissible" id="msgError" role="alert" style="display: none;"></div>
                <div class="alert alert-success alert-dismissible" id="msgSuccess" role="alert" style="display: none;"></div>

                <?php

                $total = $products['totalOfProducts'];
                $pagination = $products['pagination'];
                $p = $products;
//                foreach ($products as $k => $p) {
//                if ($pagination == $k) {

                ?>
                <div class="row">
                    <div class="col-sm-12 row">
                        <div class="col-sm-3" style="max-width: 205px;">
                            <h1 style="font-size: 21px;margin-top: 5px;">Visualização rápida</h1>
                        </div>
                        <div class="col-sm-4 row" style="width: auto;">
                            <div style="width: 100%; max-width:400px;margin-bottom: 1em; display: flex">
                                <form action="<?php echo base_url('Products/viewFast') ?>" method="GET" id="formPrev">
                                    <input type="hidden" name="prev" id="prev" value="<?= $pagination ?>">
                                    <input type="hidden" name="listview[]" id="listviewPrev">
                                    <button onclick="verifymodify(event,this);" data-identify="prev" class="btn btn-primary" <?= $pagination == 0 ? 'disabled' : '' ?>><i
                                            class="fa fa-arrow-left">&nbsp;</i> Produto Anterior
                                    </button>

                                </form>
                                &nbsp; <span class="" style="font-size: 18px;margin: 6px 12px;"><b><?= $pagination + 1 . ' de ' . $total ?></b></span>
                                <form action="<?php echo base_url('Products/viewFast') ?>" method="GET" id="formNext">
                                    <input type="hidden" name="next" id="next" value="<?= $pagination + 1 ?>">
                                    <input type="hidden" name="listview[]" id="listviewNext">
                                    <button onclick="verifymodify(event,this);" data-identify="next"  class="btn btn-primary" <?= $pagination + 1 == $total ? 'disabled' : '' ?>>
                                        Próximo Produto &nbsp;<i class="fa fa-arrow-right"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <form action="<?php echo base_url('ProductsFastEdition/save') ?>" method="post" id="formSave">
                        <input class="hidden" name="id" value="<?= $p['id'] ?>"/>
                        <input class="hidden" name="submitAndNext" value="false"/>
                        <div class="col-md-6">
                            <div class="box box-default">
                                <div class="box-body" style="padding-bottom: 45px;" id="mydiv">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <div class="form-group spance-2" style="margin-top: 25px;">
                                                <label for="exampleInputEmail1">Nome do produto</label>
                                                <input type="text" name="name" value="<?= $p['name'] ?>"
                                                       class="form-control" id=""
                                                       placeholder="" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <div class="form-group spance-2">
                                                <label for="sku">SKU do Fabricante</label>
                                                <input type="text" name="sku" value="<?= $p['sku'] ?>"
                                                       class="form-control" id="sku"
                                                       placeholder="" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if($setting_validate_completed_sku_marketplace && empty($products['has_variants'])): ?>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <div class="form-group spance-2">
                                                <label for="sku"><?= $this->lang->line('application_sku_marketplace'); ?></label>
                                                <input type="number" name="sku_mkt_prd" value="<?=$skus_seller_to_marketplace[$p['sku']] ?>" class="form-control" id="sku_mkt_prd" placeholder="" <?=$product_is_publised ? 'disabled' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-sm-6">
                                        <span>Disponibilidade</span><br>
                                        <label class="switch">
                                            <input type="checkbox"
                                                   name="status" <?= $p['status'] == 1 ? 'checked' : '' ?>  onclick="return false;" >
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="imageNull">
                                <div class="box box-default" style="height: 230px;overflow-x: auto;">
                                    <input type="hidden" id="listimages" value="<?= $p['image'] ?>">
                                    <div class="dropzone" drop-zone="" id="file-dropzone"></div>
                                    <ul id="list" class="visualizacao sortable dropzone-previews"
                                        style=";margin-top: -140px;">
                                    </ul>
                                    <div class="preview">
                                        <li>
                                            <div>
                                                <div class="dz-preview dz-file-preview">
                                                    <img data-dz-thumbnail/>
                                                </div>
                                            </div>
                                        </li>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($variations)): ?>
                        <div class="col-sm-12">
                            <div class="box box-default">
                                <div class="box-body">
                                    <div class="col-md-12 row">
                                        <div class="form-group col-sm-12">
                                            <h3 class="box-title text-black">Variações</h3>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <div id="Lnvar" class="col-md-1 ml-1 pd-no-left" style="width:7%;">
                                                    <label>Nº</label>
                                                </div>
                                                <?php if (in_array('VOLTAGEM', explode(';', $products['has_variants']))): ?>
                                                    <div id="Ltvar" class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                        <label><?= $this->lang->line('application_size'); ?></label>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (in_array('Cor', explode(';', $products['has_variants']))): ?>
                                                    <div id="Lcvar" class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                        <label><?= $this->lang->line('application_color'); ?></label>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (in_array('TAMANHO', explode(';', $products['has_variants']))): ?>
                                                    <div id="Lvvar" class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                        <label><?= $this->lang->line('application_voltage'); ?></label>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (in_array('SABOR', explode(';', $products['has_variants']))): ?>
                                                    <div id="Lsvar" class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                        <label><?= $this->lang->line('application_flavor'); ?></label>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (in_array('GRAU', explode(';', $products['has_variants']))): ?>
                                                    <div id="Lgvar" class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                        <label><?= $this->lang->line('application_degree'); ?></label>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (in_array('LADO', explode(';', $products['has_variants']))): ?>
                                                    <div id="Llvar" class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                        <label><?= $this->lang->line('application_side'); ?></label>
                                                    </div>
                                                <?php endif; ?>
                                                <div id="Lqvar" class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                    <label><?= $this->lang->line('application_stock'); ?></label>
                                                </div>
                                                <div id="Lskuvar" class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                    <label><?= $this->lang->line('application_sku'); ?></label>
                                                </div>
                                                <div id="Leanvar" class="col-md-2 pd-person" style="width:14%;">
                                                    <label><?= $this->lang->line('application_ean'); ?></label>
                                                </div>
                                                <div id="Lpricevar" class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                    <label><?= $this->lang->line('application_list_price'); ?></label>
                                                </div>
                                                <div id="Lpricevar" class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                    <label><?= $this->lang->line('application_new_price'); ?></label>
                                                </div>
                                                <?php if($setting_validate_completed_sku_marketplace): ?>
                                                    <div class="col-md-1 ml-1 pd-person" style="width:7%;">
                                                        <label style="white-space: nowrap"><?= $this->lang->line('application_sku_marketplace'); ?></label>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php foreach ($variations as $variation): ?>
                                        <div class="col-sm-12">
                                            <div class="form-group">

                                                <div class="col-md-1 pd-no-left" style="width:7%;">
                                                    <span class="form-control label label-success"><?=$variation['variant']?></span>
                                                </div>
                                                <?php foreach (explode(';', $variation['name']) as $value_variation): ?>
                                                    <div class="col-md-1 pd-no-left" style="width:7%;">
                                                        <input class="form-control" value="<?=$value_variation?>" disabled>
                                                    </div>
                                                <?php endforeach; ?>
                                                <div class="col-md-1 pd-no-left ml-2" style="width:7%;">
                                                    <input class="form-control" value="<?=$variation['qty']?>" disabled>
                                                </div>
                                                <div class="col-md-1 pd-no-left ml-2" style="width:7%;">
                                                    <input class="form-control" value="<?=$variation['sku']?>" disabled>
                                                </div>
                                                <div class="col-md-1 pd-no-left ml-2" style="width:14%;">
                                                    <input class="form-control" value="<?=$variation['EAN']?>" disabled>
                                                </div>
                                                <div class="col-md-1 pd-no-left ml-2" style="width:7%;">
                                                    <input class="form-control" value="<?=$variation['list_price']?>" disabled>
                                                </div>
                                                <div class="col-md-1 pd-no-left ml-2" style="width:7%;">
                                                    <input class="form-control" value="<?=$variation['price']?>" disabled>
                                                </div>
                                                <?php if($setting_validate_completed_sku_marketplace): ?>
                                                    <div class="col-md-1 pd-no-left ml-2" style="width:7%;">
                                                        <input type="number" class="form-control" name="sku_mkt_var[]" value="<?=$skus_seller_to_marketplace[$variation['sku']] ?? ''?>" <?=$product_is_publised ? 'disabled' : '' ?>>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-sm-12">
                            <div class="box box-default">
                                <div class="box-body">
                                    <div class="col-md-12 row">
                                        <div class="form-group col-sm-12">
                                            <h3 class="box-title text-black">Dimensões</h3>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="form-group">
                                                <div class="form-group spance-2">
                                                    <label for="peso_bruto">Peso Bruto</label> <i class="fa fa-info-circle" data-placement="top" data-toggle="tooltip" title="Kg"></i>
                                                    <input type="text" name="peso_bruto" value="<?= $p['peso_bruto'] ?>"
                                                           class="form-control" id="peso_bruto"
                                                           placeholder="" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="form-group">
                                                <div class="form-group spance-2">
                                                    <label for="peso_liquido">Peso liquido</label> <i class="fa fa-info-circle" data-placement="top" data-toggle="tooltip" title="Kg"></i>
                                                    <input type="text" name="peso_liquido" value="<?= $p['peso_liquido'] ?>"
                                                           class="form-control" id="peso_liquido"
                                                           placeholder="" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="form-group">
                                                <div class="form-group spance-2">
                                                    <label for="altura">Altura</label> <i class="fa fa-info-circle" data-placement="top" data-toggle="tooltip" title="cm"></i>
                                                    <input type="text" name="altura" value="<?= $p['altura'] ?>"
                                                           class="form-control" id="altura"
                                                           placeholder="" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="form-group">
                                                <div class="form-group spance-2">
                                                    <label for="largura">Largura</label> <i class="fa fa-info-circle" data-placement="top" data-toggle="tooltip" title="cm"></i>
                                                    <input type="text" name="largura" value="<?= $p['largura'] ?>"
                                                           class="form-control" id="largura"
                                                           placeholder="" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="form-group">
                                                <div class="form-group spance-2">
                                                    <label for="products_package">Prod. por embalagem</label> <i class="fa fa-info-circle" data-placement="top" data-toggle="tooltip" title="Qtd"></i>
                                                    <input type="text" name="products_package" value="<?= $p['products_package'] ?>"
                                                           class="form-control" id="products_package"
                                                           placeholder="" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="form-group">
                                                <div class="form-group spance-2">
                                                    <label for="profundidade">Profundidade</label> <i class="fa fa-info-circle" data-placement="top" data-toggle="tooltip" title="cm"></i>
                                                    <input type="text" name="profundidade" value="<?= $p['profundidade'] ?>"
                                                           class="form-control" id="profundidade"
                                                           placeholder="" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="box box-default">
                                <div class="box-body">
                                    <div class="col-md-6 row">
                                        <div class="form-group col-sm-12">
                                            <h3 class="box-title text-black">Preço e estoque</h3>
                                        </div>
                                        <div class="col-sm-6 spance-1">
                                            <div class="form-group">
                                                <label class="list_price" for="list_price">Preço de</label>
                                                <input type="text" name="list_price"
                                                       value="<?= $p['list_price']?>"
                                                       class="form-control money"
                                                       id="list_price" placeholder="" readonly>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 spance-1">
                                            <div class="form-group">
                                                <label class="price" for="price">Preço por</label>
                                                <input type="text" name="price" value="<?= $p['price'] ?>"
                                                       class="form-control money"
                                                       id="price" placeholder="" readonly>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 spance-1">
                                            <div class="form-group">
                                                <label for="qty">Quantidade</label>
                                                <input type="text" name="qty" value="<?= $p['qty'] ?>"
                                                       class="form-control"
                                                       id="qty" placeholder="" readonly>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 spance-1">
                                            <div class="form-group">
                                                <label for="codigo_do_fabricante">SKU do Fabricante</label>
                                                <input type="text" name="codigo_do_fabricante"
                                                       value="<?= $p['codigo_do_fabricante'] ?>"
                                                       class="form-control"
                                                       id="codigo_do_fabricante" placeholder="" readonly>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 spance-1">
                                            <div class="form-group">
                                                <label for="EAN">Código de Barras
                                                    (EAN)</label>
                                                <input type="text" name="EAN" value="<?= $p['EAN'] ?>"
                                                       class="form-control"
                                                       id="EAN" placeholder="" readonly>
                                            </div>
                                        </div>


                                        <div class="form-group col-sm-12">
                                            <div class="form-froup">
                                                <h3 class="box-title text-black">Categorização</h3>
                                            </div>

                                            <div class="form-group">
                                                <label for="category">Categoria</label>
                                                <input type="text" class="form-control" value="<?=$category_name?>" disabled>
                                            </div>
                                        </div>

                                        <div class="form-group col-sm-12">
                                            <div class="form-froup">
                                                <h3 class="box-title text-black">Marca</h3>
                                            </div>

                                            <div class="form-group">
                                                <label for="category">Marca</label>
                                                <input type="text" class="form-control" value="<?=$brand_name?>" disabled>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-6 row">
                                        <div class="form-group col-sm-12">
                                            <h3 class="box-title text-black">Descrição</h3>
                                        </div>
                                        <div class="box-body">
                                            <div class="col-sm-12 <?= (!$p['description']) ? 'validate' : '' ?>">
                                                <div id="descriptionNull" style="color: #000;">
                                                    <textarea type="text" class="form-control" id="description"
                                                              maxlength="3000"
                                                              name="description"
                                                              placeholder="<?= $this->lang->line('application_enter_description'); ?>">
                                                              <?=$p['description'] ?>
                                                    </textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="col-md-12 " id="divButton">
                        <a class="btn btn-default pull-left" onclick="javascript:window.close();" style="margin: 0px 13px 0 0;"> Cancelar</a>
                        <button class="btn btn-success change-status pull-left" onclick="changeIntegrationApproval(event,<?= "'". $p['sku'] ."','". $p['prd_integration_id'] ."','". $p['prd_id']."','". 1 ."','". $p['approved'] ."','". $p['int_to']  ."'" ?>)" style="margin: 0px 13px 0 0;" ><iclass="fa fa-check"></i>Aprovar produto</button>
                        <button class="btn btn-danger change-status pull-left"  onclick="checkboxDisapproveProduct(event,<?= "'". $p['sku'] ."','". $p['prd_integration_id'] ."','". $p['prd_id']."','". 2 ."','". $p['approved'] ."','". $p['int_to']  ."'" ?>)" data-identify2="listReproved2" style="margin: 0px 13px 0 0;" >Reprovar produto</button>
                        <button class="btn btn-primary change-status pull-left" onclick="changeIntegrationApproval(event,<?= "'". $p['sku'] ."','". $p['prd_integration_id'] ."','". $p['prd_id']."','". 3 ."','". $p['approved'] ."','". $p['int_to']  ."'" ?>)" style="margin: 0px 13px 0 0;" >Em aprovação</button>
                        <div style="" class="pull-left">
                            <span style="font-size: 18px;">
                                <b><?= $pagination + 1 . ' de ' . $total ?></b>
                            </span>
                        </div>
                        <?php if(!$product_is_publised): ?>
                            <button class="btn btn-primary pull-right" id="save_sku_updates" data-prd-id="<?=$p['prd_id']?>" data-int-to="<?=$p['int_to']?>"><?= $this->lang->line('application_update_changes'); ?></button>
                        <?php endif; ?>
                    </div>
                    <?php

                    //}
                    // }
                    ?>
                </div>
            </div>
    </section>
</div>

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/dropzone.js"></script>
<script src="<?php echo base_url('assets/bower_components/select2/dist/js/i18n/pt-BR.js') ?>"type="text/javascript"></script>
<script src="<?php echo base_url('assets/dist/js/jquery.dragsort.js') ?>" type="text/javascript"></script>

<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>";
    $(document).ready(function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('.money').mask('#.000,00', {reverse: true});
        $('.select2').select2({"language": "pt-BR",disabled:'readonly'});

        $('#listviewPrev').val(JSON.stringify(<?= json_encode($listview2) ?>));
        $('#listviewNext').val(JSON.stringify(<?= json_encode($listview2) ?>));

    });

    $("#description").summernote({
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['view', ['fullscreen', 'codeview']]
        ],
        height: 150,
        disableDragAndDrop: true,
        lang: 'pt-BR',
        shortcuts: false
    });
    $('.note-editable').attr("contenteditable", false);

    var listimages = $('#listimages').val();
    var load = '';

    $('.preview').hide();

    Dropzone.autoDiscover = false;
    $('#file-dropzone').dropzone({
        url: "<?php echo base_url('ProductsFastEdition/listImage') ?>",
        paramName: "file",
        maxThumbnailFilesize: 99999,
        maxFiles: 3,
        clickable: false,
        parallelUploads: 5,
        acceptedFiles: ".jpg,.jpeg",
        previewsContainer: '.visualizacao',
        dictDefaultMessage: '<b>Arraste as imagens para aqui ou clique para selecionar até 6 fotos em JPEG ou JPG de até 3MB </b>',
        params: {listimages: listimages},
        previewTemplate: $('.preview').html(),
        init: function () {
            this.on('completemultiple', function (file, json) {
                list_image();
            });
            this.on('success', function (file, json) {
                if (this.getQueuedFiles().length == 0 && this.getUploadingFiles().length == 0) {
                    var _this = this;
                    _this.removeAllFiles();
                }
                var load = 1;
                list_image(load);
            });
            this.on('addedfile', function (file) {
                var total = countImages();
                if(total > limiteImage){
                    $('#msgError').show().html('Só é permitido até '+limiteImage+' imagens e em formato JPEG ou JPG de até 3MB.').delay(5000).hide('fast');
                    list_image();
                }
                if(file.type != 'image/jpeg' && file.type != 'image/jpg' || file.size > 3200000) {
                    $('#msgError').show().html('Só é permitido até '+limiteImage+' imagens e em formato JPEG ou JPG de até 3MB.').delay(5000).hide('fast');
                }
                list_image();
            });
            this.on('drop', function (file) {
                list_image();
            });
        }
    });

    list_image(load);

    function list_image(load) {
        $.ajax({
            url: base_url + 'Products/listImage',
            method: "POST",
            data: {listimages: listimages,remove: false},
            success: function (data) {
                $('.preview').html(data);
                $('.visualizacao').html(data);
            }
        });
        if (load == 1) {
            $('.visualizacao').html('<h4 align="center">Aguarde...</h4>');
        }
    }


    function changeIntegrationApproval(e, sku, id, prd_id, approve, old_approve, int_to) {
        e.preventDefault();
        $.ajax({
            url: base_url + "products/changeIntegrationApproval",
            type: "POST",
            data: {
                sku: sku, id: id, prd_id: prd_id, approve: approve, old_approve: old_approve, int_to: int_to, view: 'fast_view'
            },
            async: true,
            success: function (data) {
                if (approve == 1) {
                    $('#msgSuccess').show().html('Produto aprovado com sucesso').delay(5000).hide('fast');
                } else if (approve == 2) {
                    $('#msgSuccess').show().html('Produto reprovado com sucesso').delay(5000).hide('fast');
                } else if (approve == 3) {
                    $('#msgSuccess').show().html('Produto em aprovação com sucesso').delay(5000).hide('fast');
                }

                for (let approve_count = 1; approve_count <= 3; approve_count++) {
                    $(`[onclick="changeIntegrationApproval(event,'${sku}','${id}','${prd_id}','${approve_count}','${old_approve}','${int_to}')"]`)
                        .attr('onclick', `changeIntegrationApproval(event,'${sku}','${id}','${prd_id}','${approve_count}','${approve}','${int_to}')`);

                    $(`[onclick="checkboxDisapproveProduct(event,'${sku}','${id}','${prd_id}','${approve_count}','${old_approve}','${int_to}')"]`)
                        .attr('onclick', `checkboxDisapproveProduct(event,'${sku}','${id}','${prd_id}','${approve_count}','${approve}','${int_to}')`);
                }
            },
            error: function (data) {
                $('#msgError').show().html('Error ao atualizar o produto').delay(5000).hide('fast');
            }
        });
    }

    // AÇÃO DE MUDAR O STATUS NO BACK SINGULAR
    function checkboxDisapproveProduct(e, sku, id, prd_id, approve, old_approve, int_to) {
        event.preventDefault();
        var msg,bodyForm = '';
        msg = "Tem certeza que deseja reprovar esse produto?";
        bodyForm =`
                        <div class="row" style="line-height: 4;">
                            <div class="col-sm-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="check_image" type="checkbox" id="check_image">
                                    <label class="form-check-label" for="check_image">
                                        <i class="fa fa-camera icon-color">&nbsp</i> Imagem
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="check_categeory" type="checkbox" id="check_categeory">
                                    <label class="form-check-label" for="check_categeory">
                                        <i class="fa-solid fa-sitemap icon-color">&nbsp;</i> Categoria
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="check_dimensions" type="checkbox" id="check_dimensions">
                                    <label class="form-check-label" for="check_dimensions">
                                        <i class="fa-solid fa-ruler-vertical icon-color">&nbsp;</i> Dimensões
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="check_price" type="checkbox" id="check_price">
                                    <label class="form-check-label" for="check_price">
                                        <i class="fa-solid fa-dollar-sign icon-color">&nbsp;</i> Preço
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="check_description" type="checkbox" id="check_description">
                                    <label class="form-check-label" for="check_description">
                                        <i class="fa-solid fa-file-lines icon-color">&nbsp;</i> Descrição
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-check container-fluid row">
                                    <a data-toggle="collapse" class="btn btn-primary" data-target="#comment" style="float: left;margin-bottom: 11px;">Adicionar comentário</a>
                                </div>
                                <div id="comment" class="collapse">
                                    <textarea class="form-control" name="comment_error" id="comment_error" onkeyup="countCaracter()" placeholder="Máximo 100 caracteres" cols="10" maxlength="100" rows="5"></textarea>
                                    <span id="infor"></span>
                                </div>
                            </div>
                        </div>
                    `;
        Swal.fire({
            title: msg,
            html: bodyForm,
            icon: 'warning',
            showCancelButton: true,
            cancelButtonText: "Cancelar",
            confirmButtonText: "Confirmar reprovação",
            allowOutsideClick: false
        }).then((result) => {
            if (result.value == true) {

                if ($('.form-check-input:checked').length <= 0) {
                    AlertSweet.fire({
                        icon: 'Error',
                        title: 'É obrigatório marcar alguma opção de error!'
                    });
                    return false;
                }

                var check_image = $('#check_image:checked').val();
                var check_categeory = $('#check_categeory:checked').val();
                var check_dimensions = $('#check_dimensions:checked').val();
                var check_price = $('#check_price:checked').val();
                var check_description = $('#check_description:checked').val();
                var comment_error = $('#comment_error').val();
                var disapprove_product = 2;

                $.ajax({
                    url: base_url + "products/changeIntegrationApproval",
                    type: "POST",
                    data: {
                        sku: sku,
                        id: id,
                        prd_id: prd_id,
                        approve:approve,
                        old_approve:old_approve,
                        int_to:int_to,
                        check_image:check_image,
                        check_categeory:check_categeory,
                        check_dimensions:check_dimensions,
                        check_price:check_price,
                        check_description:check_description,
                        comment_error:comment_error,
                        disapprove_product:disapprove_product,
                        view: 'fast_view'
                    },
                    async: true,
                    success: function (data) {
                        $('#msgSuccess').show().html('Produto reprovado com sucesso').delay(5000).hide('fast');
                    },
                    error: function (data) {
                        AlertSweet.fire({
                            icon: 'Error',
                            title: 'Houve um erro ao atualizar o produto!'
                        });
                        return false;
                    }
                });
            }
            return false;
        })
    }

    function countCaracter(){
        count = $('#comment_error').val();
        showCount = count.length;
        if(showCount >= 100){
            $('#infor').text('Você atingiu 100 caracteres').css({'float':'left','color':'red'});
            return false;
        }
        $('#infor').text("Total: "+showCount).css({'float':'left','color':'black'});
    }

    $('#save_sku_updates').on('click', function(){
        console.log($('[name="sku_mkt_prd"]').val(), $('[name="sku_mkt_var[]"]').val());
        let data = {
            prd_id: $(this).data('prd-id'),
            int_to: $(this).data('int-to'),
        };

        if ($('[name="sku_mkt_prd"]').length) {
            data = {... data, sku_mkt_prd: $('[name="sku_mkt_prd"]').val() }
        } else {
            data = {... data, sku_mkt_var: $('[name="sku_mkt_var[]"]').map((idx, ele) => $(ele).val()).get() }
        }

        $.ajax({
            url: base_url + 'Products/updateDataViewFast',
            method: "POST",
            dataType: 'json',
            data,
            success: function (response) {
                Swal.fire({
                    icon: response.success ? 'success' : 'warning',
                    html: response.message
                });
            }
        });
    });

</script>