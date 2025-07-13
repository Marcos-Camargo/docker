<!--
SW Serviços de Informática 2019

Listar Usuarios

Obs:
Agencias podem ver todos os usuarios das suas empresas
Admin pode ver usuarios de todas as empresas e agencias

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
              <p><?php echo $this->session->flashdata('success'); ?></p>
            </div>
          <?php elseif($this->session->flashdata('error')): ?>
            <div class="alert alert-error alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <?php echo $this->session->flashdata('error'); ?>
            </div>
          <?php endif; ?>
          
          <?php if(in_array('createUser', $user_permission)): ?>
            <a href="<?php echo base_url('users/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_user');?></a>
            <br /> <br />
          <?php endif; ?>

		  <div class="">
        
	          <div class="col-md-3">
	            <label for="buscanome" class="normal"><?=$this->lang->line('application_name');?></label>
	            <div class="input-group">
	              <input type="search" id="buscanome" onchange="buscaUser()" class="form-control" placeholder="<?=$this->lang->line('application_name');?>" aria-label="Search" aria-describedby="basic-addon1">
	              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
	            </div>
	          </div>
	          
	          <div class="col-md-3">
	            <label for="buscaemail" class="normal"><?=$this->lang->line('application_email');?></label>
	            <div class="input-group">
	              <input type="search" id="buscaemail" onchange="buscaUser()" class="form-control" placeholder="<?=$this->lang->line('application_email');?>" aria-label="Search" aria-describedby="basic-addon1">
	              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
	            </div>
	          </div>
	          
	          <div class="col-md-3">
	            <label for="buscaempresa" class="normal"><?=$this->lang->line('application_company');?></label>
	            <div class="input-group">
	              <input type="search" id="buscaempresa" onchange="buscaUser()" class="form-control" placeholder="<?=$this->lang->line('application_company');?>" aria-label="Search" aria-describedby="basic-addon1">
	              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
	            </div>
	          </div>
	          
	          <div class="col-md-3">
	            <label for="buscagrupo" class="normal"><?=$this->lang->line('application_group');?></label>
	            <div class="input-group">
	              <input type="search" id="buscagrupo" onchange="buscaUser()" class="form-control" placeholder="<?=$this->lang->line('application_group');?>" aria-label="Search" aria-describedby="basic-addon1">
	              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
	            </div>
	          </div>
	          
	          <div class="col-md-3">
	            <div class="input-group" >
	              <label for="buscastatus" class="normal"><?=$this->lang->line('application_status');?></label>
	              <select class="form-control" id="buscastatus" onchange="buscaUser()">
	                <option value=""><?=$this->lang->line('application_all');?></option>
	                <option value="1" selected><?=$this->lang->line('application_active');?></option>
	                <option value="2"><?=$this->lang->line('application_inactive');?></option>
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
              <table id="manageTable" class="table table-bordered table-striped" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                <thead>
                <tr>
                  <?php if($sellercenter!='novomundo'): ?>
                  <th><?=$this->lang->line('application_username');?></th>
                  <?php endif; ?>
                  <th><?=$this->lang->line('application_email');?></th>
                  <th><?=$this->lang->line('application_firstname');?></th>
                  <th><?=$this->lang->line('application_phone');?></th>
                  <th><?=$this->lang->line('application_group');?></th>
                  <th><?=$this->lang->line('application_company');?></th>
                  <th><?=$this->lang->line('application_last_login');?></th>
                  <th><?=$this->lang->line('application_status');?></th>
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

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

// para csrf 
var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
$(document).ready(function() {
   //   $('#userTable').DataTable( {
    //  	  "scrollX": true,
	//      "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"	 }
    //  });

      $("#mainUserNav").addClass('active');
      $("#manageUserNav").addClass('active');
      buscaUser()
      
});
 
function buscaUser(){
  let nome = $('#buscanome').val();
  let empresa = $('#buscaempresa').val();
  let grupo = $('#buscagrupo').val();
  let email = $('#buscaemail').val();
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
      url: base_url + 'users/fetchUsersData',
      data: { [csrfName]: csrfHash, nome: nome, empresa: empresa, status: status, email: email, grupo: grupo},
      pages: 2
    })
  });
}

function clearFilters(){
  $('#buscanome').val('');
  $('#buscaempresa').val('');
  $('#buscaemail').val('');
  $('#buscaempresa').val('');
  $('#buscagrupo').val('');
  buscaUser();
}
  </script>
