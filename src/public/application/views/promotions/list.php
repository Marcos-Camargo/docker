<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

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
        <a href="<?php echo base_url('promotions/createpromo') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_promotion');?></a>
        
        <div class="box">
        <div class="box-header">
                  <?php if($nmundo == "1"){ ?>
                    <h3 class="box-title">Campanhas Disponíveis</h3>
                  <?php }else{ ?>
                    <h3 class="box-title">Promoções Disponíveis</h3>
                  <?php } ?>
                  
                </div>
          <div class="box-body">
            <table id="manageTableGlobal" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_start_date');?></th>
		        <th><?=$this->lang->line('application_end_date');?></th>
		        <th><?=$this->lang->line('application_date_create');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <th><?=$this->lang->line('application_action');?></th>
              </tr>
              </thead>

            </table>
          </div>
          <!-- /.box-body -->
        </div>
        
        <div class="box">
        <div class="box-header">
                  <h3 class="box-title">Minhas promoções</h3>
                </div>
          <div class="box-body">
            <table id="manageTableMinhas" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_start_date');?></th>
		        <th><?=$this->lang->line('application_end_date');?></th>
		        <th><?=$this->lang->line('application_date_create');?></th>
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
var manageTableMinhas;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

	var filtro = $("#formFiltro").serialize()
    
  $("#mainPromotionsNav").addClass('active');
  $("#managePromotionsNav").addClass('active');

  // initialize the datatable 
  manageTableGlobal = $('#manageTableGlobal').DataTable({
  	"language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
    "scrollX": true,
    "autoWidth": false,
    'ajax': base_url + 'promotions/fetchPromotionsDataNew/2',
    'order': []
  });

//initialize the datatable 
  manageTableMinhas = $('#manageTableMinhas').DataTable({
  	"language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
  	"scrollX": true,
    "autoWidth": false,
    'ajax': base_url + 'promotions/fetchPromotionsDataNew/1',
    'order': []
  });


});

function desativarPromocao(id){
	
	if(confirm("Deseja ativar/desativar essa promoção?")){

		$("#messages").html("");

    	var pageURL = base_url.concat("promotions/desativarpromocao");
    	var form = "&id="+id;
    
    	$.post( pageURL, form , function( data ) {

    		if(data == true){
    			$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
        	            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
        	            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+"Promoção desativada com sucesso"+
        	          '</div>');
    		}else{
    			$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
        	            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
        	            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+"Erro ao desativar promoção"+
        	          '</div>');
    		}
        	
    		$('#manageTableGlobal').DataTable().ajax.reload();
    		$('#manageTableMinhas').DataTable().ajax.reload();
    	});

	}
	
}

</script>
