<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
  <?php $data['pageinfo'] = "application_manage"; $data['page_now'] = 'external_integration';  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

        <?php if($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?=$this->session->flashdata('success')?>
          </div>
        <?php elseif($this->session->flashdata('error')): ?>
          <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?=$this->session->flashdata('error')?>
          </div>
        <?php endif?>

        <div class="box box-primary" id="collapseFilter">
          <div class="box-body">
              <h4 class="mt-0">Ações</h4>
              <div class="col-md-4 form-group no-padding">
                  <button class="btn btn-primary col-md-12" id="btnResendAllNotification">Reenviar todas as notificações com erro</button>
              </div>
              <?php if($usergroup == 1): // Grupo admin coencta lá ?>
              <div class="col-md-4 form-group no-padding">
                  <button class="btn btn-primary col-md-12" id="btnResendLostOrdersNotifications">Reenviar todas as notificações de pedido perdidas</button>
              </div>
              <?php endif ?>
          </div>
        </div>

        <div class="box box-primary">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_type');?></th>
                <th><?=$this->lang->line('application_method');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <th><?=$this->lang->line('application_reference_id');?></th>
                <th><?=$this->lang->line('application_external_id');?></th>
                <th><?=$this->lang->line('application_date_create');?></th>
                <th><?=$this->lang->line('application_action');?></th>
              </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewStatusFile">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_status_file_process');?><span id="deletecategoryname"></span></h4>
      </div>
      <div class="modal-body">
          <div class="content-log">

          </div>
          <div class="buttons">
              <button class="btn btn-primary btnResendNotification col-md-12">Reenviar Notificação</button>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript" src="<?=HOMEPATH?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
let manageTable;
let base_url = "<?=base_url()?>";

$(document).ready(function() {
    $("#MainsettingNav").addClass('active');
    $("#MarketplaceExternalsNav").addClass('active');
    getTable();
});

$('#buscalojas').on('change', function(){
    getTable();
});

const getTable = () => {
    if (typeof manageTable !== 'undefined') {
        manageTable.destroy();
    }

    // initialize the datatable
    manageTable = $('#manageTable').DataTable({
        "language": {"url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
        "processing": true,
        "serverSide": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url +'Marketplace/Externals/fetchFileProcessShippingCompanyData',
            data: { },
            pages: 2
        }),
        "order": [[ 0, 'desc' ]]
    });
}

$(document).on('click', '.view-status-file', function(){
    let id = $(this).data('external-integration-id');
    $('#viewStatusFile .btnResendNotification').hide();
    $("#viewStatusFile .modal-body .content-log").empty()

    let html = '';
    $.post(`${base_url}/Marketplace/Externals/getResponseFile`, { id }, response => {
        $("#viewStatusFile").modal('show');

        if (response.uri) {
            $("#viewStatusFile .modal-body .content-log").append(`<b>URI:</b> ${response.uri}<br><br>`);
        }
        if (response.request) {
            $("#viewStatusFile .modal-body .content-log").append(`<b>Requisição:</b> <pre>${response.request}</pre><br>`);
        }
        if (response.response) {
            $("#viewStatusFile .modal-body .content-log").append(`<b>Resposta:</b> <pre>${response.response}</pre><br>`);
        }
        if (response.response_webhook && response.response_webhook != '{}') {
            $("#viewStatusFile .modal-body .content-log").append(`<b>Callback:</b> <pre>${response.response_webhook}</pre>`);
        }

        // if (response.status_webhook == 0) {
            $('#viewStatusFile .btnResendNotification').data('external-integration-id', id).show();
        // }
    });
});

$(document).on('click', '#viewStatusFile .btnResendNotification', function(){
    const btn = $(this);
    let id = $(this).data('external-integration-id');
    btn.prop('disabled', true);

    $.post(`${base_url}/Marketplace/Externals/resendNotification`, { id }, response => {
        btn.prop('disabled', false);

        if (response.hasOwnProperty('errors')) {
            return Swal.fire({
                icon: 'error',
                title: response.errors
            });
        }

        $('#viewStatusFile').modal('hide');
        getTable();
    });
});

$(document).on('click', '#btnResendAllNotification', function(){
    const btn = $(this);
    btn.prop('disabled', true);

    Swal.fire({
        title: `Tem certeza que deseja reenviar todas as notificações com erro?`,
        html: `Todas as notificações com o status de <span class="label label-danger">Erro</span> serão enviados novamente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Reenviar notificações',
        cancelButtonText: 'Cancelar Operação'
    }).then((result) => {
        if (result.value) {
            $.post(`${base_url}/Marketplace/Externals/resendAllNotification`, {}, response => {
                btn.prop('disabled', false);

                if (response.hasOwnProperty('errors') && response.errors.length) {
                    Swal.fire({
                        icon: 'error',
                        html: '<ul><li>' + response.errors.join('</li><li>') + '</li></ul>'
                    });
                }

                if (!response.all_notification_with_error) {
                    $('#viewStatusFile').modal('hide');
                    getTable();
                }
            });
        } else {
            btn.prop('disabled', false);
        }
    });
});

<?php if($usergroup == 1): // Grupo admin coencta lá ?>
$(document).on('click', '#btnResendLostOrdersNotifications', function(){
    const btn = $(this);
    btn.prop('disabled', true);

    Swal.fire({
        title: `Tem certeza que deseja reenviar todas as notificações que estão perdidas?`,
        html: `Todas as notificações não listadas abaixo, referente a pedidos, serão enviadas.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Enviar notificações',
        cancelButtonText: 'Cancelar Operação'
    }).then((result) => {
        if (result.value) {
            $.post(`${base_url}/Marketplace/Externals/resendLostOrdersNotifications`, {}, response => {
                btn.prop('disabled', false);

                if (response.hasOwnProperty('errors') && response.errors.length) {
                    Swal.fire({
                        icon: 'error',
                        html: '<ul><li>' + response.errors.join('</li><li>') + '</li></ul>'
                    });
                }

                getTable();
            });
        } else {
            btn.prop('disabled', false);
        }
    });
});
<?php endif ?>
</script>
