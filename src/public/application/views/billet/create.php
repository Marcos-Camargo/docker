<!--
SW Serviços de Informática 2019
Criar Grupos de Acesso
-->
<?php

    $colspan = 8; //quantidade de colunas de campanhas
    $colspan2 = 5;
    $colspan1 = 17;

    if ($negociacao_marketplace_campanha == "1" && $canceled_orders_data_conciliation != "1")
    {
        $colspan = 10; // 2 novas colunas em campanhas
    }
        

    if ($canceled_orders_data_conciliation == "1" && $canceled_orders_data_conciliation == "1")
    {
        $colspan = 10; // 2 novas colunas em campanhas
        $colspan2 = 8; // 3 novas colunas de cancelamento
    }
        

    if ($canceled_orders_data_conciliation != "1" && $canceled_orders_data_conciliation == "1")
    {
        $colspan2 = 8; // 3 novas colunas de cancelamento
    }

    $news_columns_fin_192 = "";
		if($fin_192_novos_calculos == "1"){		
			$colspan1 = $colspan1 + 10; 
			$news_columns_fin_192 = '<th colspan="10" bgcolor="#708090" class="text-center"><font color="#FFFFFF">Outros Valores</font></th>';
		}
        
?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data); ?>

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
              <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
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

                <div class="form-group col-md-3 col-xs-3">
                	<input type="hidden" id="hdnLote" name="hdnLote" value="<?php echo $hdnLote;?>" />
                	<input type="hidden" id="hdnExcel" name="hdnExcel" value="" />
                	<input type="hidden" id="hdnExtensao" name="hdnExtensao" value="" />
                	<input type="hidden" id="txt_carregado" name="txt_carregado" value="0" />
                  <label for="group_isadmin"><?=$this->lang->line('application_runmarketplaces');?></label>
                  <select class="form-control" id="slc_mktplace" name="slc_mktplace" >
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($mktplaces as $mktPlaces): ?>
                      <option value="<?php echo trim($mktPlaces['id']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                    <?php endforeach ?>
                    <option value="999">Conciliação Manual</option>
                  </select>
                </div>


                <div class="form-group col-md-3 col-xs-3">
                  <label for="group_isadmin"><?=$this->lang->line('application_conciliacao_month_year');?></label>
                  <input class="form-control" type="text" id="txt_ano_mes" autocomplete="off" name = "txt_ano_mes" placeholder="<?=$this->lang->line('application_conciliacao_month_year');?>"/>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                  <label for="group_isadmin"><?=$this->lang->line('application_parameter_mktplace_value_ciclo');?></label>
                  <select class="form-control" id="slc_ciclo" name="slc_ciclo">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($ciclo as $cil): ?>
                      <option value="<?php echo trim($cil['id']); ?>"><?php echo trim($cil['mkt_place']).' - do dia : '.$cil['data_inicio'].' - até: '.$cil['data_fim']; ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
                <div class="col-md-2 col-xs-2" id="divExcel" name="divExcel" style="display:none"><br>
             		<button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Excel</button>
                 <button type="button" id="btnModelo" name="btnModelo" class="btn btn-success">Modelo Ajuste</button>
        	 	</div>
               <div class="col-md-12 col-xs-12">
                <?php if(in_array('createBilletConcil', $user_permission)|| in_array('updateBilletConcil', $user_permission)): ?>
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
            
            <div class="box" id="DivTotais" style="display:none">
            	<div class="box-header"> Totais</div>
				<div class="box-body pad table-responsive">
              		<table id="manageTableTotais" class="table table-bordered table-striped" style="width: 100%;">
                      <thead>
                      <tr>
                        <th>Id</th>
                        <th>Tipo Valor</th>
                        <th><?=$this->lang->line('application_value');?></th>
                      </tr>
                      </thead>
        
                    </table>
              </div>
              <!-- /.box-body -->
            </div>
            
            <div class="box" id="divBtnControll" style="display: none">
            	<div class="box-header"> </div>
				<div class="box-body pad table-responsive">
              		<table class="table table-bordered text-center">
              			<tbody>
              				<tr>
              					<th><button type="button" class="btn btn-block btn-success btn-flat" onclick="" id="btnDivOk"> <div id="numOk"> <?=$this->lang->line('application_conciliacao_orders_ok');?> (0) </div> </button></th>
              					<th><button type="button" class="btn btn-block btn-warning btn-flat" onclick="" id="btnDivDiv"> <div id="numDiv"> <?=$this->lang->line('application_conciliacao_orders_div');?> (0) </div> </button></th>
              					<th><button type="button" class="btn btn-block btn-danger btn-flat" onclick=""  id="btnDivNfound"> <div id="numNfound"> <?=$this->lang->line('application_conciliacao_orders_not_found');?> (0) </div> </button></th>
              					<th><button type="button" class="btn btn-block btn-info btn-flat" onclick=""    id="btnDivEst"> <div id="numEst"> <?=$this->lang->line('application_conciliacao_orders_estorno');?> (0) </div> </button></th>
              					<th><button type="button" class="btn btn-block btn-primary btn-flat" onclick="" id="btnDivOther"> <div id="numOvalues"> <?=$this->lang->line('application_conciliacao_orders_others_values');?> (0) </div> </button></th>
              			</tbody>
              		</table>
              </div>
              <!-- /.box-body -->
            </div>
            
                <div class="box" id="DivOk" style="display: none">
                	<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_conciliacao_orders_ok');?></h3> <button type="button" class="btn btn-default" onclick="" data-toggle="modal" data-target="#modalmanageTableOrdersOk"><i class="fa fa-question-circle"></i></button>
                        <?php if(in_array('createBilletConcil', $user_permission)|| in_array('updateBilletConcil', $user_permission)): ?>
                            <button type="button" class="btn btn-primary" id="btnAprovarOk" name="btnAprovarOk">Aprovar Pedidos</button>
                        <?php endif; ?>
                    </div>
                  <div class="box-body">
                  	
                    <table id="manageTableOrdersOk" class="table table-bordered table-striped">
                      <thead>
                      <tr>
                            <th colspan="26" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            <th colspan="10" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            <th colspan="<?= $colspan1 ?>" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Informações</font></th>
                      </tr>  
                      <tr>
                      		<th colspan="12" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            <th colspan="4" bgcolor="#2626C9" class="text-center"><font color="#FFFFFF">Marketplace</font></th>
                            <th colspan="5" bgcolor="#16F616" class="text-center"><font color="#FFFFFF">Conecta Lá</font></th>
                            <th colspan="5" bgcolor="#E1AA98" class="text-center"><font color="#FFFFFF">Seller</font></th>

                            <th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            <th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Expectativa x Recebido</font></th>
                            <th colspan="4" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores a pagar</font></th>

                            <!-- braun -->
                            <th colspan="<?=$colspan;?>" bgcolor="purple" class="text-center"><font color="#FFFFFF">Influência de Campanhas / Ofertas / Promoções</font></th>
                            <?= $news_columns_fin_192 ?>
                            <th colspan="<?=$colspan2;?>" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Observação/Chamado/Responsável/Ação</font></th>
                      </tr>
                      <tr>
                        <th><?=$this->lang->line('application_purchase_id');?></th>
                        <th><?=$this->lang->line('application_store');?></th>
                        <th><?=$this->lang->line('application_date');?> Pedido</th>
                        <th>Pedido enviado</th>
                        <th>Tipo de Frete</th>
                        <th><?=$this->lang->line('application_status_conciliacao');?></th>
                        <th><?=$this->lang->line('application_conciliacao_mktplace_value');?></th> 
                        <th>Valor Pago Antecipação</th>            
                        <th><?=$this->lang->line('application_value_products');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_ship_value');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_rate');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_rate');?> - Seller</th>
                        <th><?=$this->lang->line('application_value');?> - Pago Marketplace</th>
                        <th><?=$this->lang->line('application_value_products');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_ship_value');?> - Marketplace</th>
                        <th>Receita - Marketplace</th>
                        <th>Valor Comissão Produto - ConectaLá</th>
                        <th>Frete Real - ConectaLá</th>
                        <th>Valor Comissão Extra Frete - ConectaLá</th>
                        <th>Valor Comissão Frete - ConectaLá</th>
                        <th>Valor a receber - ConectaLá</th>
                        <th>Valor Desconto Produto - Seller</th>
                        <th>Valor Descontro Frete - Seller</th>                      
                        <th>Valor a receber - Seller</th>
                        <th>Valor a receber Ajustado - Seller</th>
                        <th>Valor de desconto a ser acrescido</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido produto</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido frete</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido Total</th>
                        <th>Dif. Valor Recebido de produto</th>
                        <th>Dif. Valor Recebido de frete</th>
                        <th>Dif. Valor Recebido Total</th>
                        
                        <th>Valor Comissão Produto - ConectaLá</th>
                        <th>Valor Comissão Frete - ConectaLá</th>
                        <th>Valor a receber - ConectaLá</th>
                        <th>Valor a receber - Seller</th>
                        
                        <th><?=$this->lang->line('application_campaigns_pricetags');?></th>
                        <th><?=$this->lang->line('application_campaigns_campaigns');?></th>
                        <th><?=$this->lang->line('application_campaigns_marketplace');?></th>
                        <th><?=$this->lang->line('application_campaigns_seller');?></th>
                        <th><?=$this->lang->line('application_campaigns_promotions');?></th>
                        <th><?=$this->lang->line('application_campaigns_comission_reduction');?></th>
                        <th><?=$this->lang->line('application_campaigns_rebate');?></th>
                        <th><?=$this->lang->line('application_campaigns_refund');?></th>                       

                        <?php
                        if ($negociacao_marketplace_campanha == "1")
                        {
                        ?>
                            <th><?=$this->lang->line('conciliation_sc_gridok_comissionreduxchannel');?></th>
                            <th><?=$this->lang->line('conciliation_sc_gridok_rebatechannel');?></th>
                            <!-- <th><?=$this->lang->line('conciliation_sc_gridok_channelrefund');?></th> -->
                        <?php 
                        }
                        ?>

                        <?php if($fin_192_novos_calculos == "1"){ ?>    
                          <th>Comissão MarketPlace</th>
                          <th>Valor Repasse MarketPlace</th>
                          <th>Comissão Negociada Seller</th>
                          <th>Comissão Conecta Lá</th>
                          <th>Frete Conecta Lá</th>
                          <th>Retenção conecta</th>
                          <th>Recebimento Seller</th>
                          <th>Valor Recebido MarketPlace | Extrato</th>
                          <th>Check Valor</th>
                          <th>Status</th>
                        <?php } ?> 

                        <th><?=$this->lang->line('application_extract_obs');?></th>
                        <th>Chamado Marketplace</th>
                        <th>Chamado Agidesk</th>
                        <th>Responsável Conciliação</th>

                        <!-- braun -->
                        <?php
                        if ($canceled_orders_data_conciliation == "1")
                        {
                        ?>
                            <th><?=$this->lang->line('conciliation_grid_cancel_responsible');?></th>
                            <th><?=$this->lang->line('conciliation_grid_cancel_reason');?></th>
                            <th><?=$this->lang->line('conciliation_grid_cancel_penalty');?></th>
                        <?php 
                        }
                        ?>

                        <th><?=$this->lang->line('application_action');?></th>
                      </tr>
                      </thead>
        
                    </table>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->
                
                <div class="box" id="DivDiv" style="display: none">
                	<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_conciliacao_orders_div');?></h3> <button type="button" class="btn btn-default" onclick="" data-toggle="modal" data-target="#modalmanageTableOrdersDiv"><i class="fa fa-question-circle"></i></button>
                        <?php if(in_array('createBilletConcil', $user_permission)|| in_array('updateBilletConcil', $user_permission)): ?>
                            <button type="button" class="btn btn-success" id="btnMoverDivergente" name="btnMoverDivergente">Mover Pedidos para Ok</button>
                        <?php endif; ?>

                    </div>
                  <div class="box-body">
                  	
                    <table id="manageTableOrdersDiv" class="table table-bordered table-striped">
                      <thead>
                      <tr>
                            <th colspan="26"" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            <th colspan="10" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            <th colspan="<?= $colspan1 ?>" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Informações</font></th>
                      </tr>  
                      <tr>
                      		<th colspan="12" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            <th colspan="4" bgcolor="#2626C9" class="text-center"><font color="#FFFFFF">Marketplace</font></th>
                            <th colspan="5" bgcolor="#16F616" class="text-center"><font color="#FFFFFF">Conecta Lá</font></th>
                            <th colspan="5" bgcolor="#E1AA98" class="text-center"><font color="#FFFFFF">Seller</font></th>

                            <th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            <th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Expectativa x Recebido</font></th>
                            <th colspan="4" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores a pagar</font></th>

                            <!-- braun -->
                            <th colspan="<?=$colspan;?>" bgcolor="purple" class="text-center"><font color="#FFFFFF">Influência de Campanhas / Ofertas / Promoções</font></th>
                            <?= $news_columns_fin_192 ?>
                            <th colspan="<?=$colspan2;?>" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Observação/Chamado/Responsável/Ação</font></th>
                      </tr>
                      <tr>
                        <th><?=$this->lang->line('application_purchase_id');?></th>
                        <th><?=$this->lang->line('application_store');?></th>
                        <th><?=$this->lang->line('application_date');?> Pedido</th>
                        <th>Pedido enviado</th>
                        <th>Tipo de Frete</th>
                        <th><?=$this->lang->line('application_status_conciliacao');?></th>
                        <th><?=$this->lang->line('application_conciliacao_mktplace_value');?></th> 
                        <th>Valor Pago Antecipação</th>
                        <th><?=$this->lang->line('application_value_products');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_ship_value');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_rate');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_rate');?> - Seller</th>
                        <th><?=$this->lang->line('application_value');?> - Pago Marketplace</th>
                        <th><?=$this->lang->line('application_value_products');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_ship_value');?> - Marketplace</th>
                        <th>Receita - Marketplace</th>
                        <th>Valor Comissão Produto - ConectaLá</th>
                        <th>Frete Real - ConectaLá</th>
                        <th>Valor Comissão Extra Frete - ConectaLá</th>
                        <th>Valor Comissão Frete - ConectaLá</th>
                        <th>Valor a receber - ConectaLá</th>
                        <th>Valor Desconto Produto - Seller</th>
                        <th>Valor Descontro Frete - Seller</th>
                        <th>Valor a receber - Seller</th>
                        <th>Valor a receber Ajustado - Seller</th>
                        <th>Valor de desconto a ser acrescido</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido produto</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido frete</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido Total</th>
                        <th>Dif. Valor Recebido de produto</th>
                        <th>Dif. Valor Recebido de frete</th>
                        <th>Dif. Valor Recebido Total</th>
                        
                        <th>Valor Comissão Produto - ConectaLá</th>
                        <th>Valor Comissão Frete - ConectaLá</th>
                        <th>Valor a receber - ConectaLá</th>
                        <th>Valor a receber - Seller</th>
                        
                        <!-- braun -->
                        <th><?=$this->lang->line('application_campaigns_pricetags');?></th>
                        <th><?=$this->lang->line('application_campaigns_campaigns');?></th>
                        <th><?=$this->lang->line('application_campaigns_marketplace');?></th>
                        <th><?=$this->lang->line('application_campaigns_seller');?></th>
                        <th><?=$this->lang->line('application_campaigns_promotions');?></th>
                        <th><?=$this->lang->line('application_campaigns_comission_reduction');?></th>
                        <th><?=$this->lang->line('application_campaigns_rebate');?></th>
                        <th><?=$this->lang->line('application_campaigns_refund');?></th>                        


                        <?php
                        if ($negociacao_marketplace_campanha == "1")
                        {
                        ?>
                            <th><?=$this->lang->line('conciliation_sc_gridok_comissionreduxchannel');?></th>
                            <th><?=$this->lang->line('conciliation_sc_gridok_rebatechannel');?></th>
                            <!-- <th><?=$this->lang->line('conciliation_sc_gridok_channelrefund');?></th> -->
                        <?php 
                        }
                        ?>

                        <?php if($fin_192_novos_calculos == "1"){ ?>    
                          <th>Comissão MarketPlace</th>
                          <th>Valor Repasse MarketPlace</th>
                          <th>Comissão Negociada Seller</th>
                          <th>Comissão Conecta Lá</th>
                          <th>Frete Conecta Lá</th>
                          <th>Retenção conecta</th>
                          <th>Recebimento Seller</th>
                          <th>Valor Recebido MarketPlace | Extrato</th>
                          <th>Check Valor</th>
                          <th>Status</th>
                        <?php } ?>  

                        <th><?=$this->lang->line('application_extract_obs');?></th>
                        <th>Chamado Marketplace</th>
                        <th>Chamado Agidesk</th>
                        <th>Responsável Conciliação</th>

                        <!-- braun -->
                        <?php
                        if ($canceled_orders_data_conciliation == "1")
                        {
                        ?>
                            <th><?=$this->lang->line('conciliation_grid_cancel_responsible');?></th>
                            <th><?=$this->lang->line('conciliation_grid_cancel_reason');?></th>
                            <th><?=$this->lang->line('conciliation_grid_cancel_penalty');?></th>
                        <?php 
                        }
                        ?>

                        <th><?=$this->lang->line('application_action');?></th>
                      </tr>
                      </thead>
        
                    </table>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->

                <style>

                    .dataTable thead tr th:nth-of-type(1){
                        /* overflow-wrap: break-word; */
                        white-space: nowrap;
                    }

                </style>
                
                <div class="box" id="DivNfound" style="display: none"> 
                	<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_conciliacao_orders_not_found');?></h3> <button type="button" class="btn btn-default" onclick="" data-toggle="modal" data-target="#modalmanageTableOrdersNotFound"><i class="fa fa-question-circle"></i></button>
                        <?php if(in_array('createBilletConcil', $user_permission)|| in_array('updateBilletConcil', $user_permission)): ?>
                            <button type="button" class="btn btn-success" id="btnMoverNaoEncontrado" name="btnMoverNaoEncontrado">Mover Pedidos para Ok</button>
                        <?php endif; ?>

                    </div>
                  <div class="box-body">
                  	
                    <table id="manageTableOrdersNotFound" class="table table-bordered table-striped">
                      <thead>
                      <tr>
                            <th colspan="26" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            <th colspan="10" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            <th colspan="<?= $colspan1 ?>" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Informações</font></th>
                      </tr>  
                      <tr>
                      		<th colspan="12" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            <th colspan="4" bgcolor="#2626C9" class="text-center"><font color="#FFFFFF">Marketplace</font></th>
                            <th colspan="5" bgcolor="#16F616" class="text-center"><font color="#FFFFFF">Conecta Lá</font></th>
                            <th colspan="5" bgcolor="#E1AA98" class="text-center"><font color="#FFFFFF">Seller</font></th>

                            <th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            <th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Expectativa x Recebido</font></th>
                            <th colspan="4" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores a pagar</font></th>

                            <!-- braun -->
                            <th colspan="<?=$colspan;?>" bgcolor="purple" class="text-center"><font color="#FFFFFF">Influência de Campanhas / Ofertas / Promoções</font></th>
                            <?= $news_columns_fin_192 ?>
                            <th colspan="<?=$colspan2;?>" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Observação/Chamado/Responsável/Ação</font></th>
                      </tr>
                      <tr>
                        <th><?=$this->lang->line('application_purchase_id');?></th>
                        <th><?=$this->lang->line('application_store');?></th>
                        <th><?=$this->lang->line('application_date');?> Pedido</th>
                        <th>Pedido enviado</th>
                        <th>Tipo de Frete</th>
                        <th><?=$this->lang->line('application_status_conciliacao');?></th>
                        <th><?=$this->lang->line('application_conciliacao_mktplace_value');?></th> 
                        <th>Valor Pago Antecipação</th>
                        <th><?=$this->lang->line('application_value_products');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_ship_value');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_rate');?> - Marketplace</th>

                        <th><?=$this->lang->line('application_rate');?> - Seller</th>
                        <th><?=$this->lang->line('application_value');?> - Pago Marketplace</th>
                        <th><?=$this->lang->line('application_value_products');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_ship_value');?> - Marketplace</th>
                        <th>Receita - Marketplace</th>
                        <th>Valor Comissão Produto - ConectaLá</th>
                        <th>Frete Real - ConectaLá</th>
                        <th>Valor Comissão Extra Frete - ConectaLá</th>
                        <th>Valor Comissão Frete - ConectaLá</th>
                        <th>Valor a receber - ConectaLá</th>

                        <th>Valor Desconto Produto - Seller</th>
                        <th>Valor Descontro Frete - Seller</th>
                        <th>Valor a receber - Seller</th>
                        <th>Valor a receber Ajustado - Seller</th>
                        <th>Valor de desconto a ser acrescido</th>

                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido produto</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido frete</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido Total</th>
                        <th>Dif. Valor Recebido de produto</th>
                        <th>Dif. Valor Recebido de frete</th>
                        <th>Dif. Valor Recebido Total</th>
                        
                        <th>Valor Comissão Produto - ConectaLá</th>
                        <th>Valor Comissão Frete - ConectaLá</th>
                        <th>Valor a receber - ConectaLá</th>
                        <th>Valor a receber - Seller</th>
                        
                        <!-- braun -->
                        <th><?=$this->lang->line('application_campaigns_pricetags');?></th>
                        <th><?=$this->lang->line('application_campaigns_campaigns');?></th>
                        <th><?=$this->lang->line('application_campaigns_marketplace');?></th>
                        <th><?=$this->lang->line('application_campaigns_seller');?></th>
                        <th><?=$this->lang->line('application_campaigns_promotions');?></th>
                        <th><?=$this->lang->line('application_campaigns_comission_reduction');?></th>
                        <th><?=$this->lang->line('application_campaigns_rebate');?></th>
                        <th><?=$this->lang->line('application_campaigns_refund');?></th>
                        
                        <?php
                        if ($negociacao_marketplace_campanha == "1")
                        {
                        ?>
                            <th><?=$this->lang->line('conciliation_sc_gridok_comissionreduxchannel');?></th>
                            <th><?=$this->lang->line('conciliation_sc_gridok_rebatechannel');?></th>
                            <!-- <th><?=$this->lang->line('conciliation_sc_gridok_channelrefund');?></th> -->
                        <?php 
                        }
                        ?>

                        <?php if($fin_192_novos_calculos == "1"){ ?>    
                          <th>Comissão MarketPlace</th>
                          <th>Valor Repasse MarketPlace</th>
                          <th>Comissão Negociada Seller</th>
                          <th>Comissão Conecta Lá</th>
                          <th>Frete Conecta Lá</th>
                          <th>Retenção conecta</th>
                          <th>Recebimento Seller</th>
                          <th>Valor Recebido MarketPlace | Extrato</th>
                          <th>Check Valor</th>
                          <th>Status</th>
                        <?php } ?> 


                        <th><?=$this->lang->line('application_extract_obs');?></th>
                        <th>Chamado Marketplace</th>
                        <th>Chamado Agidesk</th>
                        <th>Responsável Conciliação</th>

                        <!-- braun -->
                        <?php
                        if ($canceled_orders_data_conciliation == "1")
                        {
                        ?>
                            <th><?=$this->lang->line('conciliation_grid_cancel_responsible');?></th>
                            <th><?=$this->lang->line('conciliation_grid_cancel_reason');?></th>
                            <th><?=$this->lang->line('conciliation_grid_cancel_penalty');?></th>
                        <?php 
                        }
                        ?>

                        <th><?=$this->lang->line('application_action');?></th>
                      </tr>
                      </thead>
        
                    </table>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->
                
                <div class="box" id="DivEst" style="display: none">  
                	<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_conciliacao_orders_estorno');?></h3> <button type="button" class="btn btn-default" onclick="" data-toggle="modal" data-target="#modalmanageTableOrdersEstorno"><i class="fa fa-question-circle"></i></button>
                        <?php if(in_array('createBilletConcil', $user_permission)|| in_array('updateBilletConcil', $user_permission)): ?>
                            <button type="button" class="btn btn-success" id="btnMoverEstorno" name="btnMoverEstorno">Mover Pedidos para Ok</button>
                        <?php endif; ?>

                    </div>
                  <div class="box-body">
                  	
                    <table id="manageTableOrdersEstorno" class="table table-bordered table-striped">
                      <thead>
                      <tr>
                            <th colspan="26" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            <th colspan="10" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            <th colspan="<?= $colspan1 ?>" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Informações</font></th>
                      </tr>  
                      <tr>
                      		<th colspan="12" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            <th colspan="4" bgcolor="#2626C9" class="text-center"><font color="#FFFFFF">Marketplace</font></th>
                            <th colspan="5" bgcolor="#16F616" class="text-center"><font color="#FFFFFF">Conecta Lá</font></th>
                            <th colspan="5" bgcolor="#E1AA98" class="text-center"><font color="#FFFFFF">Seller</font></th>

                            <th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            <th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Expectativa x Recebido</font></th>
                            <th colspan="4" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores a pagar</font></th>

                            <!-- braun -->
                            <th colspan="<?=$colspan;?>" bgcolor="purple" class="text-center"><font color="#FFFFFF">Influência de Campanhas / Ofertas / Promoções</font></th>
                            <?= $news_columns_fin_192 ?>
                            <th colspan="<?=$colspan2;?>" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Observação/Chamado/Responsável/Ação</font></th>
                      </tr>
                      <tr>
                      <th><?=$this->lang->line('application_purchase_id');?></th>
                        <th><?=$this->lang->line('application_store');?></th>
                        <th><?=$this->lang->line('application_date');?> Pedido</th>
                        <th>Pedido enviado</th>
                        <th>Tipo de Frete</th>
                        <th><?=$this->lang->line('application_status_conciliacao');?></th>
                        <th><?=$this->lang->line('application_conciliacao_mktplace_value');?></th> 
                        <th>Valor Pago Antecipação</th>
                        <th><?=$this->lang->line('application_value_products');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_ship_value');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_rate');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_rate');?> - Seller</th>
                        <th><?=$this->lang->line('application_value');?> - Pago Marketplace</th>
                        <th><?=$this->lang->line('application_value_products');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_ship_value');?> - Marketplace</th>
                        <th>Receita - Marketplace</th>
                        <th>Valor Comissão Produto - ConectaLá</th>
                        <th>Frete Real - ConectaLá</th>
                        <th>Valor Comissão Extra Frete - ConectaLá</th>
                        <th>Valor Comissão Frete - ConectaLá</th>
                        <th>Valor a receber - ConectaLá</th>
                        <th>Valor Desconto Produto - Seller</th>
                        <th>Valor Descontro Frete - Seller</th>
                        <th>Valor a receber - Seller</th>
                        <th>Valor a receber Ajustado - Seller</th>
                        <th>Valor de desconto a ser acrescido</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido produto</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido frete</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido Total</th>
                        <th>Dif. Valor Recebido de produto</th>
                        <th>Dif. Valor Recebido de frete</th>
                        <th>Dif. Valor Recebido Total</th>
                        
                        <th>Valor Comissão Produto - ConectaLá</th>
                        <th>Valor Comissão Frete - ConectaLá</th>
                        <th>Valor a receber - ConectaLá</th>
                        <th>Valor a receber - Seller</th>

                        <!-- braun -->
                        <th><?=$this->lang->line('application_campaigns_pricetags');?></th>
                        <th><?=$this->lang->line('application_campaigns_campaigns');?></th>
                        <th><?=$this->lang->line('application_campaigns_marketplace');?></th>
                        <th><?=$this->lang->line('application_campaigns_seller');?></th>
                        <th><?=$this->lang->line('application_campaigns_promotions');?></th>
                        <th><?=$this->lang->line('application_campaigns_comission_reduction');?></th>
                        <th><?=$this->lang->line('application_campaigns_rebate');?></th>
                        <th><?=$this->lang->line('application_campaigns_refund');?></th>    
                                                
                        <?php
                        if ($negociacao_marketplace_campanha == "1")
                        {
                        ?>
                            <th><?=$this->lang->line('conciliation_sc_gridok_comissionreduxchannel');?></th>
                            <th><?=$this->lang->line('conciliation_sc_gridok_rebatechannel');?></th>
                            <!-- <th><?=$this->lang->line('conciliation_sc_gridok_channelrefund');?></th> -->
                        <?php 
                        }
                        ?>

                        <?php if($fin_192_novos_calculos == "1"){ ?>    
                          <th>Comissão MarketPlace</th>
                          <th>Valor Repasse MarketPlace</th>
                          <th>Comissão Negociada Seller</th>
                          <th>Comissão Conecta Lá</th>
                          <th>Frete Conecta Lá</th>
                          <th>Retenção conecta</th>
                          <th>Recebimento Seller</th>
                          <th>Valor Recebido MarketPlace | Extrato</th>
                          <th>Check Valor</th>
                          <th>Status</th>
                        <?php } ?> 
                        
                        <th><?=$this->lang->line('application_extract_obs');?></th>
                        <th>Chamado Marketplace</th>
                        <th>Chamado Agidesk</th>
                        <th>Responsável Conciliação</th>

                        <!-- braun -->
                        <?php
                        if ($canceled_orders_data_conciliation == "1")
                        {
                        ?>
                            <th><?=$this->lang->line('conciliation_grid_cancel_responsible');?></th>
                            <th><?=$this->lang->line('conciliation_grid_cancel_reason');?></th>
                            <th><?=$this->lang->line('conciliation_grid_cancel_penalty');?></th>
                        <?php 
                        }
                        ?>

                        <th><?=$this->lang->line('application_action');?></th>
                      </tr>
                      </thead>
        
                    </table>
                  </div>
                  <!-- /.box-body -->
                </div>
                
                <div class="box" id="DivOther" style="display: none">   
                	<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_conciliacao_orders_others_values');?></h3> <button type="button" class="btn btn-default" onclick="" data-toggle="modal" data-target="#modalmanageTableOthersValues"><i class="fa fa-question-circle"></i></button>
                    </div>
                  <div class="box-body">
                  	
                    <table id="manageTableOthersValues" class="table table-bordered table-striped">
                    <thead>
                      <tr>
                            <th colspan="26" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            <th colspan="10" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            <th colspan="<?= $colspan1 ?>" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Informações</font></th>
                      </tr>  
                      <tr>
                      		<th colspan="12" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            <th colspan="4" bgcolor="#2626C9" class="text-center"><font color="#FFFFFF">Marketplace</font></th>
                            <th colspan="5" bgcolor="#16F616" class="text-center"><font color="#FFFFFF">Conecta Lá</font></th>
                            <th colspan="5" bgcolor="#E1AA98" class="text-center"><font color="#FFFFFF">Seller</font></th>

                            <th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            <th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Expectativa x Recebido</font></th>
                            <th colspan="4" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores a pagar</font></th>
                            
                            <!-- braun -->
                            <th colspan="<?=$colspan;?>" bgcolor="purple" class="text-center"><font color="#FFFFFF">Influência de Campanhas / Ofertas / Promoções</font></th>
                            <?= $news_columns_fin_192 ?>
                            <th colspan="<?=$colspan2;?>" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Observação/Chamado/Responsável/Ação</font></th>
                      </tr>
                      <tr>
                        <th><?=$this->lang->line('application_purchase_id');?></th>
                        <th><?=$this->lang->line('application_store');?></th>
                        <th><?=$this->lang->line('application_date');?> Pedido</th>
                        <th>Pedido enviado</th>
                        <th>Tipo de Frete</th>
                        <th><?=$this->lang->line('application_status_conciliacao');?></th>
                        <th><?=$this->lang->line('application_conciliacao_mktplace_value');?></th>
                        <th>Valor Pago Antecipação</th>
                        <th><?=$this->lang->line('application_value_products');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_ship_value');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_rate');?> - Marketplace</th>

                        <th><?=$this->lang->line('application_rate');?> - Seller</th>
                        <th><?=$this->lang->line('application_value');?> - Pago Marketplace</th>
                        <th><?=$this->lang->line('application_value_products');?> - Marketplace</th>
                        <th><?=$this->lang->line('application_ship_value');?> - Marketplace</th>
                        <th>Receita - Marketplace</th>
                        <th>Valor Comissão Produto - ConectaLá</th>
                        <th>Frete Real - ConectaLá</th>
                        <th>Valor Comissão Extra Frete - ConectaLá</th>
                        <th>Valor Comissão Frete - ConectaLá</th>
                        <th>Valor a receber - ConectaLá</th>

                        <th>Valor Desconto Produto - Seller</th>
                        <th>Valor Descontro Frete - Seller</th>
                        <th>Valor a receber - Seller</th>
                        <th>Valor a receber Ajustado - Seller</th>
                        <th>Valor de desconto a ser acrescido</th>

                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido produto</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido frete</th>
                        <th><?=$this->lang->line('application_marketplace');?> - Valor recebido Total</th>
                        <th>Dif. Valor Recebido de produto</th>
                        <th>Dif. Valor Recebido de frete</th>
                        <th>Dif. Valor Recebido Total</th>
                        
                        <th>Valor Comissão Produto - ConectaLá</th>
                        <th>Valor Comissão Frete - ConectaLá</th>
                        <th>Valor a receber - ConectaLá</th>
                        <th>Valor a receber - Seller</th>

                        <!-- braun -->
                        <th><?=$this->lang->line('application_campaigns_pricetags');?></th>
                        <th><?=$this->lang->line('application_campaigns_campaigns');?></th>
                        <th><?=$this->lang->line('application_campaigns_marketplace');?></th>
                        <th><?=$this->lang->line('application_campaigns_seller');?></th>
                        <th><?=$this->lang->line('application_campaigns_promotions');?></th>
                        <th><?=$this->lang->line('application_campaigns_comission_reduction');?></th>
                        <th><?=$this->lang->line('application_campaigns_rebate');?></th>
                        <th><?=$this->lang->line('application_campaigns_refund');?></th>   
                                                                        
                        <?php
                        if ($negociacao_marketplace_campanha == "1")
                        {
                        ?>
                            <th><?=$this->lang->line('conciliation_sc_gridok_comissionreduxchannel');?></th>
                            <th><?=$this->lang->line('conciliation_sc_gridok_rebatechannel');?></th>
                            <!-- <th><?=$this->lang->line('conciliation_sc_gridok_channelrefund');?></th> -->
                        <?php 
                        }
                        ?>

                        <?php if($fin_192_novos_calculos == "1"){ ?>    
                          <th>Comissão MarketPlace</th>
                          <th>Valor Repasse MarketPlace</th>
                          <th>Comissão Negociada Seller</th>
                          <th>Comissão Conecta Lá</th>
                          <th>Frete Conecta Lá</th>
                          <th>Retenção conecta</th>
                          <th>Recebimento Seller</th>
                          <th>Valor Recebido MarketPlace | Extrato</th>
                          <th>Check Valor</th>
                          <th>Status</th>
                        <?php } ?> 
                        
                        <th><?=$this->lang->line('application_extract_obs');?></th>
                        <th>Chamado Marketplace</th>
                        <th>Chamado Agidesk</th>
                        <th>Responsável Conciliação</th>

                        <!-- braun -->
                        <?php
                        if ($canceled_orders_data_conciliation == "1")
                        {
                        ?>
                            <th><?=$this->lang->line('conciliation_grid_cancel_responsible');?></th>
                            <th><?=$this->lang->line('conciliation_grid_cancel_reason');?></th>
                            <th><?=$this->lang->line('conciliation_grid_cancel_penalty');?></th>
                        <?php 
                        }
                        ?>

                        <th><?=$this->lang->line('application_action');?></th>
                      </tr>
                      </thead>
        
                    </table>
                  </div>
                  <!-- /.box-body -->
                </div> 
                <!-- /.box -->
              <!-- /.box-body -->
 				<div id="divTeste" name="divTeste"></div>
              <div class="box-footer">
                  <?php if(in_array('createBilletConcil', $user_permission)|| in_array('updateBilletConcil', $user_permission)): ?>
                      <button type="button" id="btnSave" name="btnSave" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                  <?php endif; ?>

                <a href="<?php echo base_url('billet/list') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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
  
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_extract_obs')?> - Pedido</h4>
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
              <div id="divChamadoObs"> 
            	<label for="group_name">Número do Chamado Marketplace</label>
            	<input type="text" class="form-control" id="txt_chamado_mktplace" name="txt_chamado_mktplace" placeholder="Número do Chamado Marketplace">
            	
            	<label for="group_name">Número do Chamado Agidesk</label>
            	<input type="text" class="form-control" id="txt_chamado_agidesk" name="txt_chamado_agidesk" placeholder="Número do Chamado Agidesk">
            </div>  
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
<div class="modal fade" tabindex="-1" role="dialog" id="comissaoModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Comissão - Pedido</h4>
      </div>
      <form role="form" action="" method="post" id="formComissao">
        <div class="modal-body">
        	<input type="hidden" class="form-control" id="txt_hdn_pedido_comissao" name="txt_hdn_pedido_comissao" placeholder="id">
          <label for="group_name">Nova Comissão</label>
          <input type="number" class="form-control" id="txt_comissao" name="txt_comissao" placeholder="Comissão">
          
          <label for="group_name">Valor Comissão Produto - ConectaLá</label>
          <input type="number" class="form-control" id="txt_comissao_produto_conectala" name="txt_comissao_produto_conectala" placeholder="Comissão">
          
          <label for="group_name">Valor Comissão Frete - ConectaLá</label>
          <input type="number" class="form-control" id="txt_comissao_frete_conectala" name="txt_comissao_frete_conectala" placeholder="Comissão">
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
          <button type="button" class="btn btn-primary" id="btnSalvarComissao" name="btnSalvarComissao"><?=$this->lang->line('application_confirm')?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modalmanageTableOrdersOk">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_conciliacao_orders_ok')?></h4>
      </div>
        <div class="modal-body">
        	Nesta Grid são exibidos os pedidos que foram conciliados de forma correta com o arquivo do marketplace e as informações de cálculo na base do Conecta Lá.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
        </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
  
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modalmanageTableOrdersDiv">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_conciliacao_orders_div')?></h4>
      </div>
        <div class="modal-body">
        	Nesta Grid são exibidos os pedidos que tiveram divergência com o valor pago pelo marketplace e o valor calculado pelo sistema da Conecta Lá.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
        </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
 
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modalmanageTableOrdersNotFound">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_conciliacao_orders_not_found')?></h4>
      </div>
        <div class="modal-body">
        	Nesta Grid ficam os pedidos não encontrados: <br>- Que estão no arquivo mas não temos na nossa base;<br>- Que estão no ciclo de pagamento selecionado mas não estão no arquivo enviado.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
        </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modalmanageTableOrdersEstorno">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_conciliacao_orders_estorno')?></h4>
      </div>
        <div class="modal-body">
        	Nesta Grid ficam os pedidos que recebemos no arquivo com a sinalização de Estorno.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
        </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modalmanageTableOthersValues">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_conciliacao_orders_others_values')?></h4>
      </div>
        <div class="modal-body">
        	Nesta Grid ficam as linhas do arquivo contendo outros valores não associados a pedidos.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
        </div>
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
// var manageTableResult;
var manageTableBillet;

