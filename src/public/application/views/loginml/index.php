 
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
            	 <div class="row">
            	 	<?php if ($this->data['userstore'] ==0) { ?>
            	 	<div class="form-group col-md-4">
	                  	<label><?=$this->lang->line('application_store');?></label>
		                 <span class="form-control"><?= $store['name'];?></span>
		                 <a href="<?php echo base_url('loginML/chooseStore') ?>" class="btn btn-success"><?=$this->lang->line('application_change_store');?></a>
		                 
	                </div>
					
	                <div class="row"></div>
	                <?php  }  ?>
	                <div class="form-group col-md-12">
		            	<p>Para realizar a configuração da integração com o Mercado Livre para enviar seus produtos e buscar seus pedidos é necessário que seja realizado login no Mercado Livre com o usuário ADMINISTRADOR para vincular o aplicativo do Conecta Lá a sua conta do Mercado Livre, usários do tipo Operador ou Colaboror não conseguem realizar esta tarefa. </p>
		            	<br>
		            	<p>Clique no ícone abaixo para ser redirecionado para o Mercado Livre e, depois de logar, clique em "Permitir" </p>
		            	<!--- <a href="<?php echo $loginURL; ?>"><img src="<?php echo base_url('assets/images/google-sign-in-btn.png'); ?>" /></a> --->
		                <br>
		                <a href="<?php echo $loginUrl; ?>" ><img style =" border: 4px solid #000;" src="<?php echo base_url('assets/images/system/logo_marketplaces/mercado_livre.png'); ?>"</a>
           			
           			
           			
           			</div>
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

