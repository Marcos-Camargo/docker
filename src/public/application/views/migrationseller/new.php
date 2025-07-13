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
          <h4><?= $this->lang->line('application_select_seller_to_migration'); ?></h4>
          <form method="post" action="<?php echo base_url('MigrationSeller/startMigrationSeller')?>"action="" id="formMigrationSeller">
            <div class="row">
            <div class="col-md-12 mt-5">
                <div class="callout alert-warning text-white ">
                  <h5 class="text-center"><?= $this->lang->line('application_store_migration_start_alert'); ?></h5>
                </div>
              </div>
              <div class="col-md-5">
                <label for="selectcompany"><?= $this->lang->line('application_company'); ?>*</label>
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
              <div class="col-md-5">
                <label for="selectstore"><?= $this->lang->line('application_store'); ?>*</label>
                <select class="form-control" id="selectstore" name="selectstore" required>
                  <option selected disabled value=""><?= $this->lang->line('application_select'); ?></option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-5">
                <label for="seller_id"><?= $this->lang->line('application_seller_id'); ?>*</label>
                <input type="text" class="form-control" id="seller_id" name="seller_id">
              </div>
                <div class="col-md-5">
                  <label for="select_marketplace"><?= $this->lang->line('application_marketplace'); ?>*</label>
                  <select class="form-control" id="int_to" name="int_to" required>
                    <option selected disabled value=""><?= $this->lang->line('application_select'); ?></option>
                    <?php foreach ($integrations as $integration) {
                      $enable = $defenable;
                      if ($integration['int_to'] == $this->session->flashdata('int_to')) {
                        $enable = "";
                      }
                    ?>
                      <option <?php echo $enable; ?> value="<?php echo $integration['int_to']; ?>" <?= set_select('int_to', $integration['int_to'], $integration['int_to'] == $this->session->flashdata('int_to')) ?>><?php echo $integration['int_to'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12  pt-4">
                  <div class="callout bg-primary text-white text-center">
                    <h5><?= $this->lang->line('application_migration_seller_attention_msg'); ?></h5>
                  </div>
                </div>
              </div>
              <div class="row inputSubmit">
                <div class="form-group col-md-12 pt-2">
                  <div class="col-md-4 pull-right">
                    <button type="submit" class="btn btn-primary col-md-12" id="startMigration"><?= $this->lang->line('application_start_migration_seller'); ?></button>
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
<script type="text/javascript">
  var manageTable;
  var base_url = "<?php echo base_url(); ?>";
  var company_id = null;
  var stores = null; //
  var data = null; //
  var int_to = null; //
  var seller_id = null; //

  $(document).ready(function() {
    $("#migrationSeller").addClass('active');
  });
  $('#company_id').change(function() {
    company_id = $('#company_id').val();
    getStores(company_id);
  });


  function getStores(company_id) {
    var options = $("#selectstore");
    options.empty();
    options.append($('<option>', {
      text: 'Selecione...'
    }))
    $.getJSON(base_url + 'MigrationSeller/fetchCompanyStores/' + company_id, (data) => {
      if(data.length == 0){
        Swal.fire({
          icon: 'error',
          title: 'Erro!',
          html: 'Não há lojas de migração para empresa selecionada.'
        }).then((result) => {
          enaBleBtn(true)
        })
      }
      $.each(data, function(i, value) {
        options.append($('<option>', {
          value: value.store_id,
          text: value.name
        }));
      });
    });
  }
  function enaBleBtn(status){
    $('#startMigration').attr('disabled', !status).text('<?= $this->lang->line('application_start_migration_seller') ?>')
  }
  var checked_seller = null;
  $('form').on('submit', function(event) {
    $('#startMigration').attr('disabled', true).text('Aguarde...');
    event.preventDefault();
    seller_id = $("#seller_id").val();
    int_to = $("#int_to").val();
    $.post(base_url + 'MigrationSeller/checkSellerIdVtex', {
      seller_id: seller_id,
      int_to: int_to
    }, function(data) {
      checked_seller = data;
      console.log(data);
      const obj = JSON.parse(checked_seller);
      const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          confirmButton: 'btn btn-success',
          cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
      })
      if(obj.error){
        Swal.fire({
          icon: 'error',
          title: 'Atenção!',
          html: 'O Seller ID informado incorreto ou inexistente na Vtex: '+ seller_id,
        }).then((result) => {
          enaBleBtn(true)
        })
      }else if(obj.integration_error){
        Swal.fire({
          icon: 'error',
          title: 'Atenção!',
          html: 'A integração Selecionada não é uma integração Vtex',
        }).then((result) => {
          enaBleBtn(true)
        })
      }else{
        Swal.fire({
          icon: 'info',
          title: 'Atenção!',
          html: 'O Seller ID informado é referente ao seguinte lojista na Vtex: ' + '<br>' + '<b>Nome da loja: </b>' + obj.name + '<br>' + '<b>CNPJ: </b>' + obj.taxCode.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5") + '<br>' + 'Deseja iniciar a migração?',
          showCancelButton: true,
          confirmButtonText: '<?= $this->lang->line('application_yes') ?>',
          cancelButtonText: '<?= $this->lang->line('application_no') ?>',
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
        }).then((result) => {
          if (!result.dismiss) {
            event.target.submit()
          } else if (result.dismiss) {
            enaBleBtn(true)
          }
        })
      }
    })
  });
</script>