<!--
SW Serviços de Informática 2019

Editar Promoção 

-->
<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12">
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

          <div class="box">
            <div class="box-header">
              <h3 class="box-title"><?=$this->lang->line('application_update_information');?></h3>
            </div>
            <form role="form" id="formedit" action="<?php base_url('promotions/update') ?>" method="post">
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
       			<div class="form-group col-md-2 col-xs-2" <?php echo (form_error('start_date')) ? "has-error" : "";?>>
					<label for="start_date"><?=$this->lang->line('application_start_date');?>(*)</label>
					<div class='input-group date' id='start_date_pick' name="start_date_pick">
		                <input type='text' required class="form-control" id='start_date' name="start_date" autocomplete="off" value="<?php echo set_value('start_date',$promotion['start_date']);?>" />
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
						  <option value="00:00" <?php echo set_select('start_time', "00:00", ($promotion['start_time'] == "00:00")); ?>>00:00</option>
						  <option value="09:00" <?php echo set_select('start_time', "09:00", ($promotion['start_time'] == "09:00")); ?>>09:00</option>
						  <option value="13:00" <?php echo set_select('start_time', "13:00", ($promotion['start_time'] == "13:00")); ?>>13:00</option>
						  <option value="17:00" <?php echo set_select('start_time', "17:00", ($promotion['start_time'] == "13:00")); ?>>17:00</option>
						</select>
		          		<span class="input-group-addon">
		                    <span class="fa fa-clock-o"></span>
		                </span>
		           </div>
		        </div>
		        
		        <div class="form-group col-md-2 col-xs-2" <?php echo (form_error('end_date')) ? "has-error" : "";?>>
					<label for="end_date"><?=$this->lang->line('application_end_date');?>(*)</label>
					<div class='input-group date' id='end_date_pick' name="end_date_pick">
		                <input type='text' required class="form-control" id='end_date' name="end_date" autocomplete="off" value="<?php echo set_value('end_date',$promotion['end_date']);?>"/>
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
						  <option value="00:00" <?php echo set_select('end_time', "00:00", ($promotion['end_time'] == "00:00")); ?>>00:00</option>
						  <option value="09:00" <?php echo set_select('end_time', "09:00", ($promotion['end_time'] == "09:00")); ?>>09:00</option>
						  <option value="13:00" <?php echo set_select('end_time', "13:00", ($promotion['end_time'] == "13:00")); ?>>13:00</option>
						  <option value="17:00" <?php echo set_select('end_time', "17:00", ($promotion['end_time'] == "13:00")); ?>>17:00</option>
						</select>
		          		<span class="input-group-addon">
		                    <span class="fa fa-clock-o"></span>
		                </span>
		           </div>
		        </div>
			      		
				<div class="form-group col-md-3 col-xs-3">
		  			<label for="typepromo"><?=$this->lang->line('application_promotion_type');?>:</label>
		  			<div class='input-group' >
		  			 <?php if (($promotion['type'] == 1) && ($promotion['stock'] < 100)) { ?>
						<input id="typepromo" name="typepromo" type="hidden" value="1" >
					 	<input disabled readonly  type="checkbox" checked  data-toggle="toggle" data-on="<?=$this->lang->line('application_promotion_type_date');?>" data-off="<?=$this->lang->line('application_promotion_type_stock');?>" data-onstyle="success" data-offstyle="primary" >
					 <?php  } else { ?>
	       			 	<input id="typepromo" name="typepromo" type="checkbox" value="1" <?php echo set_checkbox('typepromo', '1', $promotion['type'] == '1'); ?>  data-toggle="toggle" data-on="<?=$this->lang->line('application_promotion_type_date');?>" data-off="<?=$this->lang->line('application_promotion_type_stock');?>" data-onstyle="success" data-offstyle="primary" >
					<?php  } ?>
					</div>
				</div>  
				
				<div class="form-group col-md-2 col-xs-2">
		  			<label><?=$this->lang->line('application_store');?>:</label>
		  			<div class='input-group' >
	       			 	<span><?=$promotion['store']?></span>
	       			 </div>
				</div> 
				
				<div class="form-group col-md-2 col-xs-2">
		  			<label><?=$this->lang->line('application_sku');?>:</label>
		  			<div class='input-group' >
	       			 	<span><?=$promotion['sku']?></span>
	       			 </div>
				</div> 
				<div class="form-group col-md-4 col-xs-4">
		  			<label><?=$this->lang->line('application_name');?>:</label>
		  			<div class='input-group' >
	       			<span ><?=$promotion['product']?></span>
	       			</div>
				</div> 

				<div class="row">
					<div class="form-group col-md-2">
	                    <label "><?=$this->lang->line('application_qty');?></label>
	                    <input type="text" readonly class = "form-control" id="stock" value="<?=$promotion['stock']?>" >
	                </div>
					<?php if ($promotion['type'] == 1) { $display = 'style="display: none"' ; } else { $display ='';} ?>
	                <div id="porestoque" class="form-group col-md-2 col-xs-2" <?php echo $display;?> <?php echo (form_error('qty')) ? "has-error" : "";?> />
			  			<label for="qty"><?=$this->lang->line('application_promotion_qty');?>:</label>
		       		    <input type="text" class="form-control"  style="text-align:right;"  id="qty" name="qty" value="<?php echo set_value('qty',$promotion['qty']); ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
						 <?php echo '<i style="color:red">'.form_error('qty').'</i>';  ?>
					</div>
                </div>

				<div class="form-group col-md-2 col-xs-2">
		  			<label><?=$this->lang->line('application_price_from');?>:</label>
	       		    <span style="text-align:right;" class = "form-control"><?php echo get_instance()->formatprice($promotion['price_from'])?></span>
				</div>
				<div class="form-group col-md-2 col-xs-2" <?php echo (form_error('price')) ? "has-error" : "";?>>
                  <label for="price"><?=$this->lang->line('application_price_sale');?></label>
                  <input type="text" class="form-control maskdecimal2" style="text-align:right;" id="price" name="price"  value="<?php echo set_value('price', $promotion['price']); ?>" autocomplete="off"  />
               	  <?php echo '<i style="color:red">'.form_error('price').'</i>';  ?>
                </div>
                <div class="form-group col-md-2 col-xs-2">
		  			<label><?=$this->lang->line('application_discount');?>:</label>
	       		    <span style="text-align:right;" ><?php echo number_format(100-$promotion['price']/$promotion['price_from']*100,2)?>%</span>
				</div>

              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
                <a href="<?php echo base_url('promotions/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              </div>
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

 $(document).ready(function() {
    
    if ($('#typepromo').is(":checked"))  {
	//	$("#porestoque").show();
	} else {
	//	$("#porestoque").hide(); 
		
	}; 
    
  	$("#typepromo").change(function () {
  		if ($('#typepromo').is(":checked"))  {
  			$("#porestoque").hide(); 
  		} else {
  			$("#porestoque").show();
  		}
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
					   
  });  


</script>
