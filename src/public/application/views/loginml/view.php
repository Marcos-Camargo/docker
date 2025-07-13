
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
           			<h3 class="box-title">Autorização no Mercado Livre</h3>
            	</div>
            	<div class="box-body">
            		<div class="form-group col-md-12">
	                	<p><b>Loja <i><?= $store['name'];?></i> foi vinculada a seguinte conta do Mercado Livre</b></p>
	                </div> 
            		<div class="form-group col-md-2">
	                	<label for="nome"><?=$this->lang->line('application_id');?></label>
	                	<span class="form-control"><?= $user['id']; ?></span>
	                </div> 
            		<div class="form-group col-md-4">
	                	<label for="nome"><?=$this->lang->line('application_name');?></label>
	                	<span class="form-control"><?= $user['nickname']; ?></span>
	                </div> 
	                <div class="form-group col-md-4">
	                	<label for="nome"><?=$this->lang->line('application_email');?></label>
	                	<span class="form-control"><?= $user['email']; ?></span>
	                </div>   
	                <div class="form-group col-md-8">
	                	<span><a href="<?= $user['permalink']; ?>"><?= $user['permalink']; ?></a></span>
	                </div> 
	                <div class="form-group col-md-12">
	                	<label for="nome">Seus Dados no Mercado Livre</label>
	                	<pre>
	                	 <?php echo json_encode($user, JSON_PRETTY_PRINT); ?>
	                	 </pre>
	                </div>

            	       
            	    </div>  
            	</div>
           
             	<div class="box-footer">           
	            	<a href="<?php echo base_url('dashboard/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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
    $("#mainIntegrationMarketplaceNav").addClass('active');
    $("#MLIntegrationMarketplace").addClass('active');
  });
</script>

