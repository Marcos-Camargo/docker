<?php
// Redirecionamento temporário, relativo à LOG-457.
redirect('dashboard', 'refresh');
?>

<!--
SW Serviços de Informática 2019

Listar Promoções

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

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
        
 		<?php if(in_array('createPromotions', $user_permission)): ?>
	          <a href="<?php echo base_url('promotions/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_promotion');?></a>
	    <?php endif; ?>
		<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#filterModal"><i class="fa fa-filter"></i> <?=$this->lang->line('application_change_filter')?></button>
        <div class="box">
          <div class="box-body">
            <!-- table id="manageTable" class="table table-bordered table-striped" -->
            <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_promotion_type');?></th>
                <th><?=$this->lang->line('application_sku');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_price_from');?></th>
                <th><?=$this->lang->line('application_price_sale');?></th>
                <th width="70px"><?=$this->lang->line('application_discount');?></th>
                <th><?=$this->lang->line('application_qty');?></th>
                <th><?=$this->lang->line('application_promotion_qty');?></th>
                <th><?=$this->lang->line('application_start_date');?></th>
                <th><?=$this->lang->line('application_end_date');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <?php if(in_array('updatePromotions', $user_permission) || in_array('viewPromotions', $user_permission) || in_array('deletePromotions', $user_permission)): ?>
                  <th width="105px"><?=$this->lang->line('application_action');?></th>
                <?php endif; ?>
              </tr>
              </thead>

            </table>
          </div>
          <!-- /.box-body -->
        </div>
        
        <div class="box">
          <div class="box-body">
            <table id="manageTablePromotion" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_promotion_type');?></th>
                <th><?=$this->lang->line('application_sku');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_price_from');?></th>
                <th><?=$this->lang->line('application_price_sale');?></th>
                <th width="70px"><?=$this->lang->line('application_discount');?></th>
                <th><?=$this->lang->line('application_qty');?></th>
                <th><?=$this->lang->line('application_promotion_qty');?></th>
                <th><?=$this->lang->line('application_start_date');?></th>
                <th><?=$this->lang->line('application_end_date');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <?php if(in_array('updatePromotions', $user_permission) || in_array('viewPromotions', $user_permission) || in_array('deletePromotions', $user_permission)): ?>
                  <th width="105px"><?=$this->lang->line('application_action');?></th>
                <?php endif; ?>
              </tr>
              </thead>
            </table>
          </div>
          <!-- /.box-body -->
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

