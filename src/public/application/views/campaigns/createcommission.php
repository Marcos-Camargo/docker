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
            <form role="form" id="formedit" action="<?php base_url('campaigns/createcommission') ?>" method="post">
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
	       	
	       		<div class="form-group col-md-12 col-xs-12">
                  	<label><?=$this->lang->line('application_name_campaign');?>:</label>
                  	<span class="form-group"><?php echo $campaign['name']; ?></span>
                </div>
				 <div class="form-group col-md-12 col-xs-12">
                  	<label><?=$this->lang->line('application_status');?>:</label>
                  	<?php 
                  		$readonly = "";
						if ($campaign['active'] == 1) {
							$readonly = " readonly ";
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
                  		echo $status; ?>
                </div>
				<div class="form-group col-md-12 col-xs-12">
                  	<label><?=$this->lang->line('application_description');?>:</label>
                  	<textarea readonly class="form-control" rows="4" ><?php echo $campaign['description']; ?></textarea>
                </div>
                               
                <div class="form-group col-md-3 col-xs-3">
                  	<label><?=$this->lang->line('application_marketplace')?>:</label>
                  	<span class="form-group"><?php echo $campaign['marketplace']; ?></span>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
		  			<label><?=$this->lang->line('application_commission_type');?>:</label>
		  			<span class="form-group"><?php echo ($campaign['commission_type'] == 1) ? $this->lang->line('application_commission_type_by_category') : $this->lang->line('application_commission_type_unique'); ?>	
				</div>  
				
       			<div class="form-group col-md-3">
					<label><?=$this->lang->line('application_start_date');?>:</label>
					<span class="form-group"><?php echo date('d/m/Y',strtotime($campaign['start_date'])); ?></span>
		        </div>
		        
		        <div class="form-group col-md-3">
					<label><?=$this->lang->line('application_end_date');?>:</label>
					<span class="form-group"><?php echo date('d/m/Y',strtotime($campaign['end_date'])); ?></span>
		        </div>
				<div class="row"></div>
				
				<?php if ($campaign['commission_type'] == 2) { ?>
		        	<div class="form-group col-md-3 col-xs-3">
	                  	<label for="commission_mkt"><?=$this->lang->line('application_commission_mkt');?></label>
	                  	<input type="text" readonly id="commissionActual" name="commissionActual" class="form-control maskdecimal2" maxlength=5 style="text-align:right;" value="<?php echo $commissionmkt; ?>"  />
	                </div>
					<div class="form-group col-md-3 col-xs-3 <?php echo (form_error('commission_mkt_campaign')) ? "has-error" : "";?>">
	                  	<label for="commission_mkt_campaign"><?=$this->lang->line('application_commission_mkt_campaign');?></label>
	                  	<input type="text" <?= $readonly;?> class="form-control maskdecimal2" maxlength=5 style="text-align:right;" id="commission_mkt_campaign" name="commission_mkt_campaign"  value="<?php echo set_value('commission_mkt_campaign', $campaign['commission_mkt']); ?>" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_commission_mkt') ?>" />
	               	  	<?php echo '<i style="color:red">'.form_error('commission_mkt_campaign').'</i>';  ?>
	                </div>
	                <div class="form-group col-md-3 col-xs-3 <?php echo (form_error('commission_store_campaign')) ? "has-error" : "";?>">
	                  	<label for="commission_store_campaign"><?=$this->lang->line('application_commission_store_campaign');?></label>
	                  	<input type="text" <?= $readonly;?> class="form-control maskdecimal2" maxlength=5 style="text-align:right;" id="commission_store_campaign" name="commission_store_campaign"  value="<?php echo set_value('commission_store_campaign',$campaign['commission_store']); ?>" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_commission_store') ?>" />
	               	  	<?php echo '<i style="color:red">'.form_error('commission_store_campaign').'</i>';  ?>
	                </div>
	       		<?php  } else { ?>    
					<div class="table-responsive">
						<h3><?=$this->lang->line('application_enter_new_commission_campaign');?></h3>
          			<!---- < table class="table table-hover table-sm  w-100 d-block d-md-table">  ---->
          			<!----table table-striped table-hover responsive display table-condensed--->
          			 	<table class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 98%;">
		             		<thead>
		              			<tr>
		              				<th><?=$this->lang->line('application_category');?></th>
		               				<th><?=$this->lang->line('application_commission_mkt');?></th>
		                			<th><?=$this->lang->line('application_commission_mkt_campaign');?></th>
		                			<th><?=$this->lang->line('application_commission_store_campaign');?></th>
		              			</tr>
		              		</thead>
					  		<tbody>
					  			<?php foreach($commissions as $commission) { 
					  				if (($commission['int_to']==$campaign['int_to']) && ($readonly=='')) { ?>
					  				<tr>
						  				<td><?php echo $commission['categoria']; ?></td>
						  				<td><?php echo $commission['valor_aplicado']; ?></td>
						  				<td>
						  					<div class="<?php echo (form_error('commission_mkt_campaign_'.$commission['id'])) ? "has-error" : "";?>">
						  					   <input type="text"  value="<?php echo set_value('commission_mkt_campaign_'.$commission['id'],$commission['commission_mkt_campaign']); ?>" class="form-control maskdecimal2" maxlength=5 style="text-align:right;" id="commission_mkt_campaign_<?php echo $commission['id']; ?>" name="commission_mkt_campaign_<?php echo $commission['id']; ?>"  autocomplete="off" />
						  					   <?php echo '<i style="color:red">'.form_error('commission_mkt_campaign_'.$commission['id']).'</i>';  ?>
						  					</div>
						  				</td>
						  				<td>
						  					<div class="<?php echo (form_error('commission_store_campaign_'.$commission['id'])) ? "has-error" : "";?>">
						  					   <input type="text" value="<?php echo set_value('commission_store_campaign_'.$commission['id'],$commission['commission_store_campaign']); ?>" class="form-control maskdecimal2" maxlength=5 style="text-align:right;" id="commission_store_campaign_<?php echo $commission['id']; ?>" name="commission_store_campaign_<?php echo $commission['id']; ?>"  autocomplete="off" />
						  					   <?php echo '<i style="color:red">'.form_error('commission_store_campaign_'.$commission['id']).'</i>';  ?>
						  					</div>
						  				</td>
						  				
					  				</tr>

	      						<?php } 
									else {
										if ($commission['commission_mkt_campaign'] != '') { ?>
											<tr>
								  				<td><?php echo $commission['categoria']; ?></td>
								  				<td><?php echo $commission['valor_aplicado']; ?></td>
								  				<td><?php echo $commission['commission_mkt_campaign']; ?></td>
								  				<td><?php echo $commission['commission_store_campaign']; ?></td>
							  				</tr>
										<?php }
									} 
									} ?>
					   		</tbody>
				 		</table>
					</div>
				<?php  }  ?>
				</div>
              </div>
              <!-- /.box-body -->

              <div class="box-footer">
              	<a href="<?php echo base_url('campaigns/create/'.$campaign['id']); ?>" class="btn btn-primary"><?=$this->lang->line('application_goto_campaign');?></a>
                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_goto_products');?></button>
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
    
    if ($('#commission_type').is(":checked"))  {
		$("#hidebycategory").show(); 
		$("#showbycategory").hide();
		
	} else {
		$("#hidebycategory").hide();
		$("#showbycategory").show();
		
	}; 
    
  	$("#commission_type").change(function () {
  		if ($('#commission_type').is(":checked"))  {
  			$("#hidebycategory").show();
  			$("#showbycategory").hide(); 
  		} else {
  			$("#hidebycategory").hide();
  			$("#showbycategory").show();
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
