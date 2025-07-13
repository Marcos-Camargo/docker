<?php
// Redirecionamento temporário, relativo à LOG-457.
redirect('dashboard', 'refresh');
?>

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data); ?>
  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">
        <div id="messages"></div>          
        <?php if($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('success'); ?>
          </div>
        <?php elseif($this->session->flashdata('error')): ?>
          <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('error'); ?>
          </div>
        <?php endif; ?>
		<form id="formCreatePromotionLogistic" action="<?php echo base_url('PromotionLogistic/update/' . $promoId) ?>" method="post" enctype="multipart/form-data">
			<div class="box box-primary">
				<div class="box-header">
					<h3 class="box-title">Informações</h3>
				</div>        	
				<div class="box-body">            
					<div class="row">
						<div class="form-group col-md-4">
							<label for="name"><?=$this->lang->line('application_promotion_logistic_name');?></label>
							<input type="text" class="form-control" id="name" name="info[name]" required placeholder="<?=$this->lang->line('application_promotion_logistic_name')?>" autocomplete="off" value="<?php echo $promo['info']['name'];?>">
			            </div>						
						<div class="form-group col-md-2">
							<label for="name"><?=$this->lang->line('application_promotion_logistic_dt_start');?></label>							
							<div class='input-group date col-md-12' >
								<input type='text' required class="form-control" id='dt_start' name="info[dt_start]" onblur="checkDate();" autocomplete="off" value="<?php echo $promo['info']['dt_start'];?>" />
								<span class="input-group-addon">
									<span class="glyphicon glyphicon-calendar"></span>
								</span>
							</div>
						</div>
						<div class="form-group col-md-2">
							<label for="name"><?=$this->lang->line('application_promotion_logistic_start_hour');?></label>							
							<div class='input-group date col-md-12'>
								<input type='time' class="form-control" id='start_hour' name="info[start_hour]" onblur="checkDate();" value="<?php echo $promo['info']['start_hour'];?>" required autocomplete="off" min="00:00" max="23:59"/>
							</div>
						</div>

						<div class="form-group col-md-2">
							<label for="name"><?=$this->lang->line('application_promotion_logistic_dt_end');?></label>							
							<div class='input-group date col-md-12'>
								<input type='text' class="form-control" id='dt_end' name="info[dt_end]" onblur="checkDate();" autocomplete="off" required value="<?php echo $promo['info']['dt_end'];?>" />
								<span class="input-group-addon">
									<span class="glyphicon glyphicon-calendar"></span>
								</span>
							</div>
						</div>
						<div class="form-group col-md-2">
							<label for="name"><?=$this->lang->line('application_promotion_logistic_end_hour');?></label>							
							<div class='input-group date col-md-12'>
								<input type='time' class="form-control" id='end_hour' name="info[end_hour]" onblur="checkDate();" autocomplete="off" required value="<?php echo $promo['info']['end_hour'];?>" min="00:00" max="23:59"/>
							</div>
						</div>
					</div>				
				</div>				
			</div>
			<div class="box box-primary">
				<div class="box-header">
					<h3 class="box-title">Regras</h3>
				</div>				
				<div class="box-body">
					<div class="row">
						<div class="form-group col-md-12">
							<label for="name"><?=$this->lang->line('application_promotion_type');?></label>
							<select class="form-control select_group" id="type" required name="rules[type]">
								<option value=""><?=$this->lang->line('application_promotion_type_select');?></option>
								<?php foreach ($promotion['type'] as $k => $v): ?>
									<?php if($promo['rule'] == $k) { ?>
										<option value="<?php echo $k ?>" selected ><?php echo $v ?></option>
									<?php  } else { ?>
										<option value="<?php echo $k ?>"><?php echo $v ?></option>
									<?php }?>									
								<?php endforeach ?>
							</select>
						</div>						
					</div>
				</div>
			</div>
			<div class="box box-primary">
				<div class="box-header">
					<h3 class="box-title">Critério</h3>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="form-group col-md-2">
							<label for="name"><?=$this->lang->line('application_promotion_desc_type');?></label>
							<select class="form-control select_group" required id="type_desc" name="criterion[type]">
								<option value=""><?=$this->lang->line('application_promotion_desc_type_descont');?></option>
								<?php foreach ($promotion['type_desc'] as $k => $v): ?>
									<?php if($promo['criterion']['criterion_type'] == $k) { ?>
										<option value="<?php echo $k ?>" selected ><?php echo $v ?></option>
									<?php  } else { ?>
										<option value="<?php echo $k ?>"><?php echo $v ?></option>
									<?php }?>
								<?php endforeach ?>
							</select>
						</div>
						<div class="form-group col-md-2">
							<label for="name"><?=$this->lang->line('application_promotion_desc_type_value');?></label>
							<input type="number" min="0" max="99999" step="0.01" class="form-control two-decimals" id="criterion_price" name="criterion[price]" required placeholder="<?=$this->lang->line('application_promotion_desc_type_value')?>" autocomplete="off" value="<?php echo $promo['criterion']['price_type_value'];?>">
						</div>						
						<div class="form-group col-md-3">
							<label for="name"><?=$this->lang->line('application_promotion_prod_mim_value');?></label>
							<input type="number" min="0" max="99999" step="0.01" class="form-control two-decimals" id="criterion_price_mim" name="criterion[price_mim]" required placeholder="<?=$this->lang->line('application_promotion_prod_mim_value')?>" autocomplete="off" value="<?php echo $promo['criterion']['product_value_mim'];?>">
						</div>						
						<div class="form-group col-md-2">
							<label for="name"><?=$this->lang->line('application_promotion_prod_qt');?></label>
							<input type="number" min="0" max="99999" step="1" class="form-control" onkeyup="this.value=this.value.replace(/[^0-9]/g,'');" id="product_amount" name="criterion[amount]" required placeholder="<?=$this->lang->line('application_promotion_prod_qt')?>" autocomplete="off" value="<?php echo $promo['criterion']['produtct_amonut'];?>">
						</div>
						<div class="form-group col-md-3">
							<label for="name"><?=$this->lang->line('application_promotion_prod_region');?></label>
							<div class="col-md-12 no-padding">
								<input type="radio" name="criterion[region]" id="regionAll" required onchange="checkRegion(0)" <?php echo ( $promo['criterion']['region'] == 0 ) ? "checked" : "" ;?> value="0">
								<label for="regionAll">Para todo Brasil</label><br>
								<input type="radio" name="criterion[region]" id="regionToState" required onchange="checkRegion(1)" <?php echo ( $promo['criterion']['region'] == 1 ) ? "checked" : "" ;?> value="1">
								<label for="regionToState">Por Região</label><br>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="box box-primary">
				<div class="box-header">
					<h3 class="box-title">Selecione as regiões participantes</h3>
				</div>
				<div class="box-body">
					<div class="row" id="checkStateByRegion">
						<?php foreach($region as $key => $value ) { ?>
							<div class="form-group col-md-2">								
								<div class="form-check">
									<h3 class="form-check-label"><?php echo $value['name'];?></h3>
								</div>
								<?php foreach($value['state'] as $keyState => $valueState ) { ?>
									<div class="form-check">
										<input class="form-check-input" type="checkbox" onchange="setRegion();" id="states<?=$valueState['cod_uf']?>" <?php echo ( in_array($valueState['cod_uf'], $promo['region'])) ? "checked" : "" ;?> name="region[states][]" value="<?php echo $valueState['cod_uf'];?>">
										<label for="states<?=$valueState['cod_uf']?>" class="form-check-label"><?php echo $valueState['estado'];?></label>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= $this->lang->line('application_criteria_participate'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-3 col-xs-3">
                            <label for="segment"><?=lang('application_campaign_segment_by')?> *</label>
                            <select name="segment" id="segment" class="form-control">
                                <option value=""><?=lang('application_select')?></option>
                                <option value="category" <?=set_select('segment', 'category', 'category' == $promo['segment'])?>><?=lang('application_category')?></option>
                                <option value="store" <?=set_select('segment', 'store', 'store' == $promo['segment'])?>><?=lang('application_store')?></option>
                                <option value="product" <?=set_select('segment', 'product', 'product' == $promo['segment'])?>><?=lang('application_product')?></option>
                            </select>
                        </div>
                    </div>
                    <div class="row" id="parent-category">
                        <div class="form-group col-md-7">
                            <label for="category"><?=$this->lang->line('application_promotion_category');?></label>
                            <select class="form-control selectpicker show-tick" id="category" name ="category[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                                <?php foreach ($categories as $k => $v): ?>
                                    <option value="<?php echo $v['id']; ?>" <?=set_select('category', $v['id'], in_array($v['id'], $promo['categories'])) ?>><?php echo $v['name']; ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-5 no-padding">
                            <div class="form-group col-md-12">
                                <div class="d-flex justify-content-between">
                                    <label for="import_categories"><?=$this->lang->line('application_upload_categories_csv_to_massive_import');?></label>
                                    <a href="<?=base_url('assets/files/promotion_logistic_sample_categories.csv') ?>"><?=lang('application_download_sample');?></a>
                                </div>
                                <div class="input-group">
                                    <input type="file" name="fileCategory" id="import_categories" class="form-control" />
                                    <div class="input-group-addon">
                                        <button type="button" class="btn btn-outline-secondary" id="btnImportCategories" style="line-height: 0.4; padding: 0;"><?=lang('application_send');?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="parent-store">
                        <div class="form-group col-md-7">
                            <label for="category"><?=$this->lang->line('application_promotion_stores');?></label>
                            <select class="form-control selectpicker show-tick" id="store" name ="store[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                                <?php foreach ($stores as $k => $v): ?>
                                    <option value="<?php echo $v['id']; ?>" <?=set_select('store', $v['id'], in_array($v['id'], $promo['stores'])) ?>><?php echo $v['name']; ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-5 no-padding">
                            <div class="form-group col-md-12">
                                <div class="d-flex justify-content-between">
                                    <label for="import_stores"><?=$this->lang->line('application_upload_stores_csv_to_massive_import');?></label>
                                    <a href="<?=base_url('assets/files/promotion_logistic_sample_stores.csv') ?>"><?=lang('application_download_sample');?></a>
                                </div>
                                <div class="input-group">
                                    <input type="file" name="fileStore" id="import_stores" class="form-control" />
                                    <div class="input-group-addon">
                                        <button type="button" class="btn btn-outline-secondary" id="btnImportStores" style="line-height: 0.4; padding: 0;"><?=lang('application_send');?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="parent-product">
                        <div class="row">
                            <div class="form-group col-md-7">
                                <label for="product_search"><?=$this->lang->line('application_promotion_products');?></label>
                                <div class="input-group">
                                    <input class="form-control" id="product_search" name="product_search" placeholder="<?=$this->lang->line('application_inform_sku_name_description_product');?>"/>
                                    <span class="input-group-addon cursor-pointer" id="btn_product_search"><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                                </div>
                            </div>
                            <div class="col-md-5 no-padding">
                                <div class="form-group col-md-12">
                                    <div class="d-flex justify-content-between">
                                        <label for="import_products"><?=$this->lang->line('application_upload_products_csv_to_massive_import');?></label>
                                        <a href="<?=base_url('assets/files/promotion_logistic_sample_products.csv') ?>"><?=lang('application_download_sample');?></a>
                                    </div>
                                    <div class="input-group">
                                        <input type="file" name="fileProduct" id="import_products" class="form-control" />
                                        <div class="input-group-addon">
                                            <button type="button" class="btn btn-outline-secondary" id="btnImportProducts" style="line-height: 0.4; padding: 0;"><?=lang('application_send');?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <h3><?=lang('application_products_found')?></h3>
                                <table class="table" id="products-to-select">
                                    <thead>
                                    <tr>
                                        <th><?=lang('application_id')?></th>
                                        <th><?=lang('application_name')?></th>
                                        <th><?=lang('application_sku')?></th>
                                        <th><?=lang('application_price')?></th>
                                        <th><?=lang('application_stock')?></th>
                                        <th><?=lang('application_store')?></th>
                                        <th><?=lang('application_action')?></th>
                                    </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <h3><?=lang('application_products_selected')?></h3>
                                <table class="table" id="products-selected">
                                    <thead>
                                    <tr>
                                        <th><?=lang('application_id')?></th>
                                        <th><?=lang('application_name')?></th>
                                        <th><?=lang('application_sku')?></th>
                                        <th><?=lang('application_price')?></th>
                                        <th><?=lang('application_stock')?></th>
                                        <th><?=lang('application_store')?></th>
                                        <th><?=lang('application_action')?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($promo['products'] as $k => $v): ?>
                                        <tr product-selected-id="<?=$v['id']?>">
                                            <td><?=$v['id']?></td>
                                            <td><?=$v['name']?></td>
                                            <td><?=$v['sku']?></td>
                                            <td><?=$v['price']?></td>
                                            <td><?=$v['qty']?></td>
                                            <td><?=$v['store_name']?></td>
                                            <td>
                                                <input type="checkbox" class="productStatus <?=$v['active_status'] == 1 ? '' : 'product-disabled'?>" <?=$v['active_status'] == 1 ? 'checked' : 'disabled'?> data-bootstrap-switch>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box-primary">
                <div class="box-footer d-flex justify-content-between flex-wrap">
                    <a href="<?php echo base_url('PromotionLogistic'); ?>" class="btn btn-warning col-md-3"><?=$this->lang->line('application_back');?></a>
                    <button type="submit" id="sendSave" class="btn btn-success col-md-3"><?=$this->lang->line('application_save');?></button>
                </div>
            </div>
            <input type="hidden" name="products_selected" id="products_selected" value="">
            <input type="hidden" name="products_disabled" id="products_disabled" value="">
        </form>
      </div>
    </div>
  </section>
</div>

<style>
    .select2-container--default .select2-selection--multiple .select2-selection__choice{
        color: #000;
    }
    .box-footer:before, .box-footer:after {
        display: none;
    }
</style>

<script type="text/javascript">

const base_url = "<?=base_url()?>";
let tableProductsToSelect;
let tableProductsSelected;
let productsSelected = [];
let productsDisabled = [];

function checkRegion(check) {
	if(check == 0 ) {
		$("input[type=checkbox]").prop("checked", true);
	} else {
		$("input[type=checkbox]").prop("checked", false);
	}
}

function setRegion() {
    let allChecked = true;

    $('#checkStateByRegion input[type="checkbox"]').each(function (){
        if ($(this).is(':not(:checked)')) allChecked = false;
    });

    $(allChecked ? "#regionAll" : "#regionToState").prop("checked", true);
}

function convertDate(dateUser, hour) {
    return moment(dateUser, "DD/MM/YYYY").format("YYYY-MM-DD") + ' ' + hour;
}

function checkDate() {
    const dt_start      = $("#dt_start").val();
    const start_hour    = $("#start_hour").val();
    const dt_end        = $("#dt_end").val();
    const end_hour      = $("#end_hour").val();

	// time de atraso pois quando clica no calendario o evento do blur é antes da atualização do input
	setTimeout(() => {
        if(dt_start !== "" && start_hour !== "" && end_hour !== "" && dt_end !== "") {
            validaDatas();
        } else {
            $("#messages").html('');
            $("#sendSave").prop("disabled", false);
        }
        console.log('checkDate');
    }, 500);
}

function validaDatas() {
	$("#messages").html('');

    var dtStart = new Date(convertDate($("#dt_start").val(),$("#start_hour").val()));
    var dtEnd   = new Date(convertDate($("#dt_end").val(),$("#end_hour").val()));

    console.log(convertDate($("#dt_start").val(),$("#start_hour").val()),convertDate($("#dt_end").val(),$("#end_hour").val()));


    if (!dtEnd || !dtStart) {
		$("#dt_start").focus();
		$("#sendSave").prop("disabled", true);
	}

	if ( dtEnd.getTime() < dtStart.getTime() ) {
        Toast.fire({
            icon: 'warning',
            title: 'Data e hora final não podem ser menor que data início.'
        });
		$("#sendSave").prop("disabled", true);
    } else if ( dtEnd.getTime() === dtStart.getTime() ) {
        Toast.fire({
            icon: 'warning',
            title: 'As datas e horas não podem ser iguais.'
        });
		$("#sendSave").prop("disabled", true);
    } else {
		$("#sendSave").prop("disabled", false);
		$("#messages").html('');
    }
	console.log('ValidaDatas');
}

const changeStatusButtons = (disabled = true) => {
    $('.btnRmProduct, .btnAddProduct').prop('disabled', disabled);
    $('.productStatus:not(.product-disabled)').bootstrapSwitch('disabled', disabled);
}

const filterTableProductToSelect = searchText => {
    tableProductsToSelect.rows().remove().draw();

    searchText = $.trim(searchText);

    if (searchText.length === 0) {
        return false;
    }

    changeStatusButtons(true);

    const valueMin  = parseFloat($('#criterion_price_mim').val());
    const qtyMin    = parseInt($('#product_amount').val());

    if (!checkPromotionToAddProduct()) {
        return false;
    }

    $.ajax({
        url: `${base_url}PromotionLogistic/fetchProductToSelect`,
        type: 'POST',
        data: { valueMin, qtyMin, searchText, productNotIn: productsSelected },
        dataType: 'json',
        success: function(response) {
            tableProductsToSelect.rows().remove().rows.add(response).draw();

            setTimeout(() => { $('[data-toggle="tooltip"]').tooltip() }, 500);
        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }, complete: () => {
            changeStatusButtons(false);
        }
    });
}

const checkPromotionToAddProduct = () => {
    const valueMin  = parseFloat($('#criterion_price_mim').val());
    const qtyMin    = parseInt($('#product_amount').val());

    if (isNaN(qtyMin)) {
        Swal.fire({
            icon: 'warning',
            title: 'informe a quantidade mínima para os produtos',
            confirmButtonText: "Ok",
        });
        return false;
    }
    if (isNaN(valueMin)) {
        Swal.fire({
            icon: 'warning',
            title: 'informe o valor mínimo para os produtos',
            confirmButtonText: "Ok",
        });
        return false;
    }

    return true;
}

$(document).ready(function() {

	$("#mainLogisticsNav").addClass('active');
    $("#logisticPromotionAdminNav").addClass('active');

	//$('#category').select2();

	$("#criterion_price").on("change",function(){
	   $(this).val(parseFloat($(this).val()).toFixed(2));
	});

	$("#criterion_price_mim").on("change",function(){
	   $(this).val(parseFloat($(this).val()).toFixed(2));
	});

	$('#dt_start').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR",
		//startDate: new Date(+new Date() + 86400000),
		todayBtn: true,
		todayHighlight: true
	});

	$('#dt_end').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR",
		//startDate: new Date(+new Date() + 86400000),
		todayBtn: true,
		todayHighlight: true
	});
	checkDate();

    $('#segment').trigger('change');

    $("input, select").each(function() {
        let text;
        if ($(this).is(':required')) {
            text = $(this).closest('div.form-group').find('label:eq(0)').text();
            if (text.indexOf('*') === -1) {
                $(this).closest('div.form-group').find('label:eq(0)').text(text + ' (*)');
            }
        }
    });

  $("input[data-bootstrap-switch]").each(function(result){
        $(this).bootstrapSwitch({size: "small"});
    });

    $('#products-selected tbody tr').each(function(){
        const product = parseInt($(this).attr('product-selected-id'));
        productsSelected.push(product);
    });

    tableProductsToSelect = $('#products-to-select').DataTable({
        language: { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
        destroy: true,
        processing: true
    });

    tableProductsSelected = $('#products-selected').DataTable({
        language: { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
        destroy: true,
        processing: true
    });
});

$(document).on('click', '.btnAddProduct', function() {
    changeStatusButtons(true);
    const product   = parseInt($(this).attr('product-id'));
    const valueMin  = parseFloat($('#criterion_price_mim').val());
    const qtyMin    = parseInt($('#product_amount').val());

    $.ajax({
        url: `${base_url}PromotionLogistic/selectProductCreatePromotion`,
        type: 'POST',
        data: { valueMin, qtyMin, product },
        dataType: 'json',
        success: function(response) {
            Toast.fire({
                icon: response.success ? 'success' : 'error',
                title: response.message
            });

            if (response.success) {
                $('[data-toggle="tooltip"]').tooltip('destroy');
                // tableProductsSelected.row.add($(`[product-id="${product}"]`).parents('tr')).draw();

                $(`button[product-id="${product}"]`)
                    .toggleClass('btnAddProduct btnRmProduct')
                    .prop('title', 'Remover Produto')
                    .prop('disabled', false)
                    .find('i')
                    .toggleClass('fa-plus fa-minus');

                let row = tableProductsToSelect.row($(`[product-id="${product}"]`).parents('tr'));
                let rowNode = row.node();
                row.remove().draw(false);
                tableProductsSelected.row.add( rowNode ).draw(false);

                productsSelected.push(product);

                setTimeout(() => {
                    $('[data-toggle="tooltip"]').tooltip();
                }, 500);
            }

        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }, complete: () => {
            changeStatusButtons(false);
        }
    });
});

$(document).on('click', '.btnRmProduct', function(){
    const product = parseInt($(this).attr('product-id'));
    tableProductsSelected.row($(`[product-id="${product}"]`).parents('tr')).remove().draw(false);

    productsSelected = productsSelected.filter(item => item !== product);
    $('#btn_product_search').trigger('click');

    Toast.fire({
        icon: 'success',
        title: 'Produto removido.'
    });
});

$('#segment').on('change', function(){
    const segment = $(this).val();

    $('[id^=parent-]').hide();
    $(`#parent-${segment}`).show();

    $('#products_selected').prop('required', segment === 'product');
});

$('#btnImportStores').on('click', function(){
    const fileStores = $('#import_stores').prop('files')[0];

    if (typeof fileStores === 'undefined') {
        return false;
    }

    let dataForm = new FormData();
    dataForm.append('file', fileStores);

    $.ajax({
        url: `${base_url}PromotionLogistic/formatStoreByCSV`,
        type: 'POST',
        data: dataForm,
        dataType: 'json',
        enctype: 'multipart/form-data',
        processData:false,
        contentType:false,
        success: function(response) {

            Toast.fire({
                icon: response.success ? 'success' : 'error',
                title: response.message
            });

            if (response.success) {
                let stores = $('[name="store[]"]').val();
                $(response.additional).each(function(key, value) {
                    stores.push(value);
                });
                $('[name="store[]"]').selectpicker('val', stores)
            }
        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }, complete: () => {
            $('#import_stores').val('');
        }
    });
});

$('#btnImportCategories').on('click', function(){
    const fileCategories = $('#import_categories').prop('files')[0];

    if (typeof fileCategories === 'undefined') {
        return false;
    }

    let dataForm = new FormData();
    dataForm.append('file', fileCategories);

    $.ajax({
        url: `${base_url}PromotionLogistic/formatCategoryByCSV`,
        type: 'POST',
        data: dataForm,
        dataType: 'json',
        enctype: 'multipart/form-data',
        processData:false,
        contentType:false,
        success: function(response) {

            Toast.fire({
                icon: response.success ? 'success' : 'error',
                title: response.message
            });

            if (response.success) {
                let categories = $('[name="category[]"]').val();
                $(response.additional).each(function(key, value) {
                    categories.push(value);
                });
                $('[name="category[]"]').selectpicker('val', categories)
            }
        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }, complete: () => {
            $('#import_categories').val('');
        }
    });
});

$('#btnImportProducts').on('click', function(){
    const fileProducts  = $('#import_products').prop('files')[0];
    const valueMin      = $('#criterion_price_mim').val();
    const qtyMin        = $('#product_amount').val();

    if (!checkPromotionToAddProduct()) {
        return false;
    }

    if (typeof fileProducts === 'undefined') {
        return false;
    }

    let dataForm = new FormData();
    dataForm.append('file', fileProducts);
    dataForm.append('valueMin', valueMin);
    dataForm.append('qtyMin', qtyMin);
    dataForm.append('productNotIn', productsSelected);

    $.ajax({
        url: `${base_url}PromotionLogistic/formatProductByCSV`,
        type: 'POST',
        data: dataForm,
        dataType: 'json',
        enctype: 'multipart/form-data',
        processData:false,
        contentType:false,
        success: function(response) {

            if (response.additional.length) {
                Swal.fire({
                    icon: response.success ? 'warning' : 'error',
                    title: response.message,
                    width: 600,
                    html: '<ol><li>' + response.additional.join('</li><li>') + '</li></ol>',
                    showCancelButton: false,
                    confirmButtonText: "Ok",
                });
            } else {
                Toast.fire({
                    icon: response.success ? 'success' : 'error',
                    title: response.message
                });
            }

            if (response.products.length) {
                $(response.products).each(function(key, value){
                    productsSelected.push(parseInt(value[0]));
                });
                tableProductsSelected.rows.add(response.products).draw();
                $('#btn_product_search').trigger('click');
            }

        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }, complete: () => {
            $('#import_products').val('');
        }
    });
});

$('#product_search').on('change', function(){
    const searchText = $(this).val();
    filterTableProductToSelect(searchText);
});

$('#btn_product_search').on('click', function(){
    const searchText = $('#product_search').val();
    filterTableProductToSelect(searchText);
});

$('#formCreatePromotionLogistic').submit(function() {
    if (productsSelected.length === 0 && $('#segment').val() === 'product') {
        Swal.fire({
            icon: 'error',
            title: 'Nenhum produto selecionado',
            showCancelButton: false,
            confirmButtonText: "Ok",
        });
        return false;
    }
    $('#products_selected').val(productsSelected);
    $('#products_disabled').val(productsDisabled);
});

$(document).on('switchChange.bootstrapSwitch', 'input[type="checkbox"].productStatus', function(){
    const product = parseInt($(this).closest('tr').attr('product-selected-id'));

    if ($(this).is(':checked')) {
        productsDisabled = productsDisabled.filter(item => item !== product);
    } else {
        productsDisabled.push(product);
    }
})

</script>

