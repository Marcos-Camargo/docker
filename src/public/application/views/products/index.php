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
    .dropdown.bootstrap-select.show-tick.form-control{
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

  <?php $data['pageinfo'] = "application_manage";$this->load->view('templates/content_header', $data);?>

  <section class="content">
    <div class="row">
      <div class="col-md-12 col-xs-12">
        <div id="messages"></div>
        <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <?php echo $this->session->flashdata('success'); ?>
        </div>
        <?php elseif($this->session->flashdata('warning')): ?>
         <div class="alert alert-warning alert-dismissible" role="alert">
           <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
           <?php echo $this->session->flashdata('warning'); ?>
         </div>
        <?php elseif ($this->session->flashdata('error')): ?>
        <div class="alert alert-error alert-dismissible" role="alert">
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <?php echo $this->session->flashdata('error'); ?>
        </div>
        <?php endif;?>
        <div class="box box-primary mt-2" id="showActions">
          <div class="box-body">
			<span class ="pull-right">&nbsp</span>
      		<a class="pull-right btn btn-primary" href="<?php echo base_url('export/categoriesxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_categories_export');?></a>
            <a class="pull-right btn btn-primary" id="buttonCollapseFilter" style="margin-right: 5px;" role="button" data-toggle="collapse" href="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter" onclick="changeFilter()">Ocultar Filtros</a>
	        <?php if (in_array('createProduct', $user_permission)): ?>
	          <a href="<?php echo base_url('products/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_product');?></a>
			  <a href="<?php echo base_url('productsKit/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_product_kit');?></a>
	        <?php endif;?>
          </div>
        </div>

        <div class="box box-primary" id="collapseFilter">
          <div class="box-body">

            <h4 class="mt-0">Filtros <?=$sellercenter_name?></h4>
            <div class="col-md-2 form-group no-padding">
              <div class="input-group">
                <input type="search" id="busca_sku" name="sku" class="form-control" placeholder="Código SKU" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
              </div>
            </div>

            <div class="col-md-4 form-group no-padding">
              <div class="input-group">
                <input type="search" id="busca_produto" name="product" class="form-control" placeholder="Nome do Produto" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
              </div>
            </div>

            <div class="col-md-2 form-group no-padding">
              <select class="form-control" id="busca_status" name="status" onchange="personalizedSearch()">
                <option value="0"><?=$this->lang->line('application_product_status')?></option>
                <option value="1" <?=(isset($products_complete) && $products_complete == 1 ? 'selected' : '')?> ><?=$this->lang->line('application_active')?></option>
                <option value="2"><?=$this->lang->line('application_inactive')?></option>
                <option value="4"><?=$this->lang->line('application_under_analysis')?></option>
              </select>
            </div>

            <div class="col-md-2 form-group no-padding">
              <select name="situation" class="form-control" data-toggle="tooltip" data-html="true" data-placement="top" title="<b>Completo</b>: o cadastro do produto está completo e pronto para ser publicado;<p /> <b>Incompleto</b>: faltam campos que precisam ser preenchidos." id="busca_situacao" onchange="personalizedSearch()">
                <option value="0"><?=$this->lang->line('application_product_situation')?></option>
                <option value="2" <?=(isset($products_incomplete) && $products_incomplete == 2 ? 'selected' : '')?>><?=$this->lang->line('application_complete')?></option>
                <option value="1" <?=(isset($products_incomplete) && $products_incomplete == 1 ? 'selected' : '')?>><?=$this->lang->line('application_incomplete')?></option>
              </select>
            </div>

            <div class="col-md-2 form-group no-padding">
              <select class="form-control" id="busca_estoque" name="estoque" onchange="personalizedSearch()">
                <option value="0"><?=$this->lang->line('application_all') . ' ' . $this->lang->line('application_promotion_type_stock')?></option>
                <option value="1" <?=(isset($products_without_stock) && $products_without_stock == 1 ? 'selected' : '')?>><?=$this->lang->line('application_with_stock')?></option>
                <option value="2" <?=(isset($products_without_stock) && $products_without_stock == 2 ? 'selected' : '')?>><?=$this->lang->line('application_no_stock')?></option>
              </select>
            </div>

            <div class="col-md-2 form-group no-padding">
              <select class="form-control" id="busca_kit" name="kit" onchange="personalizedSearch()">
                <option value="0"><?=$this->lang->line('application_with_or_without_kit');?></option>
                <option value="1" <?=(isset($products_kit) && $products_kit == 1 ? 'selected' : '')?>><?=$this->lang->line('application_products_kit')?></option>
                <option value="2" <?=(isset($products_kit) && $products_kit == 2 ? 'selected' : '')?>><?=$this->lang->line('application_no_products_kit')?></option>
              </select>
            </div>

              <div class="col-md-3 form-group no-padding" style="<?=(count($stores_filter) > 1) ? "" : "display: none;"?>">
                  <select class="form-control selectpicker show-tick" id="buscalojas" name="loja[]" onchange="personalizedSearch()" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_search_for_store');?>">
                      <?php foreach ((array)$stores_filter as $store_filter) {?>
                          <option value="<?=$store_filter['id']?>"><?=$store_filter['name']?></option>
                      <?php }?>
                  </select>
              </div>

              <?php if ($identifying_technical_specification && $identifying_technical_specification['status'] == 1) { ?>
              <div class="col-md-3 form-group no-padding" style="<?=(count($stores_filter) > 1) ? "" : "display: none;"?>">
                  <select class="form-control selectpicker show-tick" id="buscacolecoes" name="colecoes[]" onchange="personalizedSearch()" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_search_for_collection');?>">
                      <?php foreach ($collections_catalog as $collection) { ?>
                          <option value="<?= $collection['attribute_value'] ?>"><?= $collection['attribute_value'] ?></option>
                      <?php }?>
                  </select>
              </div>
              <?php } ?>

            <h4 class="col-md-12 no-padding">Filtros Marketplace</h4>
            <div class="col-md-3 form-group no-padding">
              <select title="Selecione o marketplace" class="form-control selectpicker show-tick" id="busca_marketplace" name ="busca_marketplace[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-link" data-selected-text-format="count > 1" onchange="personalizedSearch()">
              <?php 
              if (isset($activeIntegrations)) {
                foreach ((array)$activeIntegrations as $key => $activeIntegration) {?>
                <option value=<?=$activeIntegration['int_to']?>><?=$nameOfIntegrations[$activeIntegration['int_to']]?></option>
                <?php 
                }
			        }?>
              </select>
            </div>

            <div class="col-md-3 form-group no-padding">
              <select class="form-control" id="busca_integracao" name="integration" disabled onchange="personalizedSearch()">
                <option value="999"><?=$this->lang->line('application_integration_status')?></option>
                <option value="0"><?=$this->lang->line('application_product_in_analysis')?></option>
                <option value="1"><?=$this->lang->line('application_product_waiting_to_be_sent')?></option>
                <option value="2"><?=$this->lang->line('application_product_sent')?></option>
                <option value="11"><?=$this->lang->line('application_product_higher_price')?></option>
                <option value="14"><?=$this->lang->line('application_product_release')?></option>
                <option value="20"><?=$this->lang->line('application_in_registration')?></option>
                <option value="30"><?=$this->lang->line('application_errors_tranformation')?></option>
                <option value="40"><?=$this->lang->line('application_published')?></option>
                <option value="90"><?=$this->lang->line('application_inactive')?></option>
                <option value="91"><?=$this->lang->line('application_no_logistics')?></option>
                <option value="99"><?=$this->lang->line('application_out_stock')?></option>
              </select>
            </div>
            <!-- <a class="pull-right btn btn-primary" href="<?php echo base_url('export/productsxls') ?>" id="exportProducts"><i class="fa fa-file-excel-o"></i> Excel </a> -->
            <button type="button" onclick="clearFilters()" class="pull-right btn btn-primary" style="margin-right: 5px;"> <i class="fa fa-eraser"></i> <?= $this->lang->line('application_clear'); ?> </button>
          </div>
        </div>

        <div class="box box-primary">
            <div class="box-header">
                <div class="products-actions">
                    <div class="pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false" id="actionsButton">
                                <?= $this->lang->line('application_actions')?>
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li class="dropdown-item"
                                    data-toggle="modal"
                                    data-view="list"
                                    data-type="status"
                                    data-status='<?= json_encode([
                                        'code' => Model_products::ACTIVE_PRODUCT,
                                        'alias' => 'active',
                                        'description' => $this->lang->line('application_active')
                                    ]) ?>'
                                >
                                    <a>
                                        <i class="fa fa-check-circle-o text-success"></i>
                                        <?= $this->lang->line('application_activate')?>
                                    </a>
                                </li>
                                <li class="dropdown-item"
                                    data-toggle="modal"
                                    data-view="list"
                                    data-type="status"
                                    data-status='<?= json_encode([
                                        'code' => Model_products::INACTIVE_PRODUCT,
                                        'alias' => 'inactive',
                                        'description' => $this->lang->line('application_inactive')
                                    ]) ?>'
                                >
                                    <a>
                                        <i class="fa fa-stop-circle-o text-warning"></i>
                                        <?= $this->lang->line('application_deactivate')?>
                                    </a>
                                </li>
                                <li class="divider" role="separator"></li>
                                <li class="dropdown-item<?php echo in_array('moveProdTrash', $this->permission) ? '': ' disabled';?>"
                                    data-toggle="modal"
                                    data-view="list"
                                    data-type="trash"
                                    <?php echo in_array('moveProdTrash', $this->permission) ? '': 'data-disabled=true';?>
                                >
                                    <a>
                                        <i class="fa fa-trash-o text-danger"></i>
                                        <?= $this->lang->line('application_delete')?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                <i class="fa fa-file-excel-o"></i>Excel
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li>
                                    <a class="dropdown-item" href="<?php echo (base_url('export/productsxls') . "") ?>" id="exportProductsOnly"><?= $this->lang->line('application_only_product'); ?></a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo (base_url('export/productsxls') . "") ?>" id="exportProducts"><?= $this->lang->line('application_variation_product'); ?></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
          <div class="box-body">
          	<table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
                  <th><input type="checkbox" name="select_all" value="1" id="manageTable-select-all"></th>
                  <th><?=$this->lang->line('application_image');?></th>
                  <th><?=$this->lang->line('application_sku');?></th>
                  <th><?=$this->lang->line('application_name');?></th>
                  <th><?=$this->lang->line('application_price');?></th>
                  <th><?=$this->lang->line('application_qty');?></th>
                  <th><?=$this->lang->line('application_store');?></th>
                  <th><?=$this->lang->line('application_id');?></th>
                  <th><?=$this->lang->line('application_status');?></th>
                  <th><?=$this->lang->line('application_situation');?></th>
                  <?php if ($identifying_technical_specification && $identifying_technical_specification['status'] == 1) { ?>
                      <th><?=$this->lang->line('application_collection');?></th>
                  <?php } ?>
                  <th><?=$this->lang->line('application_platform');?></th>
              </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php if (in_array('deleteProduct', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_product');?><span id="deleteproductname"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('products/remove') ?>" method="post" id="removeForm">
        <div class="modal-body">
          <p><?=$this->lang->line('messages_delete_message_confirm');?></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>

    </div>
  </div>
</div>
<?php endif;?>
<?php
include_once APPPATH . 'views/products/components/popup.update.status.product.php';
?>
<script type="text/javascript" src="<?=HOMEPATH;?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script src="<?php echo base_url('assets/dist/js/components/products/product.base.update.js') ?>"></script>
<script src="<?php echo base_url('assets/dist/js/components/products/product.update.status.js') ?>"></script>
<script src="<?php echo base_url('assets/dist/js/components/products/product.move.trash.js') ?>"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
	$("#hide").click(function(){
		$("#filterModal").hide();
		$("#showActions").show();
	});
	$("#show").click(function(){
		$("#filterModal").show();
		$("#showActions").hide();
	});

  let sku = $('#busca_sku').val();
  let product = $('#busca_produto').val();
  let marketplace = $('#busca_marketplace').val();
  let status = $('#busca_status').val();
  let situation = $('#busca_situacao').val();
  let integration = $('#busca_integracao').val();
  let estoque = $('#busca_estoque').val();
  let kit = $('#busca_kit').val();

    manageTable = $('#manageTable').DataTable( {
        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
        "processing": true,
        "serverSide": true,
        "sortable": true,
        "scrollX": true,
        "serverMethod": "post",
        'columnDefs': [{
            'targets': 0,
            'searchable': false,
            'orderable': false,
            'className': 'dt-body-center',
            'render': function (data, type, full, meta){
                return '<input type="checkbox" class="productsselect" name="id[]" value="' + $('<div/>').text(data).html() + '">';
            }
        }],
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'products/fetchProductData',
            data: {sku: sku, product: product, marketplace: marketplace, status: status, situation: situation, integration:integration, estoque: estoque, kit: kit},
            pages: 2, // number of pages to cache
        } ),
        "createdRow": function( row, data, dataIndex ) {
            $( row ).find('td:eq(3)').addClass('d-flex align-items-center');
        },
        "initComplete": function(settings, json) {
            $('#manageTable [data-toggle="tootip"]').tooltip();
        }
    } );

    $('#manageTable').on( 'draw.dt', function () {
        $('#manageTable [data-toggle="tootip"]').tooltip();
    } );

    $('#manageTable').on('preXhr.dt', function (e, settings, data) {
        //reloadFiltersExport({columns: data.columns, order: data.order});
    });

	$('body').tooltip({
		selector: '[data-toggle="tooltip"]'
	});

    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
        $($.fn.dataTable.tables(true)).DataTable()
         .columns.adjust()
         .responsive.recalc();
    });
    $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
    });

    $('#actionsButton').prop('disabled', true);
    // Handle click on "Select all" control
    $('#manageTable-select-all').on('click', function(){
        // Get all rows with search applied
        var rows = manageTable.rows({ 'search': 'applied' }).nodes();
        // Check/uncheck checkboxes for all rows in the table
        $('input[type="checkbox"]', rows).prop('checked', this.checked).trigger('change');
    });

    // Handle click on checkbox to set state of "Select all" control
    $('#manageTable tbody').on('change', 'input[type="checkbox"]', function(){
        // If checkbox is not checked
        if(!this.checked){
            var el = $('#manageTable-select-all').get(0);
            // If "Select all" control is checked and has 'indeterminate' property
            if(el && el.checked && ('indeterminate' in el)){
                // Set visual state of "Select all" control
                // as 'indeterminate'
                el.indeterminate = true;
            }
        }
        if ($('#manageTable tbody input[type="checkbox"]:checked').length > 0) {
            $('#actionsButton').prop('disabled', false);
        } else {
            $('#actionsButton').prop('disabled', true);
        }
    });

    $('li[data-toggle="modal"]').off('click').on('click', function () {
        if ($(this).data('disabled')) {
            return false;
        }
        var modal = (new ChangeProductStatusModal({
            view: $(this).data('view'),
            type: $(this).data('type'),
        }));
        if ($(this).data('status')) {
            modal.setStatus($(this).data('status'));
        }
        modal.setCount(
            $('#manageTable tbody input[type="checkbox"]:checked').length
        );
        modal.init().then(function (args) {
            var obj = null;
            if (args.type && args.type == 'trash') {
                obj = new ProductMoveTrash({
                    baseUrl: base_url,
                    endpoint: 'products'
                });
            } else if (args.type && args.type == 'status') {
                obj = new ProductUpdateStatus({
                    baseUrl: base_url,
                    endpoint: 'products'
                });
            }
            if (obj) {
                $('#manageTable tbody input[type="checkbox"]:checked').each(function () {
                    var prod = {id: $(this).val()};
                    if (args.status && args.status['code']) {
                        $.extend(prod, {status: args.status['code']});
                    }
                    obj.addProduct(prod);
                });
                obj.send().then(function (response) {
                    var res = JSON.parse(response);
                    Toast.fire({
                        icon: 'success',
                        title: res['message']
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
                    $('#manageTable-select-all').prop('checked', false);
                    $('#actionsButton').prop('disabled', true);
                    personalizedSearch();
                });
            }
        });
    });

    reloadFiltersExport({});
});

