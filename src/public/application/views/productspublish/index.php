<!--
Lista produtos para publicação/remoção de marketplaces
-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <style>
    .bootstrap-select .dropdown-toggle .filter-option {
        background-color: white !important;
    }

    .bootstrap-select .dropdown-menu li a {
        border: 1px solid gray;
    }

    dataTables_scrollHead {
        width: 100%important;
    }

    .full-table {
        width: 100% !important;
    }
    .float-right{
        float:right!important;
    }
    .btn-outline{
        border: 1px solid #3c8dbc!important;
        color: #3c8dbc!important;
    }
    .dropdown-menu {
        padding: 10px 18px;
        margin: 0px;
    }
    .btn-link, .btn-link:active, .btn-link:focus, .btn-link:hover {
        border: 1px solid #ccc!important;
    }
    select#busca_status_integracao {
        margin-left: 20px;
    }
    .st-p{
        padding-left: 18px;
        margin-top: 14px;
    }
    .box-body {
        padding: 15px;
    }
    .dropdown-menu.open {
        right: 0;
        width: fit-content;
        left: auto;
    }
    .wrapper {
        overflow: hidden;
    }
   .btn-float-right {
        float: left!important;
        margin-top: 12px;
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"/>
    <?php $data['pageinfo'] = "application_manage";
	 $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div id="messages"></div>

                <?php if($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <?php echo $this->session->flashdata('success'); ?>
                </div>
                <?php elseif($this->session->flashdata('error')): ?>
                <div class="alert alert-error alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <?php echo $this->session->flashdata('error'); ?>
                </div>
                <?php endif; ?>
                <!-- Box-body -->
                <br><button class="btn btn-primary float-right" id="btn_first_column_hide" data-toggle="collapse" data-target="#demo" aria-expanded="true">
                    <i class="fa-solid fa-filter"></i>
                    Exibir Filtros
                </button><br><br>

                <div class="box box-default mt-2 collapse in" id="demo" aria-expanded="true">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <h4 class="text-black">Filtros Conecta Lá</h4>
                            </div>
                            <div class="col-md-2">
                                <label for="buscasku" class="normal">Código SKU</label>
                                <div class="">
                                    <input type="search" id="buscasku" class="form-control"
                                        placeholder="<?=$this->lang->line('application_sku');?>" aria-label="Search"
                                        aria-describedby="basic-addon1">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <label for="buscanome" class="normal">Nome</label>
                                <div class="">
                                    <input type="search" id="buscanome" class="form-control"
                                        placeholder="<?=$this->lang->line('application_name');?>" aria-label="Search"
                                        aria-describedby="basic-addon1">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="">
                                    <label for="buscaestoque"
                                        class="normal"><?=$this->lang->line('application_stock');?></label>
                                    <select class="form-control" id="buscaestoque" >
                                        <option value=""><?=$this->lang->line('application_all');?></option>
                                        <option value="1"><?=$this->lang->line('application_with_stock');?></option>
                                        <option value="2"><?=$this->lang->line('application_no_stock');?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2 form-group no-padding">
                                <label for="busca_situacao"
                                       class="normal">Situação</label>
                                <select name="situation" class="form-control" data-toggle="tooltip" data-html="true"
                                        data-placement="top"
                                        title="<b>Completo</b>: o cadastro do produto está completo e pronto para ser publicado;<p /> <b>Incompleto</b>: faltam campos que precisam ser preenchidos."
                                        id="busca_situacao">
                                    <option value="0"><?=$this->lang->line('application_product_situation')?></option>
                                    <option value="2"
                                        <?=(isset($products_incomplete) && $products_incomplete == 2 ? 'selected' : '')?>>
                                        <?=$this->lang->line('application_complete')?></option>
                                    <option value="1"
                                        <?=(isset($products_incomplete) && $products_incomplete == 1 ? 'selected' : '')?>>
                                        <?=$this->lang->line('application_incomplete')?></option>
                                </select>
                            </div>

                            <div class="col-md-2" >
                                <label for="busca_status"
                                    class="normal">Status</label>
                                <select class="form-control" id="busca_status" name="status">
                                    <option value="0"><?=$this->lang->line('application_product_status')?></option>
                                    <option value="1"
                                        <?=(isset($products_complete) && $products_complete == 1 ? 'selected' : '')?>>
                                        <?=$this->lang->line('application_active')?></option>
                                    <option value="2"><?=$this->lang->line('application_inactive')?></option>
                                    <option value="4"><?=$this->lang->line('application_under_analysis')?></option>
                                </select>
                            </div>
                            <div class="col-md-2" >
                                <div class="">
                                    <label for="busca_lojas"
                                        class="normal"><?= $this->lang->line('application_search_for_store') ?></label>
                                    <select class="form-control selectpicker show-tick" id="busca_lojas" name="loja[]"
                                        data-live-search="true" data-actions-box="true"
                                        multiple="multiple" data-style="btn-link" data-selected-text-format="count > 1"
                                        title="<?=$this->lang->line('application_select');?>">
                                        <?php foreach ($stores_filter as $store_filter) { ?>
                                        <option value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></option>
                                        <?php } ?>

                                    </select><br>
                                </div>
                            </div>
                        </div>
                        <!-- Marketplace -->
                        <div class="row">
                            <div class="col-sm-12">
                                <h4 class="text-black">Filtros Marketplace</h4>
                            </div>
                            <div class="col-md-3 form-group no-padding ml-4">
                                <label class="normal" style="margin-top:1em">Marketplace</label>
                                <select title="Selecione o marketplace" class="form-control selectpicker show-tick"
                                    id="busca_marketplace" name="busca_marketplace[]" data-live-search="true"
                                    data-actions-box="true" multiple="multiple" data-style="btn-link"
                                    data-selected-text-format="count > 1">
                                    <?php
                                    if (isset($activeIntegrations)) {
                                        foreach ((array)$activeIntegrations as $key => $activeIntegration) {?>
                                    <option value=<?=$activeIntegration['int_to']?>>
                                        <?=$nameOfIntegrations[$activeIntegration['int_to']]?></option>
                                    <?php
                                        }
                                    }?>
                                </select>
                            </div>
                            <!-- Status de Integracao -->
                            <div class="col-md-3 form-group no-padding">
                                <label class="normal st-p" for="busca_status_integracao">Status de Integração</label>
                                <select class="form-control" id="busca_status_integracao" name="integration">
                                    <option value="999"><?=$this->lang->line('application_integration_status')?></option>																														
                                    <option value="998" selected><?=$this->lang->line('application_not_published')?></option>
                                </select>
                            </div>
                        </div>

                        <div class="float-left">

                            <button href="void:(0)" class="pull-right btn btn-primary btn-float-right"
                            style="margin-right: 5px;" onclick="buscaProduto(event)"><i class="fa fa-search"></i>
                            Buscar</button>

                            <button href="void:(0)" onclick="clearFilters()" class="pull-right btn btn-default btn-float-right"
                            style="margin-right: 5px;"> <i class="fa fa-eraser"></i>
                            <?= $this->lang->line('application_clear'); ?> </button>

                        </div>
                    </div>
                </div>
            </div>

            <div class="row"></div>

            <div class="container-fluid">
            <div class="box box-default mt-2">
                    <div class="box-body">
                        <form role="form" action="<?php echo base_url('products/markProductsApproval') ?>" method="post"
                            id="selectForm">
                            <table id="manageTable" class="table table-bordered table-striped full-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" name="select_all" value="1"
                                                id="manageTable-select-all">
                                        </th>
                                        <th>Image</th>
                                        <th><?=$this->lang->line('application_sku');?></th>
                                        <th><?=$this->lang->line('application_name');?></th>
                                        <th><?=$this->lang->line('application_store');?></th>
                                        <th><?=$this->lang->line('application_price');?></th>
                                        <th><?=$this->lang->line('application_qty');?></th>
                                        <th><?=$this->lang->line('application_status');?></th>
                                        <th>Situação</th>
                                        <th><?=$this->lang->line('application_platform');?></th>
                                        <th><?=$this->lang->line('application_action');?></th>
                                    </tr>
                                </thead>
                            </table>

                            <div class="row">

                                <div class="float-left" style="display: -webkit-inline-box;">
                                    <div class="container-fluid float-left" style="padding-right: 0px;">

<!--                                            <button class="btn btn-primary" onclick="publishAllProducts(event)" id="approve_product" name="approve_product">-->
<!--                                                <i class="fa-solid fa-boxes-packing"></i> &nbsp;-->
<!--                                                Publicar Produtos selecionados-->
<!--                                            </button>-->

<!--                                            <button class="btn btn-primary" onclick="publishAllProductsFiltereds(event)" id="approve_product_filtered" name="approve_product_filtered">-->
                                            <button class="btn btn-primary" onclick="publishAllProducts(event)" id="approve_product" name="approve_product">
                                                <i class="fa-solid fa-boxes-packing"></i> &nbsp;
                                                Publicar Produtos selecionados
                                            </button>
                                    </div>
                                    <div class="container-fluid float-left" style="padding-right: 0px;">
                                        <a type="button" class="btn btn-warning" onclick="publishAllProducts(event)" id="disapprove_product">
                                            <i class="fa-solid fa-box"></i> &nbsp;
                                            Inativar produtos Selecionados</a>
                                    </div>

                                   <!-- <div class="col-md-4">
                                       <button class="btn btn-success"><?php echo $this->lang->line('application_publish_or_inactivate_selected');?></button>
                                   </div>
                                   <div class="col-md-4">
                                       <button class="btn btn-success"><?php echo $this->lang->line('application_publish_or_inactivate_filtered');?></button>
                                    </div> -->

                                </div>

                            </div>

                        </form>
                    </div>
                    <!-- /.box-body -->
            </div>
            </div>
            <!-- /.box -->
        </div>
        <!-- col-md-12 -->

    </section>
<!-- /.content -->
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="publishProduct">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span><?=$this->lang->line('application_publish');?></span></h4>
            </div>
            <form role="form" action="<?php echo base_url('productsPublish/toPublish') ?>" method="post"
                id="publishProduct">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?=$this->lang->line('application_sku');?></label>
                                <input type="text" class="form-control" name="sku_publish" id="sku_publish" value=""
                                    readonly>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label><?=$this->lang->line('application_name');?></label>
                                <input type="text" class="form-control" name="name_publish" id="name_publish" value=""
                                    readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-10">
                            <p><b><?=$this->lang->line('application_check_uncheck_marketplaces');?></b></p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-10"
                            <?php echo (count($names_marketplaces) == 1) ? 'style="display:none"': '' ?>>
                            <input type="checkbox" onchange="toggleIntTo(this)" class="int_to_select_all"
                                id="int_to_select_all" name="int_to_select_all">
                            <span><?=$this->lang->line('application_all');?></span>
                        </div>
                    </div>

                    <?php if (count($names_marketplaces) == 1000) { ?>
                    <div class="row">
                        <div class="col-md-10">
                            <span><b><?=$this->lang->line('application_confirm_publish_marketplace');?>
                                    <?= $names_marketplaces[0]['name']?> ?</b></span>
                            <input type="hidden" class="form-control" name="int_to[]" id="int_to"
                                value="<?= $names_marketplaces[0]['int_to']?>">
                        </div>
                    </div>

                    <?php } else { ?>

                    <div class="row">
                        <div class="col-md-12" id="marketplaces_div">
                            <!--- A tabela será montada dinamicamente --->
                        </div>
                    </div>
                    <?php } ?>

                    <input type="hidden" name="id_publish" id="id_publish" value="" autocomplete="off">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                        data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                    <button type="submit" class="btn btn-primary" id="publishProductsubmit"
                        name="publishProductsubmit"><?=$this->lang->line('application_confirm');?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="publishAllProducts">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span><?=$this->lang->line('application_publish');?>/Inativar produto(s)</span></h4>
            </div>
            <form role="form" action="<?php echo base_url('productsPublish/toPublishSeveral') ?>" method="post"
                id="publishAllProductsForm">
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-12">
                            <p><b><?=$this->lang->line('application_check_marketplaces_publish_inactivate');?></b></p>
                        </div>
                    </div>
                    <div class="row" id="content_many_products">
                        <div class="col-md-12">
                            <div class="alert alert-info alert-dismissible" role="alert">
                                <?=$this->lang->line('messages_alert_for_publish_many_products')?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <table class="table-striped table-hover display table-condensed">
                                <thead>
                                    <tr <?php echo (count($names_marketplaces) == 1) ? 'style="display:none"': '' ?>>
                                        <td
                                            <?php echo ( $names_marketplaces[0]['name'] == $names_marketplaces[0]['int_to']) ? 'style="display:none"': '' ?>>
                                        </td>
                                        <td></td>
                                        <td><input type="checkbox"
                                                onchange="toggleIntToSeveral(this,'int_to_several','int_to_inactive','int_to_select_all_inactive_several')"
                                                class="int_to_select_all_several" id="int_to_select_all_several"
                                                name="int_to_select_all_several">
                                            <span><?=$this->lang->line('application_all');?></span>
                                        </td>
                                        <td><input type="checkbox"
                                                onchange="toggleIntToSeveral(this,'int_to_inactive','int_to_several','int_to_select_all_several')"
                                                class="int_to_select_all_inactive_several"
                                                id="int_to_select_all_inactive_several"
                                                name="int_to_select_all_inactive_several">
                                            <span><?=$this->lang->line('application_all');?></span>
                                        </td>
                                    </tr>
                                <?php
					            foreach($names_marketplaces as $key =>$mkt) {
                                     $isActive = $this->db->get_where('integrations', array('store_id' => 0, 'name' => $mkt['name']))->row_array();
                                     if($isActive && $isActive['active'] == '1'){
                                        ?>
                                            <tr>
                                        <td
                                            <?php echo ( $mkt['name'] == $mkt['int_to']) ? 'style="display:none"': '' ?>>
                                            <?= $mkt['name']?></td>
                                        <td><span class="label label-success"><?= $mkt['int_to']?></span></td>
                                        <td><input type="checkbox"
                                                class="int_to_several publishAllProducts_int_to<?= $key?>"
                                                onchange="toggleIntToOther(this,'int_to_inactive<?= $key?>')"
                                                id="int_to<?= $key?>" name="int_to_several[]"
                                                value="<?= $mkt['int_to']?>"><label
                                                for="int_to<?= $key?>">&nbsp;<?=$this->lang->line('application_publish');?></label>
                                        </td>
                                        <td><input type="checkbox"
                                                class="int_to_inactive publishAllProducts_int_to_inactive<?= $key?>"
                                                onchange="toggleIntToOther(this,'int_to<?= $key?>')"
                                                id="int_to_inactive<?= $key?>" name="int_to_inactive[]"
                                                value="<?= $mkt['int_to']?>"><label
                                                for="int_to_inactive<?= $key?>">&nbsp;<?=$this->lang->line('application_inactivate');?></label>
                                        </td>
                                    </tr>
                                        <?php
                                     }
                                }
                                ?>
                                </thead>
                            </table>
                        </div>
                    </div>

                    <input type="hidden" name="id_publish_several" id="id_publish_several" value="" autocomplete="off">
                    <input type="hidden" name="select_all_products" id="select_all_products" value="0">
                    <input type="hidden" name="filter_search" value="">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                        data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                    <button type="submit" class="btn btn-primary" id="publishProductsubmitSeveral"
                        name="publishProductsubmitSeveral"><?=$this->lang->line('application_confirm');?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="publishAllProductsFiltereds">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span><?=$this->lang->line('application_publish');?></span></h4>
            </div>
           <form role="form" action="<?php echo base_url('productsPublish/toPublishAllFiltered') ?>" method="post"
                id="publishAllProductsFilteredsForm">
                <input type="hidden" name="sku_filtered" id="sku_filtered" value="" autocomplete="off">
                <input type="hidden" name="product_name" id="product_name" value="" autocomplete="off">
                <input type="hidden" name="qtd_stock" id="qtd_stock" value="" autocomplete="off">
                <input type="hidden" name="marketplace" id="marketplace" value="" autocomplete="off">
                <input type="hidden" name="stores" id="stores" value="" autocomplete="off">
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-12">
                            <p><b><?=$this->lang->line('application_check_marketplaces_publish_inactivate_filtereds');?></b>
                            </p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <table class="table-striped table-hover display table-condensed">
                                <thead>
                                    <tr <?php echo (count($names_marketplaces) == 1) ? 'style="display:none"': '' ?>>
                                        <td
                                            <?php echo ( $names_marketplaces[0]['name'] == $names_marketplaces[0]['int_to']) ? 'style="display:none"': '' ?>>
                                        </td>
                                        <td></td>
                                        <td><input type="checkbox"
                                                onchange="toggleIntToSeveral(this,'int_to_several','int_to_inactive','int_to_select_all_inactive_several')"
                                                class="int_to_select_all_several" id="int_to_select_all_filtered"
                                                name="int_to_select_all_several">
                                            <span><?=$this->lang->line('application_all');?></span>
                                        </td>
                                        <td><input type="checkbox"
                                                onchange="toggleIntToSeveral(this,'int_to_inactive','int_to_several','int_to_select_all_several')"
                                                class="int_to_select_all_inactive_several"
                                                id="int_to_select_all_inactive_filtered"
                                                name="int_to_select_all_inactive_several">
                                            <span><?=$this->lang->line('application_all');?></span>
                                        </td>
                                    </tr>
                                    <?php
					             foreach($names_marketplaces as $key =>$mkt) {
					             ?>
                                    <tr>
                                        <td><?= $mkt['name']?></td>
                                        <td><span class="label label-success"><?= $mkt['int_to']?></span></td>
                                        <td><input type="checkbox"
                                                class="int_to_several publishAllProductsFiltereds_int_to<?= $key?>"
                                                onchange="toggleIntToOther(this,'int_to_inactive<?= $key?>')"
                                                id="int_to<?= $key?>" name="int_to_several[]"
                                                value="<?= $mkt['int_to']?>"><label
                                                for="int_to<?= $key?>">&nbsp;<?=$this->lang->line('application_publish');?></label>
                                        </td>
                                        <td><input type="checkbox"
                                                class="int_to_inactive publishAllProductsFiltereds_int_to_inactive<?= $key?>"
                                                onchange="toggleIntToOther(this,'int_to<?= $key?>')"
                                                id="int_to_inactive<?= $key?>" name="int_to_inactive[]"
                                                value="<?= $mkt['int_to']?>"><label
                                                for="int_to_inactive<?= $key?>">&nbsp;<?=$this->lang->line('application_inactivate');?></label>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </thead>
                            </table>
                        </div>
                    </div>


                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                        data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                    <button type="submit" class="btn btn-primary" id="publishProductsubmitFiltered"
                        name="publishProductsubmitFiltered"><?=$this->lang->line('application_confirm');?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- /.content-wrapper -->

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var base_url = "<?php echo base_url(); ?>";
var manageTable, int_to;
var MAX_SELECT_STORES = 200;

function isValidStoreSelection() {
    const selected = $('#busca_lojas').val() || [];
    return selected.length <= MAX_SELECT_STORES;
}

$(document).ready(function() {
    $('#busca_lojas').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
            var selected = $(this).val() || [];
            var isValidStoreSelection = selected.length <= MAX_SELECT_STORES
            if (isValidStoreSelection) {
                return;
            }
            $(this).selectpicker('val', previousValue);
            Swal.fire({
            title: 'Atenção!',
            html: `<h2>Você pode selecionar no máximo ${MAX_SELECT_STORES} lojas.</h2>`,
            icon: 'warning',
            showCancelButton: false,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Ok',
            })
        });

    datableDefault();

    $("#mainProductNav").addClass('active');
    $("#doProductsPublishNav").addClass('active');
    // $('#approve_product_filtered').hide();

    $('#buscamkt  option:selected').each(function() {

        int_to.push($(this).val());
    });
    $('#busca_situacao  option:selected').each(function() {
        situacao = $(this).val();
    });

    var lojas = [];
    $('#buscalojas  option:selected').each(function() {
        lojas.push($(this).val());
    });
    if (lojas == '') {
        lojas = ''
    }
});

function datableDefault(){

    if (typeof manageTable === 'object' && manageTable !== null) {
        manageTable.destroy();
    }

    manageTable = $('#manageTable').DataTable({
        "language": {
            "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"
        },
        "processing": true,
        "serverSide": true,
        "serverMethod": "post",
        'columnDefs': [{
            'targets': 0,
            'searchable': false,
            'orderable': false,
            'className': 'dt-body-center',
            'render': function(data, type, full, meta) {
                return '<input type="checkbox" class="productsselect" name="id[]" value="' +
                    $(
                        '<div/>').text(data).html() + '">';
            }
        }],
        "ajax": $.fn.dataTable.pipeline({
            url: base_url + 'productsPublish/fetchProductsPublish',
            data: {
                sku: '',
                nome: '',
                int_to: [],
                estoque: '',
                lojas: '',
                status: 0,
                marketplace: [],
                situacao: '',
                status_int: 998
            },
            pages: 2
        }),
        'fnDrawCallback': function() {
            $('#manageTable-select-all').prop('checked', false);
        }
    });

}

// Handle click on "Select all" control
$('#manageTable-select-all').on('click', function() {
    // if(this.checked) {
    //     $('#approve_product_filtered').show();
    //     $('#approve_product').hide();
    // }else{
    //     $('#approve_product_filtered').hide();
    //     $('#approve_product').show();
    // }
    // Get all rows with search applied
    var rows = manageTable.rows({
        'search': 'applied'
    }).nodes();
    // Check/uncheck checkboxes for all rows in the table
    $('input[type="checkbox"]', rows).prop('checked', this.checked);
});
// Handle click on checkbox to set state of "Select all" control
$('#manageTable tbody').on('change', 'input[type="checkbox"]', function() {
    // If checkbox is not checked
    if (!this.checked) {
        var el = $('#manageTable-select-all').get(0);
        // If "Select all" control is checked and has 'indeterminate' property
        if (el && el.checked && ('indeterminate' in el)) {
            // Set visual state of "Select all" control
            // as 'indeterminate'
            el.indeterminate = true;
        }
    }
});

function buildIntegrationsStatus(){
    
    var status = $("#busca_status_integracao");
    var selectedValue = status.val();

    let integrationStatus = {
        "<?=$this->lang->line('application_integration_status')?>" : 999,
        "<?=$this->lang->line('application_product_in_analysis')?>" : 0,
        "<?=$this->lang->line('application_product_waiting_to_be_sent')?>" : 1,
        "<?=$this->lang->line('application_product_sent')?>" : 2,
        "<?=$this->lang->line('application_product_higher_price')?>" : 11,
        "<?=$this->lang->line('application_product_release')?>" : 14,
        "<?=$this->lang->line('application_in_registration')?>" : 20,
        "<?=$this->lang->line('application_errors_tranformation')?>" : 30,
        "<?=$this->lang->line('application_published')?>" : 40,
        "<?=$this->lang->line('application_not_published')?>" : 998,
        "<?=$this->lang->line('application_inactive')?>" : 90,
        "<?=$this->lang->line('application_no_logistics')?>" : 91,
        "<?=$this->lang->line('application_out_stock')?>" : 99
    };
    
    let mkt = document.getElementById('busca_marketplace');
    
    status.html('');
    for (var key in integrationStatus) {
        let val = integrationStatus[key];
        if(mkt.value == '' && (val != 998 && val != 999)){
            continue
        }
        status.append('<option '+(val == selectedValue ? 'selected' : '')+' value="'+val+'">'+key+'</option>');                    
    }
}

buildIntegrationsStatus();

function buscaProduto(event) {
    
    if (!isValidStoreSelection()) {
            return;
    }

    let sku = $('#buscasku').val();
    let nome = $('#buscanome').val();
    let estoque = $('#buscaestoque').val();
    let situacao = $('#busca_situacao').val();
    let status = $('#busca_status').val();
    let lojas = $('#busca_lojas').val();
    let marketplace = $('#busca_marketplace').val();
    let status_int = $('#busca_status_integracao').val();
    var int_to = [];
    buildIntegrationsStatus();
    $('#busca_marketplace  option:selected').each(function() {
        int_to.push($(this).val());
    });
    if (int_to.length == 0) {
        int_to = '';       
    }

    $('#busca_situacao  option:selected').each(function() {
        situacao = $(this).val();
    });

    if (typeof manageTable === 'object' && manageTable !== null) {
        manageTable.destroy();
    }

    manageTable = $('#manageTable').DataTable({
        "language": {
            "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"
        },
        "processing": true,
        "serverSide": true,
        "serverMethod": "post",
        'columnDefs': [{
            'targets': 0,
            'searchable': false,
            'orderable': false,
            'className': 'dt-body-center',
            'render': function(data, type, full, meta) {
                return '<input type="checkbox" class="productsselect" name="id[]" value="' + $(
                    '<div/>').text(data).html() + '">';
            }
        }],
        "ajax": $.fn.dataTable.pipeline({
            url: base_url + 'productsPublish/fetchProductsPublish',
            data: {
                sku: sku,
                nome: nome,
                int_to: int_to,
                estoque: estoque,
                lojas: lojas,
                status: status,
                marketplace: marketplace,
                situacao: situacao,
                status_int: status_int
            },
            pages: 2 // number of pages to cache
        }),
        'fnDrawCallback': function() {
            $('#manageTable-select-all').prop('checked', false);
        }
    });
}

var lojas = [];
$('#buscalojas  option:selected').each(function() {
    lojas.push($(this).val());
});
if (lojas == '') {
    lojas = ''
}

function clearFilters() {
    datableDefault();
    $('#buscasku').val('');
    $('#buscanome').val('');
    $('#buscaestoque').val('');
    $('#busca_status').val('0');
    $('#busca_situacao').val('0');
    $('#busca_status_integracao').val('999');//.prop('disabled', true);
    $('#busca_lojas').selectpicker('val', '');
    $('#buscamkt').selectpicker('val', '');
    $('#busca_marketplace').selectpicker('val', '');
}

function publishProduct(e, product_id, sku_publish, name_publish) {
    e.preventDefault();
    $.ajax({
        url: base_url + 'productsPublish/getProductIntegrations/' + product_id,
        type: 'get',
        dataType: 'json',
        success: function(response) {
            document.getElementById('id_publish').value = product_id;
            document.getElementById('sku_publish').value = sku_publish;
            document.getElementById('name_publish').value = name_publish;
            var len = response.length;

            var container = $("#marketplaces_div");
            var mktremove = $("#mktdivrmove");
            mktremove.remove();
            var mkt;
            mkt = '<div id="mktdivrmove" >';
            mkt = mkt + '<table class="table-striped table-hover display table-condensed"><thead>';
            if (len > 0) {

            }
            for (var i = 0; i < len; i++) {
                var chk = '';
                if (response[i].status == 1) {
                    chk = 'checked';
                }
                mkt = mkt + '<tr>';

                mkt = mkt + '<td><input type="checkbox" class="int_to" id="int_to' + i +
                    '" name="int_to[]" value="' + response[i].int_to + '" ' + chk + ' ></td>';
                mkt = mkt + '<td>' + response[i].name + '</td>';
                if (response[i].name != response[i].int_to) {
                    mkt = mkt + '<td><span class="label label-success">' + response[i].int_to +
                        '</span></td>';
                }
                mkt = mkt + '<td><span class="label ' + response[i].label + '"> ' + response[i]
                    .description + '</span></td>';
                mkt = mkt + '</tr>';
            }
            mkt = mkt + '</thead></table>';

            mkt = mkt + '</div>';
            container.append(mkt);
            document.getElementById('int_to_select_all').checked = false;
            $("#publishProduct").modal('show');


        },
        error: function(error) {
            console.log("Error:");
            console.log(error);
        }
    });
}

function publishAllProducts(e) {
    e.preventDefault();

    var checkboxes = document.querySelectorAll('input[class=productsselect]:checked')
    if (checkboxes.length == 0) {
        AlertSweet.fire({
            icon: 'warning',
            title: 'Nenhum produto selecionado!'
        });
        return;
    }

    if(!checkIfStoreIsSelected()){
        return
    }

    var products_id = '';
    for (var i = 0; i < checkboxes.length; i++) {
        products_id = products_id + checkboxes[i].value + ";";
    }
    document.getElementById('id_publish_several').value = products_id;
    document.getElementById('int_to_select_all_several').checked = false;
    document.getElementById('int_to_select_all_inactive_several').checked = false;

    var checkboxes = document.querySelectorAll('input[class=int_to_several]')
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = false;
    }
    var checkboxes = document.querySelectorAll('input[class=int_to_inactive]')
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = false;
    }

    $('#publishAllProducts #select_all_products').val($('#manageTable-select-all').is(':checked') ? 1 : 0)

    $("#publishAllProducts").modal('show');
}

