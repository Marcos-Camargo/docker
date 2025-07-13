<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <?php $data['pageinfo'] = ""; $data['page_now'] ='legal_panel_fiscal'; $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

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
        
        <?php if(in_array('createLegalPanelFiscal', $user_permission)): ?>
          <button class="btn btn-primary" id="btn_novo_billet" name="btn_novo_billet"><?=$this->lang->line('application_create_notification');?></button>
          <br /> <br />
        <?php endif; ?>

          <div class="box">
              <div class="box-body">
                  <div class="mt-2">
                      <div class="row " style="display: flex; align-items: center;">
                          <div class="col-md-12">
                              <p style="float: left; margin-right: 10px; margin-top: 15px;"><i class="fa fa-filter fa-2x" title="Filtro"></i></p>
                              <form id="formFiltro" enctype="text/plain" style="float: left;">
                                  <div class="form-group">
                                      <label for="filter_start_date"><?= $this->lang->line('application_status'); ?></label>
                                      <select class="form-control" id="status" name="status">
                                          <option value="open">Chamado Aberto</option>
                                          <option value="closed">Chamado Fechado</option>
                                          <option value="all">Todos</option>
                                      </select>
                                  </div>
                              </form>
                              <div style="float: left; margin-left: 10px; margin-top: 25px;">
                                  <button class="btn btn-success" id="btnFilter">
                                      <i class="fa fa-filter"></i> <?=lang('application_filter');?>
                                  </button>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_store');?></th>
                <th class="col-md-2"><?=$this->lang->line('application_legal_panel_notification_type');?></th>
                <th><?=$this->lang->line('application_purchase_id');?></th>
                <th><?=$this->lang->line('application_notification_number');?></th>
                <th><?=$this->lang->line('application_balance_paid');?></th>
                <th><?=$this->lang->line('application_balance_debit');?></th>  
                <th><?=$this->lang->line('application_status');?></th> 
                <th><?=$this->lang->line('application_date_create');?> </th>
                <th><?=$this->lang->line('application_update_date');?></th>
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

<?php if(in_array('deleteLegalPanelFiscal', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_legal_panel_fiscal');?><span id="deletelegalpanel"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('legalpanelfiscal/remove') ?>" method="post" id="removeForm">
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

$(document).ready(function() {

  $("#paraMktPlaceNav").addClass('active menu-open');
  $("#paineljuridicofiscalNav").addClass('active');

    var filtros = $("#formFiltro").serialize();

    $("#btn_novo_billet").click( function(){
        window.location.assign(base_url.concat("legalpanelfiscal/create"));
    });

    // initialize the datatable
    manageTable = $('#manageTable').DataTable({
        "scrollX": true,
        'ajax': base_url + 'legalpanelfiscal/fetchLegalPanelData?'+filtros,
        'order': []
    });

    $("#btnFilter").click(function(){

        filtros = $("#formFiltro").serialize();

        $('#manageTable').DataTable().destroy();
        manageTable = $('#manageTable').DataTable({
            "scrollX": true,
            'ajax': base_url + 'legalpanelfiscal/fetchLegalPanelData?' + filtros,
            'order': []
        });

    });

});

function removeFunc(id,name)
{
  if(id) {
	 
    $("#removeForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: { id:id }, 
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
