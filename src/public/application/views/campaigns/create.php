<!--
SW Serviços de Informática 2019

Editar Promoção 


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
            <form role="form" id="formedit" action="<?php base_url('campaigns/create') ?>" method="post">
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
	       		<?php 
                  		$readonly = "";
						$disabled = "";
						if ($campaign['active'] == 1) {
							$readonly = " readonly "; 
							$disabled = " disabled ";
							$status = '<span class="label label-success">'. $this->lang->line('application_active').'</span>';
						}elseif ($campaign['active'] == 3) {
							$status = '<span class="label label-warning">'. $this->lang->line('application_approval').'</span>';
						}elseif ($campaign['active'] == 4) {
							$status = '<span class="label label-info">'. $this->lang->line('application_scheduled').'</span>';
						} elseif ($campaign['active'] == 5) {
							$status = '<span class="label label-default">'. $this->lang->line('application_on_edit').'</span>';
						} else {
							$status = '<span class="label label-danger">'. $this->lang->line('application_inactive').'</span>';
						}
                 ?>
                  	    
	       		<div class="form-group col-md-12 col-xs-12 <?php echo (form_error('name')) ? 'has-error' : '';?>">
                  	<label for="name"><?=$this->lang->line('application_name_campaign');?></label>
                  	<input type="text" <?= $readonly;?>  class="form-control" id="name" name="name" required value="<?php echo set_value('name',$campaign['name']); ?>" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_name_campaign') ?>" />
               	  	<?php echo '<i style="color:red">'.form_error('name').'</i>';  ?>
                </div>
                
                <div class="form-group col-md-12 col-xs-12">
                  	<label><?=$this->lang->line('application_status');?>:</label>
                  	<?php echo $status; ?>
                </div>
				
				<div class="form-group col-md-12 col-xs-12 <?php echo (form_error('description')) ? 'has-error' : '';?>">
                  	<label for="description"><?=$this->lang->line('application_description');?></label>
                  	<textarea class="form-control" <?= $readonly;?>  rows="4" id="description" required name="description" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_description') ?>"><?php echo set_value('description', $campaign['description']); ?></textarea>
               	  	<?php echo '<i style="color:red">'.form_error('description').'</i>';  ?>
                </div>
				                     	
                <div class="form-group col-md-3 col-xs-3 <?php echo (form_error('int_to')) ? 'has-error' : '';?>">
                  	<label for="addr_uf"><?=$this->lang->line('application_marketplace')?></label>
              		<select class="form-control" id="int_to" required name="int_to">
                	<?php foreach ($marketplaces as $marketplace): ?>
                  		<option  <?= $disabled;?> value="<?php echo $marketplace['apelido'] ?>" <?= set_select('int_to', $marketplace['apelido'], $campaign['int_to'] == $marketplace['apelido']) ?>><?php echo $marketplace['descloja'] ?></option>
                	<?php endforeach ?>
              		</select>
              		<?php echo '<i style="color:red">'.form_error('int_to').'</i>';  ?>
                </div>
                
                 <div class="form-group col-md-3 col-xs-3">
                  	<label for="addr_uf">Tipo de Pagamento</label>
              		<select class="form-control" id="scl_tipo_pagamento" name="scl_tipo_pagamento">
                  		<option value="">SELECIONE</option>
                  		<option value="1">No cartão à vista</option>
                  		<option value="2">No cartão parcelado</option>
                  		<option value="3">À vista</option>
                  		<option value="4">Boleto</option>
              		</select>
              		<?php echo '<i style="color:red">'.form_error('int_to').'</i>';  ?>
                </div>
                
                <div class="form-group col-md-2 col-xs-2">
		  			<label for="commission_type"><?=$this->lang->line('application_commission_type');?>:</label>
		  			<div class='input-group' >
	       			 	<input id="commission_type" <?= $disabled;?> data-width="180px" name="commission_type" type="checkbox" value="1" <?php echo set_checkbox('commission_type', '1', $campaign['commission_type']==1 ); ?>  data-toggle="toggle" data-on="<?=$this->lang->line('application_commission_type_unique');?>" data-off="<?=$this->lang->line('application_commission_type_by_category');?>" data-onstyle="success" data-offstyle="primary" >
					</div>
				</div>  
				
       			<div class="form-group col-md-2 <?php echo (form_error('start_date')) ? 'has-error' : '';?>">
					<label for="start_date"><?=$this->lang->line('application_start_date');?>(*)</label>
					<div class='input-group date' id='start_date_pick' name="start_date_pick">
		                <input type='text' <?= $readonly;?>  required class="form-control" id='start_date' name="start_date" autocomplete="off" value="<?php echo set_value('start_date',$campaign['start_date']);?>" />
		                <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
		            </div>
		            <?php echo '<i style="color:red">'.form_error('start_date').'</i>';  ?>
		        </div>
		        
		        <div class="form-group col-md-2 <?php echo (form_error('end_date')) ? 'has-error' : '';?>">
					<label for="end_date"><?=$this->lang->line('application_end_date');?>(*)</label>
					<div class='input-group date' id='end_date_pick' name="end_date_pick">
		                <input type='text' <?= $readonly;?>  required class="form-control" id='end_date' name="end_date" autocomplete="off" value="<?php echo set_value('end_date', $campaign['end_date']);?>"/>
		                <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
		            </div>
		            <?php echo '<i style="color:red">'.form_error('end_date').'</i>';  ?>
		        </div>
				
              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_goto_commission');?></button>
                <a href="<?php echo base_url('campaigns/'); ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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

    $("#mainCampaignsNav").addClass('active');
    $("#addCampaignsNav").addClass('active');
    
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
