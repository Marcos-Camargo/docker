<!--
Listar Jobs
-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

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
            
          <?php if(in_array('viewIntegrations', $user_permission)): ?>
              <a href="<?php echo base_url('calendar/index') ?>" class="btn btn-primary"><?=$this->lang->line('application_calendar');?></a>  
          <?php endif; ?>

		      <div class="">
        
	          <div class="col-md-3">
	            <label for="buscaMdule" class="normal"><?=$this->lang->line('application_module');?></label>
	            <div class="input-group">
	              <input type="search" id="buscaMdule" onchange="buscaJob()" class="form-control" placeholder="<?=$this->lang->line('application_module');?>" aria-label="Search" aria-describedby="basic-addon1">
	              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
	            </div>
	          </div>
	          
	          <div class="col-md-3">
	            <label for="buscaMethod" class="normal"><?=$this->lang->line('application_method');?></label>
	            <div class="input-group">
	              <input type="search" id="buscaMethod" onchange="buscaJob()" class="form-control" placeholder="<?=$this->lang->line('application_method');?>" aria-label="Search" aria-describedby="basic-addon1">
	              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
	            </div>
          </div>
	          
	          <div class="col-md-3">
	            <label for="buscaParams" class="normal"><?=$this->lang->line('application_params');?></label>
	            <div class="input-group">
	              <input type="search" id="buscaParams" onchange="buscaJob()" class="form-control" placeholder="<?=$this->lang->line('application_params');?>" aria-label="Search" aria-describedby="basic-addon1">
	              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
	            </div>
	          </div>
	          
	          <div class="col-md-3">
	            <div class="input-group" >
	              <label for="buscastatus" class="normal"><?=$this->lang->line('application_status');?></label>
	              <select class="form-control" id="buscastatus" onchange="buscaJob()">
	                <option value=""><?=$this->lang->line('application_all');?></option>
                    <option value="0">0-<?=$this->lang->line('application_status_job_history_0');?></option>
                    <option value="1" selected>1-<?=$this->lang->line('application_status_job_history_1');?></option>
                    <option value="2">2-<?=$this->lang->line('application_status_job_history_2');?></option>
                    <option value="3">3-<?=$this->lang->line('application_status_job_history_3');?></option>
                    <option value="4">4-<?=$this->lang->line('application_status_job_history_4');?></option>
                    <option value="5">5-<?=$this->lang->line('application_status_job_history_5');?></option>
                    <option value="6">6-<?=$this->lang->line('application_status_job_history_6');?></option>
                    <option value="7">7-<?=$this->lang->line('application_status_job_history_7');?></option>
                    <option value="8">8-<?=$this->lang->line('application_status_job_history_8');?></option>
	              </select>
	            </div>
	          </div>
	
	          <div class="pull-right">
				  <label  class="normal" style="display: block;">&nbsp; </label>
	       		  <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
	          </div>
        	</div>
          <div class="row"></div>
         

          <div class="box">
            <div class="box-body">
              <table id="manageTable" aria-label="table" class="table table-bordered table-striped" style="border-collapse: collapse; width: 100%; border-spacing: 0;">
                <thead>
                <tr>
                  <th><?=$this->lang->line('application_id');?></th>
                  <th><?=$this->lang->line('application_module');?></th>
                  <th><?=$this->lang->line('application_method');?></th>
                  <th><?=$this->lang->line('application_params');?></th>
                  <th><?=$this->lang->line('application_status');?></th>
                  <th><?=$this->lang->line('application_start_date');?></th>
                  <th><?=$this->lang->line('application_alert_after');?></th>
                  <th style="width:110px"><?=$this->lang->line('application_action');?></th>
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
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

// para csrf 
var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
$(document).ready(function() {

      $("#mainJobsNav").addClass('active');
      $("#manageJobsNav").addClass('active');
      buscaJob()
      
});
 
function buscaJob(){
  let module_path = $('#buscaMdule').val();
  let module_params = $('#buscaParams').val();
  let module_method = $('#buscaMethod').val();
  let status  = $('#buscastatus').val();
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
      url: base_url + 'jobs/fetchJobsData',
      data: { [csrfName]: csrfHash, module_path: module_path, module_params: module_params, module_method: module_method, status: status},
      pages: 2
    })
  });
}

function clearFilters(){
  $('#buscaMdule').val('');
  $('#buscaMethod').val('');
  $('#buscaempresa').val('');
  $('#buscaParams').val('');
  buscaJob();
}
  </script>
