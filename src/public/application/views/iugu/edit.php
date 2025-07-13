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
                <label for="volume_type"><?=$this->lang->line('application_store');?></label>
                <select class="form-control" id="cmb_mktplace" name="cmb_mktplace" disabled>
                      <option value="<?php echo trim($mktPlaces['id']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="volume_type"><?=$this->lang->line('application_category');?></label>
                <select class="form-control" id="cmb_categoria" name="cmb_categoria" disabled>
                      <option value="<?php echo trim($categs['id']); ?>"><?php echo trim($categs['categoria']); ?></option>
                </select>
              </div>
    
              <div class="form-group">
                <label for="txt_valor_aplicado"><?=$this->lang->line('application_valor_percentual');?></label>
                <input type="number" required="required" class="form-control" id="txt_valor_aplicado" name="txt_valor_aplicado" placeholder="Percentual de Desconto" autocomplete="off" min="1" max="100" step='0.01' value='<?php echo trim($paramktplace_data['valor_aplicado']);?>'>
              </div>
              
            </div>
    
            <div class="modal-footer">
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

  $(document).ready(function() {
	  $("#btnEdit").click(function() {

		  if( isNaN($("#txt_valor_aplicado").val()) || $("#txt_valor_aplicado").val() == "" ){
				alert("Valor Percentual de desconto inválido");
				return false;
		  }

		 var myform = $('#createForm');
		 var disabled = myform.find(':input:disabled').removeAttr('disabled');
		 var serialized = myform.serialize();
		 disabled.attr('disabled','disabled');
		 serialized = serialized.concat("&id="+id_edit);

		 $("#btnEdit").prop('disabled', true);
		  var pageURL = base_url.concat("paramktplace/verificaedicao");
		  $.post( pageURL, serialized, function( data ) {
			  var saida = data.split(";");
			  if(saida[0] == "1"){
				alert(saida[1]);
				$("#btnEdit").prop('disabled', false);
			  }else{
				  window.location.href = base_url+"paramktplace/list";
			  } 
		  });
	  });
  });
</script>