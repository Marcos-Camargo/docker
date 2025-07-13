<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_parameter_mktplace_ciclos";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div class="messages"></div>

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
        
        <?php if(in_array('createParamktplace', $user_permission)): ?>
          <button class="btn btn-primary" data-toggle="modal" data-target="#addModal"><?=$this->lang->line('application_add_ciclos');?></button>
          <!-- <button class="btn btn-primary" data-toggle="modal" data-target="#editMktPlaceModal"><?=$this->lang->line('application_parameter_mktplace_ciclo_edit');?></button> -->
          <br /> <br />
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_store');?></th> 
                <th><?=$this->lang->line('application_payment_start_day');?></th>
                <th><?=$this->lang->line('application_payment_end_day');?></th>
                <th><?=$this->lang->line('application_payment_date');?></th>
                <th><?=$this->lang->line('application_payment_date_conecta');?></th>
                <th><?=$this->lang->line('application_paramktplace_cut_date');?></th>
                <?php if(in_array('updateParamktplaceCiclo', $user_permission) || in_array('deleteParamktplaceCiclo', $user_permission)): ?>
                  <th><?=$this->lang->line('application_action');?></th>
                <?php endif; ?>
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

<?php if(in_array('createParamktplaceCiclo', $user_permission)): ?>
<!-- create brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="addModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_add_category');?></h4>
      </div>

      <form role="form" action="" method="post" id="createForm" name="createForm">

        <div class="modal-body">

          <div class="form-group">
            <label for="cmb_mktplace"><?=$this->lang->line('application_store');?></label>
            <select class="form-control" id="cmb_mktplace" name="cmb_mktplace">
              <option value=""><?=$this->lang->line('application_select');?></option>
                <?php foreach ($mktPlaces as $mktPlaces): ?>
                  <option value="<?php echo trim($mktPlaces['id']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                <?php endforeach ?>
            </select>
          </div>

          <div class="form-group">
            <label for="cmb_data_corte"><?=$this->lang->line('application_paramktplace_cut_date');?></label>
            <select class="form-control" id="cmb_data_corte" name="cmb_data_corte">
              <option value="Data Entrega">Data Entrega</option>
              <option value="Data Envio">Data Envio</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_payment_start_day');?></label>
            <input type="number" class="form-control" id="txt_data_inicio" name="txt_data_inicio" placeholder="<?=$this->lang->line('application_start_date')?>" min="1" max="31" step='1'>
          </div>
          
          <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_payment_end_day');?></label>
            <input type="number" class="form-control" id="txt_data_fim" name="txt_data_fim" placeholder="<?=$this->lang->line('application_start_date')?>" min="1" max="31" step='1'>
          </div>
          
          <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_payment_date');?></label>
            <input type="number" class="form-control" id="txt_data_pagamento" name="txt_data_pagamento" placeholder="<?=$this->lang->line('application_payment_date')?>" min="1" max="31" step='1'>
          </div>
          
          <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_payment_date_conecta');?></label>
            <input type="number" class="form-control" id="txt_data_pagamento_conecta" name="txt_data_pagamento_conecta" placeholder="<?=$this->lang->line('application_payment_date_conecta')?>" min="1" max="31" step='1'>
          </div>
          
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal" id="btnFechar" name="btnFechar"><?=$this->lang->line('application_close');?></button>
          <button type="button" class="btn btn-primary" id="btnSave" name="btnSave"><?=$this->lang->line('application_save');?></button>
        </div>

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- print_r($categ); print_r($mktPlace); -->
<?php endif; ?>

<?php if(in_array('deleteParamktplaceCiclo', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_remove_ciclos')?> - Marketplace</h4>
      </div>

      <form role="form" action="" method="post" id="removeForm">
        <div class="modal-body">
          <p><?=$this->lang->line('messages_remove_ciclo')?> - marketplace?</p>
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

    if(localStorage.getItem("updateCiclo")){
        $(".messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+'Atualizado efetuada com sucesso'+
            '</div>');
    }
    localStorage.removeItem("updateCiclo");

    $("#btnSave").click(function() {

	  if($("#txt_valor_ciclo").val() == "" || $("#txt_data_inicio").val() == "" || $("#txt_data_fim").val() == "" || $("#txt_data_pagamento").val() == "" || $("#txt_data_pagamento_conecta").val() == ""){
			alert("Valor de Ciclo inválido");
			return false;
	  }
	  
	  $("#btnSave").prop('disabled', true);
	  var pageURL = base_url.concat("paramktplace/verificacadastrociclo");
	  $.post( pageURL, $("#createForm").serialize(), function( data ) {
		  console.log(data);
		  var saida = data.split(";");
		  if(saida[0] == "1"){
			alert(saida[1]);
			$("#btnSave").prop('disabled', false);
		  }else{
			$("#btnFechar").click();
			$(".messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saida[1]+
		            '</div>');
			$('#manageTable').DataTable().ajax.reload();
			$("#btnSave").prop('disabled', false);
		  } 
	  });
  });

  $("#paraMktPlaceNav").addClass('active');
  $("#listCicloNav").addClass('active'); 
  
  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'paramktplace/fetchParamktPlaceDataCiclo',
    'order': []
  });

});

function removeFuncCiclo(id)
{
  if(id) {
      $("#btnRemover").click(function (){
    	  var pageURL = base_url.concat("paramktplace/removeciclo");
    	  $.post( pageURL, {id:id}, function( data ) {
    		  var saida = data.split(";");
    		  if(saida[0] == "1"){
    			  $(".messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
    		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
    		              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+saida[1]+
    		            '</div>'); 
    		  }else{
    			  $(".messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
    		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
    		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saida[1]+
    		            '</div>');

    			  $("#removeModal").modal('hide');
    			  $('#manageTable').DataTable().ajax.reload();
    		  }
    		 
    	  });
      });
	}
}

</script>
