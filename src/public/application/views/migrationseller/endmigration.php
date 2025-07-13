<div class="content-wrapper">

<?php $data['pageinfo'] = "application_migration_seller";
$this->load->view('templates/content_header', $data);
?>
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
      <div class="box">
        <div class="box-body">
          <h4><?= $this->lang->line('application_migration_seller_end'); ?></h4>
          <form method="post" action="<?php echo base_url('MigrationSeller/endMigrationSeller')?>"action="" id="formMigrationSeller">
            <div class="row">
              <div class="col-md-4">
                <label for="selectcompany"><?= $this->lang->line('application_company'); ?>:*</label>
                <select class="form-control" id="company_id" name="company_id" required>
                  <option selected disabled value=""><?= $this->lang->line('application_select'); ?></option>
                  <?php foreach ($empresas as $empresa) {
                    $enable = $defenable;
                    if ($empresa['id'] == $this->session->flashdata('company_id')) {
                      $enable = "";
                    }
                  ?>
                    <option <?php echo $enable; ?> value="<?php echo $empresa['id']; ?>" <?= set_select('company_id', $empresa['id'], $empresa['id'] == $this->session->flashdata('company_id')) ?>><?php echo $empresa['name'] ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="col-md-3">
                <label for="selectstore"><?= $this->lang->line('application_store'); ?>:*</label>
                <select class="form-control" id="selectstore" name="selectstore" onchange="getMigration()" required>
                  <option value=""><?= $this->lang->line('application_select'); ?></option>
                </select>
              </div>
              <div class="col-md-3">
                <label for="select_marketplace"><?= $this->lang->line('application_migration_seller_data'); ?>*</label>
                    <input type="text" class="form-control" id="end_migration_date" name="end_migration_date" required>
                    <!-- <div class="input-group-addon">
                        <span class="glyphicon glyphicon-th"></span>
                    </div> -->
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <input type="hidden" class="form-control" id="seller_id" name="seller_id">
                <input type="hidden" class="form-control" id="seller_id" name="seller_id" value="<?= $migration[0]['seller_id'] ?>" >
              </div>
              <div class="col-md-12 mt-5">
                <div class="callout alert-warning text-white ">
                  <h5 class="text-center"><?= $this->lang->line('application_migration_seller_finix_title'); ?></h5>
                  <h5 class="text-left"><?= $this->lang->line('application_migration_seller_finix_msg'); ?></h5>
                </div>
              </div>
            </div>
            <div class="row inputSubmit">
              <div class="form-group col-md-12">
                <div class="col-md-4 pull-right">
                  <button type="submit" class="btn btn-primary col-md-12" id="startMigration"><?= $this->lang->line('application_migration_seller_end'); ?></button>
                </div>
              </div>
            </div>
            <div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
</div>
<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script src="<?php echo base_url('assets/plugins/timepicker/bootstrap-datepicker.pt-BR.min.js') ?>"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";
var company_id = null;
var stores = null; //
var data = null; //
var date = new Date();
$(document).ready(function() {
  $("#end_migration_date").datepicker({
    format: "dd/mm/yyyy",
    autoclose: true,
    startDate: '+1d',
    daysOfWeekDisabled: [0,6]
  });
  $("#migrationSeller").addClass('active');
  $('#company_id').change(function() {
    company_id = $('#company_id').val();
    getStores(company_id);
  });
  $('#selectstore').change(function() {
    stores = $('#selectstore').val();
  });
});

function getStores(company_id) {
  var options = $("#selectstore");
  $('#selectstore').empty().append($('<option>', {
        value: "",
        text: "Selecione..."
      }));
  $.getJSON(base_url + 'MigrationSeller/fetchCompanyStoresOnMigration/' + company_id, (data) => {
    console.log(data["length"])
    if(data["length"] == 0){
      location.reload();
    }
    $.each(data, function(i, value) {
      options.append($('<option>', {
        value: value.store_id,
        text: value.name
      }));
    });
  });
}
</script>