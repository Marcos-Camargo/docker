<!--

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

<style>

  .normal {
    font-weight: normal;
  }

	.bootstrap-select .dropdown-toggle .filter-option {
		background-color: white !important;
	}
	.bootstrap-select .dropdown-menu li a {
		 border: 1px solid gray;
	}

</style>

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>
	  
	<?php $data['pageinfo'] = "application_manage";  
	 $data['page_now'] ='showcase_products_catalog';
	 $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box)
   <!--  <div class="row">
      <div class="col-md-12 col-xs-12"> -->
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
            <label for="buscaean" class="normal"><?=$label_ean;?></label>
            <div class="input-group">
              <input type="search" id="buscaean" onchange="buscaProduto()" class="form-control" placeholder="<?=$label_ean;?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
          
		  <div class="col-md-5">
            <label for="buscanome" class="normal"><?=$this->lang->line('application_name');?></label>
            <div class="input-group">
              <input type="search" id="buscanome" onchange="buscaProduto()" class="form-control" placeholder="<?=$this->lang->line('application_name');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
          
          <div class="col-md-4">
            <label for="buscamarca" class="normal"><?=$this->lang->line('application_brand');?></label>
            <div class="input-group">
              <input type="search" id="buscamarca" onchange="buscaProduto()" class="form-control" placeholder="<?=$this->lang->line('application_brand');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>

            <div class="col-md-4">
                <div class="">
            <label for="buscacatalog" class="normal"><?=$this->lang->line('application_catalogs');?></label>
            <select class="form-control selectpicker show-tick"  id="buscacatalog" name ="buscacatalog" onchange="buscaProduto()" data-live-search="true" multiple="multiple" data-selected-text-format="count > 2" title="<?=$this->lang->line('application_select');?>" >
            <?php foreach ($catalogs as $catalog) { ?>
            <option value="<?= $catalog['id'] ?>"><?= $catalog['name'] ?></option>
            <?php } ?>
            </select>
            </div>
            </div>
            <?php if ($identifying_technical_specification && $identifying_technical_specification['status'] == 1) { ?>
            <div class="col-md-4">
            <div class="">
                <label for="buscacatalog" class="normal"><?=$this->lang->line('application_collection');?></label>
                <select class="form-control selectpicker show-tick"  id="buscacollection" name ="buscacollection" onchange="buscaProduto()" data-live-search="true" multiple="multiple" data-selected-text-format="count > 2" title="<?=$this->lang->line('application_collection');?>" >
                    <?php foreach ($collections as $collection) { ?>
                        <option value="<?= $collection['attribute_value'] ?>"><?= $collection['attribute_value'] ?></option>
                    <?php } ?>
                </select>
            </div>
            </div>
            <?php } ?>

          <div class="col-md-3"></div>
        <div class="col-md-1"  >
            <label  class="normal" style="display: block;">&nbsp; </label>
            <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
        </div>
        	
        <div class="row mb-4"></div>
        <div class="box">
          <div class="box-body">
            <!-- table id="manageTable" class="table table-bordered table-striped" -->
            <table id="manageTable" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
              	<th><?=$this->lang->line('application_image');?></th>
              	<th><?=$label_ean;?></th>
                <th><?=$this->lang->line('application_name');?></th>  
                <th><?=$this->lang->line('application_brand');?></th> 
                <th><?=$this->lang->line('application_catalog');?></th> 
                <th><?=$this->lang->line('application_price');?></th> 
                <th><?=$this->lang->line('application_id');?></th>      
                <th><?=$this->lang->line('application_status');?></th>
                <?php if ($identifying_technical_specification && $identifying_technical_specification['status'] == 1) { ?>
                <th><?=$this->lang->line('application_collection');?></th>
                <?php } ?>
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
var table;

$(document).ready(function() {

  $("#mainCatalogNav").addClass('active');
  $("#manageCatalogShowCaseNav").addClass('active');

  $('.maskdecimal2').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 2, 
		  max: 999999999.99
		});

  let colecoes = [];
    <?php if ($identifying_technical_specification && $identifying_technical_specification['status'] == 1) { ?>
        $('#buscacollection  option:selected').each(function () {
            colecoes.push($(this).val());
        });
    <?php } ?>
  if (colecoes.length === 0){colecoes = ''}


  let ean  = $('#buscaean').val();
  let nome = $('#buscanome').val();
  let brand = $('#buscamarca').val();
  
  var catalog  = [];
  $('#buscacatalog  option:selected').each(function() {
	    catalog.push($(this).val());
  });
  if (catalog == ''){catalog = ''};

  table = $('#manageTable').DataTable({
    "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
    "processing": true,
    "serverSide": true,
    "responsive": true,
    "sortable": true,
    "searching": true,
    "serverMethod": "post",
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'catalogProducts/fetchShowCaseData',
      data: {ean: ean, nome: nome, catalog: catalog, brand: brand, colecoes: colecoes, internal: false},
      pages: 2 // number of pages to cache
    })
  });
});

function buscaProduto(){

    let colecoes = [];
    $('#buscacollection  option:selected').each(function() {
        colecoes.push($(this).val());
    });
    if (colecoes.length === 0){colecoes = ''}

  let ean  = $('#buscaean').val();
  let nome = $('#buscanome').val();
  let brand = $('#buscamarca').val();
  var catalog  = [];
  $('#buscacatalog  option:selected').each(function() {
	    catalog.push($(this).val());
  });
  if (catalog == ''){catalog = ''};
  
  table.destroy();
  table = $('#manageTable').DataTable({
    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
    "processing": true,
    "serverSide": true,
    "responsive": true,
    "sortable": true,
    "searching": true,
    "serverMethod": "post",
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'catalogProducts/fetchShowCaseData',
      data: {ean: ean, colecoes: colecoes, nome: nome, catalog: catalog, brand: brand, internal: false},
      pages: 2
    })
  });
}

function clearFilters(){
  $('#buscaean').val('');
  $('#buscanome').val('');
  $('#buscamarca').val('');
  $('#buscacatalog').selectpicker('val', '');
  $('#buscacollection').selectpicker('val', '');
  buscaProduto();
}

</script>
