<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <?php $data['page_now'] ='legal_panel'; $data['pageinfo'] = "application_add"; $this->load->view('templates/content_header',$data); ?>

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

				<input type="hidden" class="form-control" id="hdnChamado" name="hdnChamado" value="">
				<input type="hidden" class="form-control" id="hdnPedido" name="hdnPedido" value="">
				      
              <div class="col-md-6 col-xs-6">
              		<label for="cnpj">Número do Pedido</label>
                   <input type="text" class="form-control" id="txt_numero_chamado" name="txt_numero_chamado" placeholder="" value="">
          		</div>
              <div class="col-md-6 col-xs-6">
                  <label for="cnpj">Número da Notificação</label>
                    <input type="text" class="form-control" id="txt_numero_chamado" name="txt_numero_chamado" placeholder="" value="">
              </div>
              
              <div class="col-md-4 col-xs-4">
              		<label for="cnpj">Saldo a Debitar</label>
                   <input type="number" class="form-control" id="txt_saldo" name="txt_saldo" placeholder="" value="">
          		</div>

              <div class="col-md-4 col-xs-4">
              		<label for="cnpj">Saldo a Quitar</label>
                   <input type="number" class="form-control" id="txt_saldo_quitar" name="txt_saldo_quitar" placeholder="" value="" readonly>
          		</div>

              <div class="col-md-4 col-xs-4">
              		<label for="group_isadmin"><?=$this->lang->line('application_status');?></label>
                  <select class="form-control" id="slc_status" name="slc_status">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($status_billets as $status_billet): ?>
                          <option value="<?php echo trim($status_billet['id']); ?>"><?php echo trim($status_billet['nome']); ?></option>
                        <?php endforeach ?>
                  </select>
          		</div>
          		
          		<div class="col-md-12 col-xs-12">
          			<label for="group_name"><?=$this->lang->line('application_description');?></label>
          			<textarea class="form-control" id="txt_descricao" name="txt_descricao" placeholder="<?=$this->lang->line('application_description');?>"></textarea>
              	</div>
              </div>

              <div class="col-md-12 col-xs-12"> 
                  <div class="box-body" id="divUpload" name="divUpload" style="display:block">
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
                  </div>

              <div class="box-footer">
                <button type="button" id="btnSave" name="btnSave" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <button type="button" id="btnVoltar" name="btnVoltar" class="btn btn-warning"><?=$this->lang->line('application_back');?></button>
              </div>



            </form>
            
            <div id="divTabelaPedidos" name="divTabelaPedidos" style="display:block">
                <div class="box">
                <div class="box-header">
                  <h3 class="box-title">Conciliações já debitadas</h3>
                </div>
                  <div class="box-body">
                  
                  	<table id="tabelaPedidosAdd" name="tabelaPedidosAdd" class="table table-bordered table-striped">
                    <thead>
                      <tr>
                      	<th>Id Conciliação</th>
                        <th>Data Conciliação</th>
                        <th>Ciclo Conciliação</th>
                        <th>Responsável Pela conciliação</th>
                        <th>Valor Debitado</th>
                      </tr>
                    </thead>
        
                  	</table>
                  
                  </div>
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
var manageTable;
var base_url = "<?php echo base_url(); ?>";


$(document).ready(function() {

  $("#paraMktPlaceNav").addClass('active menu-open');
	$("#paineljuridicoNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#tabelaPedidosAdd').DataTable({
    'ajax': base_url + 'billet/fetchlegalpanelsellercenterconciliacao',
    'order': []
  });

  $("#txt_saldo").change(function(){
    $("#txt_saldo_quitar").val($("#txt_saldo").val());
  });

  $("#btnVoltar").click( function(){
    	window.location.assign(base_url.concat("billet/listlegalpanelsellercenter"));
  });

	$("#btnSave").click( function(){
	});

  var uploadUrl = base_url.concat("payment/uploadArquivoNf");
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
                store : $("#slc_store").val(),
                data_ciclo : $("#slc_ciclo_fiscal").val(),
                lote : $("#hdnLote").val()
            };
            return data;
        },
	    showClose: false,
	    showCaption: false,
	    maxFileCount: 5,
	    browseLabel: '',
	    removeLabel: '',
	    browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
	    removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
	    removeTitle: 'Cancel or reset changes',
	    elErrorContainer: '#kv-avatar-errors-1',
	    msgErrorClass: 'alert alert-block alert-danger',
	    layoutTemplates: {main2: '{preview} {remove} {browse}'},
	    allowedFileExtensions: ["xls", "pdf", "xml"]
		}).on('fileuploaderror', function(event, data, msg) {
			alert("Erro ao fazer upload do arquivo, por favor tente novamente.".msg);
		}).on('fileuploaded', function(event, preview, config, tags, extraData) {
			$('#manageTable').DataTable().ajax.reload();
			$("#txt_carregado").val("1");
		});

});
  
</script>