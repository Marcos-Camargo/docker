<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_parameter_mktplace";  $this->load->view('templates/content_header',$data); ?>

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
        
        <?php if(in_array('createParamktplace', $user_permission)): ?>
          <button class="btn btn-primary" data-toggle="modal" data-target="#addModal"><?=$this->lang->line('application_add_category');?></button>
          <button class="btn btn-primary" data-toggle="modal" data-target="#editMktPlaceModal"><?=$this->lang->line('application_parameter_mktplace_comissao_edit');?></button>
          <br /> <br />
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_store');?></th> 
                <th><?=$this->lang->line('application_category');?></th>
                <th><?=$this->lang->line('application_start_date');?></th>
                <th><?=$this->lang->line('application_end_date');?></th> 
                <th data-toggle="tooltip" data-placement="top" title="" data-container="body" data-original-title="Para valores do Mercado Livre: Premium % / Free %" ><?=$this->lang->line('application_value');?></th>
                <?php if(in_array('updateCategory', $user_permission) || in_array('deleteCategory', $user_permission)): ?>
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

<?php if(in_array('createParamktplace', $user_permission)): ?>
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
            <label for="volume_type"><?=$this->lang->line('application_store');?></label>
            <select class="form-control" id="cmb_mktplace" name="cmb_mktplace">
              <option value=""><?=$this->lang->line('application_select');?></option>
                <?php foreach ($mktPlaces as $mktPlaces): ?>
                  <option value="<?php echo trim($mktPlaces['id']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                <?php endforeach ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="volume_type"><?=$this->lang->line('application_category');?></label>
            <select class="form-control" id="cmb_categoria" name="cmb_categoria">
              <option value=""><?=$this->lang->line('application_select');?></option>
                <?php foreach ($categs as $categ): ?>
                  <option value="<?php echo trim($categ['id']); ?>"><?php echo trim($categ['categoria']); ?></option>
                <?php endforeach ?>
            </select>
          </div>

			<div style="display:none" class="form-group" id="divCategoria" name="divCategoria">
            <label for="txt_categoria"><?=$this->lang->line('application_category');?></label>
            <input  type="text" class="form-control" id="txt_categoria" name="txt_categoria" placeholder="<?=$this->lang->line('application_enter_paramktplace_new_category')?>" autocomplete="off">
         	 </div>
         	 
         <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_start_date');?></label>
            <input type="date" class="form-control" id="txt_dt_inicio" name="txt_dt_inicio" placeholder="Data Início">
            <input type="time" class="form-control" id="txt_hra_inicio" name="txt_hra_inicio" placeholder="Hora Início" value="00:00">
          </div>
          
          <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_end_date');?></label>
            <input type="date" class="form-control" id="txt_dt_fim" name="txt_dt_fim" placeholder="Data Fim">
            <input type="time" class="form-control" id="txt_hra_fim" name="txt_hra_fim" placeholder="Hora Fim">
          </div>

          <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_valor_percentual');?><div id="nomeMercadoLive1"></div></label>
            <input type="number" class="form-control" id="txt_valor_aplicado" name="txt_valor_aplicado" placeholder="<?=$this->lang->line('application_enter_paramktplace_perc_discount')?>" autocomplete="off" min="1" max="100" step='0.01' value='0.00'>
          </div>
          
          <div style="display:none" class="form-group" id="divML" name="divML">
                  <div class="form-group">
                    <label for="txt_valor_aplicado"><?=$this->lang->line('application_valor_percentual');?><div id="nomeMercadoLive2"></div></label>
                    <input type="number" class="form-control" id="txt_valor_aplicado_2" name="txt_valor_aplicado_2" placeholder="<?=$this->lang->line('application_enter_paramktplace_perc_discount');?>" autocomplete="off" min="1" max="100" step='0.01' value=''>
                  </div>
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

