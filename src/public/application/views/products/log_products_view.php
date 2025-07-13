<!--

-->
<!-- Content Wrapper. Contains page content -->

<div class="content-wrapper">
  <style>
    .dropdown.bootstrap-select.show-tick.form-control{
        display: block;
        width: 100%;
        color: #555;
        background-color: #fff;
        background-image: none;
        border: 1px solid #ccc;
    }
    .bootstrap-select > .dropdown-toggle.bs-placeholder {
        padding: 5px 12px;
    }
    .bootstrap-select .dropdown-toggle .filter-option {
        background-color: white !important;
    }
    .bootstrap-select .dropdown-menu li a {
        border: 1px solid gray;
    }
    .input-group-addon {
        cursor: pointer;
    }
  </style>

  <?php 
  $data['page_now'] = "latest_changes";
  $data['pageinfo'] = "application_product";
  $this->load->view('templates/content_header', $data);?>

  <section class="content">
    <div class="row">
      <div class="col-md-12 col-xs-12">
        <div id="messages"></div>
        <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <?php echo $this->session->flashdata('success'); ?>
        </div>
        <?php elseif ($this->session->flashdata('error')): ?>
        <div class="alert alert-error alert-dismissible" role="alert">
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <?php echo $this->session->flashdata('error'); ?>
        </div>
        <?php endif;?>

        <div class="box box-primary" id="collapseFilter">
          <div class="box-body">

            
            <div class="col-md-3 form-group no-padding">
              <div class="input-group">
                <input type="search" id="busca_username" class="form-control" placeholder="<?=$this->lang->line('application_email');?>" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
              </div>
            </div>

            <div class="col-md-3">
		     <div class="input-group date">
		        <input type='text' class="form-control" id="busca_dateupdate" placeholder="<?=$this->lang->line('application_date');?>" onchange="personalizedSearch()" autocomplete="off" />
	            <span class="input-group-addon">
	                 <span class="glyphicon glyphicon-calendar"></span>
	            </span>
	         </div>
	      </div>

			<a href="<?php echo base_url('products/update/'.$prd_id);?>" class="pull-right btn btn-warning"><?=$this->lang->line('application_back');?></a>
			<button type="button" onclick="clearFilters()" class="pull-right btn btn-primary" style="margin-right: 5px;"> <i class="fa fa-eraser"></i> Limpar </button>
          </div>
        </div>

        <div class="box box-primary">
          <div class="box-body">
          	<table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
              	<th><?=$this->lang->line('application_date');?></th>
              	<th><?=$this->lang->line('application_id');?></th>
              	<th><?=$this->lang->line('application_price');?></th>
                <th><?=$this->lang->line('application_qty');?></th>
                <th><?=$this->lang->line('application_username');?></th>
                <th><?=$this->lang->line('application_change');?></th>
              </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script type="text/javascript" src="<?=HOMEPATH;?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var manageTable = null;
var base_url = "<?php echo base_url(); ?>";
var prd_id = "<?php echo $prd_id; ?>";

$(document).ready(function() {

	$('#busca_dateupdate').datetimepicker({format: 'YYYY-MM-DD'}); 
	$('#busca_dateupdate').datetimepicker({
	    "setDate": new Date(),
        "autoclose": true
	    });
	
	$("#busca_dateupdate").on("dp.change", function (e) {
		personalizedSearch();
	});
	
	personalizedSearch();

});

function personalizedSearch() {
	let id = $('#busca_id').val();
	let username = $('#busca_username').val();
	let dateupdate = $('#busca_dateupdate').val();
	
	if (manageTable !== null) {
		manageTable.destroy();
	}

  manageTable = $('#manageTable').DataTable({
    "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "serverMethod": "post",
    "order": [[ 1, "desc" ]],
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'products/fetchLogProductsData',
      data: {prd_id: prd_id, id: id, username: username, dateupdate: dateupdate},
      pages: 2, // number of pages to cache
    })
  });
}

function clearFilters() {
  $('#busca_id').val('');
  $('#busca_username').val('');
  $('#busca_dateupdate').val('');

  personalizedSearch();
}


</script>
