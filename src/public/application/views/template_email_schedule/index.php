<!--
SW Serviços de Informática 2019

Listar Empresas 

Obs:
Agencias podem ver todos as suas empresas
Admin pode ver todas as empresas e agencias

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

  <?php $data['pageinfo'] = "application_manage";
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
        <?php if (in_array('createCompany', $user_permission)) : ?>
          <a href="<?php echo base_url('templateEmailSchedule/create') ?>" class="btn btn-primary"><?= 'Criar nova regra de disparo de template de e-mail' //$this->lang->line('application_add_company');
          ?></a>
          <br /> <br />
        <?php endif; ?>
      </div>
    </div>
    <div class="box">
      <div class="box-body">
        <div class="col-md-6">
          <label for="searchTitle" class="normal"><?= $this->lang->line('application_template_email_filter'); ?></label>
          <div class="input-group">
            <input type="search" id="searchTitle" oninput="searchTemplate()" class="form-control" placeholder="<?= $this->lang->line('application_template_email_filter'); ?>" aria-label="Search" aria-describedby="basic-addon1">
            <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
          </div>
        </div>
        <div class="col-md-6">
          <label for="searchSubject" class="normal">&nbsp;</label>
          <div class="input-group">
            <input type="search" id="searchSubject" oninput="searchTemplate()" class="form-control" placeholder="<?= $this->lang->line('application_template_email_subject'); ?>" aria-label="Search" aria-describedby="basic-addon1">
            <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="input-group">
            <label for="searchRules" class="normal"><?= $this->lang->line('application_template_select_rule'); ?></label>
            <select class="form-control" id="search_rules" onchange="searchTemplate()">
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="input-group">
            <label for="searchStatus" class="normal"><?= $this->lang->line('application_status'); ?></label>
            <select class="form-control" id="search_status" onchange="searchTemplate()">
              <option value=""><?= $this->lang->line('application_select') ?></option>
              <option value="1"><?= $this->lang->line('application_active') ?></option>
              <option value="0"><?= $this->lang->line('application_inactive') ?></option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
            <label class="normal" style="display: block;">&nbsp; </label>
            <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
        </div>
      </div>
    </div>
    <div class="box">
      <div class="box-body">
        <table id="manageTable" class="table table-bordered table-striped" cellspacing="0" style="border-collapse: collapse; width: 100%;">
          <thead>
            <tr>
              <th><?= $this->lang->line('application_trigger'); ?></th>
              <th><?= $this->lang->line('application_template_email'); ?></th>
              <th><?= $this->lang->line('application_subject'); ?></th>
              <th><?= $this->lang->line('application_status'); ?></th>
              <th style="width:140px;"><?= $this->lang->line('application_action'); ?></th>
            </tr>
          </thead>
        </table>
      </div>
      <!-- /.box-body -->
    </div>
</section>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
  var manageTable;
  var base_url = "<?php echo base_url(); ?>";

  $(document).ready(function() {
    // $("#mainCompanyNav").addClass('active');
    // $("#manageCompanyNav").addClass('active');
    searchTemplate();
    searchTrigger();
  });


  function searchTemplate() {
    let title = $('#searchTitle').val();
    let subject = $('#searchSubject').val();
    let rule = $('#search_rules').val();
    let status = $('#search_status').val();
    let buttons = '';
    debugger
    if (typeof manageTable === 'object' && manageTable !== null) {
      manageTable.destroy();
    }
    manageTable = $('#manageTable').DataTable({
      "language": {
        "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"
      },
      "processing": true,
      "serverSide": true,
      "scrollX": true,
      "sortable": true,
      "searching": true,
      "serverMethod": "post",
      "ajax": $.fn.dataTable.pipeline({
        url: base_url + 'templateEmailSchedule/getTemplatesScheduleData',
        data: {
          rule,
          title,
          subject,
          status
        },
        pages: 2
      })
    });
  }

  function searchTrigger() {
    $.ajax({
      url: base_url + 'templateEmailSchedule/fetchTemplatesScheduleRulesData',
      type: 'post',
      dataType: 'json',
      success: function(response) {
        console.log(response)
        var sel = $("#search_rules");
        sel.empty();
        sel.append('<option value="">' + '<?= $this->lang->line('application_template_select_trigger') ?>' + '</option>');
        for (var i = 0; i < response.length; i++) {

          sel.append('<option value="' + response[i].id + '">' + response[i].name + '</option>');
        }
      }
    });
  }

  function clearFilters() {
    $('#searchTitle').val('');
    $('#searchSubject').val('');
    $('#search_rules').val('');
    $('#search_status').val('');
    searchTemplate();
  }
</script>