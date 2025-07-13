<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <?php $data['page_now'] ='payment_release_fiscal'; $data['pageinfo'] = "application_add"; $this->load->view('templates/content_header',$data); ?>

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
                  </select>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                  <label for="group_isadmin"><?=$this->lang->line('application_conciliacao_month_year');?></label>
                  <input class="form-control" type="text" id="txt_ano_mes" name = "txt_ano_mes" autocomplete="off" placeholder="<?=$this->lang->line('application_conciliacao_month_year');?>"/>
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
                <div class="col-md-32 col-xs-3" id="divExcel" name="divExcel" style="display:block"><br>
                <?php if($flag_pago == false){ ?>
                  <button type="button" id="btnGerarConciliacao" name="btnGerarConciliacao" class="btn btn-primary">Gerar Arquivo</button>
                <?php } ?>
             		<button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Download Modelo</button>
        	 	    </div>
               <div class="col-md-12 col-xs-12"> 
                  <div class="box-body" id="divUpload" name="divUpload" style="display:none">
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
            </form>
            <div class="box" id="DivTotais" style="display:block">
            <div class="box-header"> <h3 class="box-title">Resumo</h3> </div>
            <div class="box-body pad table-responsive">
                      <table id="manageTableTotais" class="table table-bordered table-striped" style="width: 100%;">
                          <thead>
                          <tr>
                            <th>Id Seller</th>
                            <th>Loja</th>
                            <th>Valor</th>
                          </tr>
                          </thead>
            
                        </table>
                  </div>
                  <!-- /.box-body -->
              </div>
              <form id="formFiltroLiberacaoPagamento" name = "formFiltroLiberacaoPagamento">
                
                <div class="box" id="DivFiltros" style="display: block">
                  <div class="box-header"> 
                      <h3 class="box-title">Filtros</h3> 
                      </div>
                  <div class="box-body">
                  <div class="form-group col-md-3 col-xs-3">
                      <label for="group_isadmin"><?=$this->lang->line('application_purchase_id');?></label>
                      <input class="form-control" type="text" id="txt_numero_pedido" name = "txt_numero_pedido" autocomplete="off" placeholder="<?=$this->lang->line('application_purchase_id');?>"/>
                  </div>
                  <div class="form-group col-md-4 col-xs-4">
                      <label for="group_isadmin"><?=$this->lang->line('application_store');?></label>
                      <select class="form-control" id="slc_loja" name="slc_loja">
                        <option value="">~~SELECT~~</option>
                      </select>
                    </div>
                    <div class="form-group col-md-3 col-xs-3">
                      <label for="group_isadmin"><?=$this->lang->line('application_payment_release_payment_rule');?></label>
                      <select class="form-control" id="slc_status_pedido" name="slc_status_pedido">
                        <option value="">~~SELECT~~</option>
                      </select>
                    </div>
                  <div class="box-footer">
                  <button type="button" id="btnBuscar" name="btnBuscar" class="btn btn-primary">Buscar</button>
                  <button type="button" id="btnExcel2" name="btnExcel2" class="btn btn-success">Exportar Excel</button>
                </div>

              </form>

              </div>  
              </div>

                <div class="box" id="DivOk" style="display: block">
                	<div class="box-header"> 
                  <h3 class="box-title"><?=$this->lang->line('application_payment_release_cycle_orders');?></h3>

                  <h5>* - Percentuais com este sinal ( * ) indicam que o valor de comissão pode ter sido alterado em um ou mais produtos, devido a Campanha de Redução de Comissão</h5>
                  </div>
                  <div class="box-body">
                    <table id="manageTableOrdersOk" class="table table-bordered table-striped">
                      <thead>
                      <tr>
                        <th><?=$this->lang->line('application_purchase_id');?></th>
                        <th><?=$this->lang->line('application_payment_release_payment_rule');?></th>
                        <th><?=$this->lang->line('application_store');?></th>
                        <th><?=$this->lang->line('application_seller_index');?></th>
                        <th>Data Pagamento</th>
                        <th>Forma de Pagamento</th>
                        <th>Valor Pedido</th> 
                        <th>Valor Pago Antecipação</th>
                        <th>Valor Produto</th> 
                        <th>Valor Frete</th>                        
                        <th><?=$this->lang->line('application_rate');?> Comissão</th>
                        <th>Valor Repasse</th>
                          <?php
                          if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                          ?>
                            <th>Parcelas</th>
                          <?php
                          }
                          ?>
                        <th><?=$this->lang->line('application_payment_release_payment_responsible');?></th>
                        

                        <!-- braun -->
                        <th><?=$this->lang->line('conciliation_sc_gridok_pricetags');?></th>
                        <th><?=$this->lang->line('conciliation_sc_gridok_campaigns');?></th>
                        <th><?=$this->lang->line('conciliation_sc_gridok_totalmktplace');?></th>
                        <th><?=$this->lang->line('conciliation_sc_gridok_totalseller');?></th>
                        <th><?=$this->lang->line('conciliation_sc_gridok_promotions');?></th>
                        <th><?=$this->lang->line('conciliation_sc_gridok_comissionredux');?></th>
                        <th><?=$this->lang->line('conciliation_sc_gridok_rebate');?></th>
                        <th><?=$this->lang->line('conciliation_sc_gridok_refund');?></th>


                        <th><?=$this->lang->line('application_extract_obs');?></th>
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
                <button type="button" id="btnSave" name="btnSave" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <a href="<?php echo base_url('billet/listsellercenterfiscal') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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
          <label for="group_name">Novo Valor Comissão</label>
          <input type="number" class="form-control" id="txt_comissao" name="txt_comissao" placeholder="Comissão" step=".01" min="0.00">

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
<div class="modal fade" tabindex="-1" role="dialog" id="observacaoModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_extract_obs')?> - Pedido</h4>
      </div>
      <form role="form" action="" method="post" id="formObservacao">
        <div class="modal-body">
        	<input type="hidden" class="form-control" id="txt_hdn_pedido_obs" name="txt_hdn_pedido_obs" placeholder="observacao">
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

  $("#txt_ano_mes").datepicker( {
    format: "mm-yyyy",
    startView: "months", 
    minViewMode: "months"
  });

  $("#paraMktPlaceNav").addClass('active menu-open');
	$("#paymentReleaseFiscalNav").addClass('active');

  $("#slc_mktplace").val("<?php if($dadosBanco){ echo $dadosBanco['id_mkt']; }?>");
	$("#slc_ciclo").val("<?php if($dadosBanco){ echo $dadosBanco['id_ciclo']; }?>");
	$("#txt_ano_mes").val("<?php if($dadosBanco){ echo $dadosBanco['ano_mes']; }?>");  
	$("#txt_carregado").val("<?php if($dadosBanco){ echo $dadosBanco['carregado']; }?>"); 

  if($("#txt_carregado").val() == "0"){

    $("#divExcel").show();
    $("#divUpload").hide();
    $("#DivOk").hide(); 
    $("#DivTotais").hide();
    $("#DivFiltros").hide();

  }else{

    var pageURL = base_url.concat("billet/getstoresfromconciliacaosellercenterfiscal");
    var lote = $("#hdnLote").val();

    $('#slc_loja').empty().append('<option value="">~~SELECT~~</option>');
    
    $.post( pageURL, {lote: lote} , function( data ) {
      if(data){
        var obj = JSON.parse(data);
        Object.keys(obj).forEach(function(k){
                  $('#slc_loja').append($('<option>').text(obj[k].nome_loja).attr('value', obj[k].id_loja));
        });
      }

      $("#divExcel").show();
      $("#divUpload").show();
      $("#DivOk").show(); 
      $("#DivTotais").show();
      $("#DivFiltros").show();
    });



    var pageURL = base_url.concat("billet/getstatusfromconciliacaosellercenterfiscal");
    $('#slc_status_pedido').empty().append('<option value="">~~SELECT~~</option>');
    
    $.post( pageURL, {lote: lote} , function( data ) {
      
      if(data){
        var obj = JSON.parse(data);
        Object.keys(obj).forEach(function(k){
                  $('#slc_status_pedido').append($('<option>').text(obj[k].status).attr('value', obj[k].id));
        });
      }
    });
    
  }
 

  $("#btnGerarConciliacao").click(function() {
    $("#btnGerarConciliacao").prop('disabled', true);

    $("#messages2").html("");

    if($("#slc_ciclo").val() == "" ||
       $("#txt_ano_mes").val() == "" ||
       $("#slc_mktplace").val() == "" ){
      
        $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                             '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                             '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+'Todos os campos são de preenchimento obrigatório'+
                             '</div>');
                             $("#btnGerarConciliacao").prop('disabled', false);
       
        return false;
    } else {
        $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                             '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                             '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+"<?=$this->lang->line('application_payment_release_payment_please_wait');?>"+
                             '</div>');
        
            var pageURL2 = base_url + 'billet/geraconciliacaosellercenterfiscal/';
            $.post(pageURL2, $("#frmCadastrar").serialize(), function(data) {
                var saida = data.split(";");
                if (saida[0] == "1") {

                    $("#btnGerarConciliacao").prop('disabled', false);   
                    $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                        '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + saida[1] +
                                        '</div>');
                    $('#slc_loja').empty().append('<option value="">~~SELECT~~</option>');
                } else {
                    $("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                        '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + saida[1] +
                                        '</div>');
                    $('#manageTableOrdersOk').DataTable().ajax.reload();
                    $('#manageTableTotais').DataTable().ajax.reload();

                    var pageURL = base_url.concat("billet/getstoresfromconciliacaosellercenterfiscal");
                    var lote = $("#hdnLote").val();

                    $('#slc_loja').empty().append('<option value="">~~SELECT~~</option>');

                    $.post(pageURL, { lote: lote }, function(data) {
                        if (data) {
                            var obj = JSON.parse(data);
                            Object.keys(obj).forEach(function(k) {
                                $('#slc_loja').append($('<option>').text(obj[k].nome_loja).attr('value', obj[k].id_loja));
                            });
                        }
                    });

        var pageURL = base_url.concat("billet/getstatusfromconciliacaosellercenterfiscal");
        $('#slc_status_pedido').empty().append('<option value="">~~SELECT~~</option>');

                    $.post(pageURL, { lote: lote }, function(data) {
                        console.log(data);
                        if (data) {
                            var obj = JSON.parse(data);
                            Object.keys(obj).forEach(function(k) {
                                $('#slc_status_pedido').append($('<option>').text(obj[k].status).attr('value', obj[k].id));
                            });
                        }
                    });

                    $("#divExcel").show();
                    $("#divUpload").show();
                    $("#DivOk").show();
                    $("#DivTotais").show();
                    $("#DivFiltros").show();
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
              $("#btnGerarConciliacao").prop('disabled', false);   
                    $("#messages2").html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                        '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + "Erro ao gerar liberação de pagamento, tente novamente "+
                                        '</div>');
            });
        }
    });

    document.getElementById('formFiltroLiberacaoPagamento').addEventListener('submit', function(event) {
        // Impede o comportamento padrão de submissão do formulário
        event.preventDefault();

        // Simula o clique no botão #btnBuscar
        document.getElementById('btnBuscar').click();
    });

    document.getElementById('formComissao').addEventListener('submit', function(event) {
        // Impede o comportamento padrão de submissão do formulário
        event.preventDefault();

        // Simula o clique no botão #btnBuscar
        document.getElementById('btnSalvarComissao').click();
    });

    $("#btnBuscar").click( function(){

        var filtrosGrid = $("#formFiltroLiberacaoPagamento").serialize();

        $('#manageTableOrdersOk').DataTable().destroy();
        manageTableOrdersOk = $('#manageTableOrdersOk').DataTable({
                                                                    "language": {"url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
                                                                    "processing": true,
                                                                    "serverSide": true,
                                                                    "pageLength": 100,
                                                                    "serverMethod": "post",
                                                                    "scrollX": true,
                                                                    'ajax': base_url + 'billet/getconciliacaosellercentergridfiscal/' + $("#hdnLote").val()+'/?'+ filtrosGrid,
                                                                    'searching': false,
                                                                    "ordering": false,
                                                                    'order': []
        });


       /* $('#manageTableOrdersOk').DataTable().destroy();
        manageTableOrdersOk = $('#manageTableOrdersOk').DataTable({
            "scrollX": true,
            'ajax': base_url + 'billet/getconciliacaosellercentergrid/' + $("#hdnLote").val()+'/?'+ filtrosGrid,
            'order': []
        });*/

    });

  $('#manageTableOrdersOk').DataTable().destroy();
  manageTableOrdersOk = $('#manageTableOrdersOk').DataTable({
                                                                    "language": {"url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
                                                                    "processing": true,
                                                                    "serverSide": true,
                                                                    "pageLength": 100,
                                                                    "serverMethod": "post",
                                                                    "scrollX": true,
                                                                    'ajax': base_url + 'billet/getconciliacaosellercentergridfiscal/' + $("#hdnLote").val()+'/'+$('#slc_loja').val(),
                                                                    'searching': false,
                                                                    "ordering": false,
                                                                    'order': []
        });
  /*manageTableOrdersOk = $('#manageTableOrdersOk').DataTable({
    "scrollX": true,
      'ajax': base_url + 'billet/getconciliacaosellercentergrid/' + $("#hdnLote").val()+'/'+$('#slc_loja').val(),
      'order': []
    });*/
    

  $('#manageTableTotais').DataTable().destroy();
  manageTableTotais = $('#manageTableTotais').DataTable({
    'paging': false,
    "scrollX": false,
      'ajax': base_url + 'billet/getconciliacaosellercentergridresumofiscal/' + $("#hdnLote").val(),
      'order': []
    });


	var uploadUrl = base_url.concat("billet/uploadarquivoconciliasellercenterfiscal");
	
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
			
			var pageURL = base_url.concat("billet/learquivoconciliacaosellercenterfiscal");
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
				
          $('#manageTableOrdersOk').DataTable().ajax.reload();
          $('#manageTableTotais').DataTable().ajax.reload();  
    			

			});	
			
		});

    $("#btnSave").click( function(){
      if( confirm("Deseja salvar a conciliação?") ){
        $("#btnSave").prop('disabled', true);

        if( $("#slc_mktplace").val() 	== "" ||
          $("#txt_ano_mes").val() 	== "" ||
          $("#slc_ciclo").val() 		== "" ){
          alert("Todos os campos são de preenchimento obrigatório");
          $("#btnSave").prop('disabled', false);
          return false;
        }	
        
        var pageURL = base_url.concat("billet/cadastrarconciliacaosellercenterfiscal");
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
            window.location.assign(base_url.concat("billet/listsellercenterfiscal"));
          }
        });
      }
	  });

    $("#btnExcel2").click( function(){
      var filtrosGrid = $("#formFiltroLiberacaoPagamento").serialize();
      var saida = 'billet/exportaconciliacaosellercenterfiscal/' + $("#hdnLote").val()+'/?'+ filtrosGrid;

      window.open(base_url.concat(saida),'_blank');

    });

    $("#btnExcel").click(function(){
      /*var filtroexcel = $("#hdnExcel").val() + "/" + $("#hdnLote").val() + "/" + $("#slc_mktplace").val()
      var saida = 'http://localhost/aplicacao/Arquivo%20Modelo%20Concilia%c3%a7%c3%a3o.xlsx';
      window.open(saida,'_blank');*/
      var saida = 'assets/files/arquivo_modelo_conciliacao.xlsx';
      window.open(base_url.concat(saida),'_blank');
    });


    $("#btnSalvarObs").click(function(){
      if( $("#txt_observacao").val() != "" && $("#txt_hdn_pedido_obs").val() != "" ){
        
        var pageURL = base_url.concat("billet/salvarobssellercenterfiscal");
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
              
              $("#txt_hdn_pedido_obs").val("");
              $("#txt_observacao").val("");
              $("#observacaoModal").modal('hide');
              $('#manageTableOrdersOk').DataTable().ajax.reload();
              $('#manageTableTotais').DataTable().ajax.reload();  

          }
          
        });	


      }else{
        alert("Observação não preenchida.");
        return false;
      }
    });


    $("#btnSalvarComissao").click(function(){
      if( $("#txt_comissao").val() != "" && $("#txt_hdn_pedido_comissao").val() != "" ){
        
        var pageURL = base_url.concat("billet/alteracomissaosellercenterfiscal");
        var form = $("#formComissao").serialize()+"&hdnLote="+$("#hdnLote").val();

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
              
              $("#txt_hdn_pedido_comissao").val("");
              $("#txt_comissao").val("");
              $("#comissaoModal").modal('hide');
              $('#manageTableOrdersOk').DataTable().ajax.reload();
              $('#manageTableTotais').DataTable().ajax.reload();  

          }
          
        });	


      }else{
        alert("Comissão não preenchida.");
        return false;
      }
    });
     
    
  });

function editobservacao(id){

  $("#txt_hdn_pedido_obs").val(id);

}

function editcomissao(id){

  $("#txt_hdn_pedido_comissao").val(id);

}

function listarObservacao(id){
	if(id){
		$("#divListObsFunc").html("Carregando...");
		var pageURL = base_url.concat("billet/buscaobservacaopedidosellercenterfiscal");
		 
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

function incluiremovepedidoconciliacao(id){
  
	if(id){
		var pageURL = base_url.concat("billet/incluiremovepedidoconciliacaofiscal");
    var form = $("#formObservacao").serialize()+"&hdnLote="+$("#hdnLote").val()+"&id="+id;

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
            
            $('#manageTableOrdersOk').DataTable().ajax.reload();
            $('#manageTableTotais').DataTable().ajax.reload();  
          }
		});
	}
}

</script>