<?php if(in_array('deleteParamktplace', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_remove_category')?> - Marketplace</h4>
      </div>

      <form role="form" action="" method="post" id="removeForm">
        <div class="modal-body">
          <p><?=$this->lang->line('messages_remove_category')?> - marketplace?</p>
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

<?php if(in_array('updateParamktplace', $user_permission)): ?>
<!-- edit todos os mktsplaces modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="editMktPlaceModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_parameter_mktplace_comissao_edit');?></h4>
      </div>

      <form role="form" action="" method="post" id="createFormEdit" name="createFormEdit">

        <div class="modal-body">

		<div class="form-group">
            <label for="volume_type"><?=$this->lang->line('application_runmarketplaces');?></label>
            <select class="form-control" id="cmb_mktplace_edt" name="cmb_mktplace_edt">
              <option value="">~~SELECT~~</option>
                <?php foreach ($mktPlace2s as $mktPlace): ?>
                  <option value="<?php echo trim($mktPlace['id']); ?>"><?php echo trim($mktPlace['mkt_place']); ?></option>
                <?php endforeach ?>
            </select>
          </div>
         
          <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_start_date');?></label>
            <input type="date" class="form-control" id="txt_dt_inicio_edt" name="txt_dt_inicio_edt" placeholder="Data Início">
            <input type="time" class="form-control" id="txt_hra_inicio_edt" name="txt_hra_inicio_edt" placeholder="Hora Início" value="00:00">
          </div>
          
          <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_end_date');?></label>
            <input type="date" class="form-control" id="txt_dt_fim_edt" name="txt_dt_fim_edt" placeholder="Data Fim">
            <input type="time" class="form-control" id="txt_hra_fim_edt" name="txt_hra_fim_edt" placeholder="Hora Fim">
          </div>
          
          
          <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_valor_percentual');?> <div id="nomeMercadoLiveEdt1"></div></label>
            <input type="number" class="form-control" id="txt_valor_aplicado_edt" name="txt_valor_aplicado_edt" placeholder="Percentual de Desconto" autocomplete="off" min="1" max="100" step='0.01' value='0.00'>
          </div>
          
          <div style="display:none" class="form-group" id="divMLEdt" name="divMLEdt">
                  <div class="form-group">
                    <label for="txt_valor_aplicado"><?=$this->lang->line('application_valor_percentual');?><div id="nomeMercadoLiveEdt2"></div></label>
                    <input type="number" class="form-control" id="txt_valor_aplicado_2_edt" name="txt_valor_aplicado_2_edt" placeholder="<?=$this->lang->line('application_enter_paramktplace_perc_discount');?>" autocomplete="off" min="1" max="100" step='0.01' value="">
                  </div>
           </div>
          
        </div>
 
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal" id="btnFechar" name="btnFechar"><?=$this->lang->line('application_close');?></button>
          <button type="button" class="btn btn-primary" id="btnSaveEditarMktPlace" name="btnSaveEditarMktPlace"><?=$this->lang->line('application_save');?></button>
        </div>

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- print_r($categ); print_r($mktPlace); -->
<?php endif; ?>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {


	$("#cmb_mktplace").change(function() {
        if($("#cmb_mktplace").val() == "11"){
            $("#divML").show();
            $("#nomeMercadoLive1").html(" - Anuncio Premium");
            $("#nomeMercadoLive2").html(" - Anuncio Free");
        }else{
            $("#divML").hide();
            $("#nomeMercadoLive1").html("");
            $("#nomeMercadoLive2").html("");
        }
    });

	$("#cmb_mktplace_edt").change(function() {
        if($("#cmb_mktplace_edt").val() == "11"){
            $("#divMLEdt").show();
            $("#nomeMercadoLiveEdt1").html(" - Anuncio Premium");
            $("#nomeMercadoLiveEdt2").html(" - Anuncio Free");
        }else{
            $("#divMLEdt").hide();
            $("#nomeMercadoLiveEdt1").html("");
            $("#nomeMercadoLiveEdt2").html("");
        }
    });
	
    $("#txt_valor_aplicado").change(function(){
        parseFloat($(this).val()).toFixed(2);
    });
	
    $("#cmb_categoria").change(function() {
    if($("#cmb_categoria").val() == "0"){
        $("#divCategoria").show();
    }else{
        $("#divCategoria").hide();
    }
    });

    $("#btnSave").click(function() {

	  if(isNaN($("#txt_valor_aplicado").val())){
			alert("Valor Percentual de desconto inválido");
			return false;
	  }
	  
	  $("#btnSave").prop('disabled', true);
	  var pageURL = base_url.concat("paramktplace/verificacadastro");
	  $.post( pageURL, $("#createForm").serialize(), function( data ) {
		  console.log(data);
		  var saida = data.split(";");
		  if(saida[0] == "1"){
			alert(saida[1]);
			$("#btnSave").prop('disabled', false);
		  }else{
			$("#btnFechar").click();
			$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saida[1]+
		            '</div>');
			$('#manageTable').DataTable().ajax.reload();
			$("#btnSave").prop('disabled', false);
		  } 
	  });
  });

  $("#btnSaveEditarMktPlace").click(function() {

	  if($("#txt_valor_aplicado_edt").val() == ""){
			alert("Valor Percentual de desconto inválido");
			return false;
	  }
	  
	  $("#btnSaveEditarMktPlace").prop('disabled', true);
	  var pageURL = base_url.concat("paramktplace/editcadastromktplacefull");
	  $.post( pageURL, $("#createFormEdit").serialize(), function( data ) {
		  var saida = data.split(";");
		  if(saida[0] == "1"){
			alert(saida[1]);
			$("#btnSaveEditarMktPlace").prop('disabled', false);
		  }else{
			$("#btnFechar").click();
			$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saida[1]+
		            '</div>');
			$('#manageTable').DataTable().ajax.reload();
			$("#cmb_mktplace_edt").val("");
			$("#txt_dt_inicio_edt").val("");
			$("#txt_hra_inicio_edt").val("00:00");
			$("#txt_dt_fim_edt").val("");
			$("#txt_hra_fim_edt").val("");
			$("#txt_valor_aplicado_edt").val("0");
			$("#divMLEdt").hide();
            $("#nomeMercadoLiveEdt1").html("");
            $("#nomeMercadoLiveEdt2").html("");
		  } 
	  });
  });

  $("#paraMktPlaceNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'paramktplace/fetchParamktPlaceData',
    'order': []
  });

});

function removeFunc(id)
{
  if(id) {
      $("#btnRemover").click(function (){
    	  var pageURL = base_url.concat("paramktplace/remove");
    	  $.post( pageURL, {id:id}, function( data ) {
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
	}
}

</script>
