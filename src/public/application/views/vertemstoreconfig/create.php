

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
              <h3 class="box-title"><?=($integration_id =='') ? $this->lang->line('application_choose_the_store_for_integration_seller_id') : $this->lang->line('application_change_seller_id');?></h3>
            </div>
            <form role="form" action="<?php base_url('vertemStoreConfig/{$form}') ?>" method="post">
            	<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
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
                	 <?php 
                	  	if ($integration_id == '') {
                	  ?> 
                	<div class="form-group col-md-4 col-xs-12">
	                  <label for="store"><?=$this->lang->line('application_store');?></label>					
	                  <select class="form-control select_group" id="store" name="store" onchange="buscaStore()">
	                    <?php foreach ($stores as $k => $v): ?>
	                      <option value="<?php echo $v['id'] ?>" <?php echo set_select('store', $v['id']) ?> ><?php echo $v['name'] ?></option>
	                    <?php endforeach ?>
	                  </select>		
	                </div>
	                <?php 
                	  	} else {
                	  ?>
	                <div class="form-group col-md-4 col-xs-12">
	                  <label for="store"><?=$this->lang->line('application_store');?></label>					
	                  <span class="form-control "><?=$store;?></span>
	                  <input type="hidden" name="integration_id" value="<?=$integration_id;?>" />
	                </div>
	                <?php 
                	  	} 
                	  ?>
                	  
	                <div class="form-group col-md-5 <?php echo (form_error('seller_id')) ? 'has-error' : '';  ?>">
	                  	<label for="seller_id"><?=$this->lang->line('application_supplier_id');?>(*)</label>
	                  	<input type="text" class="form-control" id="seller_id" name="seller_id" required placeholder="<?=$this->lang->line('application_supplier_id');?>" value="<?php echo set_value('seller_id', $seller_id) ?>"  autocomplete="off">
	                	<?php echo '<i style="color:red">'.form_error('seller_id').'</i>'; ?>  
	                </div>
	                
	                <div class="form-group col-md-11">
                        <label><?=$this->lang->line('application_key_to_integration_with_vertem_store');?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="token_api" name="token_api" value="<?= $token_api ?>" readonly>
                            <span class="input-group-btn">
                                <button type="button" data-toggle="tooltip" title="<?=$this->lang->line('application_copy');?>" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                            </span>
                        </div>
                    </div>

                </div>

              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                 <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <a href="<?php echo base_url('vertemStoreConfig/index') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
             	
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

// para csrf 
var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
var base_url = "<?php echo base_url(); ?>";

  $(document).ready(function() {

    $("#mainIntegrationMarketplaceNav").addClass('active');
    $("#VertemStoreIntegrationMarketplace").addClass('active');
   
	buscaStore();
  });
  
  $('.copy-input').click(function() {
        // Seleciona o conteúdo do input
        $(this).closest('.input-group').find('input').select();
        // Copia o conteudo selecionado
        const copy = document.execCommand('copy');
        if (copy) {
            Toast.fire({
                icon: 'success',
                title: '<?php echo $this->lang->line("application_content_successfully_copied") ?>'
            })
        } else {
            Toast.fire({
                icon: 'success',
                title: '<?php echo $this->lang->line("application_unable_to_copy_content") ?>'   
            })
        }
    });
    
function buscaStore(){
  	let loja = $('#store').val();
  	
	$.ajax({
		url : base_url + 'vertemStoreConfig/getTokenApi',
        type: "POST",
        data: {
            loja: loja, 
            [csrfName]: csrfHash
        },
        dataType: 'json',
        async: true,
	    success: function(success) {
			console.log(success)
			document.getElementById("token_api").value = success.token;
		},
		error: function(error) {
			console.log(error)
		}
	});

}
    
</script>