var manageTableOrdersOk;
var manageTableOrdersDiv;
var manageTableOrdersNotFound;
var manageTableOrdersEstorno;
var manageTableOthersValues;
var manageTableTotais;

var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

    
    $("#txt_ano_mes").datepicker( 
    {
        format: "mm-yyyy",
        startView: "months", 
        minViewMode: "months"
    });

	$("#divChamadoObs").hide();

    $("#slc_obs_fixo").change(function(){
		if ( $("#slc_obs_fixo").val() == "" || $("#slc_obs_fixo").val() == "3" ){
			$("#divChamadoObs").hide();
		}else{
			$("#divChamadoObs").show();
		}
	});
	
	$("#slc_mktplace").val("<?php if($dadosBanco){ echo $dadosBanco['id_mkt']; }?>");
	$("#slc_ciclo").val("<?php if($dadosBanco){ echo $dadosBanco['id_ciclo']; }?>");
	// $("#slc_ano_mes").val("<?php if($dadosBanco){ echo $dadosBanco['ano_mes']; }?>"); 
    $("#txt_ano_mes").val("<?php if($dadosBanco){ echo $dadosBanco['ano_mes']; }?>");   
	$("#txt_carregado").val("<?php if($dadosBanco){ echo $dadosBanco['carregado']; }?>"); 

  if(	$("#txt_carregado").val() == "1"){
    $("#divUpload").show();
  }

	if($("#txt_carregado").val() == "1"){
		$("#divExcel").show();
	} 

	$("#slc_mktplace").change(function() {
    	if($("#slc_mktplace").val() != ""){
    		//$("#slc_mktplace").prop('disabled', true);
    		$("#divUpload").show();
    	}
  	});

	 var filtro = "/"+$("#slc_mktplace").val()+"/"+$("#txt_dt_inicio").val()+"/"+$("#txt_dt_fim").val()+"/"+$("#hdn_id_orders").val();
	 var url = base_url + 'billet/fetchOrdersListOrdersGrid';

	 
	  /*
    $("#mainGroupNav").addClass('active');
    $("#addGroupNav").addClass('active');
*/
    $("#paraMktPlaceNav").addClass('active');
	  $("#conciliacaoNav").addClass('active');

	manageTableResult = $('#manageTableResult').DataTable({
		"scrollX": true,
	    'ajax': url,
	    'order': []
	  });

	manageTableOrdersOk = $('#manageTableOrdersOk').DataTable({
		"scrollX": true,
	    'ajax': base_url + 'billet/fetchOrdersListOrdersGrid/ok/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
	    'order': []
	  });

	manageTableOrdersDiv = $('#manageTableOrdersDiv').DataTable({
		"scrollX": true,
	    'ajax': base_url + 'billet/fetchOrdersListOrdersGrid/div/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
	    'order': []
	  });

	manageTableOrdersNotFound = $('#manageTableOrdersNotFound').DataTable({
		"scrollX": true,
		fixedHeader: true,
	    'ajax': base_url + 'billet/fetchOrdersListOrdersGrid/nfound/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
	    'order': []
	  });

	manageTableOrdersEstorno = $('#manageTableOrdersEstorno').DataTable({
		"scrollX": true,
	    'ajax': base_url + 'billet/fetchOrdersListOrdersGrid/estorno/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
	    'order': []
	  });

	  manageTableOthersValues = $('#manageTableOthersValues').DataTable({
		  "scrollX": true,
		    'ajax': base_url + 'billet/fetchOrdersListOrdersGrid/outros/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
		    'order': []
		  });

	  manageTableTotais = $('#manageTableTotais').DataTable({
			// "scrollX": true,
            // 'bPaginate': false,
            'paging': false,            
            'scrollX': false,
		    'ajax': base_url + 'billet/fetchOrdersListTotaisGridsConciliacao/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
		    'order': []
		  });

	  
	$("#btnSave").click( function(){
		$("#btnSave").prop('disabled', true);

		if( $("#slc_mktplace").val() 	== "" ||
			// $("#slc_ano_mes").val() 	== "" ||
            $("#txt_ano_mes").val() == "" ||
			$("#slc_ciclo").val() 		== "" ){
			alert("Todos os campos são de preenchimento obrigatório");
			$("#btnSave").prop('disabled', false);
			return false;
		}	

		if ( $("#txt_carregado").val() == "0"){
			alert("É necessário subir ao menos um arquivo para realizar a conciliação");
			$("#btnSave").prop('disabled', false);
			return false;
		}
		

		
		var pageURL = base_url.concat("billet/cadastrarconciliacao");
		$.post( pageURL, $("#frmCadastrar").serialize(), function( data ) {
			$("#divTeste").html(data);
			var retorno = data.split(";");
			if(retorno[0] == "0"){
				alert(retorno[1]);
				window.location.assign(base_url.concat("billet/list"));
			}else{
				alert(retorno[1]);
				$("#btnSave").prop('disabled', false);
			}
			
		});

	});

	var uploadUrl = base_url.concat("billet/uploadArquivo");
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
                id : $("#slc_mktplace").val(),
                lote : $("#hdnLote").val()
            };
            return data;
        },
	    showClose: false,
	    showCaption: false,
	    browseLabel: '',
	    removeLabel: '',
	    browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
	    removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
	    removeTitle: 'Cancel or reset changes',
	    elErrorContainer: '#kv-avatar-errors-1',
	    msgErrorClass: 'alert alert-block alert-danger',
	    layoutTemplates: {main2: '{preview} {remove} {browse}'},
	    allowedFileExtensions: ["xls","xlsx","csv", "txt"]
		}).on('fileuploaderror', function(event, data, msg) {
			alert("Erro ao fazer upload do arquivo, por favor tente novamente.".msg);
		}).on('fileuploaded', function(event, preview, config, tags, extraData) {

			$("#hdnExtensao").val(preview.response.extensao);
			
			var pageURL = base_url.concat("billet/learquivoconciliacao");
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
			    }
				
				$("#txt_carregado").val("1");

				//Mostra o excel para exportar
				$("#divExcel").show();

    			//Carrega as tabelas de resultado
    			$('#manageTableOrdersOk').DataTable().destroy();
    			manageTableOrdersOk = $('#manageTableOrdersOk').DataTable({
    				"scrollX": true,
    			    'ajax': base_url + 'billet/fetchOrdersListOrdersGrid/ok/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
    			    'order': []
    			  });
    			
    			$('#manageTableOrdersDiv').DataTable().destroy();
    			manageTableOrdersDiv = $('#manageTableOrdersDiv').DataTable({
    				"scrollX": true,
    			    'ajax': base_url + 'billet/fetchOrdersListOrdersGrid/div/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
    			    'order': []
    			  });
    
    			$('#manageTableOrdersNotFound').DataTable().destroy();
    			manageTableOrdersNotFound = $('#manageTableOrdersNotFound').DataTable({
    				"scrollX": true,
    				fixedHeader: true,
    			    'ajax': base_url + 'billet/fetchOrdersListOrdersGrid/nfound/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
    			    'order': []
    			  });
    
    			$('#manageTableOrdersEstorno').DataTable().destroy();
    			manageTableOrdersEstorno = $('#manageTableOrdersEstorno').DataTable({
    				"scrollX": true,
    			    'ajax': base_url + 'billet/fetchOrdersListOrdersGrid/estorno/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
    			    'order': []
    			  });

    			$('#manageTableOthersValues').DataTable().destroy();
    			manageTableOthersValues = $('#manageTableOthersValues').DataTable({
    				"scrollX": true,
    			    'ajax': base_url + 'billet/fetchOrdersListOrdersGrid/outros/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
    			    'order': []
    			  });

    			$('#manageTableTotais').DataTable().destroy();
    			manageTableTotais = $('#manageTableTotais').DataTable({
    				// "scrollX": true,
                    'paging': false,            
                    'scrollX': false,
    			    'ajax': base_url + 'billet/fetchOrdersListTotaisGridsConciliacao/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val(),
    			    'order': []
    			  });
    			
    			$("#divTeste").html(data);

    			calculatotalgrid();

				$("#divBtnControll").show();
				$("#DivTotais").show();

			});	

		});


	$("#btnSalvarObs").click(function (){

		if($("#txt_hdn_pedido").val() == "" ||
  			$("#txt_observacao").val() == "" ||
  			$("#slc_obs_fixo").val() == ""){
			alert("Preencha todos os campos da Observação antes de salvar");
			return false;
		}
  		var pageURL = base_url.concat("billet/salvarobs");
		var form = $("#formObservacao").serialize()+"&hdnLote="+$("#hdnLote").val();

		$.post( pageURL, form, function( data ) {
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
	        
  			$("#txt_hdn_pedido").val("");
  			$("#txt_observacao").val("");
  			$("#slc_obs_fixo").val("");
  			$("#removeModal").modal('hide');

  			$('#manageTableOrdersOk').DataTable().ajax.reload();
  			$('#manageTableOrdersDiv').DataTable().ajax.reload();
  			$('#manageTableOrdersNotFound').DataTable().ajax.reload();
  			$('#manageTableOrdersEstorno').DataTable().ajax.reload();
  			$('#manageTableOthersValues').DataTable().ajax.reload();
  			$('#manageTableTotais').DataTable().ajax.reload();
  			

    	}
  		 
  	  });
    });	


	$("#btnSalvarComissao").click(function (){ 
		
		if( $("#txt_comissao").val() == "" 					 ||
		    $("#txt_comissao_produto_conectala").val() == "" ||
			$("#txt_comissao_frete_conectala").val() == "" 	 ||
  			$("#txt_hdn_pedido_comissao").val() == "" ){
			alert("Preencha todos os campos da Comissão antes de salvar");
			return false;
		}
  		var pageURL = base_url.concat("billet/salvarcomissao");
		var form = $("#formComissao").serialize()+"&hdnLote="+$("#hdnLote").val()+"&slc_mktplace="+$("#slc_mktplace").val();

		$.post( pageURL, form, function( data ) {
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
	        
  			$("#txt_comissao").val("");
  			$("#txt_hdn_pedido_comissao").val("");
  			$("#comissaoModal").modal('hide');

  			$('#manageTableOrdersOk').DataTable().ajax.reload();
  			$('#manageTableOrdersDiv').DataTable().ajax.reload();
  			$('#manageTableOrdersNotFound').DataTable().ajax.reload();
  			$('#manageTableOrdersEstorno').DataTable().ajax.reload();
  			$('#manageTableOthersValues').DataTable().ajax.reload();
  			$('#manageTableTotais').DataTable().ajax.reload();

    	}
  		 
  	  });
    });	

	$("#btnDivOk").click(function(){
		$("#hdnExcel").val("ok");
		$("#DivOk").show(); 
		$("#DivDiv").hide();
		$("#DivNfound").hide(); 
		$("#DivEst").hide();
		$("#DivOther").hide();
	});

	$("#btnDivDiv").click(function(){
		$("#hdnExcel").val("div");
		$("#DivOk").hide(); 
		$("#DivDiv").show();
		$("#DivNfound").hide(); 
		$("#DivEst").hide();
		$("#DivOther").hide();
	});

	$("#btnDivNfound").click(function(){
		$("#hdnExcel").val("nfound");
		$("#DivOk").hide(); 
		$("#DivDiv").hide();
		$("#DivNfound").show(); 
		$("#DivEst").hide();
		$("#DivOther").hide();
	});

	$("#btnDivEst").click(function(){
		$("#hdnExcel").val("estorno");
		$("#DivOk").hide(); 
		$("#DivDiv").hide();
		$("#DivNfound").hide(); 
		$("#DivEst").show();
		$("#DivOther").hide();
	});

	$("#btnDivOther").click(function(){
		$("#hdnExcel").val("outros");
		$("#DivOk").hide(); 
		$("#DivDiv").hide();
		$("#DivNfound").hide(); 
		$("#DivEst").hide();
		$("#DivOther").show();
	});


	if($("#txt_carregado").val() == "1"){
		calculatotalgrid();
		$("#divBtnControll").show();
		$("#DivTotais").show();
	}

	$("#btnExcel").click(function(){

		if($("#hdnExcel").val() == ""){
			alert("Selecione uma grid antes de exportar");
			return false;
		}
		
		var filtroexcel = $("#hdnExcel").val() + "/" + $("#hdnLote").val() + "/" + $("#slc_mktplace").val()
		var saida = 'billet/exportaconciliacao/' + filtroexcel;
		window.open(base_url.concat(saida),'_blank');
	});
  
  $("#btnModelo").click(function(){
    var saida = 'assets/files/modelo_ajuste_conciliacao.xlsx';
    window.open(base_url.concat(saida),'_blank');
  });

  $("#btnAprovarOk").click(function(){
    if( confirm("Deseja marcar como tratados os pedidos?") ){
      var pageURL = base_url.concat("billet/tratalinhapedidoconciliacaolote");
			var form = $("#formObservacao").serialize();
			
			$.post( pageURL, {lote : $("#hdnLote").val(), mktplace : $("#slc_mktplace").val()}, function( data ) {
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
			        
		  			$('#manageTableOrdersOk').DataTable().ajax.reload();
		  			$('#manageTableOrdersDiv').DataTable().ajax.reload();
		  			$('#manageTableOrdersNotFound').DataTable().ajax.reload();
		  			$('#manageTableOrdersEstorno').DataTable().ajax.reload();
		  			$('#manageTableOthersValues').DataTable().ajax.reload();
		  			$('#manageTableTotais').DataTable().ajax.reload();

		  			calculatotalgrid();

		    	}
			});
    }
  });

  $("#btnMoverEstorno").click(function(){
    if( confirm("Deseja marcar como Ok os pedidos Estornados?") ){
      marcapedidosoklote("Estorno");
    }
  });

  $("#btnMoverNaoEncontrado").click(function(){
    if( confirm("Deseja marcar como Ok os pedidos não encontrados?") ){
      marcapedidosoklote("Não encontrado");
    }
  });

  $("#btnMoverDivergente").click(function(){
    if( confirm("Deseja marcar como Ok os pedidos Divergentes?") ){
      marcapedidosoklote("Divergente");
    }
  });
    
});

