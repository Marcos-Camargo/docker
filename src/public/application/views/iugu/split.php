<!--
SW Serviços de Informática 2019

Criar Grupos de Acesso

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_payment_split";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12">
        
        <div id="messagesOk"></div>
        
        <div id="messagesNok"></div>
          
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
					<option value="0">Split não associado a Boleto</option>
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
                
                <div class="form-group col-md-6 col-xs-6">
                  <label for="group_isadmin"><?=$this->lang->line('application_billet_status_split');?></label>
                  <select class="form-control" id="slc_status_split" name="slc_status_split">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($status_splits as $status_split) { ?>
                    	<option value="<?php echo $status_split['id']?>"><?php echo $status_split['nome']?></option>
                    <?php }?>
                  </select>
                </div>
                
                <div class="form-group col-md-6 col-xs-6">
                  <label for="group_isadmin"><?=$this->lang->line('application_status_billet_iugu_ws');?></label>
                  <select class="form-control" id="slc_status_iugu_ws" name="slc_status_iugu_ws">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($status_iugu_wss as $status_iugu_ws) { ?>
                    	<option value="<?php echo $status_iugu_ws['status_iugu']?>"><?php echo $status_iugu_ws['status_iugu_pt_br']?></option>
                    <?php }?>
                  </select>
                </div>
                
                <div class="form-group col-md-4 col-xs-4">
                  <label for="group_isadmin"><?=$this->lang->line('application_billet_subconta_iugu');?></label>
                  <select class="form-control" id="slc_subconta" name="slc_subconta">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($subcontas_iugu as $subconta_iugu) { ?>
                    	<?php if ($subconta_iugu['ativo'] == "12"){?>
                    		<option value="<?php echo $subconta_iugu['id']?>"><?php echo $subconta_iugu['nome_loja']?></option>
                    	<?php }?>
                    <?php }?>
                  </select>
                </div>
                
                <div class="form-group col-md-4 col-xs-4">
                  <label for="group_name"><?=$this->lang->line('application_value');?></label>
                    <input type="number" class="form-control" id="txt_valor_a_Transferir" name="txt_valor_a_Transferir" placeholder="Valor a Transferir"  autocomplete="off" min="1" step='0.01' value='1.00'>
                </div>
                <div class="form-group col-md-4 col-xs-4">
                	<br>
                	<button type="button" id="btnAddSplit" name="btnAddSplit" class="btn btn-success"><?=$this->lang->line('application_billet_add_split');?></button>
                </div>
                
                <div class="form-group col-md-12 col-xs-12">
                    <label for="group_name"><?=$this->lang->line('application_billet_value_split');?></label>
                    <input type="number" class="form-control" id="txt_valor_total" name="txt_valor_total" placeholder="Valor Total Split">
                </div>
                
                
              </div>
          
              <div class="box-footer">
                <button type="button" id="btnStatus" name="btnStatus" class="btn btn-primary"><?=$this->lang->line('application_status_billet_iugu');?></button>
                <button type="button" id="btnSplit" name="btnSplit" class="btn btn-primary"><?=$this->lang->line('application_billet_split');?></button>
                <button type="button" id="btnVoltar" name="btnVoltar" class="btn btn-warning"><?=$this->lang->line('application_back');?></button>
              </div>
            </form>
            
            <div id="divTabelaTotalSplit" name="divTabelaTotalSplit" style="display:block">
            
                <div class="box">
                <div class="box-header">
                  <h3 class="box-title"><?=$this->lang->line('application_billet_list_split');?></h3>
                </div>
                  <div class="box-body">
                  
                  	<table id="tabelaSubcontaAdd" name="tabelaSubcontaAdd" class="table table-bordered table-striped">
                    <thead>
                      <tr>
                      	<th class="idTbl"><?=$this->lang->line('application_quotation_id');?></th>
                        <th><?=$this->lang->line('application_billet_subconta_iugu');?></th>
                        <th class="value"><?=$this->lang->line('application_value');?></th>
                        <th><?=$this->lang->line('application_action');?></th>
                      </tr>
                    </thead>
        
                  	</table>
                  
                  </div>
                  
				</div>
            
            
            </div>
            
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
var totalSomatorio = [];
var indice = 0;
var totalContaValorSplit = [];
var valorTotalCampo = 0;