// remove functions
function removeFunc(id,name)
{
  if(id) {
	document.getElementById("deleteproductname").innerHTML= ': '+name;
    $("#removeForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: { product_id:id },
        dataType: 'json',
        success:function(response) {

          manageTable.ajax.reload(null, false);

          if(response.success === true) {
            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
            '</div>');

            // hide the modal
            $("#removeModal").modal('hide');

          } else {

            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
            '</div>');
          }
        }
      });

      return false;
    });
  }
}

function changeQty(id, old_qty, new_qty) {
  $.ajax({
      url: base_url+"products/updateQty",
      type: 'POST',
      data: { id: id, old_qty: old_qty, new_qty: new_qty },
      async: true,
      dataType: 'json'
    });
}

function changePrice(id, old_price, new_price, elementHtml) {
    var priceFloat = parseFloat(new_price);
    var priceFormated = priceFloat.toLocaleString('pt-BR');

    $.ajax({
        url: base_url + "products/updatePrice",
        type: 'POST',
        data: {id: id, old_price: old_price, new_price: new_price},
        async: true,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $(elementHtml).val(priceFormated);
                $(elementHtml).attr("onfocus", `this.value=${priceFormated.replace(',', '.')}`);
            }
        }
    });

    return priceFormated;
}

function formatPrice(value) {
  return value.replace(/[^0-9.]/g, "");
}

