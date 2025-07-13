
<!--
SW Serviços de Informática 2019

Criar Promoção

<script type='text/javascript' src="https://rawgit.com/RobinHerbots/jquery.inputmask/3.x/dist/jquery.inputmask.bundle.js"></script>

<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
--> 

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
		   <div class="alert alert-warning alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <b><?=$this->lang->line('messages_promotional_price_sent_all_marketplaces');?></b>
          </div>
          
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
        
         
   	   <button type="button" class="btn btn-primary" id="show_me" name="show_me"><i class="fa fa-filter"></i> <?=$this->lang->line('application_change_filter')?></button>
       
        <div class="box">
        
          <form id="formprocura" action="<?php base_url('promotions/create') ?>" method="post" enctype="multipart/form-data">
              <div class="box-body">
            <?php 
            if (validation_errors()) { 
	          foreach (explode("</p>",validation_errors()) as $erro) { 
		        $erro = trim($erro);
		        if ($erro!="") { ?>
				<div class="alert alert-error alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	                <?php echo $erro."</p>"; ?>
				</div>
		<?php	}
			  }
	       	} ?>
	       		<div>
					<h4><?=$this->lang->line('application_promotions');?></h4>
		  		</div>
		  		<div class="row">
			  		    
					<div class="form-group col-md-2 col-xs-2 <?php echo (form_error('start_date')) ? "has-error" : "";?>">
						<label for="start_date"><?=$this->lang->line('application_start_date');?>(*)</label>
						<div class='input-group date' id='start_date_pick' name="start_date_pick">
			                <input type='text' required class="form-control" id='start_date' name="start_date" autocomplete="off" value="<?php echo set_value('start_date');?>" />
			                <span class="input-group-addon">
			                    <span class="glyphicon glyphicon-calendar"></span>
			                </span>
			            </div>
			            <?php echo '<i style="color:red">'.form_error('start_date').'</i>';  ?>
			        </div>
			        <div class="form-group col-md-2 col-xs-2">
			        	<label for="start_time"><?=$this->lang->line('application_start_time');?>(*)</label>
						<div class='input-group time' >
			           		<select class="form-control" id="start_time" name="start_time">
							  <option value=""><?=$this->lang->line('application_select');?></option>
							  <option value="09:00" <?php echo set_select('start_time', "09:00"); ?>>09:00</option>
							  <option value="13:00" <?php echo set_select('start_time', "13:00"); ?>>13:00</option>
							  <option value="17:00" <?php echo set_select('start_time', "17:00"); ?>>17:00</option>
							</select>
			          		<span class="input-group-addon">
			                    <span class="fa fa-clock-o"></span>
			                </span>
			           </div>
			        </div>
			        
			        <div class="form-group col-md-2 col-xs-2 <?php echo (form_error('end_date')) ? "has-error" : "";?>">
						<label for="end_date"><?=$this->lang->line('application_end_date');?>(*)</label>
						<div class='input-group date' id='end_date_pick' name="end_date_pick">
			                <input type='text' required class="form-control" id='end_date' name="end_date" autocomplete="off" value="<?php echo set_value('end_date');?>"/>
			                <span class="input-group-addon">
			                    <span class="glyphicon glyphicon-calendar"></span>
			                </span>
			            </div>
			            <?php echo '<i style="color:red">'.form_error('end_date').'</i>';  ?>
			        </div>
			        <div class="form-group col-md-2 col-xs-2">
			        	<label for="end_time"><?=$this->lang->line('application_end_time');?>(*)</label>
						<div class='input-group time' >
			           		<select class="form-control" id="end_time" name="end_time">
							  <option value=""><?=$this->lang->line('application_select');?></option>
							  <option value="09:00" <?php echo set_select('start_time', "09:00"); ?>>09:00</option>
							  <option value="13:00" <?php echo set_select('start_time', "13:00"); ?>>13:00</option>
							  <option value="17:00" <?php echo set_select('start_time', "17:00"); ?>>17:00</option>
							</select>
			          		<span class="input-group-addon">
			                    <span class="fa fa-clock-o"></span>
			                </span>
			           </div>
			        </div>
			      
					<div class="form-group col-md-3 col-xs-3">
			  			<label for="typepromo"><?=$this->lang->line('application_promotion_type');?>:</label>
			  			<div class='input-group' >
		       			 <input id="typepromo" name="typepromo" type="checkbox" value="1" <?php echo set_checkbox('typepromo', '1', false); ?>  data-toggle="toggle" data-on="<?=$this->lang->line('application_promotion_type_date');?>" data-off="<?=$this->lang->line('application_promotion_type_stock');?>" data-onstyle="success" data-offstyle="primary" >
						</div>
					</div>  
				</div>
				
				<div hidden id="filters_hide">
		       		<div >
						<h4><?=$this->lang->line('application_set_filters');?></h4>
			  		</div>
			  		<div class="row">
			       		<div class="form-group col-md-2 col-xs-2">
		                  <label for="category"><?=$this->lang->line('application_categories');?>(*)</label> 
		                 </div>
						<div class="form-group col-md-9">
		                  <select class="form-control select_group" id="category" name="category" >
		                  	<option value=""><?=$this->lang->line('application_no_search');?></option>
		                    <?php foreach ($categories as $k => $v): ?>
		                      <option value="<?php echo $v['id'] ?>" <?php echo set_select('category', $v['id'], false); ?> ><?php echo $v['name'] ?></option>
		                    <?php endforeach ?>
		                  </select>
		                </div>
		                <!--<div class="form-group col-md-1">+</div>-->
	                </div>
	                <div class="row">
	                  <div class="form-group col-md-2 col-xs-2">
	                    <label for="brands"><?=$this->lang->line('application_brands');?>(*)</label>
	                  </div>
					   <div class="form-group col-md-4">
	                    <select class="form-control select_group" id="brands" name="brands" >
	                  	    <option value=""><?=$this->lang->line('application_no_search');?></option>
	                      <?php foreach ($brands as $k => $v): ?>
	                        <option value="<?php echo $v['id'] ?>" <?php echo set_select('brands', $v['id'], false); ?> ><?php echo $v['name'] ?></option>
	                      <?php endforeach ?>
	                    </select>
	                  </div>
	                  <!--<div class="form-group col-md-1">+</div>-->
	                </div>
					<?php 
					
					$filters_fields = Array (
						0 => array( 
							'id' => "sku", 
							'op' => array ('=','LIKE'),
							'label' => $this->lang->line('application_sku'), 
							'mask' => '',
						), 
						1 => array( 
							'id' => "product_name", 
							'op' => array ('=','LIKE'),
							'label' => $this->lang->line('application_name'), 
							'mask' => '',
						),
						2 => array( 
							'id' => "id", 
							'op' => array ('=', '>','<'),
							'label' => $this->lang->line('application_id'), 
							'mask' => '',
						), 
						3 => array( 
							'id' => "EAN", 
							'op' => array ('=','LIKE'),
							'label' => $this->lang->line('application_ean'), 
							'mask' => '',
						)
					);
					foreach ($filters_fields as $field) { ?>
					  <div class="row">
		                <div class="form-group col-md-2">
					    	<label><?php echo $field['label'];?></label>
						</div>
						<div class="form-group col-md-3">
					        <div>
					          <select type="text" class="form-control" id="<?php echo $field['id'];?>_op" name="<?php echo $field['id'];?>_op">
					            <option value="0" ><?=$this->lang->line('application_no_search')?></option>
					        <?php foreach ($field['op'] as $op) { ?>}    
					            <option value="<?=$op; ?>" <?php echo set_select($field['id'].'_op', $op, false); ?>><?=$op; ?></option>
					        <?php } ?>    
					          </select>
					        </div>
						</div>
						<div class="form-group col-md-4">
							<div>
							    <input type="text" class="form-control" id="<?php echo $field['id'];?>" name="<?php echo $field['id'];?>" value="<?php echo set_value($field['id']); ?>" autocomplete="off" <?php echo $field['mask'];?>/>
							</div>
						</div>
						<!-- <div class="form-group col-md-1">+</div>-->
					  </div>
					<?php } ?> 
  				</div>
		  </div>
          <!-- /.box-body -->
          <div class="box-footer">
            <button type="submit" id="buscar" class="btn btn-primary col-md-2"><?=$this->lang->line('application_search');?></button>
            <!-- button type="button" id="varprop" class="btn btn-primary col-md-2"><?=$this->lang->line('application_variantproperties');?></button -->
            <a href="<?php echo base_url('promotions/') ?>" class="btn btn-warning col-md-2"><?=$this->lang->line('application_back');?></a>
          </div>
          
          <?php 
           if ($this->session->userdata('createPromo')) {?> 
           	<?php if (count($promotions)> 0) {?> 
           	<div >
              	<h2><?=$this->lang->line('application_promotions_confirm');?></h2>
          		<div class="table-responsive">
          			<!---- < table class="table table-hover table-sm  w-100 d-block d-md-table">  ---->
          			 <table class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
		              <thead>
		              <tr>
		               	<th><?=$this->lang->line('application_store');?></th>
		                <th><?=$this->lang->line('application_promotion_type');?></th>
		                <th><?=$this->lang->line('application_sku');?></th>
		                <th><?=$this->lang->line('application_name');?></th>
		                <th width="80px"><?=$this->lang->line('application_price_from');?></th>
		                <th width="80px"><?=$this->lang->line('application_price_sale');?></th>
		                <th width="70px"><?=$this->lang->line('application_discount');?></th>
		                <th width="70px"><?=$this->lang->line('application_qty');?></th>
		                <th width="70px"><?=$this->lang->line('application_promotion_qty');?></th>
		                <th width="80px"><?=$this->lang->line('application_start_date');?></th>
		                <th width="80px"><?=$this->lang->line('application_end_date');?></th>
		                <th><?=$this->lang->line('application_action');?></th>
		              </tr>
		              </thead>
					  <tbody>
					      
					      <?php 
					      foreach($promotions as $promotion) { 
					      	$type = ($promotion['type']!=1) ? $this->lang->line('application_promotion_type_date') : $this->lang->line('application_promotion_type_stock') ; 
			
					      	?> 
							<tr> 	
					        <td><?php echo $promotion['store'];?></td>
					        <td><?php echo $type;?></td>
					        <td><?php echo $promotion['sku'];?></td>
					        <td><?php echo $promotion['product'];?></td>
					       	<td style="text-align:right;"><?php echo get_instance()->formatprice($promotion['price_from']);?></td>						
					       	<td style="text-align:right;"><?php echo get_instance()->formatprice($promotion['price']);?></td>	
					       	<td style="text-align:right;"><?php echo number_format((1-$promotion['price']/$promotion['price_from'])*100,2);?>%</td>	
					       	<td><?php echo $promotion['stock'];?></td>
					       	<td><?php echo $promotion['qty'];?></td>
					       	<td><?php echo date('d/m/Y h:i', strtotime($promotion['start_date']));?></td>
					       	<td><?php echo date('d/m/Y h:i', strtotime($promotion['end_date']));?></td>				
					       	<td>
					       		<button class="btn btn-primary" onclick="approvePromotion(event,<?=$promotion['id'];?>)" data-toggle="tooltip" title="<?=$this->lang->line('application_approve');?>"><i class="fa fa-check"></i></button>
					       		<button class="btn btn-warning" onclick="deletePromotion(event,<?=$promotion['id'];?>)" data-toggle="tooltip" title="<?=$this->lang->line('application_delete');?>"><i class="fa fa-trash-o"></i></button>
					       		
					       		</td>
					       </tr>
					      <?php  } ?>
	      
					   </tbody>
					 </table>
					 <div>
					  <button onclick="aproveAll(event, <?php echo $this->data['usercomp'];?>, <?php echo $this->data['userstore'];?>)" class="btn btn-primary col-md-2"><i class="fa fa-check"></i> <?=$this->lang->line('application_promotions_aproval');?></button>
					</div>
          		</div>
           </div>
           	<?php } ?>
         
          	<div >
              	<h2><?=$this->lang->line('application_products');?></h2>
          		<div class="table-responsive">
          			<!---- < table class="table table-hover table-sm  w-100 d-block d-md-table">  ---->
          			 <table class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
		              <thead>
		              <tr>
		              		<th><?=$this->lang->line('application_store');?></th>
		               		<th><?=$this->lang->line('application_sku');?></th>
					        <th><?=$this->lang->line('application_name');?></th>
					        <th><?=$this->lang->line('application_category');?></th>
					        <th width="70px"><?=$this->lang->line('application_qty');?></th>
					        <?php if ($porestoque) { ?>
					        	<th width="70px"><?=$this->lang->line('application_promotion_qty');?></th>
					        <?php } ?>
					        <th width="100px"><?=$this->lang->line('application_price_from');?></th>
					        <th width="100px"><?=$this->lang->line('application_price_sale');?></th>
					        <th width="70px"><?=$this->lang->line('application_action');?></th>
		              </tr>
		              </thead>
					  <tbody>
					      
					      <?php 
					      foreach($products as $product) { ?> 
							<tr> 
							 <td><?php echo $product['store'];?></td>
					        <td><?php echo $product['sku'];?></td>
					        <td><?=$product['name'];?></td>
					        <td><?=$product['category'];?></td>
					        <td width="70px">					 
					        	<input type="text" size="5" style="text-align:center;" readonly id="qty_ini_<?=$product['id'];?>" name="qty_ini_<?=$product['id'];?>" value="<?=$product['qty'];?>">					          
					        </td>
					        <?php if ($porestoque) { ?>
					           <td width="70px">
					        	<input type="text" class="form-control"  style="text-align:right;"  id="qty_<?=$product['id'];?>" name="qty_<?=$product['id'];?>" value="<?php echo set_value('qty_'.$product['id']); ?>" autocomplete="off" onKeyPress="return digitos(event, this);">
					           </td>
					        <?php } ?>
					        <td width="100px">
					        	<input type="text" size="5" style="text-align:right;" readonly id="price_ini_<?=$product['id'];?>" name="price_ini_<?=$product['id'];?>" value="<?php echo number_format($product['price'],2);?>">
					        </td>
					        <td width="100px">
					         	<input type="text" class="form-control maskdecimal2" style="text-align:right;" id="price_<?=$product['id'];?>" name="price_<?=$product['id'];?>"  value="<?php echo set_value('price_'.$product['id']); ?>" autocomplete="off"  />
							</td>
							<td width="70px"><button  class="btn btn-primary" onclick="createPromotion(event,<?=$product['id'];?>, <?=$product['store_id'];?>, <?=$product['company_id'];?> )" data-toggle="tooltip" title="<?=$this->lang->line('application_add');?>"><i class="fa fa-money"></i></button></td>
					       </tr>
					      <?php  } ?>
					      
					   </tbody>
					 </table>
          		</div>
          	</div>
          	
          	
          	<?php 
           }
          ?> 
          </form>
        </div>
        <!-- /.box -->
      </div>
      <!-- col-md-12 -->
    </div>
    <!-- /.row -->
    

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var base_url = "<?php echo base_url(); ?>";

  $(document).ready(function() {
    

    $("#show_me").click(function(e){
		e.preventDefault();
    	$("#filters_hide").toggle(500);

  	});
  	
  	$("#typepromo").change(function () {
        $("#formprocura").submit()
    });
    
    $('.maskdecimal2').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 2, 
		  max: 999999999999.99
		});
    
    $('.maskdecimal3').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 3, 
		  max: 9999999999.999
		});

    $("#mainPromotionsNav").addClass('active');
    $("#addPromotionNav").addClass('active');
    

    $('#start_date_pick').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(+new Date() + 86400000),
		todayBtn: true, 
		todayHighlight: true
	});
	$('#end_date_pick').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(+new Date() + 2*86400000),
		todayBtn: true, 
		todayHighlight: true
	});
    $("#start_date_pick").on("changeDate", function (e) {
    	var atual = new Date(e.date);
    	if (atual == "") {
    		$("#formprocura").submit();
    		return;
    	}
    	var maisum = new Date(atual.setTime(atual.getTime() + 1 * 86400000 )); 
        $('#end_date_pick').datepicker('setStartDate', maisum);
    });
    $("#end_date_pick").on("changeDate", function (e) {
    	var atual = new Date(e.date);
    	if (atual == "") {
    		$("#formprocura").submit();
    		return;
    	}
    	var menosum =  new Date(atual.setTime(atual.getTime() - 1 * 86400000 )); 
        $('#start_date_pick').datepicker('setEndDate',menosum);
    });
    $("#end_date").change(function () {
       if ($(this).val() == "") {
        	$("#formprocura").submit()
       }
    });
    $("#start_date").change(function () {
       if ($(this).val() == "") {
        	$("#formprocura").submit()
       }
    });
					   
  });  
  
    function approvePromotion(e, promotion_id) {
  	e.preventDefault();
  	
  	$.ajax({
        type: "POST",
        data: {
            id_approve: promotion_id
        },
        url: base_url+"promotions/approvePromotion",
        dataType: "json",
        async: true,
        success: function(data) {       
        }
    }); 
  	document.getElementById('formprocura').submit();
  }
  
  function aproveAll(e, company_id, store_id) {
  	e.preventDefault();
  	
  	$.ajax({
        type: "POST",
        data: {
            company_id: company_id,
            store_id: store_id
        },
        url: base_url+"promotions/aproveAllCreation",
        dataType: "json",
        async: true,
        success: function(data) {       
        }
    }); 
  	window.location.href = base_url+"promotions/";
  }

  function deletePromotion(e, promotion_id) {
  	e.preventDefault();
  	
  	$.ajax({
        type: "POST",
        data: {
            id: promotion_id
        },
        url: base_url+"promotions/deletePromotionCreation",
        dataType: "json",
        async: true,
        success: function(data) {       
        }
    }); 
  	document.getElementById('formprocura').submit();
  }
  
  function createPromotion(e, productId, storeId, companyId) {
  	e.preventDefault();
  	var price = document.getElementById("price_"+productId).value;
  	var price_ini = document.getElementById("price_ini_"+productId).value;
  	var price_ini = price_ini.replace(',','');
  	var price_ini = parseFloat(price_ini);
  	var tipo = document.getElementById("typepromo").checked; 
  	
  	if (!tipo) {
  		var qty = document.getElementById("qty_"+productId).value;
  		var qty_ini = parseInt(document.getElementById("qty_ini_"+productId).value);
  		if (qty == "") {
	  		document.getElementById("qty_"+productId).focus();
			document.getElementById("qty_"+productId).select();
  			return;
	  	}
	  	var qty = parseInt(qty);
	  	if (qty > qty_ini) {
	  		//alert("A quantidade em promoção não pode ser maior que o estoque do produto");
	  		Swal.fire({
				  icon: 'error',
				  title: "A quantidade em promoção não pode ser maior que o estoque do produto."
				}).then((result) => {
					document.getElementById("qty_"+productId).focus();
					document.getElementById("qty_"+productId).select();
				});
	  		document.getElementById("qty_"+productId).focus();
			document.getElementById("qty_"+productId).select();
	  		return;
	  	}
	  	if (qty < 50) {
	  		//alert("A quantidade mínima de produtos em promoção é de 50 unidades.");
	  		Swal.fire({
				  icon: 'error',
				  title: "A quantidade mínima de produtos em promoção é de 50 unidades."
				}).then((result) => {
					document.getElementById("qty_"+productId).focus();
					document.getElementById("qty_"+productId).select();
				});
			document.getElementById("qty_"+productId).focus();
			document.getElementById("qty_"+productId).select(); 		
	  		return;
	  	}
  	}
  	if (price == "") {
  		document.getElementById("price_"+productId).focus();
		document.getElementById("price_"+productId).select();
  		return;
  	}
  	var price =parseFloat(price);
  	if (price >= price_ini) {
  		//alert("O preço do produto em promoção deve ser menor que o preço original do produto");
  		Swal.fire({
				  icon: 'error',
				  title: "O preço do produto em promoção deve ser menor que o preço original do produto"
				}).then((result) => {
					document.getElementById("price_"+productId).focus();
					document.getElementById("price_"+productId).select();
				});
  		document.getElementById("price_"+productId).focus();
		document.getElementById("price_"+productId).select();
  		return;
  	}
  	if (price <= 0) {
  		//alert("O preço do produto em promoção não pode ser zero");
  		Swal.fire({
				  icon: 'error',
				  title: "O preço do produto em promoção não pode ser zero"
				}).then((result) => {
					document.getElementById("price_"+productId).focus();
					document.getElementById("price_"+productId).select();
				});
  		document.getElementById("price_"+productId).focus();
		document.getElementById("price_"+productId).selec
  		return;
  	}
  //	alert("product "+productId+" preco "+price+" inicial "+price_ini);
  	
  	const url = base_url+"Api/V1/createPromotion";
  	if (!tipo) {
		 var data = {
				"product_id" : productId,
				"type" : tipo,
				"start_date" : document.getElementById("start_date").value,
				"start_time" :document.getElementById("start_time").value, 
				"end_date" : document.getElementById("end_date").value, 
				"end_time" : document.getElementById("end_time").value,
				"qty" : qty,
				"price" : price,
				"store_id" : storeId,
				"company_id" : companyId
	            };
       }
    else {
    	var data = {
				"product_id" : productId,
				"type" : tipo,
				"start_date" : document.getElementById("start_date").value, 
				"start_time" :document.getElementById("start_time").value,
				"end_date" : document.getElementById("end_date").value,
				"end_time" : document.getElementById("end_time").value,
				"price" : price,
				"store_id" : storeId,
				"company_id" : companyId
	            };
    }
//    alert (JSON.stringify(data)); 
	if(self.fetch) { 
		var other_params = {
	        headers : { "content-type" : "application/json; charset=UTF-8"},
	        body : JSON.stringify(data),
	        method : "POST"
	    };
	    fetch(url, other_params)
	            .then(function(response) {
	            if (response.ok) {
	            	//alert('Ok');
	            	//alert ( response.json());
	                return response.json();
	            } else {
	            	//alert('RUIM 1');
	            	//alert ("Erro ao gravar promoção: "+response.statusText);
	            	Swal.fire({
					  icon: 'error',
					  title: "Erro ao gravar promoção: "+response.statusText
					});
	                throw new Error("Could not reach the API: " + response.statusText);
	            }
	        }).then(function(response) {
	        //	alert("Promoção criada"); 
				document.getElementById('formprocura').submit();
	        }).catch(function(error) {
	        	//alert("Erro ao gravar promoção: "+error.message); 
	        	Swal.fire({
					  icon: 'error',
					  title: "Erro ao gravar promoção: "+error.message
					});
	            //document.getElementById("message").innerHTML = error.message;
	        });
	        return true;
	 } else {
	 	var xhttp = new XMLHttpRequest();
	    xhttp.onreadystatechange = function() {
	         if (this.readyState == 4 && this.status == 200) {
	             //alert(this.responseText);
	             Swal.fire({
					  icon: 'success',
					  title: this.responseText
					});
	         }
	    };
	    xhttp.open("POST",url, false);
	    xhttp.setRequestHeader("Content-type", "application/json");
	    xhttp.send(JSON.stringify(data));
	 }
  }
</script>