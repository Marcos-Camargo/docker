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
	          <a href="<?php echo base_url('campaigns/createcampaigns') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_campaign');?></a>
	    <?php endif; ?>
        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_marketplace');?></th>
                <th><?=$this->lang->line('application_commission_type');?></th>
                <th><?=$this->lang->line('application_start_date');?></th>
                <th><?=$this->lang->line('application_end_date');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <?php if(in_array('updateCampaigns', $user_permission) || in_array('viewCampaigns', $user_permission) || in_array('deleteCampaigns', $user_permission)): ?>
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

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
 manageTable = $('#manageTable').DataTable({
	    'ajax': base_url + 'campaigns/buscarlistacampanha/',
	    'order': []
	  });

});

 
</script>
