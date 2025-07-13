<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <?php 
  $data['page_now'] = 'externalAuthentication';
  $data['pageinfo'] = '';
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

        <?php 
          if (in_array('createExternalAuthentication', $this->permission)) { 
              foreach ($valid_types as $type) { ?>
                <a href="<?=base_url('externalAuthentication/create/'.$type)?>" class="btn btn-primary"><?= $this->lang->line('application_new'); ?> <?=$type?></a>                
              <?php 
              } ?>
          <br /> <br />
        <?php 
          } ?>

        <div class="">
        
        <div class="col-md-5">
          <label for="searchName" class="normal"><?= $this->lang->line('application_name'); ?></label>
          <div class="input-group">
            <input type="search" id="searchName" onchange="searchExternalAuth()" class="form-control" placeholder="<?= $this->lang->line('application_name'); ?>" aria-label="Search" aria-describedby="basic-addon1">
            <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
          </div>
        </div>
        
        <div class="col-md-3">
          <label for="searchType" class="normal"><?= $this->lang->line('application_type'); ?></label>
          <div class="input-group">
            <select id="searchType" onchange="searchExternalAuth()" class="form-control" placeholder="<?= $this->lang->line('application_type'); ?>" aria-label="Search" aria-describedby="basic-addon1">
                    <option value=""><?= $this->lang->line('application_select'); ?></option>
                    <?php foreach ($valid_types as $type) { ?>
                      <option value="<?=  $type ?>" ><?=  $type ?></option>
                    <?php } ?>
            </select>
            <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
          </div>
        </div>
        
        <div class="col-md-2">
            <label for="searchStatus" class="normal"><?=$this->lang->line('application_active');?></label>
            <div class="input-group" >
              <select class="form-control" id="searchStatus" onchange="searchExternalAuth()">
                <option value="" selected><?=$this->lang->line('application_all');?></option>
                <option value="1"><?=$this->lang->line('application_yes');?></option>
                <option value="2"><?=$this->lang->line('application_no');?></option>
              </select>
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
        </div>
        
        <button type="button" onclick="clearFilters()" class="pull-right btn btn-primary" style="margin-right: 5px;"> <i class="fa fa-eraser"></i> <?= $this->lang->line('application_clear'); ?> </button>
        
      </div>	
      
      <div class="row" style="padding:2%;"></div>

        <div class="box">
          <div class="box-body">
          <table id="manageTable" aria-label="table" class="table table-striped table-hover display table-condensed" style="border-spacing:0; border-collapse: collapse; width: 99%;">
              <thead>
                <tr>
                  <th><?= $this->lang->line('application_id'); ?></th>
                  <th><?= $this->lang->line('application_name'); ?></th>
                  <th><?= $this->lang->line('application_type'); ?></th>      
                  <th><?= $this->lang->line('application_updated_by'); ?></th>
                  <th><?= $this->lang->line('application_updated_at'); ?></th>
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

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
  var manageTable;
  var base_url = "<?php echo base_url(); ?>";
  var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
      csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';

  $(document).ready(function() {
    $("#mainUserNav").addClass('active');
    $("#manageExternalAuthenticationNav").addClass('active');

    $("#btn_create").click(function() {
      window.location.assign(base_url.concat("externalAuthentication/create"));
    });

    searchExternalAuth();

  });

function searchExternalAuth(){
  let authName = $('#searchName').val();
  let authType = $('#searchType').val();    
  let authStatus  = $('#searchStatus').val();

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
      url: base_url + 'externalAuthentication/fetchExternalAuthentication',
      data: { 
        [csrfName]: csrfHash,
        authName: authName, 
        authType: authType,          
        authStatus: authStatus,
      },
      pages: 100
    })
  });
}

function clearFilters(){
  $('#searchName').val('');
  $('#searchType').val('');
  $('#searchStatus').val('');
  searchExternalAuth();
  
}
</script>

<style>
.filter-option { background-color: white; }
</style>