function publishAllProductsFiltereds(e) {
    e.preventDefault();
    let sku = $('#buscasku').val();
    document.getElementById('sku_filtered').value = sku;
    let nome = $('#buscanome').val();
    document.getElementById('product_name').value = nome;
    let estoque = $('#buscaestoque').val();
    document.getElementById('qtd_stock').value = estoque;
    var int_to = [];
    $('#buscamkt  option:selected').each(function() {
        int_to.push($(this).val());
    });
    int_to = JSON.stringify(int_to);
    document.getElementById('marketplace').value = int_to;
    var stores = [];
    $('#buscalojas  option:selected').each(function() {
        stores.push($(this).val());
    });
    stores = JSON.stringify(stores);
    document.getElementById('stores').value = stores;

    $("#publishAllProductsFiltereds").modal('show');
}

function toggleIntToSeveral(element, classx, classy, idselectall) {
    let i;
    const el = $(element).closest('.modal');
    let checkboxes = document.querySelectorAll('input.' + classx);
    for (i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = element.checked;
    }
    if (element.checked) {
        checkboxes = document.querySelectorAll('input.' + classy);
        for (i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = false;
        }
        el.find(`.${idselectall}`).prop('checked', false);
    }
}

function toggleIntTo(element) {
    var checkboxes = document.querySelectorAll('input[class=int_to]')
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = element.checked;
    }
}