function marcapedidosoklote(status){
  if(status){
		
    var pageURL = base_url.concat("billet/mudastatuspedidogridlote");
    var form = $("#formObservacao").serialize();
    
    $.post( pageURL, {status: status, lote : $("#hdnLote").val(), mktplace : $("#slc_mktplace").val()}, function( data ) {
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
            
          $('#manageTableOrdersOk').DataTable().ajax.reload();
          $('#manageTableOrdersDiv').DataTable().ajax.reload();
          $('#manageTableOrdersNotFound').DataTable().ajax.reload();
          $('#manageTableOrdersEstorno').DataTable().ajax.reload();
          $('#manageTableOthersValues').DataTable().ajax.reload();
          $('#manageTableTotais').DataTable().ajax.reload();

          calculatotalgrid();

        }
    });
  }
	
}

function calculatotalgrid(){

	var pageURL2 = base_url + 'billet/contatotallinhasgridconciliacao/' + $("#hdnLote").val() + "/" + $("#slc_mktplace").val();
	//Carrega os contadores
	$.post( pageURL2, $("#frmCadastrar").serialize(), function( data ) {
		var obj = JSON.parse(data);

		$("#numOk").html("<?=$this->lang->line('application_conciliacao_orders_ok');?> ("+obj.ok+")");
		$("#numDiv").html("<?=$this->lang->line('application_conciliacao_orders_div');?> ("+obj.div+")");
		$("#numNfound").html("<?=$this->lang->line('application_conciliacao_orders_not_found');?> ("+obj.nfound+")");
		$("#numEst").html("<?=$this->lang->line('application_conciliacao_orders_estorno');?> ("+obj.est+")");
		$("#numOvalues").html("<?=$this->lang->line('application_conciliacao_orders_others_values');?> ("+obj.outros+")");

	});
	
}

