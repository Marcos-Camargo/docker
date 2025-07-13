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

        <?php if(in_array('updateShopkeeperForm', $user_permission)): ?>
          <button class="btn btn-primary" data-toggle="modal" data-target="#addFieldModal"><?=$this->lang->line('application_add_field');?></button>
          <button class="btn btn-primary" onclick="titleHeader()" data-toggle="modal" data-target="#titleHeaderModal">Adicionar Titulo </button>
          <button class="btn btn-primary" onclick="addLogo()" data-toggle="modal" data-target="#logoModal">Adicionar Logo</button>
          <button class="btn btn-primary" onclick="SuccessDescription()" data-toggle="modal" data-target="#insertSuccessDescriptionModal">Adicionar Mensagem de Sucesso</button>
          <br /> <br />
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th style="width:65%;"><?=$this->lang->line('application_name');?></th>
                <th style="width:15%;"><?=$this->lang->line('application_type');?></th>
                <th style="width:10%;"><?=$this->lang->line('application_required');?></th>
                <th style="width:10%;"><?=$this->lang->line('application_visible');?></th>
                <?php if(in_array('updateShopkeeperForm', $user_permission)): ?>
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

<?php if(in_array('updateShopkeeperForm', $user_permission)): ?>
<!-- create Setting modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="addFieldModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_add_field');?></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/insert') ?>" method="post" id="createFieldForm">

        <div class="modal-body">

          <div class="form-group">
            <label for="field_name"><?=$this->lang->line('application_name');?></label>
            <input type="text" class="form-control" id="field_name" name="field_name" placeholder="<?=$this->lang->line('application_enter_setting_name');?>" autocomplete="off">
          </div>
          <div class="form-group">
            <label for="field_type"><?=$this->lang->line('application_type');?></label>
            <select class="form-control" id="field_type" name="field_type">
              <option value="1"><?=$this->lang->line('application_text');?></option>
              <option value="2"><?=$this->lang->line('application_yes_no');?></option>
              <option value="3"><?=$this->lang->line('application_attachment');?></option>
            </select>
          </div>
          <div class="form-group">
            <label for="field_required"><?=$this->lang->line('application_required');?></label>
            <select class="form-control" id="field_required" name="field_required">
              <option value="1"><?=$this->lang->line('application_active');?></option>
              <option value="2"><?=$this->lang->line('application_inactive');?></option>
            </select>
          </div>
          <div class="form-group">
            <label for="field_visible"><?=$this->lang->line('application_visible');?></label>
            <select class="form-control" id="field_visible" name="field_visible">
              <option value="1"><?=$this->lang->line('application_active');?></option>
              <option value="2"><?=$this->lang->line('application_inactive');?></option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
        </div>

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('updateShopkeeperForm', $user_permission)): ?>
<!-- edit Setting modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="editFieldModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_edit_setting');?></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/update') ?>" method="post" id="updateFieldForm">

        <div class="modal-body">
          <div id="messages"></div>

          <div class="form-group">
            <label for="edit_label"><?=$this->lang->line('application_name');?></label>
            <input type="text" class="form-control" id="edit_label" name="edit_label" placeholder="<?=$this->lang->line('application_enter_setting_name');?>" autocomplete="off">
          </div>
          <div class="form-group">
            <label for="edit_type"><?=$this->lang->line('application_type');?></label>
            <select class="form-control" id="edit_type" name="edit_type">
              <option value="1"><?=$this->lang->line('application_text');?></option>
              <option value="2"><?=$this->lang->line('application_yes_no');?></option>
              <option value="3"><?=$this->lang->line('application_attachment');?></option>
            </select>
          </div>
          <div class="form-group">
            <label for="edit_required"><?=$this->lang->line('application_required');?></label>
            <select class="form-control" id="edit_required" name="edit_required">
              <option value="1"><?=$this->lang->line('application_active');?></option>
              <option value="2"><?=$this->lang->line('application_inactive');?></option>
            </select>
          </div>
          <div class="form-group">
            <label for="edit_visible"><?=$this->lang->line('application_visible');?></label>
            <select class="form-control" id="edit_visible" name="edit_visible">
              <option value="1"><?=$this->lang->line('application_active');?></option>
              <option value="2"><?=$this->lang->line('application_inactive');?></option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
        </div>

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('updateShopkeeperForm', $user_permission)): ?>
<!-- remove Setting modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeSettingModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="deletesettingname"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/remove') ?>" method="post" id="removeSettingForm">
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


