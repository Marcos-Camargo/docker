<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <?php $data['pageinfo'] = "";
  $data['page_now'] = 'contract_signatures';
  $this->load->view('templates/content_header', $data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

        <?php if ($this->session->flashdata('success')) : ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('success'); ?>
          </div>
        <?php elseif ($this->session->flashdata('error')) : ?>
          <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('error'); ?>
          </div>
        <?php endif; ?>

        <div class="">
        
        <div class="col-md-2">
          <label for="searchContractTitle" class="normal"><?= $this->lang->line('application_contract_title'); ?></label>
          <div class="input-group">
            <input type="search" id="searchContractTitle" onchange="searchContract()" class="form-control" placeholder="<?= $this->lang->line('application_contract_title'); ?>" aria-label="Search" aria-describedby="basic-addon1">
            <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
          </div>
        </div>
        
        <div class="col-md-2">
          <label for="searchDocumentType" class="normal"><?= $this->lang->line('application_contract_type'); ?></label>
          <div class="input-group">
            <select id="searchDocumentType" onchange="searchContract()" class="form-control" placeholder="<?= $this->lang->line('application_contract_type'); ?>" aria-label="Search" aria-describedby="basic-addon1">
                    <option value=""><?= $this->lang->line('application_select'); ?></option>
                    <?php foreach ($attribs as $atributte) { ?>
                      <option value="<?= $atributte['id'] ?>" ><?= $atributte['value'] ?></option>
                    <?php } ?>
            </select>
            <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
          </div>
        </div>
        
        <div class="col-md-3">
          <label for="searchStore" class="normal"><?= $this->lang->line('application_store'); ?></label>
          <div class="input-group" style="background:white;">
            <select class="form-control selectpicker show-tick" id="searchStore" onchange="searchContract()" class="form-control" title="<?= $this->lang->line('application_store'); ?>" aria-label="Search" aria-describedby="basic-addon1" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 2" >
              <?php foreach ($stores as $store) { ?>
                <option value="<?= $store['id'] ?>"><?= $store['name'] ?></option>
              <?php } ?>
            </select>
            <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
          </div>
        </div>

        <div class="col-md-2">
          <div class="input-group" >
            <label for="searchSign" class="normal"><?=$this->lang->line('application_signed');?></label>
            <select class="form-control" id="searchSign" onchange="searchContract()">
              <option value="" selected><?=$this->lang->line('application_all');?></option>
              <option value="1"><?=$this->lang->line('application_yes');?></option>
              <option value="0"><?=$this->lang->line('application_no');?></option>
            </select>
          </div>
        </div>

        <div class="col-md-2">
          <div class="input-group" >
            <label for="searchStatus" class="normal"><?=$this->lang->line('application_active');?></label>
            <select class="form-control" id="searchStatus" onchange="searchContract()">
              <option value="" selected><?=$this->lang->line('application_all');?></option>
              <option value="1"><?=$this->lang->line('application_yes');?></option>
              <option value="0"><?=$this->lang->line('application_no');?></option>
            </select>
          </div>
        </div>

        <div class="pull-right">
      <label  class="normal" style="display: block;">&nbsp; </label>
           <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
        </div>
      </div>	
      
      <div class="row" style="padding:2%;"></div>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th><?= $this->lang->line('application_id'); ?></th>
                  <th><?= $this->lang->line('application_contract_title'); ?></th>
                  <th><?= $this->lang->line('application_contract_type'); ?></th>
                  <th><?= $this->lang->line('application_store'); ?></th>
                  <th><?= $this->lang->line('application_cnpj'); ?></th>
                  <th><?= $this->lang->line('application_legal_administrator'); ?></th>
                  <th><?= $this->lang->line('application_cpf'); ?></th>
                  <th><?= $this->lang->line('application_signature_date'); ?></th>
                  <th><?= $this->lang->line('application_active'); ?></th>
                  <th><?= $this->lang->line('application_action'); ?></th>
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
<?php if (in_array('viewContractSignatures', $user_permission) || in_array('deleteContractSignatures', $user_permission)) : ?>
  <!-- remove brand modal -->
  <div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title"><?= $this->lang->line('application_contract_signatures'); ?><span id="deletelegalpanel"></span></h4>
        </div>

        <form role="form" action="<?php echo base_url('contractSignatures/inactiveContract') ?>" method="post" id="removeForm">
          <div class="modal-body">
            <p><?= $this->lang->line('application_ask_inactivate_contract'); ?></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
            <button type="submit" class="btn btn-primary"><?= $this->lang->line('application_confirm'); ?></button>
          </div>
        </form>


      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->
<?php endif; ?>

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
  var manageTable;
  var base_url = "<?php echo base_url(); ?>";

  $(document).ready(function() {
    
    $('.select2').select2();
    searchContract();

  });
  

  $('[data-toggle="popover"]').popover({
    placement: 'right',
    trigger: 'hover',
    width: '500px'
  });

  function inactiveFunc(id, name) {
    if (id) {

      $("#removeForm").on('submit', function() {

        var form = $(this);

        // remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: {
            id: id
          },
          dataType: 'json',
          success: function(response) {

            manageTable.ajax.reload(null, false);

            if (response.success === true) {
              $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + response.messages +
                '</div>');

              // hide the modal
              $("#removeModal").modal('hide');
              searchContract()

            } else {

              $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + response.messages +
                '</div>');
            }
          }
        });

        return false;
      });

      
    }
  }

  function searchContract(){
    let contractTitle = $('#searchContractTitle').val();
    let documentType = $('#searchDocumentType').val();    
    let store = $('#searchStore').val(); 
    let sign = $('#searchSign').val();
    let status  = $('#searchStatus').val();
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
      url: base_url + 'contractSignatures/fetchContractSignatures',
      data: { 
        contractTitle: contractTitle, 
        documentType: documentType,          
        store: (store ? store : [""]),
        sign: sign,
        status: status,
      },
      pages: 100
    })
  });
}

function clearFilters(){
  $('#searchContractTitle').val('');
  $('#searchDocumentType').val('');
  $('#searchStore').val('');
  $('#searchSign').val('');
  $('#searchStatus').val('');
  searchContract();
  
}
</script>

<style>
.filter-option { background-color: white; }
</style>