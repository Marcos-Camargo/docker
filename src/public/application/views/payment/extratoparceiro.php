<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_extract_screen";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
  
  <form id="formFiltro">
      <div class="row">
        	<div class="box">
          		<div class="box-body">
              		<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
                    </div>
                    
                    <div class="form-group col-md-2 col-xs-2">
                      <label for="group_isadmin"><?=$this->lang->line('application_runmarketplaces');?></label>
                      <select class="form-control" id="slc_mktplace" name="slc_mktplace" >
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($mktplaces as $mktPlaces): ?>
                          <option value="<?php echo trim($mktPlaces['apelido']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>
                    
                    <div class="form-group col-md-2 col-xs-2">
                      <label for="group_isadmin"><?=$this->lang->line('application_status');?></label>
                      <select class="form-control" id="slc_status" name="slc_status" >
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($filtrosts as $statu): ?>
                          <option value="<?php echo trim($statu['id']); ?>"><?php echo trim($statu['status']); ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>

					<div class="form-group col-md-2 col-xs-2">
                      <label for="group_isadmin"><?=$this->lang->line('application_store');?></label>
                      <select class="form-control" id="slc_loja" name="slc_loja" >
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($filtrostore as $loja): ?>
                          <option value="<?php echo trim($loja['id']); ?>"><?php echo trim($loja['name']); ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>

    		      	<div class="form-group col-md-3">
                            <label for="cnpj">Data do Pedido - Início</label>
                            <input type="date" class="form-control" id="txt_data_inicio" name="txt_data_inicio" required placeholder="<?=$this->lang->line('application_start_date')?>">
                    </div>
                    <div class="form-group col-md-3">
                            <label for="cnpj">Data do Pedido - Fim</label>
                            <input type="date" class="form-control" id="txt_data_fim" name="txt_data_fim" required placeholder="<?=$this->lang->line('application_end_date')?>">
                    </div>
              		<div class="col-md-2 col-xs-2"><br>
                		<button type="button" id="btnFilter" name="btnFilter" class="btn btn-primary"><?=$this->lang->line('application_filter');?></button>
            		</div>
            		
            		<div class="col-md-2 col-xs-2"><br>
                		<button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Excel</button>
            		</div>
            	</div>
            </div>
     	</div>
</form> 

