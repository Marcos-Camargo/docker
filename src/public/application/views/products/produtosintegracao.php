<!--
SW Serviços de Informática 2019

Listar Produtos

Obs:
cada usuario so pode ver produtos da sua empresa.
Agencias podem ver todos os produtos das suas empresas
Admin pode ver produtos de todas as empresas e agencias

-->
<!-- Content Wrapper. Contains page content -->

<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage"; 
	      $data['page_now'] ='products_integration';
	 $this->load->view('templates/content_header',$data); ?>

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
		<div id="showActions">
		</div>
        <br />
        
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"></h3>
          </div>
          <!-- /.box-header -->
          <div class="box-body">

            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
              	<th>Marketplace</th>         	          
                <th>Sku</th>
                <th>Sku Bling</th>
                <th>Sku Mktp</th>
                <th><?=$this->lang->line('application_name');?></th> 
                <th><?=$this->lang->line('application_category');?></th>
                <th><?=$this->lang->line('application_store');?></th>
              </tr>
              </thead>
            </table>
            
            <div class="col-md-12">
	          <!----
	          <div class="col-md-3">  
				  <button type="submit" class="btn btn-primary" id="select" name="select">Marcar como Integrados no Bling</button>
	          </div>	
	          
	          <div class="col-md-3">  
				  <button type="submit" class="btn btn-primary" id="deselect" name="deselect">Desmarcar como Integrados no Bling</button>
	          </div>
	          ----> 
			  <div id="loaderDiv" class="loader col-md-3"></div>
            </div>

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
    $("#loaderDiv").hide();

    $("#mainProcessesNav").addClass('active');
    $("#produtosIntegracaoNav").addClass('active');

  // initialize the datatable 
  //var table = $('#manageTable').DataTable({
  // 'ajax': base_url + 'produtosProblemas/semIntegracaoData',
  //  'order': []
  //});

    manageTable = $('#manageTable').DataTable( {
	    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
        "serverSide": true,
        "responsive": true,
        "sortable": true,
        "scrollX": true,
        "serverMethod": "post",        
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'products/fetchProdutosIntegracaoData',
            pages: 2 // number of pages to cache
        } )
    } );
    

});



</script>
