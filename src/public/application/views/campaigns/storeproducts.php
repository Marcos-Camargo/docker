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
	
					<div class="row"></div>		
					<?php if (($campaign['active'] != 1) && ($campaign['active'] != 2)) { ?>  		
					<div class="table-responsive">
						<h3><?=$this->lang->line('application_define_sale_price_campaign');?></h3>
						<table id="manageTable" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 98%;">
				             <thead>
					             <tr>
					                <th><?=$this->lang->line('application_store');?></th>
					                <th><?=$this->lang->line('application_sku');?></th>
					                <th><?=$this->lang->line('application_name');?></th>					        
					                <th><?=$this->lang->line('application_category');?></th>	
					                <th><?=$this->lang->line('application_qty');?></th>	
					                <th><?=$this->lang->line('application_charge_amount');?></th>
					                <th><?=$this->lang->line('application_commission_reduced');?></th>
					              	<th><?=$this->lang->line('application_price_from');?></th>    
					              	<th><?=$this->lang->line('application_sugestion');?></th>   
					              	<th><?=$this->lang->line('application_price_sale');?></th>     
					              	<th width="70px"><?=$this->lang->line('application_action');?></th>    
					             </tr>
				             </thead>
			            </table>
					</div>
					<?php  } else {  ?>	
						
					<div class="table-responsive">
						<h3><?=$this->lang->line('application_products_campaign');?></h3>
						<table id="tableRO" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 98%;">
				             <thead>
					             <tr>
					                <th><?=$this->lang->line('application_store');?></th>
					                <th><?=$this->lang->line('application_sku');?></th>                
					                <th><?=$this->lang->line('application_name');?></th>	
					                <th><?=$this->lang->line('application_category');?></th>
					                <th><?=$this->lang->line('application_qty');?></th>	
					                <th><?=$this->lang->line('application_charge_amount');?></th>
					                <th><?=$this->lang->line('application_commission_reduced');?></th>
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
               	<a href="<?php echo base_url('campaigns/storeIndex'); ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>       
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

<div class="modal fade" tabindex="-1" role="dialog" id="prdModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?=$this->lang->line('application_campaign_product');?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('campaigns/updateProductStore') ?>" method="post" id="addProductForm">
	    <div class="modal-body">
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_sku');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prd_sku" id="prd_sku" class="form-control" readonly value="" >
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_name');?></label></div>
				<div class="form-group col-md-9">
                     <input type="text" name="prd_name" id="prd_name" class="form-control" readonly value="" >
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_qty');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prd_qty" id="prd_qty" class="form-control" style="text-align: right" readonly value="" >
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_charge_amount');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prd_taxa" id="prd_taxa" class="form-control" readonly style="text-align: right" value="" >
                </div>
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_commission_reduced');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prd_taxapromo" id="prd_taxapromo" class="form-control" style="text-align: right" readonly value="" >
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_price_from');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prd_price" id="prd_price" class="form-control" style="text-align: right" readonly value="" >
				</div>
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_sugestion');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prd_sugestion" id="prd_sugestion" class="form-control" style="text-align: right" readonly value="" >
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label for="prd_sale"><?=$this->lang->line('application_price_sale');?>(*)</label></div>
                <div class="form-group col-md-3">
                  <input type="text" class="form-control maskdecimal2" id="prd_sale" name="prd_sale" required placeholder="<?=$this->lang->line('application_enter_price');?>" value="<?php echo set_value('prd_sale') ?>" autocomplete="off"  />
                </div>
			</div>
			<input type="hidden" name="id_campaign"  id="id_campaign" value="<?php echo $campaign['id'] ?>" autocomplete="off">
     		<input type="hidden" name="id_product" id="id_product" value="" autocomplete="off">
		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	      
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="prdDelModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?=$this->lang->line('application_remove_campaign_product');?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('campaigns/removeProductStore') ?>" method="post" id="delProductForm">
	    <div class="modal-body">
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_sku');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prddel_sku" id="prddel_sku" class="form-control" readonly value="" />
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_name');?></label></div>
				<div class="form-group col-md-9">
                     <input type="text" name="prddel_name" id="prddel_name" class="form-control" readonly value="" />
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_qty');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prddel_qty" id="prddel_qty" class="form-control" style="text-align: right" readonly value="" />
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_charge_amount');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prddel_taxa" id="prddel_taxa" class="form-control" readonly style="text-align: right" value="" />
                </div>
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_commission_reduced');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prddel_taxapromo" id="prddel_taxapromo" class="form-control" style="text-align: right" readonly value="" />
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_price_from');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prddel_price" id="prddel_price" class="form-control" style="text-align: right" readonly value="" >
				</div>
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_sugestion');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="prddel_sugestion" id="prddel_sugestion" class="form-control" style="text-align: right" readonly value="" />
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label for="prddel_sale"><?=$this->lang->line('application_price_sale');?>(*)</label></div>
                <div class="form-group col-md-3">
                  <input type="text" class="form-control" id="prddel_sale" name="prddel_sale" style="text-align: right" readonly value=""  />
                </div>
			</div>
			<input type="hidden" name="id_del_campaign"  id="id_del_campaign" value="<?php echo $campaign['id'] ?>" autocomplete="off">
     		<input type="hidden" name="id_del_product" id="id_del_product" value="" autocomplete="off">
		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	      
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

  
  <!-- /.content-wrapper -->
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var base_url = "<?php echo base_url(); ?>";
var id_campanha = "<?php echo $campaign['id']; ?>";

 $(document).ready(function() {

    $("#mainCampaignsNav").addClass('active');
    $("#manageCampaignsStoreNav").addClass('active');
    
    $('.maskdecimal2').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 2, 
		  max: 999999999999.99
		});
		
    var tablePrd = $('#manageTable').DataTable( {
	    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"	 , 
        			  "processing": '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only"></span> '},
        "processing": true,
        "serverSide": true,
        "serverMethod": "post",  
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'campaigns/fetchstoreproductsdata/' + id_campanha,
            pages: 2 // number of pages to cache
        } )
    } );
    
     $('#tableRO').DataTable( {
        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang', 
        			  "processing": '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only"></span> '},
        "processing": true,
        "serverSide": true,
        "responsive": true,
        "sortable": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'campaigns/fetchstoreproductscampaignsdata/'+ id_campanha,
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

function incluirProduto(event,campaign_id,product_id,sku,prod_name,price,sugerido,sale,qty,taxa,taxapromo) {
	event.preventDefault();

	document.getElementById('prd_sku').value=sku; 
	document.getElementById('prd_name').value=prod_name; 
	document.getElementById('prd_qty').value=qty; 
	document.getElementById('prd_taxa').value=taxa;
	document.getElementById('prd_taxapromo').value=taxapromo;
	document.getElementById('prd_price').value=price;
	document.getElementById('prd_sugestion').value=sugerido;
	document.getElementById('prd_sale').value=sale;  
	document.getElementById('id_product').value=product_id; 
	
	 $("#addProductForm").unbind('submit').bind('submit', function() {
        var form = $(this);

        // remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',  
          success:function(response) {
            if(response.success === true) {
              $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');
              // hide the modal
              $("#prdModal").modal('hide');
              // reset the form 
              $("#prdModal.form-group").removeClass('has-error').removeClass('has-success');
             // alert(response.messages);
              window.location.reload(false); 

            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);
                 // alert(index+":"+value); 
                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#prdModal").modal('hide');
                $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }
        }); 

        return false;
      });
	
	
	$("#prdModal").modal('show');	
}

function removerProduto(event,campaign_id,product_id,sku,prod_name,price,sugerido,sale,qty,taxa,taxapromo) {
	event.preventDefault();

	document.getElementById('prddel_sku').value=sku; 
	document.getElementById('prddel_name').value=prod_name; 
	document.getElementById('prddel_qty').value=qty; 
	document.getElementById('prddel_taxa').value=taxa;
	document.getElementById('prddel_taxapromo').value=taxapromo;
	document.getElementById('prddel_price').value=price;
	document.getElementById('prddel_sugestion').value=sugerido;
	document.getElementById('prddel_sale').value=sale;  
	document.getElementById('id_del_product').value=product_id; 
	
	 $("#delProductForm").unbind('submit').bind('submit', function() {
        var form = $(this);

        // remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',  
          success:function(response) {
            if(response.success === true) {
              $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');
              // hide the modal
              $("#prdDelModal").modal('hide');
              // reset the form 
              $("#prdModal.form-group").removeClass('has-error').removeClass('has-success');
             // alert(response.messages);
              window.location.reload(false); 

            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);
                 // alert(index+":"+value); 
                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#prdDelModal").modal('hide');
                $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }
        }); 

        return false;
      });
	
	
	$("#prdDelModal").modal('show');	
}
</script>
