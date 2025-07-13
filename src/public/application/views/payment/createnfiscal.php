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
        
        <div id="save_messages"></div>
          
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
              <!-- <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3> -->
            </div>
            <form role="form" id="frmCadastrar" name="frmCadastrar" action="<?php base_url('billet/create') ?>" method="post">
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

                <div class="form-group col-md-6 col-xs-6">
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
                
                <div class="form-group col-md-6 col-xs-6">
                    <label for="group_isadmin"><?=$this->lang->line('application_panel_cicle_fiscal');?></label>
                      <select class="form-control" id="slc_ciclo_fiscal" name="slc_ciclo_fiscal">
                        <option value="">~~SELECT~~</option>
                      </select>
                </div>
               <div class="col-md-12 col-xs-12">
                   <?php if(in_array('createNFS', $user_permission) || in_array('updateNFS', $user_permission)): ?>
                      <div class="box-body" id="divUpload" name="divUpload" style="display:none">
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
                            </div>  <!-- form group -->
                        </div> <!-- row -->
                      </div> <!-- box body -->
                   <?php endif; ?>
               </div>
            </div> <!-- box body -->
            
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
                    <th><?=$this->lang->line('application_action');?></th>
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

	$("#slc_store, #slc_ciclo_fiscal").select2();

	$("#slc_store").val(loja_id);

	if ( $("#slc_store").val() != ""){

        console.log('slc_store: ',$("#slc_store").val());

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
	    'ajax': base_url + 'payment/fetchNfs/' + $("#hdnLote").val(),
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
			$("#divUpload").hide();
		}
	});

	$("#slc_ciclo_fiscal").change(function(){

		if ( $("#slc_ciclo_fiscal").val() == ""){
			$("#divUpload").hide();	
		}else{
			$("#divUpload").show();
		}
	});
	
    $("#mainGroupNav").addClass('active');
    $("#addGroupNav").addClass('active');

	$("#btnSave").click( function(){
		$("#btnSave").prop('disabled', true);

		if( $("#slc_store").val() 	== "" ||
			$("#slc_ciclo_fiscal").val() 	== ""  ){
			alert("Todos os campos são de preenchimento obrigatório");
			$("#btnSave").prop('disabled', false);
			return false;
		}	

		if ( $("#txt_carregado").val() == "0"){
			alert("É necessário subir ao menos um arquivo de Nota Fiscal");
			$("#btnSave").prop('disabled', false);
			return false;
		}
		
		var pageURL = base_url.concat("payment/salvarnfs");

		$.post( pageURL, $("#frmCadastrar").serialize(), function( data ) {
			var saida = data.split(";");
			if(saida[0] == "1"){
	  			  $("#save_messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                        '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+saida[1]+
                      '</div>');
	  			  $("#btnSave").prop('disabled', false);
	  		  }else{
	  			  $("#save_messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                        '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saida[1]+
                      '</div>');
	  			//window.location.assign(base_url.concat("payment/listfiscal"));
	  		  }
			
		});

	});

	var uploadUrl = base_url.concat("payment/uploadArquivoNf");
	var id = $("#slc_mktplace").val();

	var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' + 
	    'onclick="alert(\'Call your custom code here.\')">' +
	    '<i class="glyphicon glyphicon-tag"></i>' +
	    '</button>';

    <?php if(in_array('createNFS', $user_permission) || in_array('updateNFS', $user_permission)): ?>

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
	    allowedFileExtensions: ["xls", "pdf", "xml"]
		}).on('fileuploaderror', function(event, data, msg) {
			alert("Erro ao fazer upload do arquivo, por favor tente novamente.".msg);
		}).on('fileuploaded', function(event, preview, config, tags, extraData) {
			$('#manageTable').DataTable().ajax.reload();
			$("#txt_carregado").val("1");
		});
    <?php endif; ?>
    
  });

function apaganfs(id){
	if(id){
		
		var pageURL = base_url.concat("payment/apaganfs");
		 
		$.post( pageURL, {id: id, lote : $("#hdnLote").val()}, function( data ) {

			var saida = data.split(";");
			
			if(saida[0] == "1"){
	  			  $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
	  		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	  		              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+saida[1]+
	  		            '</div>'); 
	  		  }else{
	  			  $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
	  		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	  		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saida[1]+
	  		            '</div>');
	  		  }

			$('#manageTable').DataTable().ajax.reload();
		
		});
	}
}

</script>