<!--

Listar Campanhas

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
        
 		<?php if(in_array('createCampaigns', $user_permission)): ?>
	          <a href="<?php echo base_url('campaigns/create/0') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_campaign');?></a>
	    <?php endif; ?>
		<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#filterModal"><i class="fa fa-filter"></i> <?=$this->lang->line('application_change_filter')?></button>
        <div class="box">
          <div class="box-body">
            <!-- table id="manageTable" class="table table-bordered table-striped" -->
            <table id="manageTable" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_marketplace');?></th>
                <th><?=$this->lang->line('application_commission_type');?></th>
                <th><?=$this->lang->line('application_start_date');?></th>
                <th><?=$this->lang->line('application_end_date');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <th width="70px"><?=$this->lang->line('application_total_product_campaign_without_descount');?></th>
                <?php if(in_array('updateCampaignsStore', $user_permission) || in_array('viewCampaignsStore', $user_permission)): ?>
                  <th ><?=$this->lang->line('application_action');?></th>
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
	    <form role="form" action="<?php echo base_url('campaigns/filterStore') ?>" method="post" id="filterForm">
	    <div class="modal-body">
	    	<?php
			$filters = get_instance()->data['filters'];
			//var_dump($filters);
			if (!is_array($filters)) {
				$filters = array (
				   	'filter_name' => '',
				   	'filter_marketplace' => '',
				    'filter_start_date' => '',
					'filter_end_date' => '',
					'filter_status' => array(1,5),
					'filter_type' => array(3),
				); 
			}
			$filters_fields = Array (
				0 => array( 
					'id' => "filter_name", 
					'op' => array ('=','LIKE'),
					'label' => $this->lang->line('application_name_campaign'), 
					'mask' => '',
				), 
				1 => array( 
					'id' => "filter_marketplace", 
					'op' => array ('=','LIKE'),
					'label' => $this->lang->line('application_marketplace'), 
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
				<div class="form-group col-md-1">+</div>
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
			            <option value="1" <?php echo set_select('filter_type', '1',(in_array('1',$filters['filter_type']))); ?> ><?=$this->lang->line('application_commission_type_unique')?></option>
			            <option value="2" <?php echo set_select('filter_type', '2',(in_array('2',$filters['filter_type']))); ?> ><?=$this->lang->line('application_commission_type_by_category')?></option>
			            <option value="3" <?php echo set_select('filter_type', '3',(in_array('3',$filters['filter_type']))); ?> ><?=$this->lang->line('application_commission_type_unique')?> e <?=$this->lang->line('application_commission_type_by_category')?></option>
			          </select>
			        </div>
				</div>

				<div class="form-group col-md-1">+</div>
			  </div>
			
		</div> <!-- modal-body -->
	    <div class="modal-footer">
          <a href="<?=base_url('campaigns/storeIndex')?>" class="btn btn-default"><?=$this->lang->line('application_clear');?></a>
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

  $("#mainCampaignsNav").addClass('active');
  $("#manageCampaignsStoreNav").addClass('active');

  // initialize the datatable 
 // manageTable = $('#manageTable').DataTable({
 //   'ajax': base_url + 'orders/fetchOrdersData',
 //   'order': []
 //  });

	$.fn.dataTable.ext.errMode = 'throw';
	
    var manageTable = $('#manageTable').DataTable( {
        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
        "processing": false,
        "serverSide": true,
        "responsive": true,
        "sortable": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'campaigns/fetchCampaignsStoreData',
            pages: 2 // number of pages to cache
        } )
    } );
    $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
    });

});

  
</script>
