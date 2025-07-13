<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_extract";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
  <?php if($perfil == 1){?>
  	<div class="row">
      	<div class="col-md-12 col-xs-12">
        	<div class="box">
            	<div class="box-header">
                  <h3 class="box-title"><?=$this->lang->line('application_resume_extract');?> - Valores na conta</h3>
                </div>
          		<div class="box-body">
              		<table class="col-md-12 col-xs-12" id=resumoExtrato">
              			<tr>
              				<td> <h5> <b> Saldo da Conta: </b> </h3> </td>
              				<td> <h5> <b> Saldo disponível para saque: </b> </h3> </td>
              				<td> <h5> <b> Saldo a receber: </b> </h3> </td>
              				<td> <h5> <b> Recebimentos esse mês: </b> </h3> </td>
              				<td> <h5> <b> Recebimentos esse mês anterior: </b> </h3> </td>
              				<td> <h5> <b>  </b> </h3> </td>
              			</tr>
              			<?php if ( $dataws[0] == "0" ){ ?>
              			<tr>
              				<td> <h5> <b> <?php echo $dataws[1]['balance']?> </b> </h3> </td>
              				<td> <h5> <b> <?php echo $dataws[1]['balance_available_for_withdraw']?> </b> </h3> </td>
              				<td> <h5> <b> <?php echo $dataws[1]['receivable_balance']?> </b> </h3> </td>
              				<td> <h5> <b> <?php echo $dataws[1]['volume_this_month']?> </b> </h3> </td>
              				<td> <h5> <b> <?php echo $dataws[1]['volume_last_month']?> </b> </h3> </td>
              				<td> <button type="button" class="btn btn-block btn-success btn-flat" id="btnSacar" data-toggle="modal" data-target="#saqueModal">Solicitar Saque </button> </td>
              			</tr>
              			<?php }else{ ?>
              			<tr>
              				<td> <h5> <b> R$ 0,00 </b> </h3> </td>
              				<td> <h5> <b> R$ 0,00 </b> </h3> </td>
              				<td> <h5> <b> R$ 0,00 </b> </h3> </td>
              				<td> <h5> <b> R$ 0,00 </b> </h3> </td>
              				<td> <h5> <b> R$ 0,00 </b> </h3> </td>
              			</tr>
              			<?php }?>
              		</table>
          		</div>
        	</div>
        </div>
 	</div>
  <?php }?>
  <form id="formFiltro">
      <div class="row">
        	<div class="box">
          		<div class="box-body">
              		<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
                    </div>
                    
                    <div class="form-group col-md-3 col-xs-3">
                      <label for="group_isadmin"><?=$this->lang->line('application_runmarketplaces');?></label>
                      <select class="form-control" id="slc_mktplace" name="slc_mktplace" >
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($mktplaces as $mktPlaces): ?>
                          <option value="<?php echo trim($mktPlaces['apelido']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>
                    
                    <div class="form-group col-md-3 col-xs-3">
                      <label for="group_isadmin"><?=$this->lang->line('application_status');?></label>
                      <select class="form-control" id="slc_status" name="slc_status" >
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($filtrosts as $statu): ?>
                          <option value="<?php echo trim($statu['id']); ?>"><?php echo trim($statu['status']); ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>
                    
    		      	<div class="form-group col-md-3">
                            <label for="cnpj"><?=$this->lang->line('application_start_date');?></label>
                            <input type="date" class="form-control" id="txt_data_inicio" name="txt_data_inicio" required placeholder="<?=$this->lang->line('application_start_date')?>">
                    </div>
                    
                    <div class="form-group col-md-3">
                            <label for="cnpj"><?=$this->lang->line('application_end_date');?></label>
                            <input type="date" class="form-control" id="txt_data_fim" name="txt_data_fim" required placeholder="<?=$this->lang->line('application_end_date')?>">
                    </div>
                    
              		<div class="col-md-2 col-xs-2"><br>
                		<button type="button" id="btnFilter" name="btnFilter" class="btn btn-primary"><?=$this->lang->line('application_filter');?></button>
            		</div>
            	</div>
            </div>
     	</div>
</form> 

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
                  <h3 class="box-title"><?=$this->lang->line('application_extract_orders');?> </h3> - Veja todos os pedidos pagos ou em processo.
                </div>
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_marketplace');?></th>
                <th><?=$this->lang->line('application_purchase_id');?></th> 
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <th><?=$this->lang->line('application_date');?> do Pedido</th>
                <th><?=$this->lang->line('application_purchase_total');?></th>
                <th><?=$this->lang->line('application_ship_value');?></th>
                <th><?=$this->lang->line('application_value_products');?></th>
                <th><?=$this->lang->line('application_extract_conciliado');?></th>
                <th><?=$this->lang->line('application_extract_conciliado');?></th>
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
<div class="modal fade" tabindex="-1" role="dialog" id="saqueModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Saque</h4>
      </div>
      <form role="form" action="" method="post" id="formSaque">
        <div class="modal-body">
        	<label for="group_name">Valor para saque</label>
        	<input type="number" class="form-control" id="txt_valor_saque" name="txt_valor_saque" placeholder="valor para saque">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
          <button type="button" class="btn btn-success" id="btnSacar" name="btnSalvarObs">Solicitar Saque</button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

	var filtros = $("#formFiltro").serialize();
    
	/*$("#btn_novo_billet").click( function(){
    	window.location.assign(base_url.concat("iugu/createbillet"));
    });

	$("#btn_novo_split").click( function(){
    	window.location.assign(base_url.concat("iugu/createsplit"));
    });

	$("#btn_cancelar_boleto").click( function(){
    	window.location.assign(base_url.concat("iugu/cancelbillet"));
    });*/
	
  $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'iugu/extratopedidos?' + filtros,
    'order': []
  });

  $("#btnFilter").click(function(){

	  filtros = $("#formFiltro").serialize();
	   
	  $('#manageTable').DataTable().destroy();
	  manageTable = $('#manageTable').DataTable({
			"scrollX": true,
		    'ajax': base_url + 'iugu/extratopedidos?' + filtros,
		    'order': []
		  });

  });
  

});

</script>
