<!--
SW Serviços de Informática 2019

Listar Pedidos sem frete para o ADMIN

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  
	 $data['page_now'] ='freight_to_wire';
	 $this->load->view('templates/content_header',$data); ?>

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

		<a class="pull-right btn btn-primary" href="<?php echo base_url('export/ordersSemFreteXls') ?>"><i class="fa fa-file-excel-o"></i> Export Data</a>
		 
		<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#filterModal"><i class="fa fa-filter"></i> <?=$this->lang->line('application_change_filter')?></button>

      <br/>
      <br/>
      <div class="box">
          <div class="box-body">
              <div class="col-md-3 form-group no-padding" style="<?=(count($stores_filter) > 1) ? "" : "display: none;"?>">
                  <label for="buscalojas"><?=$this->lang->line('application_search_for_store');?></label>
                  <div class="input-group">
                      <select class="form-control selectpicker show-tick" id="buscalojas" name ="loja[]"
                              data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue"
                              data-selected-text-format="count > 1" title="<?=$this->lang->line('application_search_for_store');?>">
                          <?php foreach ($stores_filter as $store_filter) {?>
                              <option value="<?=$store_filter['id']?>"><?=$store_filter['name']?></option>
                          <?php }?>
                      </select>
                      <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                  </div>
              </div>
          </div>
      </div>
      <div class="row"></div>

        <div class="box">
          <div class="box-body">
            <!-- table id="manageTable" class="table table-bordered table-striped" -->
            <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_order');?></th>
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_phone');?></th>
                <th><?=$this->lang->line('application_date');?></th>
                <th><?=$this->lang->line('application_ship_company');?></th>
                <th><?=$this->lang->line('application_service');?></th>
                <th><?=$this->lang->line('application_total_amount');?></th>
                <th><?=$this->lang->line('application_ship_value');?></th>
                <?php if(in_array('updateOrder', $user_permission) || in_array('viewOrder', $user_permission) || in_array('deleteOrder', $user_permission)): ?>
                  <th><?=$this->lang->line('application_action');?></th>
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
	    <form role="form" action="<?php echo base_url('orders/filterSemFrete') ?>" method="post" id="filterForm">
	    <div class="modal-body">
		    <?php
			$filters = $this->data['filters'];
		    $filters = get_instance()->data['filters'];
			foreach ($filters as $k => $v) { ?>
			<div class="row">
				<div class="form-group col-md-3">
			    	<label><?=$v['nm'];?></label>
				</div>
				<div class="form-group col-md-3">
			        <div>
			          <select type="text" class="form-control" id="<?=$k; ?>_op" name="<?=$k; ?>_op">
			            <option value="0" ><?=$this->lang->line('application_codition')?></option>
			        <?php foreach ($v['op'] as $op) { ?>}    
			            <option value="<?=$op; ?>" ><?=$op; ?></option>
			        <?php } ?>    
			          </select>
			        </div>
				</div>
				<div class="form-group col-md-5">
					<div>
					    <input type="text" class="form-control" id="<?=$k; ?>" name="<?=$k; ?>" placeholder="<?=$this->lang->line('application_enter_value')?>"  />
					</div>
				</div>
				<div class="form-group col-md-1">+</div>	
			</div>
		<?php			
			}  
		?>
		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="submit" class="btn btn-default" id="reset_filter" name="reset_filter"><?=$this->lang->line('application_clear');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";
var filters = {};

$(document).ready(function() {

  $("#mainProcessesNav").addClass('active');
    $("#semFreteNav").addClass('active');

    $('#buscalojas  option:selected').each(function () {
        lojas.push($(this).val());
    });
    $('#buscalojas').on('change', function () {
        var lojas = [];
        $('#buscalojas  option:selected').each(function () {
            lojas.push($(this).val());
        });
        setFilter('lojas', lojas);
        search();
    });
  // initialize the datatable 
 // manageTable = $('#manageTable').DataTable({
 //   'ajax': base_url + 'orders/fetchOrdersData',
 //   'order': []
 //  });
    search();
});

function setFilter(name, value) {
    filters[name] = value;
}

function getFilter() {
    return filters;
}

function search(){
    if (typeof manageTable === 'object' && manageTable !== null) {
        manageTable.destroy();
    }

    manageTable = $('#manageTable').DataTable({
        "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "sortable": true,
        "searching": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline({
            data: getFilter(),
            url: base_url + 'orders/fetchOrdersDataSemFrete',
            pages: 2 // number of pages to cache
        })
    });
}
</script>
