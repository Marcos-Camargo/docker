<!--
SW Serviços de Informática 2019

Editar Recebimentos

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

        <?php if($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <button disable type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('success'); ?>
          </div>
        <?php elseif($this->session->flashdata('error')): ?>
          <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('error'); ?>
          </div>
        <?php endif; ?>


        <div class="box">
          <form role="form" action="" method="post" id="createForm" name="createForm">
    
            <div class="modal-body">
            
            <div class="form-group">
                <label for="volume_type"><?=$this->lang->line('application_parameter_providers_ciclos');?></label>
                <select class="form-control" id="cmb_tp_ciclo" name="cmb_tp_ciclo">
                      <option value="Ciclo">Ciclo</option> 
                      <option value="Semanal">Semanal</option>
                </select>
              </div>
            
    		<div class="form-group">
                <label for="volume_type"><?=$this->lang->line('application_store');?></label>
                <select class="form-control" id="cmb_mktplace" name="cmb_mktplace" disabled>
                      <option value="<?php echo trim($paramktplace_data['providers_id']); ?>"><?php echo trim($paramktplace_data['mkt_place']); ?></option>
                </select>
              </div>
              <div id="divCiclo" style="display: none">
                  <div class="form-group">
                    <label for="txt_valor_aplicado"><?=$this->lang->line('application_payment_start_day');?></label>
                    <input type="number" class="form-control" id="txt_data_inicio" name="txt_data_inicio" placeholder="<?=$this->lang->line('application_start_date')?>" value='<?php echo $paramktplace_data['data_inicio']; ?>'  min="1" max="31" step='1'>
              	</div>
              
                  <div class="form-group">
                    <label for="txt_valor_aplicado"><?=$this->lang->line('application_payment_end_day');?></label>
                    <input type="number" class="form-control" id="txt_data_fim" name="txt_data_fim" placeholder="<?=$this->lang->line('application_start_date')?>" value='<?php echo $paramktplace_data['data_fim']; ?>'  min="1" max="31" step='1'>
                  </div>
              </div>
              
               <div id="divSemanal" style="display: none">
                  <div class="form-group">
                    <label for="volume_type"><?=$this->lang->line('application_Day');?> da <?=$this->lang->line('application_Week');?></label>
                    <select class="form-control" id="cmb_week_day" name="cmb_week_day">
                      <option value=""><?=$this->lang->line('application_select');?></option>
                          <option value="domingo">Domingo</option>
                          <option value="segunda">Segunda</option>
                          <option value="terca">Terça</option>
                          <option value="quarta">Quarta</option>
                          <option value="quinta">Quinta</option>
                          <option value="sexta">Sexta</option>
                          <option value="sabado">Sabado</option>
                    </select>
                  </div>
              </div>
              
              <div class="form-group">
                <label for="txt_valor_aplicado"><?=$this->lang->line('application_payment_date');?></label>
                <input type="number" class="form-control" id="txt_data_pagamento" name="txt_data_pagamento" placeholder="<?=$this->lang->line('application_payment_date')?>" value='<?php echo $paramktplace_data['data_pagamento']; ?>' min="1" max="31" step='1'>
              </div>
              
              <div class="form-group">
                <label for="txt_valor_aplicado"><?=$this->lang->line('application_payment_date_conecta');?></label>
                <input type="number" class="form-control" id="txt_data_pagamento_conecta" name="txt_data_pagamento_conecta" placeholder="<?=$this->lang->line('application_payment_date_conecta')?>" value='<?php echo $paramktplace_data['data_pagamento_conecta']; ?>' min="1" max="31" step='1'>
              </div>
              
            </div>
    
            <div class="modal-footer">
              <a href="<?php echo base_url('paramktplace/listciclotransp') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              <button type="submit" class="btn btn-primary" id="btnEdit" name="btnEdit"><?=$this->lang->line('application_edit');?></button>
            </div>
    
          </form>
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

<script type="text/javascript">
  var base_url = "<?php echo base_url(); ?>";
  var id_edit = "<?php echo $paramktplace_data['id']; ?>";

  var ciclo = "<?php echo $paramktplace_data['tipo_ciclo']; ?>";
  var dia_semana =  "<?php echo $paramktplace_data['dia_semana']; ?>";

  $(document).ready(function() {

	  $("#cmb_tp_ciclo").val(ciclo);
	  $("#cmb_week_day").val(dia_semana);

    	if( $("#cmb_tp_ciclo").val() == "Ciclo"){
    		$("#divCiclo").show(); 
    		$("#divSemanal").hide();
    		$("#cmb_week_day").val("");
    	}else{
    		$("#divCiclo").hide(); 
    		$("#divSemanal").show();
    		$("#txt_data_inicio").val("");
			$("#txt_data_fim").val("");
    	}


	  $("#cmb_tp_ciclo").change(function() {

			if( $("#cmb_tp_ciclo").val() == ""){
				$("#divCiclo").hide(); 
				$("#divSemanal").hide();
			}else{
				if( $("#cmb_tp_ciclo").val() == "Ciclo"){
					$("#divCiclo").show(); 
					$("#divSemanal").hide();
					$("#cmb_week_day").val("");
				}else{
					$("#divCiclo").hide(); 
					$("#divSemanal").show();
					$("#txt_data_inicio").val("");
					$("#txt_data_fim").val("");
				}
			}
		});



	  
	  $("#btnEdit").click(function() {

		  if( $("#cmb_tp_ciclo").val() == "Ciclo"){
	        	
	    		if(	$("#txt_data_inicio").val() == "" || 
	    	    	$("#txt_data_fim").val() == "" || 
	    	    	$("#txt_data_pagamento").val() == "" || 
	    	    	$("#txt_data_pagamento_conecta").val() == ""){
	    			alert("Valor de Ciclo inválido1");
	    			return false;
	    	  	}
	    	  	
	    	}else{

	    		if(	$("#cmb_week_day").val() == "" || 
	    	    	$("#txt_data_pagamento").val() == "" || 
	    	    	$("#txt_data_pagamento_conecta").val() == ""){
	    			alert("Valor de Ciclo inválido2");
	    			return false;
	        	  	}

	    		
	    	}

		 var myform = $('#createForm');
		 var disabled = myform.find(':input:disabled').removeAttr('disabled');
		 var serialized = myform.serialize();
		 disabled.attr('disabled','disabled');
		 serialized = serialized.concat("&id="+id_edit);

		 $("#btnEdit").prop('disabled', true);
		  var pageURL = base_url.concat("paramktplace/verificaedicaociclotransp");
		  $.post( pageURL, serialized, function( data ) {
			  var saida = data.split(";");
			  if(saida[0] == "1"){
				alert(saida[1]);
				$("#btnEdit").prop('disabled', false);
			  }else{
				  window.location.href = base_url+"paramktplace/listciclotransp";
			  } 
		  });
	  });

      $("#paraMktPlaceNav").addClass('active');
  });
</script>