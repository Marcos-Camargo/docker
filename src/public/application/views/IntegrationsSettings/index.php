<div class="content-wrapper">

  <?php $data['pageinfo'] = "application_manage";
  $this->load->view('templates/content_header', $data); ?>

  <section class="content">
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

        <?php if (in_array('createIntegrationsSettings', $user_permission)) : ?>
          <a href="<?php echo base_url('IntegrationsSettings/create') ?>" class="btn btn-primary">Adicionar Integração</a>
          <br /> <br />
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
                <tr>
                  <th>id</th>
                  <th>Nome</th>
                  <th>Int_to</th>
                  <th>Curadoria</th>
                  <th>Active</th>
                  <th>Ação</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script type="text/javascript">
  var manageTable;
  var base_url = "<?php echo base_url(); ?>";
  $(document).ready(function() {

    $("#MainsettingNav").addClass('active menu-open');
    $("#IntegrationsettingNav").addClass('active');

    manageTable = $('#manageTable').DataTable({
      "serverSide": true,
      "sortable": true,
      "scrollX": true,
      "processing": true,
      "serverMethod": "post",
      "order": [ 0, 'desc' ],
      "ajax": $.fn.dataTable.pipeline({
        url: base_url + 'IntegrationsSettings/fetchData',
        data: {}
      }),
      "language": {
        "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"
      }
    });
  });

  function executeExample(id) {
    event.preventDefault();
    Swal.fire({
      title: 'Tem certeza que deseja remover essa integração?',
      text: "Essa ação não poderá ser desfeita!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Sim',
      cancelButtonText: 'Não',
    }).then((result) => {
      if (result.value === true) {
        window.location.href = '<?= base_url('IntegrationsSettings/remove/?id=') ?>' + id;
      }
    })
  }
</script>