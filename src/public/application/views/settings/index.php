<!--
SW Serviços de Informática 2019

Listar Settings
Add , Edit & Delete

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

        <?php if($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('success'); ?>
          </div>
        <?php elseif($this->session->flashdata('error')): ?>
          <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('error'); ?>
          </div>
        <?php endif; ?>

        <?php if(in_array('createConfig', $user_permission)): ?>
          <button class="btn btn-primary" data-toggle="modal" data-target="#addSettingModal"><?=$this->lang->line('application_add_setting');?></button>
          <br /> <br />
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th style="width:20%;"><?=$this->lang->line('application_name');?></th>
                <th style="width:70%;"><?=$this->lang->line('application_value');?></th>
                <th style="width:5%;"><?=$this->lang->line('application_status');?></th>
                <?php if(in_array('updateSetting', $user_permission) || in_array('deleteSetting', $user_permission)): ?>
                  <th style="width:5%;"><?=$this->lang->line('application_action');?></th>
                <?php endif; ?>
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

<?php if(in_array('createConfig', $user_permission)): ?>
<!-- create Setting modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="addSettingModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_add_setting');?></h4>
      </div>

      <form role="form" aria-label="Settings Create" action="<?php echo base_url('settings/create') ?>" method="post" id="createSettingForm">

        <div class="modal-body">

          <div class="form-group">
            <label for="setting_name"><?=$this->lang->line('application_name');?></label>
            <input type="text" class="form-control" id="setting_name" name="setting_name" placeholder="<?=$this->lang->line('application_enter_setting_name');?>" autocomplete="off">
          </div>
          <div class="form-group">
            <label for="setting_name"><?=$this->lang->line('application_value');?></label>
            <input type="text" class="form-control" id="setting_value" name="setting_value" placeholder="<?=$this->lang->line('application_enter_setting_value');?>" autocomplete="off">
          </div>
          <div class="form-group">
            <label for="active"><?=$this->lang->line('application_status');?></label>
            <select class="form-control" id="active" name="active">
              <option value="1"><?=$this->lang->line('application_active');?></option>
              <option value="2"><?=$this->lang->line('application_inactive');?></option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" id="create_save_button" name="create_save_button" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
        </div>

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('updateConfig', $user_permission)): ?>
<!-- edit Setting modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="editSettingModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_edit_setting');?></h4>
      </div>

      <form role="form" aria-label="Settings Update" action="<?php echo base_url('settings/update') ?>" method="post" id="updateSettingForm">

        <div class="modal-body">
          <div id="messages"></div>

          <div class="form-group">
            <label for="edit_setting_name"><?=$this->lang->line('application_name');?></label>
            <input type="text" class="form-control" id="edit_setting_name" name="edit_setting_name" placeholder="<?=$this->lang->line('application_enter_setting_name');?>" autocomplete="off">
          </div>
          <div class="form-group">
            <label for="edit_setting_value"><?=$this->lang->line('application_value');?></label>
            <input type="text" class="form-control" id="edit_setting_value" name="edit_setting_value" placeholder="<?=$this->lang->line('application_enter_setting_value');?>" autocomplete="off">
          </div>
          <div class="form-group">
            <label for="edit_active"><?=$this->lang->line('application_status');?></label>
            <select class="form-control" id="edit_active" name="edit_active">
              <option value="1"><?=$this->lang->line('application_active');?></option>
              <option value="2"><?=$this->lang->line('application_inactive');?></option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" id="edit_save_button" name="edit_save_button" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
        </div>
        <input type="hidden" name="edit_setting_name_old" id="edit_setting_name_old">

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('deleteConfig', $user_permission)): ?>
<!-- remove Setting modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeSettingModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="deletesettingname"></span></h4>
      </div>

      <form role="form" aria-label="Settings remove" action="<?php echo base_url('settings/remove') ?>" method="post" id="removeSettingForm">
        <div class="modal-body">
          <p><?=$this->lang->line('messages_delete_message_confirm');?></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>



<script type="text/javascript">
var manageTable;
var base_url = "<?= base_url() ?>";

$(document).ready(function() {

  $("#configNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
	"language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },	  
    "scrollX": true,
    'ajax': base_url + 'settings/fetchSettingData',
    'order': []
  });

  // submit the create from 
  $("#createSettingForm").unbind('submit').on('submit', function() {
    var form = $(this);

    $('#create_save_button').prop('disabled', true);

    // remove the text-danger
    $(".text-danger").remove();

    $.ajax({
      url: form.attr('action'),
      type: form.attr('method'),
      data: form.serialize(), // /converting the form data into array and sending it to server
      dataType: 'json',
      success:function(response) {
        
        manageTable.ajax.reload(null, false); 

        if(response.success === true) {
          $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
          '</div>');


          // hide the modal
          $("#addSettingModal").modal('hide');

          // reset the form
          $("#createSettingForm")[0].reset();
          $("#createSettingForm .form-group").removeClass('has-error').removeClass('has-success');

        } else {

          if(response.messages instanceof Object) {
            $.each(response.messages, function(index, value) {
              console.log(index+" "+value);
              var id = $("#"+index);

              id.closest('.form-group')
              .removeClass('has-error')
              .removeClass('has-success')
              .addClass(value.length > 0 ? 'has-error' : 'has-success');
              
              id.after(value);

            });
          } else {
            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
            '</div>');
          }
        }
      }, error: e => console.log(e)
    }); 
    $('#create_save_button').prop('disabled', false);

    return false;
  });


});

function editSetting(id)
{ 

  $('#edit_save_button').prop('disabled', true);
  $.ajax({
    url: 'fetchSettingDataById/'+id,
    type: 'post',
    dataType: 'json',
    success:function(response) {

      $("#edit_setting_name").val(response.name);
      $("#edit_setting_value").val(response.value);
      $("#edit_active").val(response.status);
      $("#edit_setting_name_old").val(response.name);

      // submit the edit from 
      $("#updateSettingForm").unbind('submit').bind('submit', function() {
        var form = $(this);

        // remove the text-danger
        $(".text-danger").remove();

        $.ajax({
          url: form.attr('action') + '/' + id,
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',
          success:function(response) {

            manageTable.ajax.reload(null, false); 

            if(response.success === true) {
              $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');


              // hide the modal
              $("#editSettingModal").modal('hide');
              // reset the form 
              $("#updateSettingForm .form-group").removeClass('has-error').removeClass('has-success');

            } else {

              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);

                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  
                  id.after(value);

                });
              } else {
                $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }
        }); 

        return false;
      });

    }
  });
  $('#edit_save_button').prop('disabled', false);
}

function removeSetting(id,name)
{
  if(id) {
	document.getElementById("deletesettingname").innerHTML= ': '+name;  
    $("#removeSettingForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: { setting_id:id }, 
        dataType: 'json',
        success:function(response) {

          manageTable.ajax.reload(null, false); 

          if(response.success === true) {
            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
            '</div>');

            // hide the modal
            $("#removeSettingModal").modal('hide');

          } else {

            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
            '</div>'); 
          }
        }
      }); 

      return false;
    });
  }
}


</script>
