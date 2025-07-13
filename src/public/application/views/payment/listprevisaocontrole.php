<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_extract";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
  
  <div class="row">
      	<div class="col-md-12 col-xs-12">
        	<div class="box">
            	<div class="box-header">
                  <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
                </div>
          		<div class="box-body">
              		<label for="group_isadmin"><?=$this->lang->line('application_conciliacao_month_year');?></label>
                  <select class="form-control" id="slc_ano_mes" name="slc_ano_mes"">
                    <option value="">~~SELECT~~</option>
                    <option value="01/2020">Janeiro/2020</option>
                    <option value="02/2020">Fevereiro/2020</option>
                    <option value="03/2020">Março/2020</option>
                    <option value="04/2020">Abril/2020</option>
                    <option value="05/2020">Maio/2020</option>
                    <option value="06/2020">Junho/2020</option>
                    <option value="07/2020">Julho/2020</option>
                    <option value="08/2020">Agosto/2020</option>
                    <option value="09/2020">Setembro/2020</option>
                    <option value="10/2020">Outubro/2020</option>
                    <option value="11/2020">Novembro/2020</option>
                    <option value="12/2020">Dezembro/2020</option>
                  </select>
          		</div>
        	</div>
        </div>
 	</div>
  	<div class="row">
      	<div class="col-md-12 col-xs-12">
        	<div class="box">
            	<div class="box-header">
                  <h3 class="box-title"><?=$this->lang->line('application_parameter_payment_forecast');?></h3>
                </div>
          		<div class="box-body">
          			
                    <label for="group_name"><?=$this->lang->line('application_conciliacao_month_year');?></label>
          			<input type="text" class="form-control" id="txt_ano_mes" name="txt_ano_mes" value="Maio/2020" readonly / >
                    <br>
              		<table class="col-md-12 col-xs-12" id=resumoExtrato" border="0">
              			<tr>
              				<td> <b>Parceiro / Dia</b> </td>
              				<?php 
              				$arrayOrdem = array();
              				$arrayOrdemZero = array();
              				$i = 0;
              				$j = 0;
              				$k = 0;
              				foreach($grid_valores_pagamentos as $grid_valores_pagamento){
              				    if($grid_valores_pagamento['data_pagamento'] <> "0"){
              				        $valor = $grid_valores_pagamento['mktplace'].$grid_valores_pagamento['data_pagamento'];
              				        if (!in_array($valor, $arrayOrdem)) { 
              				            $arrayOrdem[$i] = $grid_valores_pagamento['mktplace'].$grid_valores_pagamento['data_pagamento'];
              				            $i++;
              				            echo "<td> <b>Dia do ciclo ".$grid_valores_pagamento['mktplace'].": ".$grid_valores_pagamento['data_pagamento']."</b> </td>";
              				        }
              				    }
              				}
              				
              				foreach($grid_valores_pagamentos as $grid_valores_pagamento){
              				    if($grid_valores_pagamento['data_pagamento'] == "0"){
              				        $valor = $grid_valores_pagamento['mktplace'].$grid_valores_pagamento['data_pagamento'];
              				        if (!in_array($valor, $arrayOrdemZero)) {
              				            $arrayOrdemZero[$k] = $grid_valores_pagamento['mktplace'].$grid_valores_pagamento['data_pagamento'];
              				            $k++;
              				            echo "<td> <b>Pedidos Aguardando NF/Envio ".$grid_valores_pagamento['mktplace']."</b> </td>";
              				        }
              				    }
              				}
              				
              				echo "</tr>";
              				
              			    $j = 0;
              			    $arrayLoja = array();
              			    $z = 0;
              			    foreach($grid_valores_pagamentos as $grid_valores_pagamento){
              			        $j = 0;
              			        $loja = $grid_valores_pagamento['loja'];
              			        //Valores de dia por mktplace loja
              			        if(!in_array($loja, $arrayLoja)){
                  			        echo "<tr><td>".$loja."</td>";
                  			        foreach($grid_valores_pagamentos as $grid_valores_pagamento2){
                  			            
                  			            if($grid_valores_pagamento2['loja'] == $loja and $grid_valores_pagamento2['data_pagamento'] <> "0"){
                  			                $valorComp = $grid_valores_pagamento2['mktplace'].$grid_valores_pagamento2['data_pagamento'];
                  			                if ($valorComp == $arrayOrdem[$j]) {
                  			                    echo "<td>R$ ".$grid_valores_pagamento2['valor_total_ciclo']."</td>";
                  			                }else{
                  			                    echo "<td>R$ 00.00</td>";
                  			                }
              			                    $j++;
                  			            }
                  			        }
                  			        
                  			        //coloca as colunas que não tem ao final do ciclo antes do somatório de pedidos fora do ciclo
                  			        while($j<=$i-1){
                  			            echo "<td>R$ 00.00</td>";
                  			            $j++;
                  			        }
                  			        
                  			        $j = 0;
                  			        foreach($grid_valores_pagamentos as $grid_valores_pagamento2){
                  			            
                  			            if($grid_valores_pagamento2['loja'] == $loja and $grid_valores_pagamento2['data_pagamento'] == "0"){
                  			                $valorComp = $grid_valores_pagamento2['mktplace'].$grid_valores_pagamento2['data_pagamento'];
                  			                if ($valorComp == $arrayOrdemZero[$j]) {
                  			                    echo "<td>R$ ".$grid_valores_pagamento2['valor_total_ciclo']."</td>";
                  			                }else{
                  			                    echo "<td>R$ 00.00</td>";
                  			                }
                  			                $j++;
                  			            }
                  			        }
                  			        
                      			   echo "</tr>";
                  			     }
                  			     $arrayLoja[$z] = $loja;
                  			     $z++;
              			    }
              			   ?>
              				
              		</table>
          		</div>
              		<br>- Os Pedidos são agrupados e contabilizados nas previsões de acordo com a data de entrega ou pela data de previsão de entrega. <br> Caso o pedido não possua essas datas preenchidas, ele irá contabilizar na coluna "Pedidos Aguardando NF/Envio".
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
                  <h3 class="box-title"><?=$this->lang->line('application_orders');?></h3>
                </div>
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_purchase_id');?></th> 
				<th><?=$this->lang->line('application_status');?></th> 
                <th><?=$this->lang->line('application_date');?></th>
                <th><?=$this->lang->line('application_payment_date_delivered');?></th>
                <th><?=$this->lang->line('application_total_amount');?></th>
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

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    

  // $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'payment/extratoprevisao',
    'order': []
  });

});

</script>
