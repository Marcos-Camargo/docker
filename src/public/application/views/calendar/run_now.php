<!--
SW Serviços de Informática 2019

Deletar usuarios

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_run_now";  $this->load->view('templates/content_header',$data); ?>

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

          <h2><?=$this->lang->line('messages_run_now_confirmation');?></h2>
          
          <form action="<?php echo base_url('calendar/runNow/'.$id) ?>" method="post">
          	<div class="col-md-12 col-xs-12 pull pull-left">
	          	<div class="form-group col-md-6">
		            <label for="id"><?=$this->lang->line('application_module');?></label>
		            <div>
		              <span class="form-control"><?php echo $event['module_path'] ;?></span>
		            </div>
		        </div>
		        <div class="form-group col-md-2">
		            <label for="id"><?=$this->lang->line('application_method');?></label>
		            <div>
		              <span class="form-control"><?php echo $event['module_method']; ?></span>
		            </div>
		        </div>
		        <div class="form-group col-md-2">
		            <label for="id"><?=$this->lang->line('application_params');?></label>
		            <div>
		              <span class="form-control"><?php echo (is_null($event['params'])) ? 'null' : $event['params'] ; ?></span>
		            </div>
		        </div>
          	 </div>
          	<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
            <input type="submit" class="btn btn-primary" name="confirm" value="<?=$this->lang->line('application_confirm');?>">
            <a href="<?php echo base_url('calendar') ?>" class="btn btn-warning"><?=$this->lang->line('application_cancel');?></a>
          </form>

        </div>
        <!-- col-md-12 -->
      </div>
      <!-- /.row -->
      

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->


<script type="text/javascript">
  $(document).ready(function() {
    $("#mainJobsNav").addClass('active');
    $("#manageJobsNav").addClass('active');
  });
</script>