function incluirObservacao(id)
{
  if(id) {
		$("#txt_hdn_pedido").val(id);
	}
}


function mudacomissaoparceiro(id)
{
  if(id) {
		$("#txt_hdn_pedido_comissao").val(id);
	}
}


function marcapedidook(id){
	if(id){
		if( confirm("Deseja marcar como conciliado o pedido "+id+"?") ){

			var pageURL = base_url.concat("billet/mudastatuspedidogrid");
			var form = $("#formObservacao").serialize();
			
			$.post( pageURL, {pedido: id, lote : $("#hdnLote").val(), mktplace : $("#slc_mktplace").val()}, function( data ) {
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
			        
		  			$('#manageTableOrdersOk').DataTable().ajax.reload();
		  			$('#manageTableOrdersDiv').DataTable().ajax.reload();
		  			$('#manageTableOrdersNotFound').DataTable().ajax.reload();
		  			$('#manageTableOrdersEstorno').DataTable().ajax.reload();
		  			$('#manageTableOthersValues').DataTable().ajax.reload();
		  			$('#manageTableTotais').DataTable().ajax.reload();

		  			calculatotalgrid();

		    	}
			});
		}
	}
}

function marcapedidotratado(id){
	if(id){
		if( confirm("Deseja marcar como tratado o pedido "+id+"?") ){

			var pageURL = base_url.concat("billet/tratalinhapedidoconciliacao");
			var form = $("#formObservacao").serialize();
			
			$.post( pageURL, {pedido: id, lote : $("#hdnLote").val(), mktplace : $("#slc_mktplace").val()}, function( data ) {
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
			        
		  			$('#manageTableOrdersOk').DataTable().ajax.reload();
		  			$('#manageTableOrdersDiv').DataTable().ajax.reload();
		  			$('#manageTableOrdersNotFound').DataTable().ajax.reload();
		  			$('#manageTableOrdersEstorno').DataTable().ajax.reload();
		  			$('#manageTableOthersValues').DataTable().ajax.reload();
		  			$('#manageTableTotais').DataTable().ajax.reload();

		  			calculatotalgrid();

		    	}
			});
		}
	}
}


function listarObservacao(id){
	if(id){
		$("#divListObsFunc").html("Carregando...");
		var pageURL = base_url.concat("billet/buscaobservacaopedido");
		 
		$.post( pageURL, {pedido: id, lote : $("#hdnLote").val()}, function( data ) {
		
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


function addbillet(id, func){

}
  
</script>