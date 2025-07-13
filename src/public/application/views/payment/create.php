<!--
SW Serviços de Informática 2019

Criar Grupos de Acesso

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12">
          
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

                <div class="form-group col-md-4 col-xs-4">
                  <label for="group_isadmin"><?=$this->lang->line('application_runmarketplaces');?></label>
                  <select class="form-control" id="slc_mktplace" name="slc_mktplace">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($mktplaces as $mktPlaces): ?>
                      <option value="<?php echo trim($mktPlaces['id']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
                
                <div class="form-group col-md-4 col-xs-4">
                  <label for="group_name"><?=$this->lang->line('application_start_date');?></label>
                  <input type="date" class="form-control" id="txt_dt_inicio" name="txt_dt_inicio" placeholder="Data Início">
                </div>
                
                <div class="form-group col-md-4 col-xs-4">
                  <label for="group_name"><?=$this->lang->line('application_deadline');?></label>
                  <input type="date" class="form-control" id="txt_dt_fim" name="txt_dt_fim" placeholder="Data Fim">
                </div>
                <input type="textarea" class="form-control" id="hdn_id_orders" name="hdn_id_orders" style="display:none;">
                <input type="textarea" class="form-control" id="hdn_id_function" name="hdn_id_function" style="display:none;">
                
                <button type="button" class="btn btn-primary" id="btn_filtro" name="btn_filtro"><?=$this->lang->line('application_filter');?></button>
                
                
              </div>
              
              
              <div class="box">
              		<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_orders_list');?></h3>
                    </div>
                  <div class="box-body">
                    <table id="manageTableResult" class="table table-bordered table-striped">
                      <thead>
                      <tr>
                        <th><?=$this->lang->line('application_id');?></th>
                        <th><?=$this->lang->line('application_runmarketplaces');?></th> 
                        <th><?=$this->lang->line('application_purchase_id');?></th>
                        <th><?=$this->lang->line('application_date');?></th>
                        <th><?=$this->lang->line('application_status');?></th>
                        <th><?=$this->lang->line('application_value');?></th>
                        <th><?=$this->lang->line('application_action');?></th>
                      </tr>
                      </thead>
                    </table>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->
                
                <div class="box">
                	<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_orders_list_added');?></h3>
                    </div>
                  <div class="box-body">
                  	
                    <table id="manageTableBillet" class="table table-bordered table-striped">
                      <thead>
                      <tr>
                        <th><?=$this->lang->line('application_id');?></th>
                        <th><?=$this->lang->line('application_runmarketplaces');?></th> 
                        <th><?=$this->lang->line('application_purchase_id');?></th>
                        <th><?=$this->lang->line('application_value');?></th>
                        <th><?=$this->lang->line('application_billet_situation');?></th>
                      </tr>
                      </thead>
        
                    </table>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->
              
              <div class="box">
              		<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_billet_total_value');?></h3>
                    </div>
                  <div class="box-body">
                  	<input type="text" id="txt_valor_total" name="txt_valor_total" placeholder="Valor Total do Boleto" readonly="readonly"/>
                  </div>
                  
              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                <button type="button" id="btnSave" name="btnSave" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
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

