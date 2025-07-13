<!--
SW Serviços de Informática 2019

Criar Grupos de Acesso

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_panel_cicle_fiscal_add_invoice"; $data['page_now'] ='panel_fiscal'; $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">

      <!-- Small boxes (Stat box) -->
      <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages2"></div>
          
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

          <form role="form" id="frmCadastrar" name="frmCadastrar" action="<?php base_url('billet/create') ?>" method="post">
          <div class="box">
            <div class="box-header">
              <!-- <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3> -->
            </div>

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

                <div class="form-group col-md-3 col-xs-3">
                	<input type="hidden" id="hdnLote" name="hdnLote" value="<?php echo $hdnLote;?>" />
                	<input type="hidden" id="txt_carregado" name="txt_carregado" value="0" />
                	<input type="hidden" id="hdnId" name="hdnId" value="<?php echo $hdnId;?>" />
                	
                  <label for="group_isadmin"><?=$this->lang->line('application_store');?></label>
                  <select class="form-control" id="slc_store" name="slc_store" >
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($stores as $store): ?>
                      <option value="<?php echo trim($store['id']); ?>"><?php echo trim($store['name']); ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                  <label for="group_isadmin"><?=$this->lang->line('application_panel_cicle_fiscal');?></label>
                  <select class="form-control" id="slc_ciclo_fiscal" name="slc_ciclo_fiscal">
                    <option value="">~~SELECT~~</option>
                  </select>
                </div>

<!--              </div>-->
				<div class="form-group col-md-3 col-xs-3">
                  <label for="group_isadmin"><?=$this->lang->line('application_panel_fiscal_url');?></label>
                  <input type="text" class="form-control" id="txt_url" name="txt_url" />
                </div>

				<div class="col-md-3 col-xs-3" id="divExcel" name="divExcel" style="display:block"><br>
                    <?php if(in_array('createNFS', $user_permission) || in_array('updateNFS', $user_permission)): ?>
                	<button type="button" id="btnAddNfsUrl" name="btnAddNfsUrl" class="btn btn-primary">Adicionar Nota Fiscal</button>
             	<!--	<button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Download Modelo</button> -->
                    <?php endif; ?>
        	 	</div>
                
             <!--  <div class="col-md-12 col-xs-12"> 
                  <div class="box-body" id="divUpload" name="divUpload" style="display:block">

              	</div> -->
          	</div>
        </div>
<!--            </div>-->

            <div class="box">
            <div class="box-header">
                      <h3 class="box-title">Notas Fiscais Cadastradas</h3>
                    </div>
              <div class="box-body">
                <table id="manageTable" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th><?=$this->lang->line('application_id');?></th>
                    <th><?=$this->lang->line('application_store');?></th>
                    <th><?=$this->lang->line('application_panel_cicle_fiscal');?></th>
                    <?php if (isset($show_extra_details_nfs) && ($show_extra_details_nfs)): ?>
                        <th><?=$this->lang->line('application_nfse_num');?></th>
                        <th><?=$this->lang->line('application_issue_date');?></th>
                        <th><?=$this->lang->line('application_valuenfe');?></th>
                        <th><?=$this->lang->line('application_ir_value');?></th>
                    <?php else: ?>
                        <th><?=$this->lang->line('application_action');?></th>
                    <?php endif ?>
                  </tr>
                  </thead>
    
                </table>
              </div>
              <!-- /.box-body -->
            </div>
            <!-- /.box -->
            
            
 			<div id="divTeste" name="divTeste"></div>
              <div class="box-footer">
                  <?php if(in_array('createNFS', $user_permission)|| in_array('updateNFS', $user_permission)): ?>
                <button type="button" id="btnSave" name="btnSave" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                  <?php endif; ?>
                <a href="<?php echo base_url('payment/listfiscal') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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
var manageTable;

var base_url = "<?php echo base_url(); ?>";

var loja_id = "<?php echo $dados['store_id']?>";
var ciclo_id = "<?php echo $dados['data_ciclo']?>";


