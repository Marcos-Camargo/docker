<!--
SW Serviços de Informática 2019

Editar Promoção 

-->
<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	<?php 
	if ($campaign['active'] != 1) {
	 	$data['pageinfo'] = "application_edit"; 
	} else {
	 	$data['pageinfo'] = "application_view"; 
	}
	$this->load->view('templates/content_header',$data); 
	?>
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
            	 <?php if (($campaign['active'] != 1) && ($campaign['active'] != 2)) {?>
                <h3 class="box-title"><?=$this->lang->line('application_update_information');?></h3>
              	<?php } else { ?>
              	<h3><b><?=$campaign['name'];?></b></h3>
              	 <?php } ?>
            </div>
            <form role="form" id="selectForm" action="<?php echo base_url('campaigns/prdselect') ?>" method="post">
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
	       		 <?php if (($campaign['active'] != 1) && ($campaign['active'] != 2)) {?>
	       		<div class="form-group col-md-12 col-xs-12">
                  	<label><?=$this->lang->line('application_name_campaign');?>:</label>
                  	<span class="form-group"><?php echo $campaign['name']; ?></span>
                </div>
               	<?php } ?>
                <div class="form-group col-md-12 col-xs-12">
                  	<label><?=$this->lang->line('application_status');?>:</label>
                  	<?php 
                  		$readonly = "";
						$disabled = "";
						if ($campaign['active'] == 1) {
							$status = '<span class="label label-success">'. $this->lang->line('application_active').'</span>';
							$readonly = " readonly ";
							$disabled = " disabled ";
						}elseif ($campaign['active'] == 3) {
							$status = '<span class="label label-warning">'. $this->lang->line('application_approval').'</span>';
						}elseif ($campaign['active'] == 4) {
							$status = '<span class="label label-info">'. $this->lang->line('application_scheduled').'</span>';
						} elseif ($campaign['active'] == 5) {
							$status = '<span class="label label-default">'. $this->lang->line('application_on_edit').'</span>';
						} else {
							$status = '<span class="label label-danger">'. $this->lang->line('application_inactive').'</span>';
							$readonly = " readonly ";
							$disabled = " disabled ";
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
					<div class="form-group col-md-4 col-xs-4">
	                  	<label><?=$this->lang->line('application_commission_mkt_campaign');?>:</label>
	                  	<span class="form-group"><?php echo $campaign['commission_mkt']; ?></span>
	                </div>
	                <div class="form-group col-md-4 col-xs-4">
	                  	<label><?=$this->lang->line('application_commission_store_campaign');?>:</label>
	                  	<span class="form-group"><?php echo $campaign['commission_store']; ?></span>
	                </div>
	            
	       		<?php  } else { ?>    
					<div class="table-responsive">
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
					  			<?php foreach($commissions as $commission) { ?>
					  				<tr>
						  				<td><?php echo $commission['categoria']; ?></td>
						  				<td><?php echo $commission['valor_aplicado']; ?></td>
						  				<td><?php echo $commission['commission_mkt']; ?></td>
						  				<td><?php echo $commission['commission_store']; ?></td>
					  				</tr>

	      						<?php  
									} ?>
					   		</tbody>
				 		</table>
					</div>
					<?php  }  ?>
					<div class="row"></div>		
					<?php if (($campaign['active'] != 1) && ($campaign['active'] != 2)) { ?>  		
					<div class="table-responsive">
						<h3><?=$this->lang->line('application_choose_products_campaign');?></h3>
						<h4><?=$this->lang->line('application_total_chosen');?><?= $exist_products?></h4>
						<table id="manageTable" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 98%;">
				             <thead>
					             <tr>
					             	     	
						          	<th><input type="checkbox" <?= $disabled;?> name="select_all" value="1" id="manageTable-select-all"></th>              
					                <th></th> 
					                <th><?=$this->lang->line('application_company');?></th>
					                <th><?=$this->lang->line('application_store');?></th>
					                <th><?=$this->lang->line('application_charge_amount');?></th>
					                <th><?=$this->lang->line('application_sku');?></th>	
					                <th>SKU MKTPLACE</th>		               
					                <th><?=$this->lang->line('application_name');?></th>					        
					                <th><?=$this->lang->line('application_category');?></th>	
					              	<th><?=$this->lang->line('application_price_from');?></th>
					              	<th><?=$this->lang->line('application_price_sale');?></th>	        
					             </tr>
				             </thead>
			            </table>
					</div>
					<?php  } else {  ?>	
						
					<div class="table-responsive">
						<table id="tableRO" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 98%;">
				             <thead>
					             <tr>
					                <th><?=$this->lang->line('application_company');?></th>
					                <th><?=$this->lang->line('application_store');?></th>
					                <th><?=$this->lang->line('application_charge_amount');?></th>
					                <th><?=$this->lang->line('application_sku');?></th>	
					                <th>SKU MKTPLACE</th>				                           
					                <th><?=$this->lang->line('application_name');?></th>	
					                <th><?=$this->lang->line('application_category');?></th>
					                <th><?=$this->lang->line('application_price_from');?></th>
					                <th><?=$this->lang->line('application_price_sale');?></th>			        					                	     
					             </tr>
				             </thead>
			            </table>
					</div>
					<?php  }  ?>		
				</div>
              </div>
              <!-- /.box-body -->
			  <input type="hidden" id="campaign_id" name="campaign_id" required value="<?php echo $campaign['id']; ?>" />
			  <input type="hidden" id="int_to" name="int_to" required value="<?php echo $campaign['int_to']; ?>" />
               	  	
              <div class="box-footer">
              	<?php if ($campaign['active'] == 5) { ?>
              		<a href="<?php echo base_url('campaigns/createcommission/'.$campaign['id']); ?>" class="btn btn-primary"><?=$this->lang->line('application_back_commission');?></a>       
				<?php } ?>
				<?php if (($campaign['active'] == 4) || ($campaign['active'] == 5))  { ?>
				    <button type="submit" class="btn btn-success" id="select" name="select"><i class="fa fa-check"></i> <?=$this->lang->line('application_select_products');?></button>
				<?php } ?>
				<?php if (($campaign['active'] == 5) && ($exist_products)) { ?>
				    <button type="submit" class="btn btn-success" id="deselect" name="deselect"><i class="fa fa-times"></i> <?=$this->lang->line('application_deselect_products');?></button>
                    <button type="button" class="btn btn-primary" onclick="activateCampaign(event,<?php echo $campaign['id'] ?>)"><?=$this->lang->line('application_activate_campaign');?></button>
               	<?php } ?>
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
var base_url = "<?php echo base_url(); ?>";
var id_campanha = "<?php echo $campaign['id']; ?>";

 $(document).ready(function() {

    $("#mainCampaignsNav").addClass('active');
    $("#addCampaignsNav").addClass('active');
    
    var tablePrd = $('#manageTable').DataTable( {
	    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>",	
	    			  "processing": '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span> ' },
        "processing": true,
        "serverSide": true,
        "serverMethod": "post",  
         selected: undefined,   
        'columnDefs': [{
           'targets': 0,
           'searchable': false,
           'orderable': false,
           'className': 'dt-body-center',
           'render': function (data, type, full, meta){
               return '<input type="checkbox" name="id[]" value="' + $('<div/>').text(data).html() + '">';
            }
        }],
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'campaigns/fetchproductsdata/' + id_campanha,
            data: { }, 
            pages: 2 // number of pages to cache
        } )
    } );
    
     $('#tableRO').DataTable( {
        "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>",	
	    			  "processing": '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span> ' },
	    "processing": true,
        "serverSide": true,
        "responsive": true,
        "sortable": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'campaigns/fetchproductscampaignsdata/'+ id_campanha,
            pages: 2 // number of pages to cache
        } )
    } );
    
    
   // Handle click on "Select all" control
   $('#manageTable-select-all').on('click', function(){
      // Get all rows with search applied
      var rows = tablePrd.rows({ 'search': 'applied' }).nodes();
      // Check/uncheck checkboxes for all rows in the table
      $('input[type="checkbox"]', rows).prop('checked', this.checked);
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
   });
   // Handle form submission event
   $('#selectForm').on('submit', function(e){
      var form = this;
      // Iterate over all checkboxes in the table
      tablePrd.$('input[type="checkbox"]').each(function(){
         // If checkbox doesn't exist in DOM
        // if(!$.contains(document, this)){
         	
            // If checkbox is checked
            if(this.checked){
            //alert('vou fazer o submit 4');	
               // Create a hidden element
               $(form).append(
                  $('<input>')
                     .attr('type', 'hidden')
                     .attr('name', this.name)
                     .val(this.value)
               );
            }
        //}
      });
   });
					   
  });  

  function activateCampaign(e, campaign_id) {
  	e.preventDefault();
  	if (confirm("<?=$this->lang->line('application_cofirm_activate_campaign');?>")) {
	  	$.ajax({
	        type: "POST",
	        data: {
	            id: campaign_id
	        },
	        url: base_url+"campaigns/approveCampaign",
	        dataType: "json",
	        async: true,
	        success: function(data) {       
	        }
	    }); 
	  	//location.reload();
	  	window.location.href = base_url+"campaigns";
	}
  	
  }


</script>
