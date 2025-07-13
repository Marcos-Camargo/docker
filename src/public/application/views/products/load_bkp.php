<!--
SW Serviços de Informática 2019

Carga de Produtos

Obs:
cada usuario so pode Carregar produtos da sua empresa.
Agencias NAO podem carregar produtos de outras empresas
Admin NAO podem carrgar produtos de outras empresas ou agencias

ADMIN e AGENCIAS NAO DEVEM CARREGAR PRODUTOS

-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
<?php
ini_set("auto_detect_line_endings", true);    // Treat EOL from all architectures
?>
	<?php $data['pageinfo'] = "application_import";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
	  
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">
		
		<?php if(in_array('createProduct', $user_permission)): ?>
		<div id="showActions" style="height:20px">
			<a class="pull-right btn btn-primary" href="<?php echo base_url('export/categoriesxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_categories_export')?></a>
			<span class ="pull-right">&nbsp</span> 
			<a class="pull-right btn btn-primary" href="<?php echo base_url('export/fabricantesxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_brands_export')?></a>
			<span class ="pull-right">&nbsp</span> 
			<a class="pull-right btn btn-primary" href="<?php echo base_url('export/origemxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_origem_export')?></a>			
			<span class ="pull-right">&nbsp</span> 
			<a class="pull-right btn btn-primary" href="<?php echo base_url('export/lojaxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_store_export')?></a>			
	    	<!---<span class ="pull-right">&nbsp</span> 
	    	<a class="pull-right btn btn-primary" href="<?php echo base_url('export/CategoriesMarketplaceXls') ?>"><i class="fa fa-file-excel-o"></i>Categorias Marketplaces</a>	
	    	---> 
	    </div>  
	    <br />  
	    <?php endif; ?>
	    
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
        
        <?php if (($this->data['upload_file'] != "Nenhum Arquivo foi escolhido.") && (strpos($this->data['upload_file'],'assets/files/product_upload'))===false) : ?>
			<div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->data['upload_file'];  ?>
          	</div>
		<?php endif; ?>

        <form role="form" action="<?php base_url('products/load') ?>" method="post" enctype="multipart/form-data">
        <?php if(in_array('createProduct', $user_permission)): ?>
          <?php if ($this->data['upload_point']==1) {  ?> 
		  <div class="box">
            <div class="box-body">
                <!-- ?php echo validation_errors(); ?  -->
				<div class="row">
	                <div class="form-group col-md-12">
		              <div class="col-md-2">
	                 	 <label for="product_upload"><?=$this->lang->line('messages_upload_file');?></label>
		              </div>
	                  <div class="kv-avatar col-md-6">
	                      <div class="file-loading">
	                          <input id="product_upload" name="product_upload" type="file">
	                      </div>
	                  </div>
		              <div class="col-md-4">
					  	<!-- button type="submit" class="btn btn-primary" name="validate" ><?=$this->lang->line('application_sample_product_file');?></button -->
					  	<a download="sample_products.csv" href="<?php echo base_url('assets/files/sample_products.csv') ?>" class="btn btn-primary"><?=$this->lang->line('application_sample_product_file');?></a>
		              </div>	
	                </div>  <!-- form group -->
				</div> <!-- row -->
				<div class="row">
		              <div class="col-md-2">
					  	<button type="submit" class="btn btn-primary" name="validate" ><?=$this->lang->line('application_validate_file');?></button>
		              </div>	
 				</div>
          	</div> <!-- box body -->
		  </div> <! -- BOX -->	
          <br />
	   <?php } // upload point ?> 
       <?php endif; //  can create ?>
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"><?=$this->lang->line('application_file_analysis');?></h3>
          </div>
          <!-- /.box-header -->
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
<?php
// Rows
	$process = false;
	$goterrors = false;
	$erros = 0;
	$filename = FCPATH.$this->data['upload_file'];
	if (file_exists ($filename))
	{
		$process = true; 
		$linha = 1;
		$prim = true; 
		$head = array(); 
		$column = array();
		$handle = fopen($filename, 'r');
	    while (($row = fgetcsv($handle, 0, ";")) !== FALSE) 
	    //while (($row = utf8_encode(fgets($handle))) !== FALSE) 
	    {
	       //$row = array_map("utf8_encode", $row);
		   try {
			   if (count($row)>5) {
				   if ($prim) {
						echo "<th class='col-md-1'>{$this->lang->line('application_success')}</th>";
						echo "<th class='col-md-1'>{$this->lang->line('application_line')}</th>";
						echo "<th class='col-md-9'>{$this->lang->line('application_message')}</th>";
			            echo "</tr></thead><tbody>";
			            // $head = $row;
			            $head = array();
			            foreach($row as $r) {
			            	$head[]= preg_replace( '/[^a-z0-9_ ]/i', '',$r);
			            }  
			            $prim = false;
					} else {
						if (count($head) == count($row)) {
							$column = array_combine($head, $row);
					        $msg = "";
					        if ($this->data['upload_point']==2) {
								$ok = CheckCSVFile($column,$this,$msg,$goterrors);
								if ($ok == "N") {
					           		echo "<tr>";
									echo "<td>".$ok."</td>";
									echo "<td>".$linha."</td>";
									echo "<td>".$msg."</td>";
						            echo "</tr>";
									$erros++;
					            } 
							} else {
								$ok = LoadProduct($column,$this,$msg,$goterrors);
				           		echo "<tr>";
								echo "<td>".$ok."</td>";
								echo "<td>".$linha."</td>";
								echo "<td>".$msg."</td>";
					            echo "</tr>";
							}
						} else {
							$goterrors =TRUE;
							$erros++; 
							if (count($head) > count($row)) {
								echo "<tr>";
								echo "<td>N</td>";
								echo "<td>".$linha."</td>";
								echo "<td>Falta colunas</td>";
					            echo "</tr>";
							}
							else {
								echo "<tr>";
								echo "<td>N</td>";
								echo "<td>".$linha."</td>";
								echo "<td>Colunas excedentes</td>";
					            echo "</tr>";
							} 
						}
		            }
	            
		       // print_r($row);
			    } else {
					echo "<tr><td>{$this->lang->line('messages_file_format_invalid')}o</td></tr>";
					break;
			    }
		    }
			catch (customException $e) {
			  //display custom message
			  echo $e->errorMessage();
			  break;
			}
			$linha++;
		}
		$linha = $linha - 2;
	    if (!$goterrors) {
       		echo "<tr>";
			echo "<td></td>";
			echo "<td></td>";
			echo "<td>".$linha." ".$this->lang->line('messages_linesprocessed')." ".$this->lang->line('messages_noerrors')."</td>";
            echo "</tr>";
		} else {
       		echo "<tr>";
			echo "<td></td>";
			echo "<td></td>";
			echo "<td>".$linha." ".$this->lang->line('messages_linesprocessed')." ".$erros." ".$this->lang->line('messages_errors')."</td>";
            echo "</tr>";
		}
	} else {
		if ($this->data['upload_file'] != "Nenhum Arquivo foi escolhido.") {
			echo '
			<div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            '.$this->data['upload_file'].'
          </div>
          ';
		};
	}
