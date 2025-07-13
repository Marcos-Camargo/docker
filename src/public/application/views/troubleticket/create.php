<!--
SW Serviços de Informática 2019

Criar Grupos de Acesso

-->
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_adm_troubleticket_mktplace";  $this->load->view('templates/content_header',$data); ?>

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

				<input type="hidden" class="form-control" id="hdnChamado" name="hdnChamado" value="<?php echo $chamado['id']?>">
				<input type="hidden" class="form-control" id="hdnPedido" name="hdnPedido" value="<?php echo $chamado['hdnPedido']?>">
				
				<div class="col-md-3 col-xs-3">
              		<label for="group_isadmin"><?=$this->lang->line('application_runmarketplaces');?></label>
                  <select class="form-control" id="slc_marketplace" name="slc_marketplace">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($mktplaces as $mktplace): ?>
                          <option value="<?php echo trim($mktplace['id']); ?>"><?php echo trim($mktplace['mkt_place']); ?></option>
                        <?php endforeach ?>
                  </select>
          		</div>

               <div class="col-md-3 col-xs-3">
              		<label for="cnpj"><?=$this->lang->line('application_number_troubleticket');?></label>
                            <input type="text" class="form-control" id="txt_numero_chamado" name="txt_numero_chamado" placeholder="<?=$this->lang->line('application_number_troubleticket')?>" value="<?php echo $chamado['numero_chamado']?>">
          		</div>
          		
          		<div class="col-md-3 col-xs-3">
              		<label for="group_isadmin"><?=$this->lang->line('application_status');?></label>
                  <select class="form-control" id="slc_status" name="slc_status">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($status_billets as $status_billet): ?>
                          <option value="<?php echo trim($status_billet['id']); ?>"><?php echo trim($status_billet['nome']); ?></option>
                        <?php endforeach ?>
                  </select>
          		</div>
          		
          		<div class="col-md-3 col-xs-3">
              		<label for="cnpj"><?=$this->lang->line('application_date_forcast_troubleticket')?></label>
                            <input type="date" class="form-control" id="txt_data_previsao" name="txt_data_previsao" placeholder="<?=$this->lang->line('application_date_forcast_troubleticket')?>" value="<?php echo $chamado['previsao_solucao_formatada']?>">
          		</div>
          		
          		<div class="col-md-12 col-xs-12">
          			<label for="group_name"><?=$this->lang->line('application_description');?></label>
          			<textarea class="form-control" id="txt_descricao" name="txt_descricao" placeholder="<?=$this->lang->line('application_description');?>"><?php echo $chamado['descricao']?></textarea>
              	</div>	
              	
              	 <div class="col-md-10 col-xs-10">
                  <label for="group_name"><?=$this->lang->line('application_orders');?></label>
                    <input type="text" class="form-control" id="txt_pedido" name="txt_pedido" placeholder="<?=$this->lang->line('application_orders')?>">
                </div>
                <div class="col-md-2 col-xs-2">
                	<br>
                	<button type="button" id="btnAddPedido" name="btnAddPedido" class="btn btn-success">Adicionar <?=$this->lang->line('application_orders')?></button>
                </div>
              		
              		
              </div>
              
          
              <div class="box-footer">
                <button type="button" id="btnSave" name="btnSave" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <button type="button" id="btnVoltar" name="btnVoltar" class="btn btn-warning"><?=$this->lang->line('application_back');?></button>
              </div>
            </form>
            
            <div id="divTabelaPedidos" name="divTabelaPedidos" style="display:block">
                <div class="box">
                <div class="box-header">
                  <h3 class="box-title"><?=$this->lang->line('application_orders');?></h3>
                </div>
                  <div class="box-body">
                  
                  	<table id="tabelaPedidosAdd" name="tabelaPedidosAdd" class="table table-bordered table-striped">
                    <thead>
                      <tr>
                      	<th class="idTbl2"><?=$this->lang->line('application_quotation_id');?></th>
                        <th><?=$this->lang->line('application_runmarketplaces');?></th>
                        <th class="value2"><?=$this->lang->line('application_orders');?></th>
                        <th><?=$this->lang->line('application_action');?></th>
                      </tr>
                    </thead>
        
                  	</table>
                  
                  </div>
                  
				</div>
            </div>
            
            
            <div id="retornoTeste" name="retornoTest"></div>
            
            
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
var indice = 0;
var status ="<?php echo $chamado['billet_status_id']?>";
var mktplace ="<?php echo $chamado['integ_id']?>";
var pedidos = "<?php echo $pedidos['pedidos']?>".split(",");;