<div class="row">
      	<div class="col-md-12 col-xs-12">
        	<div class="box">
            	<div class="box-header">
                  <h3 class="box-title"><?=$this->lang->line('application_resume_extract');?> - Valores na conta</h3>
                </div>
          		<div class="box-body">
              		<table id="manageTableResumo" class="table table-bordered table-striped">
                      <thead>
                      <tr>
                      	<th><?=$this->lang->line('application_marketplace');?></th>
                        <th><?=$this->lang->line('application_date');?> <?=$this->lang->line('application_bank_transfer');?></th>
                        <th><?=$this->lang->line('application_value');?></th>
                      </tr>
                      </thead>
                    </table>
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
                  <h3 class="box-title"><?=$this->lang->line('application_extract_orders');?> </h3> - Veja todos os pedidos pagos ou em processo.
                </div>
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              
              <?php if($gsoma == "1"){ ?>
              	<tr>
                    <th><?=$this->lang->line('application_id');?> - Pedido</th>
                    <th><?=$this->lang->line('application_status');?></th>
    				<th><?=$this->lang->line('application_date');?> Pedido</th>
                    <th><?=$this->lang->line('application_date');?> de Entrega</th>
                    <th><?=$this->lang->line('application_payment_date');?> - Marketplace</th>
                    <th><?=$this->lang->line('application_order_2');?></th>
                    <th>Expectativa Recebimento</th>
                    <th><?=$this->lang->line('application_extract_obs');?></th>
                  </tr>
              <?php }else {?>
                  <tr>
                    <th><?=$this->lang->line('application_id');?> - Pedido</th>
                    <th><?=$this->lang->line('application_status');?></th>
    				<th><?=$this->lang->line('application_date');?> Pedido</th>
                    <th><?=$this->lang->line('application_date');?> de Entrega</th>
                    <th><?=$this->lang->line('application_payment_date');?> - Marketplace</th>
                    <th><?=$this->lang->line('application_payment_date_conecta');?></th>
                    <th><?=$this->lang->line('application_order_2');?></th>
                    <th><?=$this->lang->line('application_date');?> <?=$this->lang->line('application_bank_transfer');?></th>
                    <th>Expectativa Recebimento</th>
                    <th>Valor pago pelo Marketplace</th>
                    <th>Pedido Conciliado</th>
                    <th><?=$this->lang->line('application_extract_obs');?></th>
                  </tr>
              <?php }?>
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
  var manageTableResumo;
  var base_url = "<?php echo base_url(); ?>";

  $(document).ready(function() {

/*     var filtros = $("#formFiltro").serialize(); */

    // $("#mainReceivableNav").addClass('active');
    $("#addReceivableNav").addClass('active');
    /* 
        // initialize the datatable 
        manageTable = $('#manageTable').DataTable({
          'ajax': base_url + 'payment/extratopedidosparceiro?' + filtros,
          "pageLength": 100,
          'order': []
        });

      //initialize the datatable 
        manageTableResumo = $('#manageTableResumo').DataTable({
          'ajax': base_url + 'payment/extratopedidosresumoparceiro?' + filtros,
          "pageLength": 100,
          'order': []
        });

        $("#btnFilter").click(function(){

          filtros = $("#formFiltro").serialize();
          
          $('#manageTable').DataTable().destroy();
          manageTable = $('#manageTable').DataTable({
            "scrollX": true,
            "pageLength": 100,
              'ajax': base_url + 'payment/extratopedidosparceiro?' + filtros,
              'order': []
            });

          $('#manageTableResumo').DataTable().destroy();
          manageTableResumo = $('#manageTableResumo').DataTable({
            "scrollX": true,
            "pageLength": 100,
              'ajax': base_url + 'payment/extratopedidosresumoparceiro?' + filtros,
              'order': []
            });

        });
    */    
function montaDataTable() {
        if ($('#manageTable').length) {
            $('#manageTable').DataTable().destroy();
        }

        var filtros = $("#formFiltro").serialize();
        slc_status = $('#slc_status').val();
        filtros += '&slc_status='+slc_status;
        manageTable = $('#manageTable').DataTable({
            "language": {"url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "pageLength": 100,
            "serverMethod": "post",
            'ajax': base_url + 'payment/extratopedidosparceiro?' + filtros,
            'searching': false,
            'order': []
        });
        if ($('#manageTableResumo').length) {
            $('#manageTableResumo').DataTable().destroy();
        }

        //initialize the datatable
        manageTableResumo = $('#manageTableResumo').DataTable({
            "language": {"url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "lengthMenu": [[3, 10, 25, 50, 100], [3, 10, 25, 50, 100]],
            "pageLength": 100,
            "serverMethod": "post",
            'ajax': base_url + 'payment/extratopedidosresumoparceiro?' + filtros,
            'searching': false,
            'order': []
        });
    }

    montaDataTable()

    $("#btnFilter").click(function () {
        montaDataTable();
    });

    $("#btnExcel").click(function(){
      var filtros = $("#formFiltro").serialize();
      slc_status = $('#slc_status').val();
      filtros += '&slc_status='+slc_status;
      var saida = 'payment/extratopedidosexcelparceiro?' + filtros;
      window.open(base_url.concat(saida),'_blank');
      
    });
    let executeSearch = true;
    $('#search').on('keyup', () => {
        if (executeSearch) {
            setTimeout(() => {
                montaDataTable();
                executeSearch = true;
            }, 500)
        }
        executeSearch = false;
    })


  });

  function listarObservacao(id, lote){
    if(id){
      $("#divListObsFunc").html("Carregando...");
      var pageURL = base_url.concat("payment/buscaobservacaopedido");
      
      $.post( pageURL, {pedido: id, lote : lote}, function( data ) {
      
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
