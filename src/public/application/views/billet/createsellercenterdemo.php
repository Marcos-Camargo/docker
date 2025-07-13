 <!-- Content Wrapper. Contains page content -->
 <div class="content-wrapper">
  <?php $data['page_now'] ='conciliacao'; $data['pageinfo'] = "application_add"; $this->load->view('templates/content_header',$data); ?>

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
                  <select class="form-control" id="slc_mktplace" name="slc_mktplace" >
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($mktplaces as $mktPlaces): ?>
                      <option value="<?php echo trim($mktPlaces['id']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                  <label for="group_isadmin"><?=$this->lang->line('application_conciliacao_month_year');?></label>
                  <input class="form-control" type="text" id="txt_ano_mes" name = "txt_ano_mes" placeholder="<?=$this->lang->line('application_conciliacao_month_year');?>"/>
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

                <div class="col-md-3 col-xs-3" id="divExcel" name="divExcel" style="display:block"><br>
             		  <button type="button" id="btnPrepara" name="btnPrepara" class="btn btn-primary">Peparar arquivo Conciliação</button>
                   <button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Download Conciliação</button>
        	 	    </div>

               
            </div> <!-- box body -->

              <div class="box-footer">
                <a href="<?php echo base_url('dashboard/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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
    <div id="teste"></div>
  </div>
  <!-- /.content-wrapper -->
<script type="text/javascript">

var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  $("#paraMktPlaceNav").addClass('active menu-open');
	$("#conciliacaoNav").addClass('active');

  $('#btnExcel').attr("disabled", true); 

  $("#txt_ano_mes").datepicker( {
    format: "mm-yyyy",
    startView: "months", 
    minViewMode: "months"
  });


	$("#btnPrepara").click( function(){

    $("#messages2").html("");

    if($("#slc_ciclo").val() == "" ||
      $("#txt_ano_mes").val() == "" ||
      $("#slc_mktplace").val() == "" ){
      $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                          '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                          '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+'Todos os campos são de preenchimento obrigatório'+
                        '</div>');
      return false;
    }else{

      $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                          '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                          '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+'Gerando conciliação por favor aguarde...'+
                        '</div>');
      
      var pageURL2 = base_url + 'billet/geraconciliacaosellercenter/';
      $.post( pageURL2, $("#frmCadastrar").serialize(), function( data ) {

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
          $('#btnExcel').attr("disabled", false); 

          
        }
      
      });

      
    }

	});

  $("#btnExcel").click( function(){
      var filtroexcel =  $("#hdnLote").val() ;
      var saida = 'billet/exportaconciliacaosellercenter/' + filtroexcel;
      window.open(base_url.concat(saida),'_blank');
    });

});
</script>