$(document).ready(function() {


	$("#slc_status").val(status);
	$("#slc_marketplace").val(mktplace);

	$('#tabelaSubcontaAdd tr > *:nth-child('+1+')').toggle();

	if(Array.isArray(pedidos)){
		pedidos.forEach(adicionarArray);
	}

	$("#btnVoltar").click( function(){
    	window.location.assign(base_url.concat("TroubleTicket/list"));
    });

	$("#btnSave").click( function(){

		if(confirm("Deseja realmente salvar esse chamado?")){
		
    		$("#btnVoltar").prop('disabled', true);
    		$("#btnSave").prop('disabled', true);

    		if($("#slc_marketplace").val() 			== ""	||
    			$("#txt_numero_chamado").val() 		== ""	||
    			$("#slc_status").val() 				== ""	||
    			$("#txt_descricao").val() 			== ""	||
    			$("#hdnPedido").val() 				== "0"	)
    		{
    			alert("Todos os campos são de preenchimento obrigatório");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
    			return false;
    		}

			//Busca todos os pedidos
    		var totalContaValorSplit = [];
    		$('#tabelaPedidosAdd tr > *:nth-child('+1+')').toggle();
    		    	   	$("#tabelaPedidosAdd .value2").each(function() {
    		    			   var value = $(this).text();
    		    			   if(value != "Pedidos"){
    		    				   totalContaValorSplit.push( value );
    		    			   }   
    		    	   	});
    		    	   	$('#tabelaPedidosAdd tr > *:nth-child('+1+')').toggle();

    		var dados = $("#frmCadastrar").serialize()+"&arraysplit="+totalContaValorSplit;
    
    		var pageURL = base_url.concat("TroubleTicket/createchamado");
    		$.post( pageURL, dados, function( data ) {

    			var retorno = data.split(";");
    			if(retorno[0] == "0"){
    				$("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">'+
        		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
        		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+retorno[1]+
        		            '</div>');
    				$("#btnVoltar").prop('disabled', false);
    			}else{
    				$("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
    	              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
    	              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+retorno[1]+
    	            '</div>'); 
    				$("#btnSave").prop('disabled', false);
    				$("#btnVoltar").prop('disabled', false);
    			}
    			
    		});

		}

	});


	$("#btnAddPedido").click( function(){

        if( $("#txt_pedido").val() == ""){
            alert("Selecione um pedido");
            return false;
        }

        if( $("#slc_marketplace").val() == ""){
            alert("Selecione um marketplace");
            return false;
        }

        if($("#txt_pedido").val().indexOf(";") == "-1"){
            adicionarArray($("#txt_pedido").val());
        }else{
			var n_pedidos =  $("#txt_pedido").val().split(";");
			n_pedidos.forEach(adicionarArray);
        }


        $("#txt_pedido").val("")
        
    });

	$("#tabelaPedidosAdd").on("click", "#DeleteButton", function() {
 	   $(this).closest("tr").remove();
 	   indice--;
	 });

	
});

function adicionarArray(pedido){

	if(pedido == ""){
		return false;
	}

	if($("#hdnPedido").val() == "0"){
    	indice = 1;
    	$("#hdnPedido").val("1");
    }else{
        if(indice == 0){
        	indice = 1;
        }else{    
    		indice++;
        }
    }

	$('#tabelaPedidosAdd tr > *:nth-child('+1+')').toggle();
    var table = document.getElementById("tabelaPedidosAdd");
    var row = table.insertRow(indice);
    var cell1 = row.insertCell(0);
    var cell2 = row.insertCell(1);
    var cell3 = row.insertCell(2);
    var cell4 = row.insertCell(3);
    var cell5 = row.insertCell(4);
    cell1.innerHTML = pedido;
    cell1.className = 'idTbl2';	
    cell2.innerHTML = indice;
    cell3.innerHTML = $("#slc_marketplace option:selected").html();
    cell4.innerHTML = pedido;
    cell4.className = 'value2';
    cell5.innerHTML = '<button type="button" class="btn btn-default" onclick="#" name="DeleteButton" id="DeleteButton" ><i class="fa fa-minus"></i></button>';
    $('#tabelaPedidosAdd tr > *:nth-child('+1+')').toggle();
	
}


  
</script>