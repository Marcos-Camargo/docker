<!--
SW Serviços de Informática 2019

Index de Clientes

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
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

        <?php if(in_array('createClient', $user_permission)): ?>
          <a href="<?php echo base_url('clients/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_new_client');?></a>
          <br /> <br />
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_address');?></th>
                <th><?=$this->lang->line('application_phone');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <?php if(in_array('updateClients', $user_permission) || in_array('viewClients', $user_permission) || in_array('deleteClients', $user_permission)): ?>
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
    </div>    
    <!-- fluid -->
  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->


<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  $("#mainClientsNav").addClass('active');
  $("#manageClientsNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
	      "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },	      
    'ajax': base_url + 'clients/fetchClientsData',
    'company': []
  });

});
  
</script>

