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

            <?php if(in_array('createCalendar', $user_permission)): ?>
				      <a href="<?php echo base_url('calendar/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_calendar_event');?></a>
               <?php endif; ?>
            <?php if(in_array('viewCalendar', $user_permission) ): ?>
				     <a href="<?php echo base_url('jobs/index') ?>" class="btn btn-primary"><?=$this->lang->line('application_jobs');?></a>
            <?php endif; ?>
              
            <div class="">

            <div class="col-md-3">
	            <label for="buscaName" class="normal"><?=$this->lang->line('application_event_name');?></label>
	            <div class="input-group">
	              <input type="search" id="buscaName" onchange="buscaJob()" class="form-control" placeholder="<?=$this->lang->line('application_event_name');?>" aria-label="Search" aria-describedby="basic-addon1">
	              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
	            </div>
	          </div>

	          <div class="col-md-3">
	            <label for="buscaModule" class="normal"><?=$this->lang->line('application_module');?></label>
	            <div class="input-group">
	              <input type="search" id="buscaModule" onchange="buscaJob()" class="form-control" placeholder="<?=$this->lang->line('application_module');?>" aria-label="Search" aria-describedby="basic-addon1">
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
	              <label for="buscaEventType" class="normal"><?=$this->lang->line('application_event_type');?></label>
                <select class="form-control select_group"  id="buscaEventType" onchange="buscaJob()">
                  <option value=""><?=$this->lang->line('application_all')?></option>
                  <optgroup label="<?=$this->lang->line('application_specific')?>">
                    <option value="71"><?=$this->lang->line('application_daily')?></option>
                    <option value="72"><?=$this->lang->line('application_weekly')?></option>
                    <option value="73"><?=$this->lang->line('application_monthly')?></option>
                    <option value="74"><?=$this->lang->line('application_annually')?></option>
                  </optgroup>
                  <optgroup label="<?=$this->lang->line('application_timed')?>">
                    <option value="5">5 Min</option>
                    <option value="10">10 Min</option>
                    <option value="15">15 Min</option>
                    <option value="20">20 Min</option>
                    <option value="30">30 Min</option>
                    <option value="45">45 Min</option>
                    <option value="60">60 Min</option>
                    <option value="120">2 Horas</option>
                    <option value="240">4 Horas</option>
                    <option value="480">8 Horas</option>
                  </optgroup>
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
                  <th><?=$this->lang->line('application_event_name');?></th>
                  <th><?=$this->lang->line('application_module');?></th>
                  <th><?=$this->lang->line('application_method');?></th>
                  <th><?=$this->lang->line('application_params');?></th>
                  <th><?=$this->lang->line('application_event_type');?></th>
                  <th><?=$this->lang->line('application_start_time');?></th>
                  <th><?=$this->lang->line('application_start_date');?></th>
                  <th><?=$this->lang->line('application_end_date');?></th>
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
      buscaJob();

      $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
    });
      
});
 
function buscaJob(){
  let title = $('#buscaName').val();
  let module_path = $('#buscaModule').val();
  let module_params = $('#buscaParams').val();
  let module_method = $('#buscaMethod').val();
  let event_type  = $('#buscaEventType').val();

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
      url: base_url + 'calendar/fetchCalendarData',
      data: { [csrfName]: csrfHash, title:title, module_path: module_path, module_params: module_params, module_method: module_method, event_type: event_type},
      pages: 2
    })
  });
}

function clearFilters(){
  $('#buscaName').val('');
  $('#buscaModule').val('');
  $('#buscaMethod').val('');
  $('#buscaParams').val('');
  $('#buscaEventType').val('');
  buscaJob();
}

  </script>
