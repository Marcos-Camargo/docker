<!--
SW Serviços de Informática 2019

Atributos 
Add

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_add_values";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div class="box">
          <div class="box-body">
            <h4><?=$this->lang->line('application_attribute_name');?>: <?php echo $attribute_data['name']; ?></h4>
          </div>
        </div>

        <div class="messages"></div>

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

        <?php //if(in_array('createGroup', $user_permission)): ?>
          <button class="btn btn-primary" data-toggle="modal" data-target="#addModal"><?=$this->lang->line('application_add_value');?></button>
          <br /> <br />
        <?php //endif; ?>


        <div class="box">
          <div class="box-header">
            <h3 class="box-title"><?=$this->lang->line('application_values');?></h3>
          </div>
          <!-- /.box-header -->
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_commission_charges');?>?</th>
                <th><?=$this->lang->line('application_default_reason');?>?</th>
                <th><?=$this->lang->line('application_action');?></th>
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


<!-- create brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="addModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <form role="form" action="<?php echo base_url('attributes/createValue') ?>" method="post" id="createForm">

        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title"><?=$this->lang->line('application_add_value');?></h4>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
            <input type="hidden" name="attribute_parent_id" id="attribute_parent_id" value="<?php echo $attribute_data['id']; ?>">

            <label for="brand_name"><?=$this->lang->line('application_value');?></label>
            <input type="text" class="form-control" id="attribute_value_name" name="attribute_value_name" placeholder="<?=$this->lang->line('application_enter_attribute_value')?>" autocomplete="off">
            <br>
            <input type="checkbox" class="form-check-input" id="ck_commission_charges" name="ck_commission_charges">
            <label><?=$this->lang->line('application_commission_charges_check_label');?> </label>
            <br>
            <input type="checkbox" class="form-check-input" id="ck_default_reason" name="ck_default_reason">
            <label><?=$this->lang->line('application_default_reason_check_label');?> </label>

          </div>

          <div class="callout callout-danger" id="divAlertaCriacaoPadrao">
            <h4><i class="icon fa fa-ban"></i> Atenção!</h4>
            <p>Ao selecionar esta opção como Padrão, o atual motivo será substituido por esse.</p>
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

<!-- edit brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="editModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_edit_value');?></h4>
      </div>

      <form role="form" action="<?php echo base_url('attributes/updateValue') ?>" method="post" id="updateForm">
        <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
        <div class="modal-body">
          <div id="messages"></div>

          <div class="form-group">
            <label for="edit_brand_name"><?=$this->lang->line('application_value');?></label>
            <input type="text" class="form-control" id="edit_attribute_value_name" name="edit_attribute_value_name" placeholder="<?=$this->lang->line('application_enter_attribute_value')?>" autocomplete="off">
            <br>
            <input type="checkbox" class="form-check-input" id="edit_ck_commission_charges" name="edit_ck_commission_charges">
            <label><?=$this->lang->line('application_commission_charges_check_label');?> </label>
            <br> 
            <input type="checkbox" class="form-check-input" id="edit_ck_default_reason" name="edit_ck_default_reason">
            <label><?=$this->lang->line('application_default_reason_check_label');?> </label>
          </div>
          <div class="callout callout-danger" id="divAlertaEdicaoPadrao">
              <h4><i class="icon fa fa-ban"></i> Atenção!</h4>
              <p>Ao selecionar esta opção como Padrão, o atual motivo será substituido por esse.</p>
          </div>
        </div>

        <div class="modal-footer">
          <input type="hidden" name="attribute_parent_id" id="attribute_parent_id" value="<?php echo $attribute_data['id']; ?>">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
        </div>

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_value');?><span id="deletevalue"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('attributes/removeValue') ?>" method="post" id="removeForm">
      	<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
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

<script type="text/javascript">
  
var manageTable;
var base_url = "<?php echo base_url(); ?>";

// para csrf 
var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
$(document).ready(function() {

  $("#attributeNav").addClass('active');

  $("#divAlertaCriacaoPadrao").hide();
  $("#divAlertaEdicaoPadrao").hide();


  $('#ck_default_reason').change(function() {
    if(this.checked) {
      $("#divAlertaCriacaoPadrao").show();
    }else{
      $("#divAlertaCriacaoPadrao").hide();
    }      
  });

  $('#edit_ck_default_reason').change(function() {
    if(this.checked) {
      $("#divAlertaEdicaoPadrao").show();
    }else{
      $("#divAlertaEdicaoPadrao").hide();
    }      
  });

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url+'attributes/fetchAttributeValueData/'+<?php echo $attribute_data['id']; ?>,
    'order': []
  });

  // submit the create from 
  $("#createForm").unbind('submit').on('submit', function() {
    
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
          $(".messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
          '</div>');


          // hide the modal
          $("#addModal").modal('hide');

          // reset the form
          $("#createForm")[0].reset();
          $("#createForm .form-group").removeClass('has-error').removeClass('has-success');

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
            $(".messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
            '</div>');
          }
        }
      }
    }); 

    return false;
  });

});

// edit function
// id => attribute value id
function editFunc(id)
{ 

  $("#edit_ck_commission_charges").prop( "checked", false );
  $("#edit_ck_default_reason").prop( "checked", false );
  $("#divAlertaEdicaoPadrao").hide();

  $.ajax({
    url: base_url+'attributes/fetchAttributeValueById/'+id,
    type: 'post',
    dataType: 'json',
    data: { [csrfName]: csrfHash },
    success:function(response) {
       
      var edt_commission_charges = response.commission_charges;
      var edt_default_reason = response.default_reason;
      
      if(edt_commission_charges == 0){
        $("#edit_ck_commission_charges").prop( "checked", true );
      }

      if(edt_default_reason == 1){
        $("#edit_ck_default_reason").prop( "checked", true );
        $("#divAlertaEdicaoPadrao").show();
      }

      $("#edit_attribute_value_name").val(response.value);
      
      
      // submit the edit from 
      $("#updateForm").unbind('submit').bind('submit', function() {
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
              $(".messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');


              // hide the modal
              $("#editModal").modal('hide');
              // reset the form 
              $("#updateForm .form-group").removeClass('has-error').removeClass('has-success');

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
                $(".messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
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

// remove functions 
function removeFunc(id,name)
{
  if(id) {
	document.getElementById("deletevalue").innerHTML= ': '+name;  
    $("#removeForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: { attribute_value_id:id }, 
        dataType: 'json',
        success:function(response) {

          manageTable.ajax.reload(null, false); 

          if(response.success === true) {
            $(".messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
            '</div>');

            // hide the modal
            $("#removeModal").modal('hide');

          } else {

            $(".messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
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
