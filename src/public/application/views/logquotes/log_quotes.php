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
  $data['page_now'] = "quotations";
  $data['pageinfo'] = "application_logistics";
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
			      <a href="<?php echo base_url('products/update/'.$prd_id);?>" class="pull-right btn btn-warning"><?=$this->lang->line('application_back');?></a>			      
          </div>
        </div>

        <div class="box box-primary">
          <div class="box-body">
          	<table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>                
              	<th><?=$this->lang->line('application_date');?></th>
              	<th><?=$this->lang->line('application_sku');?></th>
              	<th><?=$this->lang->line('application_zip_code');?></th>
                <th><?=$this->lang->line('application_seller_l');?></th>
                <th><?=$this->lang->line('application_integration');?></th>
                <th><?=$this->lang->line('application_success');?></th>
                <th>Contingência</th>
                <th>Response(ms)</th>
                <th>Total(ms)</th>
                <th><?=$this->lang->line('application_action');?></th>
              </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- edit Setting modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="showResponseDetails">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Detalhes</h4>
      </div>
      
        <div class="modal-body">
            <pre id="responseDetails"></pre>
            <p id="errorMessage"></p>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>          
        </div>

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<script type="text/javascript" src="<?=HOMEPATH;?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var manageTable = null;
var base_url = "<?php echo base_url(); ?>";
var prd_id = "<?php echo $prd_id; ?>";

$(document).ready(function() {
	
	personalizedSearch();

});

function personalizedSearch() {

	if (manageTable !== null) {
		manageTable.destroy();
	}

  manageTable = $('#manageTable').DataTable({
    "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie("swlanguage"))?>.lang'},
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "serverMethod": "post",
    "order": [[ 0, "desc" ]],
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'logQuotes/fetchLogQuotessData',
      data: {prd_id: prd_id},
      pages: 2, // number of pages to cache
    })
  });
}

function viewDetails(id, type)
{ 

  $.ajax({
    url: base_url + 'logQuotes/getDetails/'+id+'/'+type,
    type: 'post',
    dataType: 'json',
    success:function(response) {
//        alert(response);
        $('#responseDetails').html(response.message);
        $('#errorMessage').text(response.error_message);
        $("#showResponseDetails").modal('show');
      
    }
  });
}

</script>
