<!--
SW Serviços de Informática 2019

Index de Categorias 
Add, Edit & delete

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
            <?php if ($this->session->flashdata('success') !== 'create_success' && $this->session->flashdata('success') !== 'update_success'): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>
        
        <?php elseif($this->session->flashdata('error')): ?>
            <div class="alert alert-error alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <?php if(in_array('createCategory', $user_permission)): ?>
          <a href="<?php echo base_url() ?>category/create_new" button class="btn btn-primary"><?=$this->lang->line('application_add_category');?></a>
          <button type="button" id="btnExcel" name="btnExcel" class="btn btn-success"><?=$this->lang->line('application_export_category');?></button>
          <br /> <br />
        <?php endif; ?>
    
          <div class="callout callout-warning">
              <h4>Atenção!</h4>
              <p>Valores da coluna <strong>Qtd Produtos</strong> são atualizados 4 vezes por dia: 08h, 12h, 16h e 20h. <br>Caso precise ver o valor atualizado da quantidade de produtos na categoria, clique sobre o ícone &nbsp;&nbsp;<i class="fa fa-pencil font-weight-bold" aria-hidden="true"></i>&nbsp;&nbsp; e visualize no campo <strong>Qtd Produtos</strong>.</p>
          </div>
        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
             <!--- 	<th><?=$this->lang->line('application_id');?></th> --->
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <th><?=$this->lang->line('application_volume_type');?></th>
                <th><?=$this->lang->line('application_marketplace');?></th>
                <th><?=$this->lang->line('application_qtd_products');?></th>
                <?php if(in_array('updateCategory', $user_permission) || in_array('deleteCategory', $user_permission)): ?>
                  <th width="120px"><?=$this->lang->line('application_action');?></th>
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

<?php if(in_array('createCategory', $user_permission)): ?>
<!-- create brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="addModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_add_category');?></h4>
      </div>

      <form role="form" action="<?php echo base_url('category/create') ?>" method="post" id="createForm">

        <div class="modal-body">
            <div class="row">
              <div class="form-group col-md-12">
                <label for="brand_name"><?=$this->lang->line('application_name');?></label>
                <input type="text" class="form-control" id="category_name" name="category_name" placeholder="<?=$this->lang->line('application_enter_category_name')?>" autocomplete="off">
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="active"><?=$this->lang->line('application_status');?></label>
                <select class="form-control" id="active" name="active">
                  <option value="1"><?=$this->lang->line('application_active');?></option>
                  <option value="2"><?=$this->lang->line('application_inactive');?></option>
                </select>
              </div>
              <div class="form-group col-md-6">
                <label for="cross_docking"><?=$this->lang->line('application_cross_docking_in_days');?></label>
                <input type="number" class="form-control" id="cross_docking" name="cross_docking" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_cross_docking_in_days');?>">
                <small><?=$this->lang->line('messages_alert_limit_cross_docking');?></small>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="volume_type"><?=$this->lang->line('application_volume_type');?></label>
                <select class="form-control" id="tipo_volume" name="tipo_volume">
                    <option value=""><?=$this->lang->line('application_select');?></option>
                    <?php foreach ($tipos_volumes as $tipo_volume): ?>
                      <option value="<?php echo trim($tipo_volume['id']); ?>"><?php echo trim($tipo_volume['produto']).'('.trim($tipo_volume['codigo']).')'; ?></option>
                    <?php endforeach ?>
                </select>
              </div>
                <div class="form-group col-md-6">
                <label for="cross_docking"><?=$this->lang->line('application_cross_docking_in_days');?></label>
                <input type="number" class="form-control" id="cross_docking" name="cross_docking" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_cross_docking_in_days');?>">
                <small><?=$this->lang->line('messages_alert_limit_cross_docking');?></small>
              </div>
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

