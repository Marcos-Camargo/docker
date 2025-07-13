<!--
SW Serviços de Informática 2019

Editar Grupos de Acesso

-->


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_billets";  $this->load->view('templates/content_header',$data); ?>

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
              <h3 class="box-title"><?=$this->lang->line('application_view_billet');?></h3>
            </div>
            <form role="form" action="<?php base_url('groups/update') ?>" method="post">
              <div class="box-body">

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
	       	
                <div class="form-group col-md-8 col-xs-8">
                  <label for="group_name1"><?=$this->lang->line('application_id');?></label>
                  <input type="text" class="form-control" id="group_name1" name="group_name1"  value="<?php echo $billets['id']; ?>" readonly="readonly">
               	  
               	  <label for="group_name2"><?=$this->lang->line('application_runmarketplaces');?></label>
                  <input type="text" class="form-control" id="group_name2" name="group_name2"  value="<?php echo $billets['marketplace']; ?>" readonly="readonly">
               
               	  <label for="group_name3"><?=$this->lang->line('application_number_billet');?></label>
                  <input type="text" class="form-control" id="group_name3" name="group_name3"  value="<?php echo $billets['id_boleto_iugu']; ?>" readonly="readonly">
               
               	  <label for="group_name4"><?=$this->lang->line('application_date');?></label>
                  <input type="text" class="form-control" id="group_nam4e" name="group_name4"  value="<?php echo $billets['data_geracao']; ?>" readonly="readonly">
               
               	  <label for="group_name5"><?=$this->lang->line('application_value');?></label>
                  <input type="text" class="form-control" id="group_name5" name="group_name5"  value="R$ <?php echo str_replace(".",",",$billets['valor_total']); ?>" readonly="readonly">
               
               	  <label for="group_name6"><?=$this->lang->line('application_status_billet');?></label>
                  <input type="text" class="form-control" id="group_name6" name="group_name6"  value="<?php echo $billets['status_billet']; ?>" readonly="readonly">
               
               	  <label for="group_name7"><?=$this->lang->line('application_status_billet_iugu');?></label>
                  <input type="text" class="form-control" id="group_name7" name="group_name7"  value="<?php echo $billets['status_iugu']; ?>" readonly="readonly">
               
                </div>
                
                  
                
              </div>
              
              <div class="box-body">
              
              <div class="form-group col-md-10 col-xs-10">
                  <label for="permission"><?=$this->lang->line('application_permission');?></label>

                  <table class="table table-responsive">
                    <thead>
                      <tr>
                        <th><?=$this->lang->line('application_runmarketplaces');?></th>
                        <th><?=$this->lang->line('application_purchase_id');?></th>
                        <th><?=$this->lang->line('application_status');?></th>
                        <th><?=$this->lang->line('application_value');?></th>
                      </tr>
                    </thead>
                    <tbody>
                    	<?php foreach($billetsData as $boletos){?>
                    	<tr>
                        	<td><?php echo $boletos['marketplace'];?></td>
                        	<td><?php echo $boletos['bill_no'];?></td>
                        	<td><?php echo $boletos['ativo'];?></td>
                        	<td><?php echo $boletos['total_order'];?></td>
                      	</tr>
                      	<?php }?>
                    </tbody>
                  </table>
                  
                </div>
                
              </div>  
              <!-- /.box-body -->
				<div class="box-footer">
                <a href="<?php echo base_url('billet/list') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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
    $("#mainGroupNav").addClass('active');
    $("#manageGroupNav").addClass('active');

    $('input[type="checkbox"].minimal').iCheck({
      checkboxClass: 'icheckbox_minimal-blue',
      radioClass   : 'iradio_minimal-blue'
    });
  });
</script>
