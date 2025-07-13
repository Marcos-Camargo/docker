

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
              <h3 class="box-title"><?=$this->lang->line('application_choose_the_store_for_integration');?></h3>
            </div>
            <form role="form" action="<?php base_url('users/chooseStore') ?>" method="post">
              <div class="box-body">
                <div class="row">
            <?php 
            if (validation_errors()) { 
	          foreach (explode("</p>",validation_errors()) as $erro) { 
		        $erro = trim($erro);
		        if ($erro!="") { ?>
				<div class="alert alert-error alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	                <?php echo $erro."</p>"; ?>
				</div>
		<?php	}
			  }
	       	} ?>
                </div>
                <div class="row">
                	<div class="form-group col-md-4 col-xs-12">
	                  <label for="store"><?=$this->lang->line('application_store');?>(*)</label>					
	                  <select class="form-control select_group" id="store" name="store">
	                    <?php foreach ($stores as $k => $v): ?>
	                      <option value="<?php echo $v['id'] ?>" <?php echo set_select('store', $v['id']) ?> ><?php echo $v['name'] ?></option>
	                    <?php endforeach ?>
	                  </select>		
	                </div>

                </div>

              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_continue');?></button>
                <a href="<?php echo base_url('dashboard/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
             	
              </div>
            </form>
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

    $("#mainIntegrationMarketplaceNav").addClass('active');
    $("#MLIntegrationMarketplace").addClass('active');
   

  });
  
</script>