const toggleIntToOther = (element, int_id) => {
    const el = $(element).closest('.modal');
    const idModal = el.attr('id');
    const classCheckboxAction = $(`.${idModal}_${int_id}`);

    if (classCheckboxAction.parents('tr').find(`input[type="checkbox"]`).is(':checked')) {
        classCheckboxAction.parents('tr').find(`input[type="checkbox"]`).prop('checked', true);
    }
    el.find('.int_to_select_all_several, .int_to_select_all_inactive_several').parents('tr').find(
        `input[type="checkbox"]`).prop('checked', false)
    classCheckboxAction.prop('checked', false);
}

$('#publishAllProducts, #publishAllProductsFiltereds').on('show.bs.modal', function() {
    $(this).find('input[type="checkbox"]').prop('checked', false);
});

$(document).on('change', '.productsselect', function() {
    if (!$(this).is(":checked")) {
        $('#manageTable-select-all').prop('checked', false);
    }
});

$(document).on('click', '#btn_first_column_show', function() {
    $(this).attr("id","btn_first_column_hide").html('Exibir Filtros')
        .append('<i class="fa-solid fa-filter pull-left" style="padding: 4px 5px 0px 0px;"></i>');
});

$(document).on('click', '#btn_first_column_hide', function() {
    $(this).attr("id","btn_first_column_show").html('Ocultar Filtros')
        .append('<i class="fa-solid fa-filter pull-left" style="padding: 3px 5px 0px 0px;"></i>');
});

