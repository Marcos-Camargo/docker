<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_logs";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
  
  	<div class="row">
      	<form id="frmBusca" name="frmBusca">
          	<div class="col-md-12 col-xs-12">
            	<div class="box">
                	<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
                    </div>
              		<div class="box-body">
                  		<div class="col-md-4 col-xs-4">
                  		<label for="group_isadmin"><?=$this->lang->line('application_store');?></label>
                          <select class="form-control" id="slc_store" name="slc_store"> 
                            <option value="">~~SELECT~~</option>
                            <?php foreach ($stores as $store) { ?>
                            	<option value="<?php echo $store['id']?>"><?php echo $store['name']?></option>
                            <?php }?>
                          </select>
                  		</div>
                  		
                  		<div class="col-md-4 col-xs-4">
                  		<label for="group_isadmin"><?=$this->lang->line('application_providers');?></label>
                          <select class="form-control" id="slc_transportadora" name="slc_transportadora">
                            <option value="">~~SELECT~~</option>
                             <?php foreach ($transportadoras as $transportadora): ?>
                              <option value="<?php echo trim($transportadora['id']); ?>"><?php echo trim($transportadora['name']); ?></option>
                            <?php endforeach ?>
                          </select>
                  		</div>
                  		
                  		<div class="col-md-4 col-xs-4">
                  		<div class="box-footer"> <br>
                            <button type="button" id="btnBuscar" name="btnBuscar" class="btn btn-primary"><?=$this->lang->line('application_search');?></button>
                          </div>
                  		</div>
              		</div>
            	</div>
            </div>
        </form>
 	</div>
  
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
        
        <div class="box">
        <div class="box-header">
                  <h3 class="box-title"><?=$this->lang->line('application_extract_orders');?></h3>
                </div>
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_store');?></th> 
                <th><?=$this->lang->line('application_providers');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <th><?=$this->lang->line('application_logs');?></th>
                <th><?=$this->lang->line('application_date');?></th>
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

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  $("#slc_transportadora, #slc_store").select2();

	$("#btnBuscar").click( function(){

    	var novoCaminho = base_url + 'iugu/subcontastatuslog/'+$("#slc_store").val()+"/"+$("#slc_transportadora").val();
    	$('#manageTable').DataTable().destroy();
    	manageTable = $('#manageTable').DataTable({
		    'ajax': novoCaminho,
		    'order': []
		  });


    });
    
  $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'iugu/subcontastatuslog/'+$("#slc_store").val()+"/"+$("#slc_transportadora").val(),
    'order': []
  });

});

</script>
