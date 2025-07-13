<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_panel_fiscal";  $this->load->view('templates/content_header',$data); ?>

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
          <?php if(in_array('createNFS', $user_permission) ): ?>
              <a href="<?php echo base_url('payment/createnfs') ?>" class="btn btn-primary"><?=$this->lang->line('application_insert_invoice');?></a>
              <?php if($checkVariavel){ ?>
                  <a href="<?php echo base_url('payment/createnfsurlmassa') ?>" class="btn btn-primary"><?=$this->lang->line('application_insert_invoice_massa');?></a>
              <?php } ?>
          <?php endif; ?>

        <div class="box">
        <div class="box-header">
                  <h3 class="box-title">Notas Fiscais Cadastradas</h3>
                </div>
          <div class="box-body">
            <table id="manageTableGlobal" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_panel_cicle_fiscal');?></th>
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

<script type="text/javascript">
var manageTableGlobal;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  var filtro = $("#formFiltro").serialize()
    
  // $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTableGlobal = $('#manageTableGlobal').DataTable({
    'ajax': base_url + 'payment/fetchnfsgroup/',
    'order': []
  });



});

function apaganfs(id){
	if(id){
		
		var pageURL = base_url.concat("payment/apaganfsgroup");
		 
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
	  		  }

			$('#manageTableGlobal').DataTable().ajax.reload();
		
		});
	}
}

</script>