<?php if(in_array('updateCategory', $user_permission)): ?>
<!-- edit brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="editModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_edit_category');?></h4>
      </div>

      <form role="form" action="<?php echo base_url('category/update') ?>" method="post" id="updateForm">

        <div class="modal-body">
          <div id="messages"></div>

            <div class="row">
              <div class="form-group col-md-12">
                <label for="edit_brand_name"><?=$this->lang->line('application_name');?></label>
                <input type="text" class="form-control" id="edit_category_name" name="edit_category_name" placeholder="<?=$this->lang->line('application_enter_category_name')?>" autocomplete="off">
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="edit_active"><?=$this->lang->line('application_status');?></label>
                <select class="form-control" id="edit_active" name="edit_active">
                  <option value="1"><?=$this->lang->line('application_active');?></option>
                  <option value="2"><?=$this->lang->line('application_inactive');?></option>
                </select>
              </div>
              <div class="form-group col-md-6">
                <label for="edit_cross_docking"><?=$this->lang->line('application_cross_docking_in_days');?></label>
                <input type="number" class="form-control" id="edit_cross_docking" name="edit_cross_docking" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_cross_docking_in_days');?>" min="1">
                <small><?=$this->lang->line('messages_alert_limit_cross_docking');?></small>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="volume_type"><?=$this->lang->line('application_volume_type');?></label>
                <select class="form-control" id="edit_tipo_volume" name="edit_tipo_volume">
                    <option value=""><?=$this->lang->line('application_select');?></option>
                    <?php foreach ($tipos_volumes as $tipo_volume): ?>
                      <option value="<?php echo trim($tipo_volume['id']); ?>" ><?php echo trim($tipo_volume['produto']).'('.trim($tipo_volume['codigo']).')'; ?></option>
                    <?php endforeach ?>
                </select>
              </div>
              <div class="form-group col-md-6">
                <label for="edit_cross_docking"><?=$this->lang->line('application_qtd_products')?></label>
                <input type="text" class="form-control" id="qtd_products" name="qtd_products" readonly>
              </div>
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

<?php if(in_array('deleteCategory', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_category');?><span id="deletecategoryname"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('category/remove') ?>" method="post" id="removeForm">
        <div class="modal-body">
          <p><?=$this->lang->line('messages_delete_message_confirm');?></p>
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

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

    <?php if ($this->session->flashdata('success') === 'create_success'): ?>
        Swal.fire({
            icon: 'success',
            title: '<?=$this->lang->line('messages_successfully_created')?>'
        })
    <?php endif; ?>

    <?php if ($this->session->flashdata('success') === 'update_success'): ?>
        Swal.fire({
            icon: 'success',
            title: '<?=$this->lang->line('messages_successfully_updated')?>'
        })
    <?php endif; ?>

  $("#mainCategoryNav").addClass('active');
  $("#manageCategoryNav").addClass('active');
  
  // initialize the datatable 
   manageTable = $('#manageTable').DataTable({
 	"language": {  "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
     "processing": true,
     "scrollX": true,
     'ajax': base_url +'category/fetchCategoryData',
     'order': []
   });
	
 // manageTable = $('#manageTable').DataTable({
 //   "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
 //   "processing": true,
 //   "serverSide": true,
 //    "scrollX": true,
 //   "sortable": true,
 //   "searching": true,
 //   "serverMethod": "post",
 //   "ajax": $.fn.dataTable.pipeline({
 //     url: base_url + 'category/fetchCategoryData',
 //     pages: 2 // number of pages to cache
 //   })
 // });


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
          $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
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

  $("#btnExcel").click(function(){

    var path_file = 'assets/images/categoriesMapping.xlsx';
    window.open(base_url.concat(path_file),'_blank');
    
  });
});

// edit function
function editFunc(id)
{ 
  $.ajax({
    url: 'fetchCategoryDataById/'+id,
    type: 'post',
    dataType: 'json',
    success:function(response) {

      $("#edit_category_name").val(response.name);
      $("#edit_cross_docking").val(response.days_cross_docking);
      $("#edit_active").val(response.active);
      $("#edit_tipo_volume").val(response.tipo_volume_id);
      $("#qtd_products").val(response.qtd_products);

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
              $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
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

// remove functions 
function removeFunc(id,name)
{
  if(id) {
	document.getElementById("deletecategoryname").innerHTML= ': '+name;  
    $("#removeForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: { category_id:id }, 
        dataType: 'json',
        success:function(response) {

          manageTable.ajax.reload(null, false); 

          if(response.success === true) {
            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
            '</div>');

            // hide the modal
            $("#removeModal").modal('hide');

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
