<!--
SW Serviços de Informática 2019

Listar Log History

Obs:
Somente para o administrador 

Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
     
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
		  <div class="col-md-2">
            <div class="input-group" >
              <label for="buscalog" class="normal">log</label>
              <select class="form-control" id="buscalog" onchange="buscaLog()">
              	<option value="api">API</option>
                <option value="batch">BATCH</option>
                <option value="general" selected>GENERAL</option>
              </select>
            </div>
          </div>
          	        
		  <div class="col-md-2">
            <div class="input-group" >
              <label for="buscatype" class="normal"><?=$this->lang->line('application_type');?></label>
              <select class="form-control" id="buscatype" onchange="buscaLog()">
                <option value=""><?=$this->lang->line('application_select');?></option>
                <option value="E" selected>Erro</option>
                <option value="I">Info</option>
                <option value="W">Warning</option>
              </select>
            </div>
          </div>
          
          <div class="col-md-3">
            <label for="buscamodule" class="normal"><?=$this->lang->line('application_module');?></label>
            <div class="input-group">
              <input type="search" id="buscamodule" onchange="buscaLog()" class="form-control" placeholder="<?=$this->lang->line('application_module');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
          
		  <div class="col-md-3">
            <label for="buscaaction" class="normal"><?=$this->lang->line('application_action');?></label>
            <div class="input-group">
              <input type="search" id="buscaaction" onchange="buscaLog()" class="form-control" placeholder="<?=$this->lang->line('application_action');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
          
          <div class="col-md-3">
            <label for="buscavalue" class="normal"><?=$this->lang->line('application_value');?></label>
            <div class="input-group">
              <input type="search" id="buscavalue" onchange="buscaLog()" class="form-control" placeholder="<?=$this->lang->line('application_value');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
  
          <div class="row"></div>
          
	      <div class="col-md-3">
	         <label for="start_date" class="normal"><?=$this->lang->line('application_start_date')?></label>
		     <div class="input-group date">
		        <input type='text' class="form-control" name="start_date" id="start_date_ini" onchange="buscaLog()" autocomplete="off" />
	            <span class="input-group-addon">
	                 <span class="glyphicon glyphicon-calendar"></span>
	            </span>
	         </div>
	      </div>
	      <div class="col-md-3">
	         <label for="end_date" class="normal"><?=$this->lang->line('application_end_date')?></label>
		     <div class="input-group date">
		        <input type='text' class="form-control" name="end_date" id="end_date_ini" onchange="buscaLog()" autocomplete="off" />
	            <span class="input-group-addon">
	                 <span class="glyphicon glyphicon-calendar"></span>
	            </span>
	         </div>
	      </div>
	        
          <div class="col-md-1"  >
			  <label  class="normal" style="display: block;">&nbsp; </label>
       		  <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
          </div>
        </div>
        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>           	
              	<th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_date');?></th>
                <th><?=$this->lang->line('application_username');?></th>
                <th><?=$this->lang->line('application_type');?></th>
                <th><?=$this->lang->line('application_module');?></th>
                <th><?=$this->lang->line('application_action');?></th>
                <th><?=$this->lang->line('application_ip');?></th>
                <th><?=$this->lang->line('application_action');?></th>
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
var startdate='';
var enddate='';

$(document).ready(function() {
 
	let log = $('#buscalog').val();
	let type = $('#buscatype').val();
	let value  = $('#buscavalue').val();
	let action  = $('#buscaaction').val();
	let module = $('#buscamodule').val();

	$('#start_date_ini').datetimepicker({format: 'YYYY-MM-DD HH:mm' ,
	    useCurrent: false //Important! See issue #1075
	    });
	$('#end_date_ini').datetimepicker({
	    format: 'YYYY-MM-DD HH:mm',
	    useCurrent: false //Important! See issue #1075
	});
	$("#start_date_ini").on("dp.change", function (e) {
		buscaLog();
		('#end_date_ini').data("DateTimePicker").minDate(e.date);
	});
	$("#end_date_ini").on("dp.change", function (e) {
		buscaLog();
	    $('#start_date_ini').data("DateTimePicker").maxDate(e.date);
	});
       
  
  $("#storeNav").addClass('active');
      table = $('#manageTable').DataTable( {
	    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
        "processing": true,
        "serverSide": true,
        "responsive": true,
        "sortable": true,
        "serverMethod": "post",        
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'listlog/fetchLogData',
             data: {log: log, type: type, value: value, action: action, module: module, startdate: startdate, enddate: enddate},
            pages: 2 // number of pages to cache   
        } )
    } );
});

function buscaLog(){
  let log = $('#buscalog').val();
  let type = $('#buscatype').val();
  let value  = $('#buscavalue').val();
  let action  = $('#buscaaction').val();
  let module = $('#buscamodule').val();
  startdate = $('#start_date_ini').val();
  enddate = $('#end_date_ini').val();

  table.destroy();
  table = $('#manageTable').DataTable( {
	    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
        "processing": true,
        "serverSide": true,
        "responsive": true,
        "sortable": true,
        "serverMethod": "post",        
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'listlog/fetchLogData',
             data: {log: log, type: type, value: value, action: action, module: module, startdate: startdate, enddate: enddate},
            pages: 2 // number of pages to cache   
        } )
    });
}

function clearFilters(){
  $('#buscatype').val('');
  $('#buscavalue').val('');
  $('#buscaaction').val('');
  $('#buscamodule').val('');
  $('#start_date_ini').val('');
  $('#end_date_ini').val('');
  buscaLog();
}

</script>