$(document).ready(function() {

	$("#paraMktPlaceNav").addClass('active');
	$("#painelFiscalMenu").addClass('active');

	$("#slc_store, #slc_ciclo_fiscal").select2();

	$("#slc_store").val(loja_id);

	if ( $("#slc_store").val() != ""){

		var pageURL = base_url.concat("payment/getciclopagamentoseller");
		var store_id = $("#slc_store").val();

		$('#slc_ciclo_fiscal').empty().append('<option value="">~~SELECT~~</option>');
		
		$.post( pageURL, {store_id: store_id} , function( data ) {
			if(data){
				var obj = JSON.parse(data);
				Object.keys(obj).forEach(function(k){
	                $('#slc_ciclo_fiscal').append($('<option>').text(obj[k].data_transferencia).attr('value', obj[k].data_id));
				});
				$("#slc_ciclo_fiscal").val(ciclo_id);
				$("#divUpload").show();
				$("#txt_carregado").val("1");
			}
		});
	}

	 // initialize the datatable 
	  manageTable = $('#manageTable').DataTable({
		"scrollX": false,
	    'ajax': base_url + 'payment/fetchnfsurl/' + $("#hdnLote").val(),
	    'order': []
	  });

	$("#slc_store").change(function(){
		if ( $("#slc_store").val() != ""){

			var pageURL = base_url.concat("payment/getciclopagamentoseller");
			var store_id = $("#slc_store").val();

			$('#slc_ciclo_fiscal').empty().append('<option value="">~~SELECT~~</option>');
			
			$.post( pageURL, {store_id: store_id} , function( data ) {
				if(data){
					var obj = JSON.parse(data);
					Object.keys(obj).forEach(function(k){
		                $('#slc_ciclo_fiscal').append($('<option>').text(obj[k].data_transferencia).attr('value', obj[k].data_id));
					});
				}
			});
		}else{
			$('#slc_ciclo_fiscal').empty().append('<option value="">~~SELECT~~</option>');
		}
	});
    <?php if(in_array('createNFS', $user_permission) || in_array('updateNFS', $user_permission)): ?>

	$("#btnAddNfsUrl").click( function(){
		
		if( $("#slc_store").val() 	== "" ||
			$("#slc_ciclo_fiscal").val() 	== "" || 
			$("#txt_url").val() 	== ""){
			alert("Todos os campos são de preenchimento obrigatório");
			$("#btnSave").prop('disabled', false);
			return false;
		}

		var pageURL = base_url.concat("payment/addnfsurl");
		$.post( pageURL, $("#frmCadastrar").serialize(), function( data ) {
			
			var saida = data.split(";");
			
			if(saida[0] == "1"){
	  			  $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
	  		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	  		              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+saida[1]+
	  		            '</div>'); 
	  			  $("#btnSave").prop('disabled', false);
	  		  }else{
	  			  $("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">'+
	  		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	  		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saida[1]+
	  		            '</div>');
				  $('#manageTable').DataTable().ajax.reload();
				  $("#txt_carregado").val("1");
	  		  }
			
		});
		
	});
    <?php endif; ?>

	$("#btnSave").click( function(){
		$("#btnSave").prop('disabled', true);

		if ( $("#txt_carregado").val() == "0"){
			alert("É necessário subir ao menos uma Nota Fiscal");
			$("#btnSave").prop('disabled', false);
			return false;
		}
		
		var pageURL = base_url.concat("payment/salvarnfsurl");
		$.post( pageURL, $("#frmCadastrar").serialize(), function( data ) {

			var saida = data.split(";");
			
			if(saida[0] == "1"){
	  			  $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
	  		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	  		              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+saida[1]+
	  		            '</div>'); 
	  			  $("#btnSave").prop('disabled', false);
	  		  }else{
	  			  $("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">'+
	  		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	  		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saida[1]+
	  		            '</div>');
	  			//window.location.assign(base_url.concat("payment/listfiscal"));
	  		  }
			
		});

	});

	var uploadUrl = base_url.concat("payment/uploadarquivonfurl");
	var id = $("#slc_mktplace").val();

	var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' + 
	    'onclick="alert(\'Call your custom code here.\')">' +
	    '<i class="glyphicon glyphicon-tag"></i>' +
	    '</button>'; 
	$("#product_upload").fileinput({
	    overwriteInitial: true,
	    maxFileSize: 15000,
	    uploadUrl: uploadUrl,
        uploadExtraData:function(previewId, index) {
            var data = {
                store : $("#slc_store").val(),
                data_ciclo : $("#slc_ciclo_fiscal").val(),
                lote : $("#hdnLote").val()
            };
            return data;
        },
	    showClose: false,
	    showCaption: false,
	    maxFileCount: 5,
	    browseLabel: '',
	    removeLabel: '',
	    browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
	    removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
	    removeTitle: 'Cancel or reset changes',
	    elErrorContainer: '#kv-avatar-errors-1',
	    msgErrorClass: 'alert alert-block alert-danger',
	    layoutTemplates: {main2: '{preview} {remove} {browse}'},
	    allowedFileExtensions: ["xls", "xlsx"]
		}).on('fileuploaderror', function(event, data, msg) {
			alert("Erro ao fazer upload do arquivo, por favor tente novamente.".msg);
		}).on('fileuploaded', function(event, preview, config, tags, extraData) {
			$('#manageTable').DataTable().ajax.reload();
			$("#txt_carregado").val("1");
		});

		$("#btnExcel").click(function(){
			var saida = 'assets/files/arquivo_modelo_notafiscal_url.xlsx';
			window.open(base_url.concat(saida),'_blank');
		});
    
  });

function apaganfs(id){
	if(id){
		
		var pageURL = base_url.concat("payment/apaganfsurl");
		 
		$.post( pageURL, {id: id, lote : $("#hdnLote").val()}, function( data ) {

			var saida = data.split(";");
			
			if(saida[0] == "1"){
	  			  $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
	  		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	  		              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+saida[1]+
	  		            '</div>'); 
	  		  }else{
	  			  $("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">'+
	  		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	  		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saida[1]+
	  		            '</div>');
	  		  }

			$('#manageTable').DataTable().ajax.reload();
		
		});
	}
}

function abrelink(id){
	if(id){
		window.open(id, '_blank');
	}
}

</script>