<div class="modal fade" tabindex="-1" role="dialog" id="filterModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?=$this->lang->line('application_set_filters');?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('promotions/filter') ?>" method="post" id="filterForm">
	    <div class="modal-body">
	    	<?php
			$filters = get_instance()->data['filters'];
			//var_dump($filters);
			if (!is_array($filters)) {
				$filters = array (
				   	'filter_sku' => '',
				   	'filter_store' => '',
				    'filter_start_date' => '',
					'filter_end_date' => '',
					'filter_status' => array(1,3,4),
					'filter_type' => array(3),
				); 
			}
			$filters_fields = Array (
				0 => array( 
					'id' => "filter_sku", 
					'op' => array ('=','LIKE'),
					'label' => $this->lang->line('application_sku'), 
					'mask' => '',
				), 
				1 => array( 
					'id' => "filter_store", 
					'op' => array ('=','LIKE'),
					'label' => $this->lang->line('application_store'), 
					'mask' => '',
				), 
				2 => array( 
					'id' => "filter_start_date", 
					'op' => array ('=', '>','<'),
					'label' => $this->lang->line('application_start_date'), 
					'mask' => 'onKeyPress="return digitos(event, this);" onKeyUp="Mascara(\'DATAHORA\',this,event);"',
				), 
				3 => array( 
					'id' => "filter_end_date", 
					'op' => array ('=', '>','<'),
					'label' => $this->lang->line('application_end_date'), 
					'mask' => 'onKeyPress="return digitos(event, this);" onKeyUp="Mascara(\'DATAHORA\',this,event);"',
				), 
			); 
			foreach ($filters_fields as $field) { ?>
			  <div class="row">
                <div class="form-group col-md-3">
			    	<label><?php echo $field['label'];?></label>
				</div>
				<div class="form-group col-md-3">
			        <div>
					  <select type="text" class="form-control" id="<?php echo $field['id'];?>_op" name="<?php echo $field['id'];?>_op">
			            <option value="0" ><?=$this->lang->line('application_codition')?></option>
			        <?php foreach ($field['op'] as $op) { ?>}    
			            <option value="<?=$op; ?>" <?php echo set_select($field['id']."_op", $op); ?> ><?=$op; ?></option>
			        <?php } ?>    
			          </select>
			        </div>
				</div>
				<div class="form-group col-md-5">
					<div>
					    <input type="text" class="form-control" id="<?php echo $field['id'];?>" name="<?php echo $field['id'];?>" value="<?php echo set_value($field['id'],$filters[$field['id']]); ?>" autocomplete="off" <?php echo $field['mask'];?>/>
					</div>
				</div>
				<!---<div class="form-group col-md-1">+</div>--->
			  </div>
			<?php } ?> 
			
			 <div class="row">
                <div class="form-group col-md-3">
			    	<label><?=$this->lang->line('application_status')?></label>
				</div>
				<div class="form-group col-md-3">
			        <div>
			          <select type="text" class="mdb-select md-form colorful-select " multiple id="filter_status" name="filter_status[]">
			            <option value="1" <?php echo set_select('filter_status', '1',(in_array('1',$filters['filter_status']))); ?> ><?=$this->lang->line('application_active')?></option>
			            <option value="2" <?php echo set_select('filter_status', '2',(in_array('2',$filters['filter_status']))); ?> ><?=$this->lang->line('application_inactive')?></option>
			            <option value="3" <?php echo set_select('filter_status', '3',(in_array('3',$filters['filter_status']))); ?> ><?=$this->lang->line('application_approval')?></option>
			            <option value="4" <?php echo set_select('filter_status', '4',(in_array('4',$filters['filter_status']))); ?> ><?=$this->lang->line('application_scheduled')?></option>
			          </select>
			        </div>
				</div>

				<div class="form-group col-md-1">+</div>
			  </div>
			<div class="row">
                <div class="form-group col-md-3">
			    	<label><?=$this->lang->line('application_type')?></label>
				</div>
				<div class="form-group col-md-3">
			        <div>
			          <select type="text" class="mdb-select md-form colorful-select " id="filter_type" name="filter_type[]">
			            <option value="1" <?php echo set_select('filter_type', '1',(in_array('1',$filters['filter_type']))); ?> ><?=$this->lang->line('application_promotion_type_stock')?></option>
			            <option value="2" <?php echo set_select('filter_type', '2',(in_array('2',$filters['filter_type']))); ?> ><?=$this->lang->line('application_promotion_type_date')?></option>
			            <option value="3" <?php echo set_select('filter_type', '3',(in_array('3',$filters['filter_type']))); ?> ><?=$this->lang->line('application_promotion_type_stock')?> e <?=$this->lang->line('application_promotion_type_date')?></option>
			          </select>
			        </div>
				</div>

				<div class="form-group col-md-1">+</div>
			  </div>
			
		</div> <!-- modal-body -->
	    <div class="modal-footer">
          <a href="<?=base_url('promotions')?>" class="btn btn-default"><?=$this->lang->line('application_clear');?></a>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="approvePromotion">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?=$this->lang->line('application_approve_promotion');?></h4>
		</div>
	    <form role="form" action="<?php echo base_url('promotions/approvePromotion') ?>" method="post" id="approvePromotionForm">
	    <div class="modal-body">
			<p><?=$this->lang->line('application_confirm_approve_promotion');?></p>
			<input type="hidden" name="id_approve"  id="id_approve" value="" autocomplete="off">
		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="inactivePromotion">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?=$this->lang->line('application_inactivate_promotion');?></h4>
		</div>
	    <form role="form" action="<?php echo base_url('promotions/inactivePromotion') ?>" method="post" id="inactivePromotionForm">
	    <div class="modal-body">
			<p><?=$this->lang->line('application_confirm_inactivate_promotion');?></p>
			<input type="hidden" name="id_inactive"  id="id_inactive" value="" autocomplete="off">
		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="removePromotion">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?=$this->lang->line('application_remove_promotion');?></h4>
		</div>
	    <form role="form" action="<?php echo base_url('promotions/removePromotion') ?>" method="post" id="removePromotionForm">
	    <div class="modal-body">
			<p><?=$this->lang->line('application_confirm_remove_promotion');?></p>
			<input type="hidden" name="id_remove"  id="id_remove" value="" autocomplete="off">
		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
//var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  $("#mainPromotionsNav").addClass('active');
  $("#managePromotionsNav").addClass('active');

  // initialize the datatable 
 // manageTable = $('#manageTable').DataTable({
 //   'ajax': base_url + 'orders/fetchOrdersData',
 //   'order': []
 //  });

    var manageTable = $('#manageTable').DataTable( {
        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
        "processing": false,
        "serverSide": true,
        "scrollX": true,
        "sortable": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'promotions/fetchPromotionsData',
            pages: 2 // number of pages to cache
        } )
    } );
    $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
    });

});

function approvePromotion(e, promotion_id) {
  	e.preventDefault();
    document.getElementById('id_approve').value=promotion_id; 
	$("#approvePromotion").modal('show');	
}

function inactivePromotion(e, promotion_id) {
  	e.preventDefault();
    document.getElementById('id_inactive').value=promotion_id; 
	$("#inactivePromotion").modal('show');	
}

function deletePromotion(e, promotion_id) {
  	e.preventDefault();
    document.getElementById('id_remove').value=promotion_id; 
	$("#removePromotion").modal('show');	
}

</script>
