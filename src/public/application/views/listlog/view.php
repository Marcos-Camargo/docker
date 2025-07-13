
  <?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<!--

Ver Log
 
-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>

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

          <div class="box">
            <div class="box-header">
              <h3 class="box-title">LOG</h3>
            </div>
            <div class="box-footerx">
	          	<?php  if ($log_data['retorna']): ?>
	            <a href="<?php echo base_url('listlog/view/'.$log_data['log'].'/'.($log_data['id']-1)) ?>" class="btn btn-success">
	            	<i class="glyphicon glyphicon-arrow-left" aria-hidden="true"></i><?php echo $log_data['id']-1 ?>
	            </a>
	            <?php endif; ?>
	            <?php  if ($log_data['avanca']): ?>
	            <a href="<?php echo base_url('listlog/view/'.$log_data['log'].'/'.($log_data['id']+1)) ?>" class="btn btn-success">
	            	<i class="glyphicon glyphicon-arrow-right" aria-hidden="true"></i><?php echo $log_data['id']+1 ?>
	            </a>
	            <?php endif; ?>
	         </div>
            <div class="box-body">
            	<div class="form-group col-md-3">
                  <label><?=$this->lang->line('application_date');?></label>
                 	<p><?php echo $log_data['date_log'] ?></p>
                </div>
                <div class="form-group col-md-3">
                  <label><?=$this->lang->line('application_username');?></label>
                 	 <p><?php echo $log_data['user'] ?></p>
                </div>
                <div class="form-group col-md-3">
                  <label><?=$this->lang->line('application_module');?></label>
                 	 <p><?php echo $log_data['module'] ?></p>
                </div>
                <div class="form-group col-md-3">
                  <label><?=$this->lang->line('application_type');?></label>
                 	 <p><?php
                 	 	if ($log_data['tipo'] == 'I') {
							echo  '<span class="label label-success">Info</span>';			
						} elseif ($log_data['tipo'] == 'W') {
							echo '<span class="label label-warning">Warning</span>';
						} else {
							echo '<span class="label label-danger">Error</span>'; 
						}
				   	  ?></p>
                </div>
                <div class="form-group col-md-3">
                  <label><?=$this->lang->line('application_action');?></label>
                 	<p><?php echo $log_data['action'] ?></p>
                </div>
                <div class="form-group col-md-3">
                  <label><?=$this->lang->line('application_ip');?></label>
                 	<p><?php echo $log_data['ip'] ?></p>
                </div>
                <div class="form-group col-md-12">
                  <label><?=$this->lang->line('application_value');?></label>
                  <textarea readonly class="form-control" id="valtext" width="100%" rows="12"><?php echo ((print_r($log_data['value'],true))) ?></textarea>

                </div>
               
               
            </div>
            
            
             <div class="box-footer">
	            
	            <a href="<?php echo base_url('listlog/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
	         </div>
            
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
  $(document).ready(function() {
    $("#storeNav").addClass('active');
  });
</script>

