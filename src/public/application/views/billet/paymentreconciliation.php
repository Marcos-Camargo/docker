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
            <form role="form" id="frmCadastrar" name="frmCadastrar" action="" method="post">
              <div class="box-body">
                <div class="form-group col-md-3 col-xs-3">
                	<input type="hidden" id="hdnLote" name="hdnLote" value="<?php echo $hdnLote;?>" />
                  <label for="group_isadmin"><?=$this->lang->line('application_runmarketplaces');?></label>
                  <select class="form-control" id="slc_mktplace" name="slc_mktplace" disabled>
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($mktplaces as $mktPlaces): ?>
                      <option value="<?php echo trim($mktPlaces['id']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                  <label for="group_isadmin"><?=$this->lang->line('application_conciliacao_month_year');?></label>
                  <select class="form-control" id="slc_ano_mes" name="slc_ano_mes" disabled >
                    <option value="">~~SELECT~~</option>
                    <option value="Janeiro/2021">Janeiro/2021</option>
                    <option value="Fevereiro/2021">Fevereiro/2021</option>
                    <option value="Março/2021">Março/2021</option>
                    <option value="Abril/2021">Abril/2021</option>
                    <option value="Maio/2021">Maio/2021</option>
                    <option value="Junho/2021">Junho/2021</option>
                    <option value="Julho/2021">Julho/2021</option>
                    <option value="Agosto/2021">Agosto/2021</option>
                    <option value="Setembro/2021">Setembro/2021</option>
                    <option value="Outubro/2021">Outubro/2021</option>
                    <option value="Novembro/2021">Novembro/2021</option>
                    <option value="Dezembro/2021">Dezembro/2021</option>
                    <option value="Janeiro/2020">Janeiro/2020</option>
                    <option value="Fevereiro/2020">Fevereiro/2020</option>
                    <option value="Março/2020">Março/2020</option>
                    <option value="Abril/2020">Abril/2020</option>
                    <option value="Maio/2020">Maio/2020</option>
                    <option value="Junho/2020">Junho/2020</option>
                    <option value="Julho/2020">Julho/2020</option>
                    <option value="Agosto/2020">Agosto/2020</option>
                    <option value="Setembro/2020">Setembro/2020</option>
                    <option value="Outubro/2020">Outubro/2020</option>
                    <option value="Novembro/2020">Novembro/2020</option>
                    <option value="Dezembro/2020">Dezembro/2020</option>
                  </select>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                  <label for="group_isadmin"><?=$this->lang->line('application_parameter_mktplace_value_ciclo');?></label>
                  <select class="form-control" id="slc_ciclo" name="slc_ciclo" disabled>
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($ciclo as $cil): ?>
                      <option value="<?php echo trim($cil['id']); ?>"><?php echo trim($cil['mkt_place']).' - do dia : '.$cil['data_inicio'].' - até: '.$cil['data_fim']; ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
                <div class="col-md-2 col-xs-2" id="divExcel" name="divExcel" style="display:block"><br>
             		<button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Excel</button>
        	 	</div>
            </div> <!-- box body -->
                <div class="box" id="DivOk" style="display: block">
                	<div class="box-header">
                      <h3 class="box-title"><?=$this->lang->line('application_conciliacao_checkout');?></h3> 
                    </div>
                  <div class="box-body">
                  	
                    <table id="manageTableOrdersOk" class="table table-bordered table-striped">
                      <thead>
                      <tr>
                      <th><?=$this->lang->line('application_id');?></th>
                        <th><?=$this->lang->line('application_store');?></th>
                        <th>Valor a receber - ConectaLá</th>
                        <th>Valor a receber - Seller</th>
                        <th>Responsável Conciliação</th>
                        <th><?=$this->lang->line('application_rec_5');?></th>
                        <th><?=$this->lang->line('application_action');?></th>
                      </tr>
                      </thead>
        
                    </table>
                  </div>
                  <!-- /.box-body -->
                </div>
                <!-- /.box -->
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
// var manageTableResult;
var manageTableBillet;

var manageTableOrdersOk;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  $("#paraMktPlaceNav").addClass('active');
	$("#conciliacaoNav").addClass('active');

	$("#slc_mktplace").val("<?php if($dadosBanco){ echo $dadosBanco['id_mkt']; }?>");
	$("#slc_ciclo").val("<?php if($dadosBanco){ echo $dadosBanco['id_ciclo']; }?>");
	$("#slc_ano_mes").val("<?php if($dadosBanco){ echo $dadosBanco['ano_mes']; }?>");  
	$("#txt_carregado").val("<?php if($dadosBanco){ echo $dadosBanco['carregado']; }?>"); 

   manageTableOrdersOk = $('#manageTableOrdersOk').DataTable({
		  "scrollX": false,
	    'ajax': base_url + 'billet/fetchConciliacaoAPagarGridData/' + $("#hdnLote").val(),
	    'order': []
	  });

	$("#btnSave").click( function(){
    $("#messages2").html("");
		$("#btnSave").prop('disabled', true);

		if( $("#slc_mktplace").val() 	== "" ||
			$("#slc_ano_mes").val() 	== "" ||
			$("#slc_ciclo").val() 		== "" ){
			alert("Todos os campos são de preenchimento obrigatório");
			$("#btnSave").prop('disabled', false);
			return false;
		}	

		var pageURL = base_url.concat("billet/cadastrarrepasse");
		$.post( pageURL, $("#frmCadastrar").serialize(), function( data ) {
			var retorno = data.split(";");
			if(retorno[0] == "0"){

        $("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                      '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                      '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+retorno[1]+
                    '</div>');
			}else{

        $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                      '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                      '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+retorno[1]+
                    '</div>'); 
				$("#btnSave").prop('disabled', false);

			}
			
		});

	});

	$("#btnExcel").click(function(){

		var filtroexcel = $("#hdnLote").val();
		var saida = 'billet/exportarepasse/' + filtroexcel;
		window.open(base_url.concat(saida),'_blank');
	});

});

function ajustarepassetemp(id){
  if(id){
		$("#messages2").html("");
    var pageURL = base_url.concat("billet/mudastatusrepassetemp");
    var form = $("#formObservacao").serialize();
    
    $.post( pageURL, {id: id, lote : $("#hdnLote").val()}, function( data ) {
      
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

        }
    });
  }
	
}

</script>