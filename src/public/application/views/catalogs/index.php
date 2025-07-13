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
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

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
		
		<?php if(in_array('createCatalog', $user_permission)): ?>
			<div class="col-md-12 col-xs-12">
	          <a href="<?php echo base_url('catalogs/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_products_catalog');?></a>
	   		</div>
	    <?php endif; ?>
	      
        <div class="">

		  <div class="col-md-3">
            <label for="buscanome" class="normal"><?=$this->lang->line('application_name');?></label>
            <div class="input-group">
              <input type="search" id="buscanome" onchange="buscaCatalog()" class="form-control" placeholder="<?=$this->lang->line('application_name');?>" aria-label="Search" aria-describedby="basic-addon1" >
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>

          <div class="col-md-2">
            <div class="input-group" >
              <label for="buscastatus" class="normal"><?=$this->lang->line('application_status');?></label>
              <select class="form-control" id="buscastatus" onchange="buscaCatalog()">
                <option value=""><?=$this->lang->line('application_all');?></option>
                <option value="1" selected><?=$this->lang->line('application_active');?></option>
                <option value="2"><?=$this->lang->line('application_inactive');?></option>
              </select>
            </div>
          </div>
         
          <div class="col-md-3">
            <div class="">
              <label for="buscalojas" class="normal">Buscar por Lojas</label>
              <select class="form-control selectpicker show-tick" id="buscalojas" name ="loja[]" onchange="buscaCatalog()" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                <?php foreach ($stores_filter as $store_filter) { ?>
                <option value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></option>
           		<?php } ?>
                
              </select>
            </div>
          </div>
 			
 		  <div class="col-md-3">
            <label for="buscadescricao" class="normal"><?=$this->lang->line('application_description');?></label>
            <div class="input-group">
              <input type="search" id="buscadescricao" onchange="buscaCatalog()" class="form-control" placeholder="<?=$this->lang->line('application_description');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
          
          <div class="pull-right">
			  <label  class="normal" style="display: block;">&nbsp; </label>
       		  <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
        	</div>
        	<br>
        <div class="row"></div>
        <div class="box">
          <div class="box-body">
            <!-- table id="manageTable" class="table table-bordered table-striped" -->
            <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
              	<th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_description');?></th>       
                <th><?=$this->lang->line('application_price_min');?></th>
                <th><?=$this->lang->line('application_price_max');?></th>
                <th><?=$this->lang->line('application_status');?></th>
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
  $("#manageCatalogNav").addClass('active');

  let name = $('#buscanome').val();
  let status  = $('#buscastatus').val();
  let description  = $('#buscadescricao').val();
  let price_min  = $('#price_min').val();
  let price_max  = $('#price_max').val();
	var lojas = [];
    $('#buscalojas  option:selected').each(function() {
        lojas.push($(this).val());
    });
	if (lojas == ''){lojas = ''}

  table = $('#manageTable').DataTable({
    "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "searching": true,
    "serverMethod": "post",
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'catalogs/fetchCatalogsData',
      data: {name: name, status: status, description: description,  lojas: lojas, internal: false, price_min: price_min, price_max: price_max},
      pages: 2 // number of pages to cache
    })
  });
});

function buscaCatalog(){
  let name = $('#buscanome').val();
  let status  = $('#buscastatus').val();
  let description  = $('#buscadescricao').val();
  let price_min  = $('#price_min').val();
  let price_max  = $('#price_max').val();
  
  var lojas = [];
    $('#buscalojas  option:selected').each(function() {
        lojas.push($(this).val());
    });
  if (lojas == ''){lojas = ''}
  
  table.destroy();
  table = $('#manageTable').DataTable({
    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "searching": true,
    "serverMethod": "post",
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'catalogs/fetchCatalogsData',
      data: {name: name, status: status, description: description,  lojas: lojas, price_min: price_min, price_max: price_max},
      pages: 2 // number of pages to cache
    })
  });
}

function clearFilters(){
  $('#buscanome').val('');
  $('#buscastatus').val('');
  $('#buscadescricao').val('');
  $('#buscalojas').selectpicker('val', '');
  $('#price_min').val('');
  $('#price_max').val('');
  buscaCatalog();
}

</script>
