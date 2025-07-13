<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <?php $data['pageinfo'] = "";
  $data['page_now'] = 'contracts';
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

        <?php if (in_array('createContracts', $user_permission)) : ?>
          <button class="btn btn-primary" id="btn_new_contract" name="btn_new_contract"><?= $this->lang->line('application_create_contract'); ?></button>
          <br /> <br />
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
        
        <div class="col-md-2">
          <div class="input-group" >
            <label for="searchBlock" class="normal"><?= $this->lang->line('application_block'); ?></label>
            <select class="form-control" id="searchBlock" onchange="searchContract()">
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

        <div class="pull-right col-md-2">
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
                  <th><?= $this->lang->line('application_store'); ?></th>
                  <th><?= $this->lang->line('application_accountable_opening'); ?></th>
                  <th><?= $this->lang->line('application_date_create'); ?></th>
                  <th><?= $this->lang->line('application_contract_type'); ?></th>
                  <th><?= $this->lang->line('application_active'); ?></th>
                  <th><?= $this->lang->line('application_block'); ?></th>
                  <th><?= $this->lang->line('application_validity'); ?> </th>
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

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
  var manageTable;
  var base_url = "<?php echo base_url(); ?>";

  $(document).ready(function() {

    $("#btn_new_contract").click(function() {
      window.location.assign(base_url.concat("contracts/create"));
    });

    searchContract();

  });

  function searchContract(){
    let contractTitle = $('#searchContractTitle').val();
    let documentType = $('#searchDocumentType').val();    
    let store = $('#searchStore').val(); 
    let block = $('#searchBlock').val();
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
      url: base_url + 'contracts/fetchContracts',
      data: { 
        contractTitle: contractTitle, 
        documentType: documentType,          
        store: (store ? store : [""]),
        block: block,
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
  $('#searchBlock').val('');
  $('#searchStatus').val('');
  searchContract();
  
}
</script>

<style>
.filter-option { background-color: white; }
</style>