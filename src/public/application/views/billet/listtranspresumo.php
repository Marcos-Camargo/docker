<!--
SW Serviços de Informática 2019
Acompanhar Creditos em conta
-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_conciliacao";  $this->load->view('templates/content_header',$data); ?>

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
        
        <?php if(in_array('createBillet', $user_permission)): ?>
            <div class="box">
              <div class="box-body">
              	<div class="box-header">
                  <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                	<label for="group_isadmin"><?=$this->lang->line('application_ship_company');?></label>
                	<select class="form-control" id="slc_transportadora" name="slc_transportadora" >
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($transportadoras as $transportadora): ?>
                      <option value="<?php echo trim($transportadora['id']); ?>"><?php echo trim($transportadora['ship_company']); ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                	<label for="group_isadmin"><?=$this->lang->line('application_parameter_providers_ciclos');?></label>
					<select class="form-control" id="slc_ciclo_transp" name="slc_ciclo_transp" >
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($ciclos as $ciclo): ?>
                          <option value="<?php echo trim($ciclo['id']); ?>"><?php echo trim($ciclo['nome_transportadora'])." - Início: " .trim($ciclo['data_inicio'])." Fim: ".trim($ciclo['data_fim'])   ; ?></option>
                        <?php endforeach ?>
                      </select>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                	<label for="group_isadmin"><?=$this->lang->line('application_payment_start_day');?></label>
                	<input class="form-control" type="date" id="txt_dt_inicio" name="txt_dt_inicio" placeholder="<?=$this->lang->line('application_payment_start_day');?>">
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                	<label for="group_isadmin"><?=$this->lang->line('application_payment_end_day');?></label>
                	<input class="form-control" type="date" id="txt_dt_fim" name="txt_dt_fim" placeholder="<?=$this->lang->line('application_payment_start_day');?>">
                </div>
                <br />
                	<button class="btn btn-primary" id="btn_filtrar_transp" name="btn_filtrar_transp"><?=$this->lang->line('application_search');?></button>
              </div>
              <!-- /.box-body -->
            </div>
        <?php endif; ?>
        
        <div class="box">
          <div class="box-body">
            <table id="resumoTable" class="table table-bordered table-striped">
              <thead>
              <tr>
				<th><?=$this->lang->line('application_status');?></th>
                <th><?=$this->lang->line('application_ship_value');?></th>
                <th><?=$this->lang->line('application_ship_value');?> - Pago</th>
                <th><?=$this->lang->line('application_ship_value');?> - Diferença</th>
                <th><?=$this->lang->line('application_parameter_mktplace_type_ciclo');?></th>
                <th><?=$this->lang->line('application_Day');?> da <?=$this->lang->line('application_Week');?></th>
                <th><?=$this->lang->line('application_payment_start_day');?></th>
                <th><?=$this->lang->line('application_payment_end_day');?></th>         
                <th><?=$this->lang->line('application_payment_date');?></th>
                <th><?=$this->lang->line('application_payment_date_conecta');?></th>
              </tr>
              </thead>
            </table>
          </div>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
              	<th><?=$this->lang->line('application_cnpj');?></th>
                <th><?=$this->lang->line('application_ship_company');?></th>
                <th><?=$this->lang->line('application_ship_value');?></th>
                <th><?=$this->lang->line('application_ship_value');?> - Pago</th>
                <th><?=$this->lang->line('application_ship_value');?> - Diferença</th>
                <th><?=$this->lang->line('application_parameter_mktplace_type_ciclo');?></th>
                <th><?=$this->lang->line('application_Day');?> da <?=$this->lang->line('application_Week');?></th>
                <th><?=$this->lang->line('application_payment_start_day');?></th>
                <th><?=$this->lang->line('application_payment_end_day');?></th>         
                <th><?=$this->lang->line('application_payment_date');?></th>
                <th><?=$this->lang->line('application_payment_date_conecta');?></th>
				<th><?=$this->lang->line('application_status');?></th>
              </tr>
              </thead>
            </table>
          </div>
          <!-- /.box-body -->
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

