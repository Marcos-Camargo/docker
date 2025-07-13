<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

  <?php $data['pageinfo'] = "application_manage";
  $this->load->view('templates/content_header', $data); 
  
  $template_data = json_encode($this->data['template_email_schedule_data']);
  $template_notification_data = json_encode($this->data['template_notification_trigger_rules_data']);
  $template_notification_data = json_decode($template_data);
  $template_data = json_decode($template_data);

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
          <div class="box-header">
                <h3 class="box-title"><?=$this->lang->line('application_edit_template_schedule');?></h3>
          </div>
          <form role="form" action="<?php echo base_url('templateEmailSchedule/update/'.$template_data->id) ?>" method="post"  id="createRuleForm">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" />
            <div class="box-body">
              <div class="row">
                <input type="hidden" id="template_id" name="template_id" value="<?=$template_data->id?>">
                <div class="form-group col-md-12">
                  <label for="template_email_id"><?= $this->lang->line('application_template_select_email'); ?>*</label>
                  <select class="form-control" id="template_email_id" name="template_email_id" onchange="chengeTemplate()">
                    <option selected disabled value=""><?= $this->lang->line('application_select'); ?></option>
                  </select>
                  <?php echo '<i style="color:red">' . form_error('template_email_id') . '</i>'; ?>
                </div>
              </div>
              <div class="row">
                <div class="form-group col-md-12">
                  <label for="template_email_rule_id"><?= $this->lang->line('application_template_select_rule'); ?>*</label>
                  <select class="form-control" id="template_email_rule_id"  value="" name="template_email_rule_id" onchange="chengeRule()">
                  </select>
                  <?php echo '<i style="color:red">' . form_error('template_email_rule_id') . '</i>'; ?>
                </div>
              </div>
              <div class="row">
                <div class="form-group col-md-12">
                  <label class="switch" for="template_email_rule_status"><?=$this->lang->line('application_status') . " :";?>
                  <input id="template_email_rule_status" name="template_email_rule_status" type="checkbox" checked data-toggle="toggle" data-on="Ativo" data-off="Inativo" data-onstyle="success" data-offstyle="danger" onclick="toggleChenge()">
                  </label>
                  <?php echo '<i style="color:red">' . form_error('template_email_rule_status') . '</i>'; ?>
                </div>
              </div>
            </div>

            <div class="box-footer">
            <?php if(in_array('updateStore', $this->permission)): ?>
                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
            <?php endif;?>
                <a href="<?php echo base_url('templateEmailSchedule/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
            </div>

          </form>

        </div>
      </div>
    </div>
  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
  var manageTable;
  var base_url = "<?php echo base_url(); ?>";
  var template_email_id = "<?php  echo $template_data->template_email_id; ?>";
  var template_data_rule_id = "<?php echo $template_data->notification_trigger_id; ?>";
  var template_email_status = "<?php echo $template_notification_data->status; ?>";
  $(document).ready(function() {
      // $("#mainCompanyNav").addClass('active');
      // $("#manageCompanyNav").addClass('active');
      searchTemplateEmail();
      searchRules();
      checkDanger();
      setStatus(template_email_status);
  });

  function searchTemplateEmail() {
    $.ajax({
      url: base_url + 'templateEmailSchedule/fetchTemplatesData',
      type: 'post',
      dataType: 'json',
      success: function(response) {
        var sel = $("#template_email_id");
        sel.empty();
        sel.append('<option value="">' + 'Selecione uma Template de e-mail' + '</option>');
        for (var i = 0; i < response.length; i++) {

          sel.append('<option value="' + response[i].id + '">' + response[i].title + '</option>');
          if(response[i].id === template_email_id){
            $("#template_email_id").val(response[i].id);
          }
        }
      }
    });
  }

  function searchRules() {
    $.ajax({
      url: base_url + 'templateEmailSchedule/fetchTemplatesScheduleRulesData',
      type: 'post',
      dataType: 'json',
      success: function(response) {
        var sel = $("#template_email_rule_id");
        sel.empty();
        sel.append('<option value="">' + 'Selecione um gatilho de disparo' + '</option>');
        for (var i = 0; i < response.length; i++) {

          sel.append('<option value="' + response[i].id + '">' + response[i].name + '</option>');
          if(response[i].id == template_data_rule_id){
            $("#template_email_rule_id").val(response[i].id);
          }
        }
      }
    });
  }

  function checkDanger(){
    const p = document.querySelector('p');
    if(p){
      $("#createRuleForm .form-group").addClass('has-error')
    }
  }
  function chengeTemplate(){
    $('#template_email_id').change(function() {

      $("#createRuleForm .form-group").removeClass('has-error')
    })
  }
  function chengeRule(){
    $('#template_email_rule_id').change(function() {

    $("#createRuleForm .form-group").removeClass('has-error')
    })
  }
  function setStatus(template_email_status) {
      if(template_email_status == 1){
        $("#template_email_rule_status").bootstrapToggle('on');
      }else{
        $("#template_email_rule_status").bootstrapToggle('off');
      }
  }
  $(function() {
    $('#template_email_rule_status').change(function() {
      $("#template_email_rule_status").val()
    });
  })
  function goBack() {
    window.location.href = base_url + 'templateEmailSchedule/index';
  }
</script>