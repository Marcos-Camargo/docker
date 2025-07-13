<!--

Em Shopify Requests o time pode verificar quais lojas já foram criadas
e quais lojas terão que ser criadas.

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

		<div class="">
        
          <div class="col-md-3">
            <label for="buscaid" class="normal"><?=$this->lang->line('application_company_id');?></label>
            <div class="input-group">
              <input type="search" id="buscaid" onchange="buscaLoja()" class="form-control" placeholder="<?=$this->lang->line('application_id');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
          
          <div class="col-md-3">
            <label for="buscacnpj" class="normal"><?=$this->lang->line('application_company');?></label>
            <div class="input-group">
              <input type="search" id="buscacnpj" onchange="buscaLoja()" class="form-control" placeholder="<?=$this->lang->line('application_cnpj');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
          
          
            
          </div>
                  

          <div class="col-md-2">
            <div class="input-group" >
              <label for="buscastatus" class="normal"><?=$this->lang->line('application_status');?></label>
              <select class="form-control" id="buscastatus" onchange="buscaLoja()">
                <option value="" selected><?=$this->lang->line('application_all');?></option>
                <option value="1" ><?=$this->lang->line('application_created_stores');?></option>
                <option value="0" ><?=$this->lang->line('application_not_created_stores');?></option>
             </select>
            </div>
          </div>
          </div>
          <div class="pull-right">
			  <label  class="normal" style="display: block;">&nbsp; </label>
       		  <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
          </div>
        </div>	
        
        <div class="row"></div>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped" cellspacing="0" style="border-collapse: collapse; width: 100%;">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_company_id');?></th>
                <th><?=$this->lang->line('application_company_name');?></th>
                <th><?=$this->lang->line('application_cnpj');?></th> 
                <th><?=$this->lang->line('application_email');?></th>
                <th><?=$this->lang->line('application_email_date');?></th>
                <th><?=$this->lang->line('application_store_creation_date');?></th>
                <th><?=$this->lang->line('application_store_status');?></th>
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




<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  $("#mainProcessesNav").addClass('active');
  $("#ShopifyRequestsNav").addClass('active');

	buscaLoja();
  // initialize the datatable 
//  manageTable = $('#manageTable').DataTable({
//	"language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },	  
//    "scrollX": true,
 //   'ajax': 'fetchStoresData',
//    'order': []
 //});


});

// remove functions 


function buscaLoja(){
  let company_id = $('#buscaid').val();
  let CNPJ = $('#buscacnpj').val();
  let creation_status = $('#buscastatus').val();
  if (typeof manageTable === 'object' && manageTable !== null) {
  	manageTable.destroy();
  }
  
  manageTable = $('#manageTable').DataTable({
    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "searching": true,
    "serverMethod": "post",
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'shopify/fetchStoresData',
      data: { company_id, CNPJ, creation_status},
      pages: 2,
      error:function(a,b){console.log(a,b)}
    })
  });
}

function clearFilters(){
  $('#buscaid').val('');
  $('#buscacnpj').val('');
  $('#buscastatus').val('');
  buscaLoja();
  
}

</script>