$('#publishAllProductsForm').on('submit', function() {
    if ($('#manageTable-select-all').is(':checked')) {
        if(!checkIfStoreIsSelected()){
            return false						 
        }
    }
});

$('#publishAllProducts').on('show.bs.modal', function() {
    $('#content_many_products').hide();

    if ($('#manageTable-select-all').is(':checked')) {
        const products_count = $('#manageTable').dataTable().fnSettings().fnRecordsDisplay();
        if(!checkIfStoreIsSelected()){										   
            return false
        }
        $('#content_many_products').show();
        $('#content_many_products .products_count').text(products_count);
    }
    $([
        'buscasku',
        'buscanome',
        'buscaestoque',
        'busca_situacao',
        'busca_status',
        'busca_lojas',
        'busca_marketplace',
        'busca_status_integracao'
    ]).each(function(key, value) {
        if ($(`[name="${value}"]`).length) {
            $(`[name="${value}"]`).val($(`#${value}`).val());
        } else {
            $('#publishAllProductsForm').append(`<input type="hidden" name="${value}" value="${$(`#${value}`).val()}">`)
        }
    });

    $(`#publishAllProductsForm [name="filter_search"]`).val($('#manageTable_filter input[type="search"]').val());
});

$('#buscasku, #buscanome, #buscaestoque, #busca_situacao, #busca_status, #busca_lojas, #busca_marketplace, #busca_status_integracao').on('change', function(e) {
    // if ($(e.target).attr('id') === 'busca_marketplace') {
    //     if ($(e.target).val().length) {
    //         $('#busca_status_integracao').prop('disabled', false);
    //     } else {
    //         $('#busca_status_integracao').prop('disabled', true);
    //     }
    // }
    buscaProduto();
});

$('#approve_product, #disapprove_product').on('click', function(e){
    $('#publishAllProductsForm [name="int_to_several[]"], #int_to_select_all_several').prop('checked', $(e.target).attr('id') === 'approve_product');
    $('#publishAllProductsForm [name="int_to_inactive[]"], #int_to_select_all_inactive_several').prop('checked', $(e.target).attr('id') === 'disapprove_product');
});

const checkIfStoreIsSelected = () => {
    <?php if($this->data['only_admin'] == "1") { ?>
        let lojas = $('#busca_lojas').val();
        if(lojas.length === 0 || lojas.length > 1){
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                html: `Selecione 1 loja para realizar a publicação de produtos.`
            });
            return false;
        }
    <?php } ?>
    return true;
}
</script>