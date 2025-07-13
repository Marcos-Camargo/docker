<!--
SW Serviços de Informática 2019

Carga de NFE

Obs:
cada usuario so pode Carregar Notas Fiscais dos pedidos da sua empresa.
Agencias NAO podem carregar NFEs de outras empresas
Admin NAO podem carregar produtos de outras empresas ou agencias

ADMIN e AGENCIAS NAO DEVEM CARREGAR PRODUTOS

-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
    <?php

    ini_set("auto_detect_line_endings", true);    // Treat EOL from all architectures
    $data['pageinfo'] = "application_import_nfe";  $this->load->view('templates/content_header',$data);

    ?>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
	  
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">
        <form role="form" action="<?php base_url('orders/loadnfe') ?>" method="post" enctype="multipart/form-data">
        <?php if(in_array('updateOrder', $user_permission)): ?>
          <?php if ($this->data['upload_point']==1) {  ?> 
		  <div class="box">
            <div class="box-body">
                <!-- ?php echo validation_errors(); ?  -->
				<div class="row">
	                <div class="form-group col-md-12">
		              <div class="col-md-2">
	                 	 <label for="nfe_upload"><?=$this->lang->line('messages_upload_file');?></label>
		              </div>
	                  <div class="kv-avatar col-md-6">
	                      <div class="file-loading">
	                          <input id="nfe_upload" name="nfe_upload" type="file">
	                      </div>
	                  </div>
		              <div class="col-md-4">
					  	<!-- button type="submit" class="btn btn-primary" name="validate" ><?=$this->lang->line('application_sample_nfes_file');?></button -->
					  	<a download="sample_nfes.csv" href="<?php echo base_url('assets/files/sample_nfes.csv') ?>" class="btn btn-primary"><?=$this->lang->line('application_sample_nfes_file');?></a>
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
            <h3 class="box-title"><?=$this->lang->line('application_file_analysis')?></h3>
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
		$process = true; $linha = 1;
		$prim = true; $head = array(); $column = array();
		$handle = fopen($filename, 'r');
	    while (($row = fgetcsv($handle, 0, ";")) !== FALSE) 
	    {
		   try {
			   if (count($row)>5) { 
				   if ($prim) { 
						echo "<th class='col-md-1'>" . $this->lang->line('application_success') . "</th>";
						echo "<th class='col-md-1'>" . $this->lang->line('application_line') . "</th>";
						echo "<th class='col-md-9'>" . $this->lang->line('application_message') . "</th>";
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
								$ok = LoadNfe($column,$this,$msg,$goterrors);
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
								echo "<td>" . $this->lang->line('application_missing_column') . "</td>";
					            echo "</tr>";
							}
							else {
								echo "<tr>";
								echo "<td>N</td>";
								echo "<td>".$linha."</td>";
								echo "<td>" . $this->lang->line('application_surplus_columns') . "</td>";
					            echo "</tr>";
							} 
						}
		            }
	            
		       // print_r($row);
			    } else {
					echo "<tr><td>" . $this->lang->line('messages_file_format_invalid') . "</td></tr>";
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
		echo "Arquivo não existe:". $this->data['upload_file'];
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
            <a href="<?php echo base_url('orders/loadnfe') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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
    $("#mainOrdersNav").addClass('active menu-open');
    $("#loadOrdersNFENav").addClass('active');
    
    var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' + 
        'onclick="alert(\'Call your custom code here.\')">' +
        '<i class="glyphicon glyphicon-tag"></i>' +
        '</button>'; 
    $("#nfe_upload").fileinput({
        overwriteInitial: true,
        maxFileSize: 1500,
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
	  if ($me->data['mycontroller']->CheckNfeLoadData($linha,$msg,$data)) {	
		  return "S";
	  } else {
		  $goterrors = true;
		  return "N";
	  }	
	}
	function LoadNfe($linha,$me,&$msg,&$goterrors) {
		if ($me->data['usercomp']==1) {
			$cpy = $linha['empresa'];
		} else {
			$cpy = $me->data['usercomp'];
		}
		$data = array();	
		$data['company_id'] = $cpy;
	  if ($me->data['mycontroller']->CheckNfeLoadData($linha,$msg,$data)) {
	  	 if ($data != '') {	// ignoro linha em branco	
			$create = true;
			$datacoleta = $data['data_coleta'];
			unset($data['data_coleta']);
			$create = $me->model_nfes->replace($data);
			if($create == true) {
				$updatacoleta = $me->model_orders->updateDataColeta($data['order_id'],$datacoleta);
				$uppaidstatus = $me->model_orders->updatePaidStatus($data['order_id'],'52');
				$msg = "Linha IMPORTADA";
				return "S";
			}
			else {
				$msg = "ERRO NA IMPORTAÇÃO. TENTE NOVAMENTE.";
				$goterrors = true;
				return "N";
			}
		 }
			 else {
		 	$msg = "Linha em Branco Ignorada";
		    return "S";
		 }
		//} else {
		//	$msg = "Linha NÃO IMPORTADA. " . $msg;
		//	return "N";
		//}	
	  } else {
		  $msg = "Linha NÃO IMPORTADA. " . $msg;
		  return "N";
	  }	
	}