<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="observacaoModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_extract_obs')?> - Transportadora</h4>
      </div>
      <form role="form" action="" method="post" id="formObservacao">
        <div class="modal-body">
        	<input type="hidden" class="form-control" id="txt_hdn_pedido" name="txt_hdn_pedido" placeholder="observacao">
        	
        	<label for="group_isadmin"><?=$this->lang->line('application_extract_obs_fixed');?></label>
              <select class="form-control" id="slc_obs_fixo" name="slc_obs_fixo"">
                <option value="">~~SELECT~~</option>
                <?php foreach ($obsFixo as $obs): ?>
                  <option value="<?php echo trim($obs['id']); ?>"><?php echo trim($obs['observacao_fixa']); ?></option>
                <?php endforeach ?>
              </select>
              
          <label for="group_name"><?=$this->lang->line('application_extract_obs');?></label>
          <textarea class="form-control" id="txt_observacao" name="txt_observacao" placeholder="<?=$this->lang->line('application_extract_obs');?>"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
          <button type="button" class="btn btn-primary" id="btnSalvarObs" name="btnSalvarObs"><?=$this->lang->line('application_confirm')?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="valorModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_ship_value');?> - Real</h4>
      </div>
      <form role="form" action="" method="post" id="formFrete">
        <div class="modal-body">
        	<input type="hidden" class="form-control" id="txt_hdn_pedido_valor" name="txt_hdn_pedido_valor" placeholder="observacao">
              
         <label for="group_name"><?=$this->lang->line('application_ship_value');?> - Real</label>
		<input class="form-control" type="number" id="txt_novo_frete" name="txt_novo_frete" placeholder="<?=$this->lang->line('application_extract_obs');?>">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
          <button type="button" class="btn btn-primary" id="btnSalvarValorFrete" name="btnSalvarValorFrete"><?=$this->lang->line('application_confirm')?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="listaObs">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_extract_obs')?></h4>
      </div>
        <div class="modal-body" id="divListObsFunc">
        	Carregando....
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
        </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<script type="text/javascript">
var manageTable;
var resumoTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    
    $("#btn_filtrar_transp").click( function(){

    	var novoCaminho = base_url + 'billet/fetchConciliacaoGridDataTranspresumo/'+$("#slc_transportadora").val()+"/"+$("#slc_ciclo_transp").val();

    	$('#manageTable').DataTable().destroy();
    	manageTable = $('#manageTable').DataTable({
    		"scrollX": true,
		    'ajax': novoCaminho,
		    'order': []
		  });

    	$('#resumoTable').DataTable().destroy();
    	resumoTable = $('#resumoTable').DataTable({
       		'scrollX': true,
       	    'ajax': base_url + 'billet/fetchConciliacaoGridDataTranspresumoTotais',
       	    'order': []
   	  	});


    });

  $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
	 'scrollX': true,
    'ajax': base_url + 'billet/fetchConciliacaoGridDataTranspresumo',
    'order': []
  });

  resumoTable = $('#resumoTable').DataTable({
		 'scrollX': true,
	    'ajax': base_url + 'billet/fetchConciliacaoGridDataTranspresumoTotais',
	    'order': []
	  });

  $("#btnSalvarObs").click(function (){

		if($("#txt_hdn_pedido").val() == "" ||
			$("#txt_observacao").val() == "" ||
			$("#slc_obs_fixo").val() == "" ){
			alert("Preencha todos os campos da Observação antes de salvar");
			return false;
		}
		var pageURL = base_url.concat("billet/salvarobstranpsresumo");
		var form = $("#formObservacao").serialize()+"&hdnLote="+$("#hdnLote").val();

		$.post( pageURL, form, function( data ) {
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
	        
			$("#txt_hdn_pedido").val("");
			$("#txt_observacao").val("");
			$("#slc_obs_fixo").val("");
			$("#txt_novo_frete").val("");
			$("#observacaoModal").modal('hide');

			$("#btn_filtrar_transp").click();

		}
		 
	  });
  });	

  $("#btnSalvarValorFrete").click(function (){

		if($("#txt_hdn_pedido_valor").val() == "" ||
			$("#txt_novo_frete").val() == ""){
			alert("Preencha todos os campos do Frete antes de salvar");
			return false;
		}
		var pageURL = base_url.concat("billet/salvarfretetranpsresumo");
		var form = $("#formFrete").serialize();

		$.post( pageURL, form, function( data ) {
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
	        
			$("#txt_hdn_pedido_valor").val("");
			$("#txt_novo_frete").val("");
			$("#valorModal").modal('hide');

			$("#btn_filtrar_transp").click();

		}
		 
	  });
});

});

function listarObservacao(id){
	if(id){
		$("#divListObsFunc").html("Carregando...");
		var pageURL = base_url.concat("billet/buscaobservacaotranspresumo");
		var form = $("#formFrete").serialize();
		$.post( pageURL, {chave: id}, function( data ) {
		
			var obj = JSON.parse(data);
			var texto = '<table class="table table-bordered table-striped"><tr><td>CNPJ</td><td>Observação</td><td>Data Observação</td></tr>';

			Object.keys(obj).forEach(function(k){
                texto = texto.concat("<tr><td>",obj[k].cnpj,"</td><td>",obj[k].observacao,"</td><td>",obj[k].data_criacao,"</td></tr>");    
			});

            texto = texto.concat("</table>");
			$("#divListObsFunc").html(texto);
		});
	}
}

function incluirObservacao(id)
{
  if(id) {
		$("#txt_hdn_pedido").val(id);
	}
}

function incluirNovoValor(id)
{
  if(id) {
		$("#txt_hdn_pedido_valor").val(id);
	}
}


</script>