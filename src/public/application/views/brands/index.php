<!--
SW Serviços de Informática 2019

Index de Marcas/Fabricantes 
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

        <?php if(in_array('createBrand', $user_permission)): ?>
          <button class="btn btn-primary" data-toggle="modal" data-target="#addBrandModal"><?=$this->lang->line('application_add_brand');?></button>
          <a class="pull-right btn btn-primary" href="<?php echo base_url('export/FabricantesXls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_brands_export');?></a>
          <br /> <br />
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-striped table-hover responsive display table-condensed"  style="border-collapse: collapse; width: 99%; border-spacing: 0; ">
              <thead>
              <tr>
              	<th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <?php if(in_array('updateBrand', $user_permission) || in_array('deleteBrand', $user_permission)): ?>
                  <th><?=$this->lang->line('application_action');?></th>
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

<?php if(in_array('createBrand', $user_permission)): ?>
<!-- create brand modal -->
<div class="modal fade"  tabindex="-1" role="dialog" id="addBrandModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_add_brand');?></h4>
      </div>

      <form aria-label="Brands Create" role="form" action="<?php echo base_url('brands/create') ?>" method="post" id="createBrandForm">
		<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
        <div class="modal-body">

          <div class="form-group">
            <label for="brand_name"><?=$this->lang->line('application_name');?></label>
            <input type="text" class="form-control" id="brand_name" name="brand_name" placeholder="<?=$this->lang->line('application_enter_brand_name')?>" autocomplete="off">
            <p id="brandNameDanger" class="text-danger d-none"><?=$this->lang->line('application_brand_required_name')?></p>
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
          <button type="submit" class="btn btn-primary" id="btnCreateBrand"><?=$this->lang->line('application_save');?></button>
        </div>

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('updateBrand', $user_permission)): ?>
<!-- edit brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="editBrandModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_edit_brand');?></h4>
      </div>

      <form role="form" aria-label="Brands Update" action="<?php echo base_url('brands/update') ?>" method="post" id="updateBrandForm">
		<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
        <div class="modal-body">
          <div id="messages"></div>

          <div class="form-group">
            <label for="edit_brand_name"><?=$this->lang->line('application_name');?></label>
            <input type="text" class="form-control" id="edit_brand_name" name="edit_brand_name" placeholder="<?=$this->lang->line('application_enter_brand_name')?>" autocomplete="off">
            <p id="editBrandNameDanger" class="text-danger d-none"><?=$this->lang->line('application_brand_required_name')?></p>
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
          <button type="submit" id="btnEditBrand" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
        </div>

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('deleteBrand', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeBrandModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_brand');?><span id="deletebrandname"></span></h4>
      </div>

      <form role="form" aria-label="Brands Remove" action="<?php echo base_url('brands/remove') ?>" method="post" id="removeBrandForm">
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
<?php endif; ?>

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

// para csrf 
var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';

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
  
  $("#brandNav").addClass('active');

  manageTable = $('#manageTable').DataTable({
        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
        "processing": true,
        "serverSide": true,
        "sortable": true,
        "searching": true,
        "scrollX": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline({
            url: base_url + 'brands/fetchBrandData',
            pages: 2, // number of pages to cache
            data: { [csrfName]: csrfHash }
        } )
    });
    
    
  // submit the create from 
  $("#btnCreateBrand").click(function() {
    var form = $(this);

    if ($('#brand_name').val() === '') {
        $('#brand_name').css('border-color', '#dd4b39');
        $('#brandNameDanger').show();

        return false;
    }

    if ($('#brand_name').val() !== '') {
        $('#brand_name').css('border-color', '#d2d6de');
        $('#brandNameDanger').hide();
    }
  });

  $("#btnEditBrand").click(function() {
    if ($('#edit_brand_name').val() === '') {
        $('#edit_brand_name').css('border-color', '#dd4b39');
        $('#editBrandNameDanger').show();
        return false;
    }
    
    if ($('#edit_brand_name').val() !== '') {
        $('#edit_brand_name').css('border-color', '#d2d6de');
        $('#editBrandNameDanger').hide();
    }
  });

  $("#btnExcel").click(function(){

    var path_file = 'assets/images/brandsMapping.xlsx';
    window.open(base_url.concat(path_file),'_blank');

  });
});

function editBrand(id)
{ 
  var dataJson = { [csrfName]: csrfHash};

  $('#updateBrandForm button[type="submit"]').prop('disabled', true);

  $('#updateBrandForm').attr('action', base_url + 'brands/update/' + id);

  $.ajax({
    url: base_url + 'brands/fetchBrandDataById/' + id,
    type: 'post',
    dataType: 'json',
    data: dataJson,  
    success:function(response) {
      $("#edit_brand_name").val(response.name);
      $("#edit_active").val(response.active);

      $('#updateBrandForm button[type="submit"]').prop('disabled', false);
    }
  });
}

function removeBrand(id,name)
{
  if(id) {
	document.getElementById("deletebrandname").innerHTML= ': '+name;  
    $("#removeBrandForm").on('submit', function() {

      var form = $(this);
	  var dataJson = { [csrfName]: csrfHash, brand_id:id };	
      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: dataJson, 
        dataType: 'json',
        success:function(response) {

          if(response.success === true) {
            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
            '</div>');

            // hide the modal
            $("#removeBrandModal").modal('hide');

            Swal.fire({
                icon: 'success',
                title: response.messages
            }).then(function() {
              window.location.reload();
            });

          } else {
            
            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
            '</div>'); 
          }
        },
        error: function (jqXHR, exception) {
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connect.\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else if (exception === 'parsererror') {
                msg = 'Requested JSON parse failed.';
            } else if (exception === 'timeout') {
                msg = 'Time out error.';
            } else if (exception === 'abort') {
                msg = 'Ajax request aborted.';
            } else {
                msg = 'Uncaught Error.\n' + jqXHR.responseText;
            }
            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+msg+
            '</div>'); 
            $("#removeBrandModal").modal('hide');
        },

      }); 

      return false;
    });
  }
}


</script>
