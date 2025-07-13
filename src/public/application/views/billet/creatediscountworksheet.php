<!--
SW Serviços de Informática 2019
Criar Grupos de Acesso
-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_discountworksheet_insert_tag"; $data['page_now'] ='discount_worksheet'; $this->load->view('templates/content_header',$data); ?>

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
					<input type="hidden" id="hdnExtensao" name="hdnExtensao" value="" />

					<label for="group_isadmin"><?=$this->lang->line('application_panel_cicle_fiscal');?></label>
					<select class="form-control" id="slc_ciclo_fiscal" name="slc_ciclo_fiscal">
						<option value="">~~SELECT~~</option>
						<option value="Janeiro/2021">Janeiro/2021</option>
						<option value="Fevereiro/2021">Fevereiro/2021</option>
						<option value="Março/2021">Março/2021</option>
						<option value="Abril/2021">Abril/2021</option>
						<option value="Maio/2021">Maio/2021</option>
						<option value="Junho/2021">Junho/2021</option>
						<option value="Julho/2021">Julho/2021</option>
						<option value="Agosto/2021">Agosto/2021</option>
						<option value="Setembro/2021">Setembro/2021</option>
						<option value="Outubro/2021">Outubro/2021</option>
						<option value="Novembro/2021">Novembro/2021</option>
						<option value="Dezembro/2021">Dezembro/2021</option>
						<option value="Janeiro/2022">Janeiro/2022</option>
						<option value="Fevereiro/2022">Fevereiro/2022</option>
						<option value="Março/2022">Março/2022</option>
						<option value="Abril/2022">Abril/2022</option>
						<option value="Maio/2022">Maio/2022</option>
						<option value="Junho/2022">Junho/2022</option>
						<option value="Julho/2022">Julho/2022</option>
						<option value="Agosto/2022">Agosto/2022</option>
						<option value="Setembro/2022">Setembro/2022</option>
						<option value="Outubro/2022">Outubro/2022</option>
						<option value="Novembro/2022">Novembro/2022</option>
						<option value="Dezembro/2022">Dezembro/2022</option>
					</select>
                </div>
                
               <div class="col-md-12 col-xs-12"> 
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
          	</div>
            </div> <!-- box body -->
            
 			<div id="divTeste" name="divTeste"></div>
              <div class="box-footer">
                <button type="button" id="btnSave" name="btnSave" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <a href="<?php echo base_url('billet/listdiscountworksheet') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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

$(document).ready(function() {

	$("#slc_ciclo_fiscal").select2();
    $("#mainGroupNav").addClass('active');
    $("#addGroupNav").addClass('active');

	$("#slc_ciclo_fiscal").change(function(){

		if ( $("#slc_ciclo_fiscal").val() == ""){
			$("#divUpload").hide();	
		}else{
			$("#divUpload").show();
		}
	});

	$("#btnSave").click( function(){
		$("#btnSave").prop('disabled', true);
		
		$("#messages2").html("");

		if( $("#slc_ciclo_fiscal").val() 	== ""  ){
			alert("Todos os campos são de preenchimento obrigatório");
			$("#btnSave").prop('disabled', false);
			return false;
		}	

		if ( $("#txt_carregado").val() == "0"){
			alert("É necessário subir ao menos um arquivo");
			$("#btnSave").prop('disabled', false);
			return false;
		}
		
		var pageURL = base_url.concat("billet/salvardiscountworksheet");
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
	  		  }
			
		});

	});

	var uploadUrl = base_url.concat("billet/uploadArquivoDiscountworksheet");

	$("#product_upload").fileinput({
	    overwriteInitial: true,
	    maxFileSize: 15000,
	    uploadUrl: uploadUrl,
        uploadExtraData:function(previewId, index) {
            var data = {
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

			$("#hdnExtensao").val(preview.response.extensao);
			
			var pageURL = base_url.concat("billet/learquivodiscountworksheet");
			$.post( pageURL, $("#frmCadastrar").serialize(), function( data ) {
				$("#messages2").html("");
				var teste = data.indexOf("Erro");
				if(teste != "-1"){
			        $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
			  		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
			  		              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+data+
			  		            '</div>');
			        $("#txt_carregado").val("0");
			        
  		            return false;
			    }else{
					$("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">'+
        	            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
        	            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+"Planilha de desconto carregada com sucesso"+
        	          '</div>');

					$("#txt_carregado").val("1");
				}
				
			});


			$('#manageTable').DataTable().ajax.reload();
			$("#txt_carregado").val("1");
		});
    
  });

</script>