$(document).ready(function() {

	$("#slc_subconta").select2();

	$('#tabelaSubcontaAdd tr > *:nth-child('+1+')').toggle();

	$("#btnSplit").prop('disabled', true);
	$("#txt_id").prop('disabled', true);
	$("#txt_url").prop('disabled', true);
	$("#txt_valor").prop('disabled', true);
	$("#txt_status_iugu").prop('disabled', true);
	$("#slc_status_iugu_ws").prop('disabled', true);
	$("#btnStatus").prop('disabled', true);
	$("#slc_status_split").prop('disabled', true);
	$("#slc_subconta").prop('disabled', true);
	$("#txt_valor_a_Transferir").prop('disabled', true);
	$("#btnAddSplit").prop('disabled', true);
	$("#txt_valor_total").prop('disabled', true);
	
	$("#btnVoltar").click( function(){
    	window.location.assign(base_url.concat("iugu/list"));
    });

    $("#slc_billet").change( function(){

    	$("#txt_id").prop('disabled', false);
    	$("#txt_url").prop('disabled', false);
    	$("#txt_valor").prop('disabled', false);
    	$("#txt_status_iugu").prop('disabled', false);
        
		if ($("#slc_billet").val() == ""){
			
			$("#txt_id").val("");
			$("#txt_url").val("");
			$("#txt_valor").val("");
			$("#txt_status_iugu").val("");
			$("#slc_status_split").val("");
			
			$("#txt_id").prop('disabled', true);
			$("#txt_url").prop('disabled', true);
			$("#txt_valor").prop('disabled', true);
			$("#txt_status_iugu").prop('disabled', true);
			$("#slc_status_split").prop('disabled', true);
			$("#btnStatus").prop('disabled', true);
				
		}else{


			if ($("#slc_billet").val() == "0"){

				$("#txt_id").val("Sem boleto");
				$("#txt_url").val("Sem boleto");
				$("#txt_valor").val(0);
				$("#txt_status_iugu").prop('disabled', true);

				$("#txt_id").prop('disabled', true);
				$("#txt_url").prop('disabled', true);
				$("#txt_valor").prop('disabled', true);
				$("#txt_status_iugu").prop('disabled', true);
				$("#btnStatus").prop('disabled', false);
				
			}else{

				var pageURL2 = base_url.concat("iugu/buscarboletoid");
// 				console.log(pageURL2);
				$.post( pageURL2, $("#frmCadastrar").serialize(), function( data ) {

					var obj = JSON.parse(data);
					
					$("#txt_id").val(obj.id_boleto_iugu);
					$("#txt_url").val(obj.url_boleto_iugu);
					$("#txt_valor").val(obj.valor_total);
					$("#txt_status_iugu").val(obj.status_iugu);
					$("#slc_status_split").val(obj.status_split_id);

					$("#txt_id").prop('disabled', true);
					$("#txt_url").prop('disabled', true);
					$("#txt_valor").prop('disabled', true);
					$("#txt_status_iugu").prop('disabled', true);
					$("#btnStatus").prop('disabled', false);
					
				});
				
			}

		}

    });    

    $("#btnStatus").click( function(){

		if( $("#slc_billet").val() == "" ) {
			alert("Selecione pelo menos 1 boleto");
			return false;
		}

		if( $("#slc_billet").val() == "0" ) {
			$("#btnSplit").prop('disabled', true);
			$("#slc_status_iugu_ws").val("paid");
			$("#slc_status_split").val(7);
			$("#slc_subconta").prop('disabled', false);
			$("#txt_valor_a_Transferir").prop('disabled', false);
			$("#btnAddSplit").prop('disabled', false);
			return false;
		}
        
    	$("#txt_id").prop('disabled', false);
    	$("#btnSplit").prop('disabled', true);
    	$("#btnStatus").prop('disabled', true);
    	$("#slc_status_iugu_ws").prop('disabled', false);
		$("#slc_status_iugu_ws").val("");
		$("#slc_status_iugu_ws").prop('disabled', true);
		$("#messagesOk").html("");
		$("#messagesNok").html("");
    	var pageURL2 = base_url.concat("iugu/cancelbilletstatusiugu");
		$.post( pageURL2, $("#frmCadastrar").serialize(), function( data ) {
			
			var retorno2 = data.split(";");

			if(retorno2[0] == "0"){
    			$("#btnStatus").prop('disabled', false);
    			var obj = JSON.parse(retorno2[1]);
    			$("#slc_status_iugu_ws").prop('disabled', false);
    			$("#slc_status_iugu_ws").val(obj.status_iugu);
    			$("#slc_status_iugu_ws").prop('disabled', true);
    			$("#btnSplit").prop('disabled', true);
    			$("#slc_subconta").prop('disabled', false);
    			$("#txt_valor_a_Transferir").prop('disabled', false);
    			$("#btnAddSplit").prop('disabled', false);
			}else{
				$("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
			              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
			              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>Erro ao buscar Status na IUGU, por favor tente novamente.</div>'); 
			}
			
    		$("#btnStatus").prop('disabled', false);
		});
		
		$("#txt_id").prop('disabled', true);
    	
    });

    $("#btnAddSplit").click( function(){

        if( $("#slc_subconta").val() == ""){
            alert("Selecione uma subconta");
            return false;
        }

        if($("#txt_valor_total").val() == ""){
        	$("#txt_valor_total").val($("#txt_valor_a_Transferir").val());
        	indice = 1;
        }else{
        	var x = $("#txt_valor_a_Transferir").val();
            var y = $("#txt_valor_total").val();
            var w = +x + +y;
            $("#txt_valor_total").val(w.toFixed(2));
        	indice++;
        }
        
        $('#tabelaSubcontaAdd tr > *:nth-child('+1+')').toggle();
        var table = document.getElementById("tabelaSubcontaAdd");
        var row = table.insertRow(indice);
        var cell1 = row.insertCell(0);
        var cell2 = row.insertCell(1);
        var cell3 = row.insertCell(2);
        var cell4 = row.insertCell(3);
        cell1.innerHTML = $("#slc_subconta").val()+"-"+$("#txt_valor_a_Transferir").val();
        cell1.className = 'idTbl';	
        cell2.innerHTML = $("#slc_subconta").val()+" - "+$("#slc_subconta option:selected").html();
        cell3.innerHTML = $("#txt_valor_a_Transferir").val();
        cell3.className = 'value';	
        cell4.innerHTML = '<button type="button" class="btn btn-default" onclick="#" name="DeleteButton" id="DeleteButton" ><i class="fa fa-minus"></i></button>';
        $('#tabelaSubcontaAdd tr > *:nth-child('+1+')').toggle();

        $("#btnSplit").prop('disabled', false);

    });

    $("#tabelaSubcontaAdd").on("click", "#DeleteButton", function() {
    	   $(this).closest("tr").remove();
    	   indice--;
    	   
    	   $("#tabelaSubcontaAdd .value").each(function() {
    		   var value = $(this).text();
    		   if(value != "Valor"){
    			   valorTotalCampo = +valorTotalCampo + +value;
    		   }   
    	   });

    	   $("#txt_valor_total").val(valorTotalCampo.toFixed(2));

    });

	$("#btnSplit").click( function(){

		$("#messagesOk").html("");
		$("#messagesNok").html("");
		$("#btnVoltar").prop('disabled', true);
		$("#btnSplit").prop('disabled', true);
		$("#btnStatus").prop('disabled', true);
		$("#retornoTeste").html("");
		$("#messages").html("");

		$("#slc_status_iugu_ws").prop('disabled', false);
		
		if( $("#slc_status_iugu_ws").val() == ""){
			alert("É necessário verificar o status do boleto na IUGU antes de realizar o split");
			$("#slc_status_iugu_ws").prop('disabled', true);
			return false;
		}

		if( indice == 0 ){
			alert("Selecione ao menos uma subconta para split");
			$("#btnVoltar").prop('disabled', false);
			$("#btnSplit").prop('disabled', false);
			return false;
		}	

		$("#slc_status_iugu_ws").prop('disabled', true);
		
		if( confirm("Deseja realizar o split?") ){
			$("#btnStatus").prop('disabled', true);
			$("#btnSplit").prop('disabled', true);
			$("#btnVoltar").prop('disabled', true);
			$("#btnAddSplit").prop('disabled', true);

	    	var pageURL = base_url.concat("iugu/splitbilletiugu");

	    	totalContaValorSplit = [];
	    	
	    	$('#tabelaSubcontaAdd tr > *:nth-child('+1+')').toggle();
    	   	$("#tabelaSubcontaAdd .idTbl").each(function() {
    			   var value = $(this).text();
    			   if(value != "ID"){
    				   totalContaValorSplit.push( value );
    			   }   
    	   	});
    	   	$('#tabelaSubcontaAdd tr > *:nth-child('+1+')').toggle();

			var post = $("#frmCadastrar").serialize()+"&arraysplit="+totalContaValorSplit;

			var saidaNok = "";
			var saidaOk = "";
	    	
			$.post( pageURL, post, function( data ) {
				var retorno = data.split("$");
				
				if(retorno[0] == "0"){
					
    				var splitRetorno = retorno[1].split("|");
    
    				for (var i = 0; i < splitRetorno.length; i++) {
    					
    					var resultadoFinal = splitRetorno[i].split(";");
    
    					if(saidaOk == ""){
    						saidaOk = resultadoFinal[1];
    					}else{
    						saidaOk = saidaOk + "<br>" + resultadoFinal[1];
    					}
    
    				}
				
					$("#messagesOk").html('<div class="alert alert-success alert-dismissible" role="alert">'+
	    		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	    		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saidaOk+
	    		            '</div>');
					$("#btnVoltar").prop('disabled', false);
					
				}else{

					var splitRetorno = retorno[1].split("|");

					for (var i = 0; i < splitRetorno.length; i++) {
						var resultadoFinal = splitRetorno[i].split(";");

						if(resultadoFinal[0] == "1"){
							if(saidaNok == ""){
								saidaNok = resultadoFinal[1];
							}else{
    							saidaNok = saidaNok + "<br>" + resultadoFinal[1];
							}
						}else{
							if(saidaOk == ""){
								saidaOk = resultadoFinal[1];
							}else{
								saidaOk = saidaOk + "<br>" + resultadoFinal[1];
							}
						}

					} 

					if ( saidaNok != "" ){
    					$("#messagesNok").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
    		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
    		              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+saidaNok+
    		            '</div>'); 
					}

					if ( saidaOk != "" ){
    					$("#messagesOk").html('<div class="alert alert-success alert-dismissible" role="alert">'+
    	    		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
    	    		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saidaOk+
    	    		            '</div>');
					}
					$("#btnSplit").prop('disabled', false);
					$("#btnVoltar").prop('disabled', false);
					$("#btnStatus").prop('disabled', false);
				}

				$("#txt_id").prop('disabled', true);
				$("#txt_url").prop('disabled', true);
				$("#txt_valor").prop('disabled', true);
				$("#txt_status_iugu").prop('disabled', true);

			});
		}else{
			$("#btnVoltar").prop('disabled', false);
			$("#btnSplit").prop('disabled', false);
			$("#btnStatus").prop('disabled', false);
		}
		
	});

	
});
  
</script>