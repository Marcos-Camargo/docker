<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php  $data['pageinfo'] = "application_parameter_payment_forecast_screen";  
	       $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
  
  <div class="row">
    	<div class="box">
      		<div class="box-body">
          		<div class="box-header">
                  <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
                </div> 
		      	<div class="col-md-5 col-xs-5">
              		<label for="group_isadmin"><?=$this->lang->line('application_Month');?></label>
                  <select class="form-control" id="slc_mes" name="slc_mes">
                    <option value="">~~SELECT~~</option>
                    <option value="01">Janeiro</option>
                    <option value="02">Fevereiro</option>
                    <option value="03">Março</option>
                    <option value="04">Abril</option>
                    <option value="05">Maio</option>
                    <option value="06">Junho</option>
                    <option value="07">Julho</option>
                    <option value="08">Agosto</option>
                    <option value="09">Setembro</option>
                    <option value="10">Outubro</option>
                    <option value="11">Novembro</option>
                    <option value="12">Dezembro</option>
                  </select>
          		</div>
          		<div class="col-md-4 col-xs-4">
              		<label for="group_isadmin"><?=$this->lang->line('application_Year');?></label>
                  <select class="form-control" id="slc_ano" name="slc_ano">
                    <option value="">~~SELECT~~</option>
                    <option value="2020">2020</option>
                    <option value="2021">2021</option>
                    <option value="2022">2022</option>
                    <option value="2023">2023</option>
                    <option value="2024">2024</option>
                  </select>
          		</div>
          		
          		<div class="col-md-3 col-xs-3">
              		<label for="group_isadmin">Status</label>
                  <select class="form-control" id="slc_status_ciclo" name="slc_status_ciclo">
                    <option value="">~~SELECT~~</option>
                    <option value="Em andamento">Em andamento</option>
                    <option value="Em andamento - Fora de Ciclo">Em andamento - Fora de Ciclo</option>
                    <option value="Encerrado - Sem repasse">Encerrado - Sem repasse</option>
                    <option value="Encerrado - Conciliado">Encerrado - Conciliado</option>
                    <option value="Encerrado - Não Conciliado">Encerrado - Não Conciliado</option>
                  </select>
          		</div>
          		
          		<div class="col-md-2 col-xs-2"><br>
            		<button type="button" id="btnFilter" name="btnFilter" class="btn btn-primary"><?=$this->lang->line('application_filter');?></button>
        		</div>
        	</div>
        </div>
 	</div>
  
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
                  <h3 class="box-title"><?=$this->lang->line('application_orders');?> - <?=$this->lang->line('application_parameter_mktplace_ciclos');?></h3> 
                  <br><br>
                  - <b>Data do Ciclo:</b> As datas de Ciclo são formadas pela data de entrega do pedido e atualizadas pelo status do código de rastreio;<br>
                  - <b>Dia Ciclo Marketplace:</b> Dia em que o Marketplace repassa para o Conecta Lá (referente ao mês seguinte);<br>
                  - <b>Dia Conecta Lá:</b> Dia em que o Conecta Lá repassa para o Lojista (referente ao mês seguinte);<br>
                  - <b>Status do Ciclo:</b><br> 
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- 
                  <b>Em andamento</b> -> Pedidos que tiverem atualização de status, para <b>Entregue</b>, até a data fim do ciclo estarão sendo computados;<br>
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- 
                  <b>Em andamento - Fora de Ciclo</b> -> Pedidos que ainda não estão no status <b>Entregue</b>;<br>
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- 
                  <b>Encerrado - Sem repasse</b> -> Ciclos encerrados que não possuem pedidos com status <b>Entregue</b>;<br>
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- 
                  <b>Encerrado - Conciliado</b> -> Pedidos que tiveram atualização de status, para <b>Entregue</b>,até a data fim do ciclo estarão sendo computados e já passaram pela conciliação;<br>
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- 
                  <b>Encerrado - Não Conciliado</b> -> Pedidos que tiveram atualização de status, para <b>Entregue</b>, até a data fim do ciclo estarão sendo computados e ainda <b>não</b> passaram pela conciliação;<br>
                  - <b>Valor Previsto:</b> Projeção de recebimento para o Lojista;<br>
                  - <b>Valor Recebido:</b> Valor pago pelo Marketplace;<br>
                  - <b>Divergência:</b> Diferença entre o Valor Previsto x Valor Recebido;<br>
                  - <b>Observação:</b> Descrição das divergências;<br>
                </div>
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
              	<th>Id Ciclo</th>
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_marketplace');?></th>
                <!-- <th>Mês/Ano do Ciclo</th>  -->
                <th><?=$this->lang->line('application_date');?> início do Ciclo</th>
                <th><?=$this->lang->line('application_date');?> fim do Ciclo</th>
                <th>Dia pagamento<br> Marketplace</th>
                <th>Dia repasse<br> Conecta Lá</th>
                <th>Status do Ciclo</th>
                <th>Valor Previsto</th>
                <th>Valor Recebido</th>
                <th>Divergência</th>
                <th><?=$this->lang->line('application_extract_obs');?></th>
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
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  var concatenado = $("#slc_ano").val()+$("#slc_mes").val(); 

  // $("#mainReceivableNav").addClass('active');
  // $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'payment/extratoprevisao?data=' + concatenado + '&status=' + $("#slc_status_ciclo").val(),
    "scrollX": true,
	"lengthChange": false,
	"bFilter": false,
    'order': []
  });

  $("#btnFilter").click(function(){

	if( ($("#slc_ano").val() != "" && $("#slc_mes").val() == "") || ($("#slc_ano").val() == "" && $("#slc_mes").val() != "")){
		alert("É necessário o preenchimento de todos os campos para o filtro");
		return false;
	}
	  
	  concatenado = $("#slc_ano").val()+$("#slc_mes").val(); 

	  $('#manageTable').DataTable().destroy();
	  manageTable = $('#manageTable').DataTable({
			"scrollX": true,
			"lengthChange": false,
			"bFilter": true,
		    'ajax': base_url + 'payment/extratoprevisao?data=' + concatenado + '&status=' + $("#slc_status_ciclo").val(),
		    'order': []
		  });

 	  
  });

});

function listarObservacao(lote, store_id){
	if(lote){
		$("#divListObsFunc").html("Carregando...");
		var pageURL = base_url.concat("payment/buscaobservacaopedidolote");
		 
		$.post( pageURL, {lote : lote, store_id: store_id}, function( data ) {
		
			var obj = JSON.parse(data);
			var texto = '<table class="table table-bordered table-striped"><tr><td>Pedido</td><td>Observação</td><td>Data Observação</td></tr>';

			Object.keys(obj).forEach(function(k){
                texto = texto.concat("<tr><td>",obj[k].num_pedido,"</td><td>",obj[k].observacao,"</td><td>",obj[k].data_criacao,"</td></tr>");    
			});

            texto = texto.concat("</table>");
			$("#divListObsFunc").html(texto);
		});
	}
}

</script>
