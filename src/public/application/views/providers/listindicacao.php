<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage_providers_indication";  $this->load->view('templates/content_header',$data); ?>

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
        
        <div class="box">
          <div class="box-body">
          	<div class="box-header">
              <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
            </div>
            
            <div class="form-group col-md-4 col-xs-4">
            	<label for="group_isadmin"><?=$this->lang->line('application_providers');?></label>
            	<select class="form-control" id="slc_transportadora" name="slc_transportadora" >
                <option value="">~~SELECT~~</option>
                <?php foreach ($transportadoras as $transportadora): ?>
                  <option value="<?php echo trim($transportadora['id']); ?>"><?php echo trim($transportadora['name']); ?></option>
                <?php endforeach ?>
              </select>
            </div>
            
            <div class="form-group col-md-4 col-xs-4">
            	<label for="group_isadmin"><?=$this->lang->line('application_store');?></label>
				<select class="form-control" id="slc_loja" name="slc_loja" >
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($stores as $ciclo): ?>
                      <option value="<?php echo trim($ciclo['id']); ?>"><?php echo trim($ciclo['name']); ?></option>
                    <?php endforeach ?>
                  </select>
            </div>
            <br />
            	<button class="btn btn-primary" id="btn_filtrar_transp" name="btn_filtrar_transp"><?=$this->lang->line('application_search');?></button>
            	<button class="btn btn-primary" id="btn_nova_indicacao" name="btn_nova_indicacao" data-toggle="modal" data-target="#newModal"><?=$this->lang->line('application_manage_providers_new_indication');?></button>
          </div>
          <!-- /.box-body -->
        </div>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_ship_company');?></th>
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_value');?></th>
                <th><?=$this->lang->line('application_active');?></th>
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
<div class="modal fade" tabindex="-1" role="dialog" id="newModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_extract_obs')?> - Transportadora</h4>
      </div>
      <form role="form" action="" method="post" id="formObservacao">
        <div class="modal-body">
        	<input type="hidden" class="form-control" id="txt_hdn_id" name="txt_hdn_id" placeholder="observacao">
        	
        	<label for="group_isadmin"><?=$this->lang->line('application_providers');?></label>
              <select class="form-control" id="slc_transportadora_new" name="slc_transportadora_new">
                <option value="">~~SELECT~~</option>
                <<?php foreach ($transportadoras as $transportadora): ?>
                      <option value="<?php echo trim($transportadora['id']); ?>"><?php echo trim($transportadora['name']); ?></option>
                    <?php endforeach ?>
              </select>
              
              <label for="group_isadmin"><?=$this->lang->line('application_store');?></label>
              <select class="form-control" id="slc_loja_new" name="slc_loja_new">
                <option value="">~~SELECT~~</option>
                <?php foreach ($stores as $ciclo): ?>
                  <option value="<?php echo trim($ciclo['id']); ?>"><?php echo trim($ciclo['name']); ?></option>
                <?php endforeach ?>
              </select>

          <label for="group_name"><?=$this->lang->line('application_ship_value');?></label>
          <input class="form-control" type="number" id="txt_desconto" name="txt_desconto">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
          <button type="button" class="btn btn-primary" id="btnSalvarDesconto" name="btnSalvarDesconto"><?=$this->lang->line('application_confirm')?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->



<?php if(in_array('deleteParamktplace', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_manage_providers_indication_remove')?></h4>
      </div>

      <form role="form" action="" method="post" id="removeForm">
      <input type="hidden" name="txt_hdn_id_remove" id="txt_hdn_id_remove" />
        <div class="modal-body">
          <p><?=$this->lang->line('messages_remove_provders_indication')?>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
          <button type="button" class="btn btn-primary" id="btnRemover" name="btnRemover"><?=$this->lang->line('application_confirm')?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    
    $("#btn_filtrar_transp").click( function(){

    	var novoCaminho = base_url + 'providers/fetchIndicacaoGridDataTransp/'+$("#slc_transportadora").val()+"/"+$("#slc_loja").val();

    	$('#manageTable').DataTable().destroy();
    	manageTable = $('#manageTable').DataTable({
		    'ajax': novoCaminho,
		    'order': []
		  });


    });

  // $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'providers/fetchIndicacaoGridDataTransp',
    'order': []
  });

  $("#btnSalvarDesconto").click(function (){
	  
		if(	$("#slc_transportadora_new").val() == "" ||
			$("#slc_loja_new").val() == "" ||
			$("#txt_desconto").val() == ""){
			alert("Preencha todos os campos da Observação antes de salvar");
			return false;
		}
		var pageURL = base_url.concat("providers/salvarindicacaotransp");
		
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

			$("#txt_hdn_id").val("");
			$("#slc_transportadora_new").val("");
			$("#slc_loja_new").val("");
			$("#txt_desconto").val("");
			$("#removeModal").modal('hide');

			$("#btn_filtrar_transp").click();

		}
		 
	  });
  });	

  $("#btnRemover").click(function (){
	  var pageURL = base_url.concat("providers/removeindicacao");
	  $.post( pageURL, $("#removeForm").serialize() , function( data ) {
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

			  $("#removeModal").modal('hide');
			  $('#manageTable').DataTable().ajax.reload();
		  }
		 
	  });
  });

});

function excluirIndicacao(id)
{
  if(id) {
		$("#txt_hdn_id_remove").val(id);
	}
}

function editarIndicacao(id)
{
  if(id) {
	  window.location.assign(base_url.concat("providers/editindicacao/"+id));
	}
}

</script>