?>		          
               </tbody>
            </table>
          </div>
          <!-- /.box-body -->
          <div class="box-footer">
 <?php if ($this->data['upload_point']==2) { if (!$goterrors) { ?>
                <button type="submit" class="btn btn-success" name="noerrors"><?=$this->lang->line('application_load_file');?></button>
                <input id="upload_file" name="upload_file" type="hidden" value="<?=$this->data['upload_file']; ?>">
 <?php } else { ?>
                <button type="submit" class="btn btn-warning" name="witherrors"><?=$this->lang->line('application_upload_error_file');?></button>
                <input id="upload_file" name="upload_file" type="hidden" value="<?=$this->data['upload_file']; ?>">
 <?php } ?>    
            <a href="<?php echo base_url('products/load') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
<?php } ?>            
          </div>
          <!-- /.box footer -->
        </div>
        <!-- /.box -->
 <?php if ($this->data['upload_point']>2) {
	 		if ($goterrors) {
		 	$this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
	 		} else {               
		 	$this->session->set_flashdata('success', 'Successfully uploaded');
		 	}
		} 
?>		
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

		</form>

      </div>
      <!-- col-md-12 -->
    </div>
    <!-- /.row -->
	</div>
	<!-- /.Fluid -->
  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->


<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";
  $(document).ready(function() {
    var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' + 
        'onclick="alert(\'Call your custom code here.\')">' +
        '<i class="glyphicon glyphicon-tag"></i>' +
        '</button>'; 
    $("#product_upload").fileinput({
        overwriteInitial: true,
        maxFileSize: 100000,
        showClose: false,
        showCaption: false,
        browseLabel: '',
        removeLabel: '',
        language:'pt-BR',
        browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
        removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
        removeTitle: 'Cancel or reset changes',
        elErrorContainer: '#kv-avatar-errors-1',
        msgErrorClass: 'alert alert-block alert-danger',
        // defaultPreviewContent: '<img src="/uploads/default_avatar_male.jpg" alt="Your Avatar">',
        layoutTemplates: {main2: '{preview} {remove} {browse}'},
        allowedFileExtensions: ["csv", "txt"]
    });

  });
</script>
<?php
	function CheckCSVFile($linha,$me,&$msg,&$goterrors) {
		$data = array();	
	  if ($me->data['mycontroller']->CheckProductLoadData($linha,$msg,$data,false)) {	
		  return "S";
	  } else {
		  $goterrors = true;
		  return "N";
	  }	
	}
	function LoadProduct($linha,$me,&$msg,&$goterrors) {
//		if ($me->data['usercomp']==1) {
//			$cpy = $linha['empresa'];
//		} else {
			$cpy = $me->data['usercomp'];
//		}
		$data = array();	
		$data['company_id'] = $cpy;
	  if ($me->data['mycontroller']->CheckProductLoadData($linha,$msg,$data,true)) {
	  	 if ($data != '') {	// ignoro linha em branco
			$create = true;
		 //var_dump($data);
			$create = $me->model_products->replace($data);
			
			//echo print_r($create,true); 
			//echo print_r($data,true); 
			//die;
			if($create == true) {
				$msg = "Linha IMPORTADA";
				return "S";
			}
			else {
				$msg = "ERRO NA IMPORTAÇÃO. TENTE NOVAMENTE.";
				$goterrors = true;
				return "N";
			}
		//} else {
		//	$msg = "Linha NÃO IMPORTADA. " . $msg;
		//	return "N";
		//}	
		}
		 else {
		 	$msg = "Linha em Branco Ignorada";
		    return "S";
		 }
	  } else {
		  $msg = "Linha NÃO IMPORTADA. " . $msg;
		  return "N";
	  }	
	}
