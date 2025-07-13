

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_link";  $this->load->view('templates/content_header',$data); ?>

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
              <h3 class="box-title"><?=$this->lang->line('application_category_link');?></h3>
            </div>
            <form role="form" action="<?php base_url('category/link') ?>" method="post"  enctype="multipart/form-data" id="editForm">
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
                    <?php
                    }
                  }
                } ?>

                <div class="row">
                    <div class="form-group col-md-9">
                        <label><?=$this->lang->line('application_category');?></label>
                        <span class="form-control"><?php echo $category['name'];?></span>
                    </div>
                    <div class="form-group col-md-3">
                    	<label><?=$this->lang->line('application_status');?></label>
                        <?php  $active = ($category['active'] == 1) ?  $this->lang->line('application_active') : $this->lang->line('application_inactive'); ?>
						<span class="form-control"><?php echo $active;?></span>
                    </div>
                </div>
                <div class="row">
                	<div class="form-group col-md-12">
                		<label >Pesquisa categorias: </label>
                		<label class="checkbox-inline">
                            <input id="todos_limitado" data-size="normal" name="todos_limitado" type="checkbox" <?php echo set_checkbox('todos_limitado', 'on'); ?> data-toggle="toggle" data-off="Limitado" data-on="Todas" data-onstyle="success" data-offstyle="warning" >
                        </label>
                    </div>
                </div>
               
				<?php foreach($marketplaces as $int_to): ?> 
				<div class="row">
					<div class="form-group col-md-2">
                        <label ><?=$this->lang->line('application_marketplace');?></label>
                        <span class="form-control"><?php echo $int_to;?></span>
                    </div>

	            	<div class="form-group col-md-10 <?php echo (form_error('tipos_volumes[]')) ? "has-error" : "";?>">
	                    <label for="link_cat_<?=$int_to;?>"><?=$this->lang->line('application_marketplace_category');?></label>
	                    
                        <div class="todas">
	                    <select class="form-control selectpicker" data-size="8" data-live-search="true" data-actions-box="true" id="link_cat_<?=$int_to;?>" name="link_cat_<?=$int_to;?>" title="<?=$this->lang->line('application_select');?>"  >
	                      <!--  <option  disabled value=""><?=$this->lang->line('application_select');?></option> -->
	                       
	                        <?php  foreach ($categorias_mkt[$int_to] as $categoria_mkt):
								$selecionado = false;
								foreach ($category_link as $catlk) {
									if ($catlk['category_id']) {
										if (($categoria_mkt['id'] == $catlk['category_marketplace_id']) && ($catlk['int_to'] == $int_to)) {
											$selecionado = true;
			
											break;
										}
										
									}
								}
							 ?>
	                            <option value="<?php echo $categoria_mkt['id']; ?>" <?=set_select('link_cat_'.$int_to, $categoria_mkt['id'],  $selecionado )?> data-subtext="<?php echo "(".$categoria_mkt['id'].")" ?>" ><?php echo $categoria_mkt['nome'] ?></option>
	                        <?php endforeach ?>
	                    </select>
	                    </div>
	                    <div class="limitado">
	                    <select class="form-control selectpicker" data-size="8" data-live-search="true" data-actions-box="true" id="link_cat_limitado_<?=$int_to;?>" name="link_cat_limitado_<?=$int_to;?>" title="<?=$this->lang->line('application_select');?>"  >
	                      <!--  <option  disabled value=""><?=$this->lang->line('application_select');?></option> -->
	                       
	                        <?php  foreach ($categorias_mkt_limitado[$int_to] as $categoria_mkt):
								$selecionado = false;
								foreach ($category_link as $catlk) {
									if ($catlk['category_id']) {
										if (($categoria_mkt['id'] == $catlk['category_marketplace_id']) && ($catlk['int_to'] == $int_to)) {
											$selecionado = true;
											break;
										}
									}
								}
							 ?>
	                            <option value="<?php echo $categoria_mkt['id']; ?>" <?=set_select('link_cat_limitado_'.$int_to, $categoria_mkt['id'],  $selecionado )?> data-subtext="<?php echo "(".$categoria_mkt['id'].")" ?>" ><?php echo $categoria_mkt['nome'] ?></option>
	                        <?php endforeach ?>
	                    </select>
	                    </div>
	                    <?php echo '<i style="color:red">'.form_error('tipos_volumes[]').'</i>';  ?>
	            	</div>
	           	</div>
	  			<?php endforeach ?>
              	<div class="box-footer">
                	<button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
                	<a href="<?php echo base_url('category/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              	</div>
              	<div class="box-body">
              		<h4><?=$this->lang->line('application_products_using_this_category');?></h4>
	              	<table id="manageTable" class="table table-bordered table-striped">
		              <thead>
		              <tr>
		             	<th><?=$this->lang->line('application_id');?></th>
		             	<th><?=$this->lang->line('application_sku');?></th>
		                <th><?=$this->lang->line('application_name');?></th>
		                <th><?=$this->lang->line('application_store');?></th>
		              </tr>
		              </thead>
	
	            	</table>
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

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";
var idcat = "<?php echo $category['id'] ?>";

  $(document).ready(function() {
  	$("#mainCategoryNav").addClass('active');
  	$("#manageCategoryNav").addClass('active');
    
    if(document.getElementById('todos_limitado').checked) {
   		$(".limitado").hide();
    	$(".todas").show();
    } else {
    	$(".todas").hide();
    	$(".limitado").show();
    }
    
    $("#todos_limitado").change(function() {
        if(this.checked) {
          	$(".limitado").hide();
    		$(".todas").show();
        } else{
        	$(".limitado").show();
  			$(".todas").hide();
        }   
    });
    manageTable = $('#manageTable').DataTable({
      "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
      "processing": true,
      "serverSide": true,
      "responsive": true,
      "sortable": true,
      "searching": true,
      "serverMethod": "post",
      "ajax": $.fn.dataTable.pipeline({
        url: base_url + 'products/fetchProductsByCategoryData/'+idcat,
        pages: 2 // number of pages to cache
     })
   });

  });

</script>

