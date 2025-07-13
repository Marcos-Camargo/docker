<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
<?php $data['pageinfo'] = "application_discountworksheet_list"; $data['page_now'] ='discount_worksheet'; $this->load->view('templates/content_header',$data); ?>

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
        <a href="<?php echo base_url('billet/creatediscountworksheet') ?>" class="btn btn-primary"><?=$this->lang->line('application_discountworksheet_insert');?></a>
        
        <div class="box">
        <div class="box-header">
                  <h3 class="box-title">Notas Fiscais Cadastradas</h3>
                </div>
          <div class="box-body">
            <table id="manageTableGlobal" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_panel_cicle_fiscal');?></th>
                <th><?=$this->lang->line('application_created_on');?></th>
                <th><?=$this->lang->line('application_status');?></th>
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
    'ajax': base_url + 'billet/fetchdiscountworksheetgroup/',
    'order': []
  });



});

function apagadiscountworksheetgroup(id){
	
	if(confirm("Deseja ativar/desativar essa carga de descontos?")){

		$("#messages").html("");

    	var pageURL = base_url.concat("billet/apagadiscountworksheetgroup");
    	var form = "&id="+id;
    
    	$.post( pageURL, form , function( data ) {

    		if(data == true){
    			$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
        	            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
        	            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+"Planilha de desconto ativada/desativada com sucesso"+
        	          '</div>');
    		}else{
    			$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
        	            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
        	            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+"Erro ao desativar Planilha de desconto"+
        	          '</div>');
    		}
    		$('#manageTableGlobal').DataTable().ajax.reload();
    	});

	}
	
}

</script>