<div class="modal fade" tabindex="-1" role="dialog" id="titleHeaderModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="addtitleHeaderForm"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/insertTitleHeader') ?>" method="post" id="titleHeaderForm">
        <div class="modal-body">
            <div class="form-group col-md-12 col-xs-12">
                <label for="description">Titulo do cabecalho(*)</label>
                <textarea type="text" class="form-control" id="description" maxlength="1000" name="header_description" placeholder="titulo cabecalho"></textarea>
                <span id="char_description"></span><br />
                <span class="label label-warning" id="words_description" data-toggle="tooltip" data-placement="top" title="titulo cabecalho"></span>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="logoModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="modallogoForm"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/insertLogoForm') ?>" method="post" id="logoForm">
        <div class="modal-body">
            <div class="form-group col-md-12 col-xs-12">
                <label class="custom-file-label" for="logotipo_header">Upload logotipo do cabecalho:</label>                                
                <input type="file" class="custom-file-input" id="logotipo_form" name="logotipo_header">                                    
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="insertSuccessDescriptionModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="SuccessDescriptionForm"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/insertSuccessDescription') ?>" method="post" id="addSuccessDescriptionForm">
        <div class="modal-body">
            <div class="form-group col-md-12 col-xs-12">
            <label for="description"><?=$this->lang->line('application_success_description');?>(*)</label>
                <textarea type="text" class="form-control" id="description" maxlength="1000" name="success_description" ><?=set_value('success_description', $results["success_description"])?></textarea>
                <span class="label label-warning" id="words_description" data-toggle="tooltip" data-placement="top" ></span>
                
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->




<script type="text/javascript">
var manageTable;
var base_url = "<?=base_url(); ?>";

$(document).ready(function() {

  $("#manageShopkeeperformNav").addClass('active');
  $("#mainShopkeeperformNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
	"language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },	  
    "scrollX": true,
    'ajax': base_url + 'ShopkeeperForm/fetchShopkeeperformData',
    'order': []
  });

  // submit the create from 
  $("#createFieldForm").unbind('submit').on('submit', function() {
    var form = $(this);

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
          $("#addFieldModal").modal('hide');

          // reset the form
          $("#createFieldForm")[0].reset();
          $("#createFieldForm .form-group").removeClass('has-error').removeClass('has-success');

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
      }, error: e => console.log(e)
    }); 

    return false;
  });

  $("#description").summernote({
			toolbar: [
				// [groupName, [list of button]]
				['style', ['bold', 'italic', 'underline', 'clear']],
				['view', ['fullscreen', 'codeview']]
			],
			height: 150,
			disableDragAndDrop: true,
			lang: 'pt-BR',
			shortcuts: false,
			callbacks: {
				onBlur: function(e) {
					//verifyWords();
				},
				onKeyup: function(e) {
					// var conteudo = $(".note-editable").text();
					var conteudo = $(".note-editable").html();
					var limit = $('#description').attr('maxlength');
					if (conteudo.length > limit) {
						// $(".note-editable").text(conteudo.slice(0,-(conteudo.length-limit)));
						$(".note-editable").html(conteudo.slice(0, -(conteudo.length - limit)));
					}
					//characterLimit(this);
				}
			}
		}); 
});

function SuccessDescription()
{
    $("#addSuccessDescriptionForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: { success_description: $('[name = "success_description"]',form).val() }, 
        dataType: 'json',
        success:function(response) {

          if(response.success === true) {
            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
            '</div>');

            // hide the modal
            $("#insertSuccessDescriptionModal").modal('hide');

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

function editField(id)
{ 
  $.ajax({
    url: base_url + 'ShopkeeperForm/fetchShopkeeperFormDataById/'+id,
    type: 'post', 
    dataType: 'json',
    success:function(response) {

      $("#edit_label").val(response.label);
      $("#edit_required").val(response.required);
      $("#edit_visible").val(response.visible);
      $("#edit_type").val(response.type);

      // submit the edit from 
      $("#updateFieldForm").unbind('submit').bind('submit', function() {
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
              $("#editFieldModal").modal('hide');
              // reset the form 
              $("#updateFieldForm .form-group").removeClass('has-error').removeClass('has-success');

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
}




function addLogo()
{
    $("#logoForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: new FormData($('#logoForm')[0]),
        cache: false,
        contentType: false,
        processData: false,
        success:function(response) {
          $("#logoModal").modal('hide');
          
            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
            '</div>');
        }
      }); 

      return false;
    });
}

function titleHeader()
{
  
    $("#titleHeaderForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: { header_description: $('[name = "header_description"]',form).val() }, 
        dataType: 'json',
        success:function(response) {

          if(response.success === true) {
            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
            '</div>');

            // hide the modal
            $("#titleHeaderModal").modal('hide');

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


</script>
