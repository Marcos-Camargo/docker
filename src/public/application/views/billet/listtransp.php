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
                
                <div class="form-group col-md-4 col-xs-4">
                	<label for="group_isadmin"><?=$this->lang->line('application_ship_company');?></label>
                	<select class="form-control" id="slc_transportadora" name="slc_transportadora" >
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($transportadoras as $transportadora): ?>
                      <option value="<?php echo trim($transportadora['id']); ?>"><?php echo trim($transportadora['ship_company']); ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
                
                <div class="form-group col-md-4 col-xs-4">
                	<label for="group_isadmin"><?=$this->lang->line('application_parameter_providers_ciclos');?></label>
					<select class="form-control" id="slc_ciclo_transp" name="slc_ciclo_transp" >
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($ciclos as $ciclo): ?>
                          <option value="<?php echo trim($ciclo['id']); ?>"><?php echo trim($ciclo['nome_transportadora'])." - Início: " .trim($ciclo['data_inicio'])." Fim: ".trim($ciclo['data_fim'])   ; ?></option>
                        <?php endforeach ?>
                      </select>
                </div>
                <br />
                	<button class="btn btn-primary" id="btn_filtrar_transp" name="btn_filtrar_transp"><?=$this->lang->line('application_search');?></button>
              </div>
              <!-- /.box-body -->
            </div>
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_company');?></th>
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_ship_company');?></th>
                <th><?=$this->lang->line('application_order');?></th>
                <th><?=$this->lang->line('application_status');?></th>
				<th><?=$this->lang->line('application_payment_date_delivered');?></th>
                <th><?=$this->lang->line('application_ship_value');?></th>
                <th><?=$this->lang->line('application_billet_ship_value_new');?></th>
                <th><?=$this->lang->line('application_extract_obs');?></th>
                <th><?=$this->lang->line('application_action');?></th>
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
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
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
          
          <label for="group_name"><?=$this->lang->line('application_ship_value');?></label>
          <input class="form-control" type="number" id="txt_novo_frete" name="txt_novo_frete" placeholder="<?=$this->lang->line('application_extract_obs');?>">
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
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    
    $("#btn_filtrar_transp").click( function(){

    	var novoCaminho = base_url + 'billet/fetchConciliacaoGridDataTransp/'+$("#slc_transportadora").val()+"/"+$("#slc_ciclo_transp").val();

    	$('#manageTable').DataTable().destroy();
    	manageTable = $('#manageTable').DataTable({
		    'ajax': novoCaminho,
		    'order': []
		  });


    });

  $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'billet/fetchConciliacaoGridDataTransp',
    'order': []
  });

  $("#btnSalvarObs").click(function (){

		if($("#txt_hdn_pedido").val() == "" ||
			$("#txt_observacao").val() == "" ||
			$("#slc_obs_fixo").val() == "" ||
			$("#txt_novo_frete").val() == ""){
			alert("Preencha todos os campos da Observação antes de salvar");
			return false;
		}
		var pageURL = base_url.concat("billet/salvarobstranps");
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
			$("#removeModal").modal('hide');

			$("#btn_filtrar_transp").click();

		}
		 
	  });
  });	

});

function incluirObservacao(id)
{
  if(id) {
		$("#txt_hdn_pedido").val(id);
	}
}

</script>