<script type="text/javascript">
var manageTableResult;
var manageTableBillet;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

	 var filtro = "/"+$("#slc_mktplace").val()+"/"+$("#txt_dt_inicio").val()+"/"+$("#txt_dt_fim").val()+"/"+$("#hdn_id_orders").val();
	 var url = base_url + 'billet/fetchOrdersListData' + filtro;

	 var filtro2 = "/"+$("#hdn_id_orders").val()+"/"+$("#hdn_id_function").val();
	 var url2 = base_url + 'billet/fetchOrdersListAddedData' + filtro2;
	 
	  
    $("#mainGroupNav").addClass('active');
    $("#addGroupNav").addClass('active');

	$("#btn_filtro").click( function() {
		  // initialize the datatable 
		  if($("#slc_mktplace").val() == ""){
			alert("Selecione o marketPlace");
			return false;
		  }

		  var table = $('#manageTableResult').DataTable();
		  table.destroy();
		  var dtInicio;
		  var dtFim;
		  var dtId;
		  if($("#txt_dt_inicio").val() == ""){ dtInicio = 0; } else { dtInicio = $("#txt_dt_inicio").val(); }
		  if($("#txt_dt_fim").val() == ""){ dtFim = 0; } else { dtFim = $("#txt_dt_fim").val(); }
		  if($("#hdn_id_orders").val() == ""){ dtId = 0; } else { dtId = $("#hdn_id_orders").val(); }
		  filtro = "/"+$("#slc_mktplace").val()+"/"+dtInicio+"/"+dtFim+"/"+dtId;
		  url = base_url + 'billet/fetchOrdersListData' + filtro;
		  manageTableResult = $('#manageTableResult').DataTable({
		    'ajax': url,
		    'order': []
		  });

		  $("#slc_mktplace").prop('disabled', true);
	});	

	manageTableResult = $('#manageTableResult').DataTable({
	    'ajax': url,
	    'order': []
	  });

	manageTableBillet = $('#manageTableBillet').DataTable({
	    'ajax': url2,
	    'order': []
	  });


	$("#btnSave").click( function(){
		$("#btnSave").prop('disabled', true);

		var checkStatus = $("#hdn_id_function").val().split("-");
		var teste = 0;
		for (i = 0; i < checkStatus.length; i++) {
			if(checkStatus[i] == "Add"){
				teste = 1;
			}
		}

		if($("#hdn_id_orders").val() == "" ||  teste == 0){
			alert("Selecione ao menos 1 pedido para gerar o boleto");
			return false;
		}

		$("#slc_mktplace").prop('disabled', false);
		
		var pageURL = base_url.concat("billet/create");
		$.post( pageURL, $("#frmCadastrar").serialize(), function( data ) {
			$("#slc_mktplace").prop('disabled', true);
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

	
  });

function addbillet(id, func){

	var textArea = $("#hdn_id_orders").val();
	var textArea2 = $("#hdn_id_function").val();
	
	if(textArea == ""){
		$("#hdn_id_orders").val(id);
	}else{
		$("#hdn_id_orders").val(textArea+"-"+id);
	}

	if(textArea2 == ""){
		$("#hdn_id_function").val(func);
	}else{
		$("#hdn_id_function").val(textArea2+"-"+func);
	}


	var pageURL = base_url.concat("billet/totalBillet");
	$.post( pageURL, {id_orders:$("#hdn_id_orders").val(),id_function:$("#hdn_id_function").val()}, function( data ) {

		$("#txt_valor_total").val(data);
		
		var table = $('#manageTableResult').DataTable();
	  	table.destroy();
	  	var dtInicio;
	  	var dtFim;
	  	var dtId;
	  	if($("#txt_dt_inicio").val() == ""){ dtInicio = 0; } else { dtInicio = $("#txt_dt_inicio").val(); }
	  	if($("#txt_dt_fim").val() == ""){ dtFim = 0; } else { dtFim = $("#txt_dt_fim").val(); }
	  	if($("#hdn_id_orders").val() == ""){ dtId = 0; } else { dtId = $("#hdn_id_orders").val(); }
	  	filtro = "/"+$("#slc_mktplace").val()+"/"+dtInicio+"/"+dtFim+"/"+dtId;
	  	url = base_url + 'billet/fetchOrdersListData' + filtro;
	  	manageTableResult = $('#manageTableResult').DataTable({
	  	  'ajax': url,
	  	  'order': []
	  	});

		var table2 = $('#manageTableBillet').DataTable();
	  	table2.destroy();
	  	filtro2 = "/"+$("#hdn_id_orders").val()+"/"+$("#hdn_id_function").val();
	  	url2 = base_url + 'billet/fetchOrdersListAddedData' + filtro2;
	  	manageTableBillet = $('#manageTableBillet').DataTable({
	  	  'ajax': url2,
	  	  'order': []
	  	});
	});
	
}
  
</script>

