<!--
SW Serviços de Informática 2019

Criar Grupos de Acesso

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_billet_cancel";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12">
        
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

          <div class="box">
            <div class="box-header">
              <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
            </div>
            <form role="form" id="frmCadastrar" name="frmCadastrar" method="post">
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

                <div class="form-group col-md-12 col-xs-12">
                  <label for="group_isadmin"><?=$this->lang->line('application_billets');?></label>
                  <select class="form-control" id="slc_billet" name="slc_billet">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($billets as $billet) { ?>
                    	<option value="<?php echo $billet['id']?>"><?php echo $billet['store_nome']?></option>
                    <?php }?>
                  </select>
                </div>
              
                <div class="form-group col-md-3 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_billet_id_iugu');?></label>
                    <input type="text" class="form-control" id="txt_id" name="txt_id" placeholder="ID Boleto">
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_billet_url_iugu');?></label>
                    <input type="text" class="form-control" id="txt_url" name="txt_url" placeholder="URL IUGU">
                </div>
                <div class="form-group col-md-3 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_status_billet_iugu');?></label>
                    <input type="text" class="form-control" id="txt_status_iugu" name="txt_status_iugu" placeholder="Status IUGU" >
                </div>
                <div class="form-group col-md-3 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_value');?></label>
                    <input type="number" class="form-control" id="txt_valor" name="txt_valor" placeholder="Valor Cobrança" >
                </div>
                
                <div class="form-group col-md-12 col-xs-12">
                  <label for="group_isadmin"><?=$this->lang->line('application_status_billet_iugu_ws');?></label>
                  <select class="form-control" id="slc_status_iugu_ws" name="slc_status_iugu_ws">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($status_iugu_wss as $status_iugu_ws) { ?>
                    	<option value="<?php echo $status_iugu_ws['status_iugu']?>"><?php echo $status_iugu_ws['status_iugu_pt_br']?></option>
                    <?php }?>
                  </select>
                </div>
                
              </div>
          
              <div class="box-footer">
                <button type="button" id="btnStatus" name="btnStatus" class="btn btn-primary"><?=$this->lang->line('application_status_billet_iugu');?></button>
                <button type="button" id="btnCancel" name="btnCancel" class="btn btn-primary"><?=$this->lang->line('application_billet_cancel');?></button>
                <button type="button" id="btnVoltar" name="btnVoltar" class="btn btn-warning"><?=$this->lang->line('application_back');?></button>
              </div>
            </form>
            
            <div id="retornoTeste" name="retornoTest">
            
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
var manageTableResult;
var manageTableBillet;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

	$("#slc_billet").select2();

	$("#btnCancel").prop('disabled', true);
	$("#txt_id").prop('disabled', true);
	//$("#txt_url").prop('disabled', true);
	$("#txt_url").attr('readonly', true); 
	$("#txt_valor").prop('disabled', true);
	$("#txt_status_iugu").prop('disabled', true);
	$("#slc_status_iugu_ws").prop('disabled', true);
	$("#btnStatus").prop('disabled', true);
	
	$("#btnVoltar").click( function(){
    	window.location.assign(base_url.concat("iugu/list"));
    });

    $("#slc_billet").change( function(){

    	$("#txt_id").prop('disabled', false);
//     	$("#txt_url").prop('disabled', false);
    	$("#txt_url").attr('readonly', false); 
    	$("#txt_valor").prop('disabled', false);
    	$("#txt_status_iugu").prop('disabled', false);
        
		if ($("#slc_billet").val() == ""){
			
			$("#txt_id").val("");
			$("#txt_url").val("");
			$("#txt_valor").val("");
			$("#txt_status_iugu").val("");
			
			$("#txt_id").prop('disabled', true);
// 			$("#txt_url").prop('disabled', true);
			$("#txt_url").attr('readonly', true);
			$("#txt_valor").prop('disabled', true);
			$("#txt_status_iugu").prop('disabled', true);
			$("#btnStatus").prop('disabled', true);
				
		}else{

			var pageURL2 = base_url.concat("iugu/buscarboletoid");
			console.log(pageURL2);
			$.post( pageURL2, $("#frmCadastrar").serialize(), function( data ) {

				var obj = JSON.parse(data);
				
				$("#txt_id").val(obj.id_boleto_iugu);
				$("#txt_url").val(obj.url_boleto_iugu);
				$("#txt_valor").val(obj.valor_total);
				$("#txt_status_iugu").val(obj.status_iugu);

				$("#txt_id").prop('disabled', true);
// 				$("#txt_url").prop('disabled', true);
				$("#txt_url").attr('readonly', true);
				$("#txt_valor").prop('disabled', true);
				$("#txt_status_iugu").prop('disabled', true);
				$("#btnStatus").prop('disabled', false);
				
			});

		}

    });    

    $("#txt_url").click( function(){
		window.open($("#txt_url").val());
    });

    $("#btnStatus").click( function(){

		if( $("#slc_billet").val() == "" ) {
			alert("Selecione pelo menos 1 boleto");
			return false;
		}
        
    	$("#txt_id").prop('disabled', false);
    	$("#btnCancel").prop('disabled', true);
    	$("#btnStatus").prop('disabled', true);
    	$("#slc_status_iugu_ws").prop('disabled', false);
		$("#slc_status_iugu_ws").val("");
		$("#slc_status_iugu_ws").prop('disabled', true);
		$("#messages").html("");
    	var pageURL2 = base_url.concat("iugu/cancelbilletstatusiugu");
		$.post( pageURL2, $("#frmCadastrar").serialize(), function( data ) {
			//$("#retornoTeste").html(data);
			
			var retorno2 = data.split(";");

			if(retorno2[0] == "0"){
    			$("#btnStatus").prop('disabled', false);
    			var obj = JSON.parse(retorno2[1]);
    			$("#slc_status_iugu_ws").prop('disabled', false);
    			$("#slc_status_iugu_ws").val(obj.status_iugu);
    			$("#slc_status_iugu_ws").prop('disabled', true);
    			$("#btnCancel").prop('disabled', false);
			}else{
				$("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
			              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
			              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>Erro ao buscar Status na IUGU, por favor tente novamente.</div>'); 
			}
			
    		$("#btnStatus").prop('disabled', false);
		});
		
		$("#txt_id").prop('disabled', true);
    	
    });

	$("#btnCancel").click( function(){
		$("#btnVoltar").prop('disabled', true);
		$("#btnCancel").prop('disabled', true);
		$("#btnStatus").prop('disabled', true);
		$("#retornoTeste").html("");
		$("#messages").html("");

		$("#slc_status_iugu_ws").prop('disabled', false);
		
		if( $("#slc_status_iugu_ws").val() == ""){
			alert("É necessário verificar o status do boleto na IUGU antes de realizar o cancelamento");
			$("#slc_status_iugu_ws").prop('disabled', true);
			return false;
		}

		if( $("#slc_status_iugu_ws").val() != 'in_analysis' && $("#slc_status_iugu_ws").val() != 'pending' ){
			alert("Não é possível cancelar um boleto nesse status");
			$("#slc_status_iugu_ws").prop('disabled', true);
			return false;
		}
			
		$("#slc_status_iugu_ws").prop('disabled', true);
		
		if( confirm("Deseja cancelar esse boleto "+$("#txt_id").val()+"?") ){

			$("#txt_id").prop('disabled', false);
// 	    	$("#txt_url").prop('disabled', false);
	    	$("#txt_url").attr('readonly', false);
	    	$("#txt_valor").prop('disabled', false);
	    	$("#txt_status_iugu").prop('disabled', false);

	    	var pageURL = base_url.concat("iugu/cancelbilletiugu");
			$.post( pageURL, $("#frmCadastrar").serialize(), function( data ) {
				//$("#retornoTeste").html(data);
				var retorno = data.split(";");
				if(retorno[0] == "0"){
					$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
	    		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	    		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+retorno[1]+
	    		            '</div>');
					$("#btnVoltar").prop('disabled', false);
				}else{
					$("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
		              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+retorno[1]+
		            '</div>'); 
					$("#btnCancel").prop('disabled', false);
					$("#btnVoltar").prop('disabled', false);
					$("#btnStatus").prop('disabled', false);
				}

				$("#txt_id").prop('disabled', true);
// 				$("#txt_url").prop('disabled', true);
				$("#txt_url").attr('readonly', true);
				$("#txt_valor").prop('disabled', true);
				$("#txt_status_iugu").prop('disabled', true);

			});
		}else{
			$("#btnVoltar").prop('disabled', false);
			$("#btnCancel").prop('disabled', false);
			$("#btnStatus").prop('disabled', false);
		}
		
		
		/*$.post( pageURL, $("#frmCadastrar").serialize(), function( data ) {
		var pageURL = base_url.concat("iugu/createbilletiugu");

			if($("#slc_billet").val() == "Outros"){
				$("#txt_outros").prop('disabled', false);
			}else{
				$("#txt_outros").prop('disabled', true);
			}

			if($("#slc_store").val() == "0"){
    			$("#txt_nome").prop('disabled', false);
    			$("#txt_cpf_cnpj").prop('disabled', false); 
    			$("#txt_cep").prop('disabled', false);
    			$("#txt_numero").prop('disabled', false);
			}else{
				$("#txt_nome").prop('disabled', true);
    			$("#txt_cpf_cnpj").prop('disabled', true); 
    			$("#txt_cep").prop('disabled', true);
    			$("#txt_numero").prop('disabled', true);
			}
			
			var retorno = data.split(";");
			if(retorno[0] == "0"){
				$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
    		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
    		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+retorno[1]+
    		            '</div>');
				$("#btnVoltar").prop('disabled', false);
			}else{
				$("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
	              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+retorno[1]+
	            '</div>'); 
				$("#btnSave").prop('disabled', false);
				$("#btnVoltar").prop('disabled', false);
			}
			
		});*/

	});

	
});


  
</script>