function personalizedSearch() {
  let sku = $('#busca_sku').val();
  let product = $('#busca_produto').val();
  let marketplace = $('#busca_marketplace').val();
  let status = $('#busca_status').val();
  let situation = $('#busca_situacao').val();
  let integration = $('#busca_integracao').val();
  let estoque = $('#busca_estoque').val();
  let kit = $('#busca_kit').val();
  var lojas = [];
    $('#buscalojas  option:selected').each(function() {
        lojas.push($(this).val());
    });
	if (lojas == ''){lojas = ''}

    let colecoes = [];
    <?php if ($identifying_technical_specification && $identifying_technical_specification['status'] == 1) { ?>
        $('#buscacolecoes  option:selected').each(function () {
            colecoes.push($(this).val());
        });
    <?php } ?>
    if (colecoes.length === 0){colecoes = ''}

  if (marketplace.length) {
    document.getElementById('busca_integracao').removeAttribute('disabled');
  } else {
    document.getElementById('busca_integracao').setAttribute('disabled', 'disabled');
  }

  manageTable.destroy();
console.log(colecoes)
  manageTable = $('#manageTable').DataTable({
    "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "serverMethod": "post",
    'columnDefs': [{
      'targets': 0,
      'searchable': false,
      'orderable': false,
      'className': 'dt-body-center',
      'render': function (data, type, full, meta){
          return '<input type="checkbox" class="productsselect" name="id[]" value="' + $('<div/>').text(data).html() + '">';
      }
    }],
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'products/fetchProductData',
      data: {sku: sku, product: product, marketplace: marketplace, status: status, situation: situation, integration:integration, estoque: estoque, kit: kit , lojas: lojas, colecoes: colecoes},
      pages: 2, // number of pages to cache
    })
  });
  reloadFiltersExport({});
}

