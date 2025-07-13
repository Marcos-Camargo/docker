<!--
SW Serviços de Informática 2019

Listar Integracoes

Obs:
cada usuario so pode ver integracoes da sua empresa.
Agencias podem ver todos as integracoes das suas empresas
Admin pode ver integracoes de todas as empresas e agencias

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

        <?php if(in_array('createIntegrations', $user_permission)): ?>
          <a href="<?php echo base_url('integrations/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_integration');?></a>
          <br /> <br />
        <?php endif; ?>

        <div class="box">
          <div class="box-header">
            <h3 class="box-title"></h3>
          </div>
          <!-- /.box-header 
          <div class="box-body"> -->
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
              	<?php if($usergroup<5) { ?>  
              	<th><?=$this->lang->line('application_company');?></th>
              	<?php }   // endif onlyadmin ?> 
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_from');?></th>
                <th><?=$this->lang->line('application_to');?></th>
                <th><?=$this->lang->line('application_type');?></th>
                <th><?=$this->lang->line('application_active');?></th>
                <?php if(in_array('updateIntegrations', $user_permission) || in_array('deleteIntegrations', $user_permission)): ?>
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
    

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php if(in_array('deleteIntegration', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_integration');?><span id="deleteintegrationname"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('integrations/remove') ?>" method="post" id="removeForm">
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
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

    $("#mainIntegrationNav").addClass('active');
    $("#manageIntegrationNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
	"language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },
    'ajax': base_url + 'integrations/fetchIntegrationsData',
    'order': []
  });

});

// remove functions 
function removeFunc(id,name)
{
  if(id) {
	document.getElementById("deleteintegrationname").innerHTML= ': '+name;  
    $("#removeForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: { integration_id:id }, 
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
