 <?php include_once(APPPATH . '/third_party/zipcode.php') ?>
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
              <h3 class="box-title"><?=$this->lang->line('application_edit');?></h3>
            </div>
            <form id="formEditar" name="formEditar" action="">
            <input type="hidden" id="txt_hdn_id" name="txt_hdn_id" value="<?php echo $valores['id_pi'];?>" />
                <div class="form-group col-md-3 col-xs-3">
                	<label for="group_isadmin"><?=$this->lang->line('application_providers');?></label>
                	<select class="form-control" id="slc_transportadora" name="slc_transportadora" >
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($transportadoras as $transportadora): ?>
                      <option value="<?php echo trim($transportadora['id']); ?>"><?php echo trim($transportadora['name']); ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                	<label for="group_isadmin"><?=$this->lang->line('application_store');?></label>
    				<select class="form-control" id="slc_loja" name="slc_loja" >
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($stores as $ciclo): ?>
                          <option value="<?php echo trim($ciclo['id']); ?>"><?php echo trim($ciclo['name']); ?></option>
                        <?php endforeach ?>
                      </select>
                </div>
                
                 <div class="form-group col-md-3 col-xs-3">
    				<label for="group_name"><?=$this->lang->line('application_ship_value');?></label>
              		<input class="form-control" type="number" id="txt_desconto" name="txt_desconto" value="<?php echo $valores['percentual_desconto'];?>" >
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                	<label for="active"><?=$this->lang->line('application_active');?></label>
                      <br />
                      <input type="checkbox" class="minimal" id="active" name="active" <?=set_checkbox('active', 'on', $valores['ativo'] == 1) ?>">
                </div>
            </form>
            	<div class="form-group col-md-12 col-xs-12">
                <br />
                	<button class="btn btn-primary" id="btnSalvar" name="btnSalvar"><?=$this->lang->line('application_save');?></button>
                	<button class="btn btn-warning" id="btnVoltar" name="btnVoltar"><?=$this->lang->line('application_back');?></button>
                </div>
          </div>
          <!-- /.box-body -->
        </div>

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
var providers = "<?php echo $valores['providers_id']?>";
var store = "<?php echo $valores['id_loja']?>";

$(document).ready(function() {
	$("#slc_transportadora").prop('disabled', true);
	$("#slc_transportadora").val(providers);
	$("#slc_loja").val(store);


	$("#mainReceivableNav").addClass('active');
	$("#addReceivableNav").addClass('active');


  	$("#btnSalvar").click(function (){

	  $("#slc_transportadora").prop('disabled', false);
	  
		if(	$("#slc_transportadora_new").val() == "" ||
			$("#slc_loja_new").val() == "" ||
			$("#txt_desconto").val() == ""||
			$("#txt_hdn_id").val() == ""){
			
			alert("Preencha todos os campos da edição antes de salvar");
			$("#slc_transportadora").prop('disabled', true);
			return false;
			
		}
		var pageURL = base_url.concat("providers/editarindicacaotransp");
		
		var form = $("#formEditar").serialize();

		$.post( pageURL, form, function( data ) {
		  $("#slc_transportadora").prop('disabled', true);
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

			  window.location.assign(base_url.concat("providers/listindicacao"));

		}
		 
	  });
  });	

  $("#btnVoltar").click(function (){
	  window.location.assign(base_url.concat("providers/listindicacao"));
  });

});


</script>