function clearFilters() {
  $('#busca_sku').val('');
  $('#busca_produto').val('');
  $('#busca_marketplace').val('');
  $('#busca_status').val('0');
  $('#busca_situacao').val('0');
  $('#busca_integracao').val('999');
  $('#busca_estoque').val('0');
  $('#busca_kit').val('0');
  $('#buscalojas').selectpicker('val', '');
  $('#buscacolecoes').selectpicker('val', '');

  personalizedSearch();
}

function changeFilter() {
  let text = document.getElementById('buttonCollapseFilter').innerHTML;
  if (text == 'Ocultar Filtros') {
    document.getElementById('buttonCollapseFilter').innerHTML = 'Exibir Filtros';
  } else {
    document.getElementById('buttonCollapseFilter').innerHTML = 'Ocultar Filtros';
  }
}

const reloadFiltersExport = (addFilters) => {
    $.extend(addFilters, {variation: false});
    setHrelButtom('exportProductsOnly', addFilters);
    $.extend(addFilters, {variation: true});
    setHrelButtom('exportProducts', addFilters);
}
const setHrelButtom = (id, adicional_param) => {
    const href = $('#' + id).attr('href');

    const splitHref = href.split('?');
    let filters = {};
    $('#collapseFilter input').each(function () {
        if (typeof $(this).attr('id') !== "undefined" && typeof $(this).val() !== "undefined" && $(this).val() != '') {
            var filter = {};
            if ($(this).attr('name').length > 0) {
                filter[$(this).attr('name')] = $(this).val();
            } else {
                filter[$(this).attr('id')] = $(this).val();
            }
            $.extend(filters, filter);
        }
    });
    $('#collapseFilter select').each(function () {
        if (typeof $(this).attr('id') !== "undefined" && typeof $(this).val() !== "undefined") {
            var filter = {};
            if ($(this).attr('name').length > 0) {
                if ($(this).val() == '0' && !($(this).attr('name') == 'integration')) return;

                if ($(this).attr('id') == 'buscalojas') {
                    filter['lojas'] = $(this).val();
                } else if ($(this).attr('id') == 'busca_marketplace') {
                    filter['marketplace'] = $(this).val();
                } else {
                    filter[$(this).attr('name')] = $(this).val();
                }
            } else {
                filter[$(this).attr('id')] = $(this).val();
            }
            $.extend(filters, filter);
        }
    });
    $.extend(filters, adicional_param);

    $.each(filters, function (filter, value) {
        return $.extend(filters, {
            [filter]: typeof value === 'string' ? encodeURIComponent(value) : value
        });
    });
    let new_href = splitHref[0] + '?' + $.param(filters);
    console.log(id, new_href);
    $('#' + id).attr('href', new_href);
}
</script>
