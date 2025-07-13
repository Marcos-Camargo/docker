
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
              <h3 class="box-title"><?=$this->lang->line('application_log_history');?></h3>
            </div>           
            <div class="box-body">            
            	  <div class="form-group col-md-1">
                  <label><?=$this->lang->line('application_id');?></label>
                 	<p><?php echo $job['id'] ?></p>
                </div>
                <div class="form-group col-md-2">
                  <label><?=$this->lang->line('application_error');?></label>
                 	 <p>
                      <?php 
                          echo $jobhistory['success']==1 ? '<span class="label label-success">' . $this->lang->line('application_no') . '</span>': '<span class="label label-danger">' . $this->lang->line('application_yes') . '</span>'     
				   	          ?>
                   </p>
                </div>
                <div class="form-group col-md-2">
                  <label><?=$this->lang->line('application_start_date');?></label>
                 	<p><?php echo (is_null( $job['date_start']) ? '' : date('d/m/Y H:i:s', strtotime($job['date_start']))) ?></p>
                </div>
                <div class="form-group col-md-2">
                  <label><?=$this->lang->line('payment_balance_transfers_date_end');?></label>
                 	<p><?php echo (is_null($jobhistory['date_create']) ? '' : date('d/m/Y H:i:s', strtotime($jobhistory['date_create']))) ?></p>
                </div>

                <div class="form-group col-md-3">
                  <label><?=$this->lang->line('application_status');?></label>
                  <p><?="<span class='label label-".getColorLabelJobStatus($job['status'])."'>{$this->lang->line("application_status_job_history_$job[status]")}</span>"?></p>
                </div>
                <div class="form-group col-md-6">
                  <label><?=$this->lang->line('application_module');?></label>
                 	 <p><?php echo $jobhistory['job'] ?></p>
                </div>
                
                
                <div class="form-group col-md-12">
                  <label><?=$this->lang->line('iugu_filter_option_view_last');?></label>
                  <a href="<?=$jobhistory['log_url']?>"><?=$this->lang->line('application_download_file');?></a>
                  <textarea readonly class="form-control" id="valtext" width="100%" rows="24"><?php echo ((print_r($log,true))) ?></textarea>
                  
                </div>
               
               
            </div>
            
            
             <div class="box-footer">
	            
	            <a href="<?php echo base_url('jobsHistory/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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
    $("#mainJobsNav").addClass('active');
    $("#manageJobsNav").addClass('active');
  });
</script>

