<!--
SW Serviços de Informática 2019

Listar Pedidos

Obs:
cada usuario so pode ver pedidos da sua empresa.
Agencias podem ver todos os pedidos das suas empresas
Admin pode ver todas as empresas e agencias

-->
<div class="content-wrapper">
  <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>
  <section class="content">
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
        <div class="box box-primary">
          <div class="box-body">
              <div class="col-md-12 col-xs-12">
                  <a class="pull-right btn btn-primary" href="<?php echo base_url('export/billermodulexls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_data_export')?> </a>
              </div>
          </div>
        </div>
        <div class="box box-primary">
          <div class="box-body">
              <div class="col-md-12 no-padding">
                <table id="manageTable" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                  <thead>
                  <tr>
                    <th><?=$this->lang->line('application_store');?></th>
                    <th><?=$this->lang->line('application_company');?></th>
                    <th>ERP</th>
                    <th>Token Tiny</th>
                    <th><?=$this->lang->line('application_date_requested');?></th>
                    <th><?=$this->lang->line('application_situation');?></th>
                      <th><?=$this->lang->line('application_action');?></th>
                  </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewErrorInvoice">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_order_57');?></span></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <h3 class="text-center"><?=$this->lang->line('messages_problem_to_order_invoice')?></h3>
                    <div class="col-md-12">
                        <div class="bd-callout bd-callout-danger">
                            <h4 id="jquery-incompatibility"><?=$this->lang->line('messages_problems_found')?></h4>
                            <div class="errors mt-4">
                                <ul></ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <h4><?=$this->lang->line('messages_after_correcting_back_here')?></h4>
                        <label><?=$this->lang->line('messages_all_problems_fixed')?></label> <br><button class="btn btn-sm btn-success btnTryToIssue"><?=$this->lang->line('application_yes_send_again')?></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="viewErrorOrder">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_problem_order');?></span></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <h3 class="text-center"><?=$this->lang->line('messages_problem_to_order_invoice')?></h3>
                    <div class="col-md-12">
                        <div class="bd-callout bd-callout-danger">
                            <h4><?=$this->lang->line('messages_problems_found')?></h4>
                            <div class="errors mt-4">
                                <ul></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="viewSetting">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_settings');?></span></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="d-flex justify-content-between"><?=$this->lang->line('application_store');?> <a href="" target="_blank" class="link_store"><?=$this->lang->line('application_view');?></a></label>
                            <input type="text" class="form-control" name="store" value="" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="d-flex justify-content-between"><?=$this->lang->line('application_company');?> <a href="" target="_blank" class="link_company"><?=$this->lang->line('application_view');?></a></label>
                            <input type="text" class="form-control" name="company" value="" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?=$this->lang->line('application_date_requested');?></label>
                            <input type="text" class="form-control" name="date_requested" value="" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>ERP</label>
                            <input type="text" class="form-control" name="erp" value="" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?=$this->lang->line('application_situation');?></label>
                            <input type="text" class="form-control" name="situation" value="" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?=$this->lang->line('application_file');?> <?=$this->lang->line('application_certificate');?></label><br>
                            <a download class="certificate btn btn-primary col-md-12" href=""><i class="fa fa-download"></i> <?=$this->lang->line('application_download');?></a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?=$this->lang->line('application_password');?> <?=$this->lang->line('application_certificate');?></label>
                            <input type="text" class="form-control" name="password" value="" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label>Token Tiny</label>
                        <div class="input-group ">
                            <input type="text" class="form-control" name="token-tiny">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-primary btn-flat btnValidateToken"><?=$this->lang->line('application_validate');?> Token</button>
                                <button type="button" class="btn btn-success btn-flat btnUpdateToken"><?=$this->lang->line('application_update');?> Token</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary col-md-3 pull-left" id="active_inactive"><?=$this->lang->line('application_active');?></button>
                <button type="button" class="btn btn-primary col-md-3 pull-right" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="deleteRequest">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Excluir Solicitação</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <h3 class="text-center">Você tem certeza que deseja excluir a solicitação da loja <br><strong class="store_name"></strong>?</h3>
                        <h4 class="text-center">Essa operação não terá como reverter, precisará refazer a solicitação!</h4>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-danger col-md-3"><?=$this->lang->line('application_delete');?></button>
                </div>
                <input type="hidden" name="store_id">
            </form>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/datatables.net/js/processing.js"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

    $("#mainProcessesNav").addClass('active');
    $("#billerModuleNav").addClass('active');

    manageTable = $('#manageTable').DataTable( {
        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
        "processing": true,
        "responsive": true,
        "sortable": true
    });

    loadTable();

});

// Filtragem
const loadTable = () => {
    const url = "<?=base_url('stores/fetchStoresInvoice')?>";

    manageTable.clear().draw().processing(true);

    $.ajax({
        url,
        type: "GET",
        dataType: 'json',
        success: response => {
            rowsTable = [];
            $.each(response.data, function( index, value ) {
                rowsTable.push(value);
            });

            manageTable.clear().draw().rows.add(rowsTable).columns.adjust().draw().processing(false);

            $('[data-toggle="tooltip"], [data-togg="tooltip"]').tooltip();
        }
    });
}

$(document).on('click', '.btnConfig', function () {
    const url = "<?=base_url('stores/getDataStoreRequestInvoice')?>";
    const store_id = $(this).attr('store-id');

    $.ajax({
        url,
        type: "POST",
        data: { store_id },
        dataType: 'json',
        success: response => {
            console.log(response);
            el = $('#viewSetting');
            el.find('[name="store"]').val(response.store_name);
            el.find('[name="company"]').val(response.company_name);
            el.find('[name="erp"]').val(response.erp);
            el.find('[name="date_requested"]').val(response.created_at);
            el.find('[name="password"]').val(response.certificado_pass);
            el.find('[name="situation"]').val(response.active);
            el.find('[name="token-tiny"]').val(response.token_tiny);
            el.find('a.certificate').attr('href',response.certificado_path);
            el.find('button.btnUpdateToken').attr('store-id', response.store_id)
            el.find('a.link_store').attr('href',response.store_id_decode);
            el.find('a.link_company').attr('href',response.company_id_decode);
            el.find('#active_inactive').attr('store_id', response.store_id);
            el.find('#active_inactive').attr('status', response.status == 1 ? 0 : 1);
            if (response.status == 1)
                el.find('#active_inactive').removeClass('btn-success').addClass('btn-danger').text('Inativar');
            else
                el.find('#active_inactive').removeClass('btn-danger').addClass('btn-success').text('Ativar');

            el.modal();
        }
    });
})

$(document).on('click', '.btnDelete', function () {
    const store_id = $(this).attr('store-id');
    const store_name = $(this).closest('tr').find('td:eq(0)').text();

    $('#deleteRequest strong.store_name').text(store_name);
    $('#deleteRequest input[name="store_id"]').val(store_id);
    $('#deleteRequest').modal();
})

$('.btnUpdateToken').on('click', function () {
    const url       = "<?=base_url('stores/updateRequestStoreInvoice')?>";
    const store_id  = $(this).attr('store-id');
    const token     = $('#viewSetting').find('[name="token-tiny"]').val();

    $.ajax({
        url,
        type: "POST",
        data: { store_id, token },
        dataType: 'json',
        success: response => {
            if(response[0] == false){
                Toast.fire({
                    icon: 'error',
                    title: response[1]
                });
                return false;
            }

            Toast.fire({
                icon: 'success',
                title: response[1]
            });

            $('#viewSetting').modal('hide');
            loadTable();
        }
    });
})

$('.btnValidateToken').on('click', function () {
    const url       = "<?=base_url('stores/getDataTokenTiny')?>";
    const token     = $('#viewSetting').find('[name="token-tiny"]').val();

    $.ajax({
        url,
        type: "POST",
        data: { token },
        dataType: 'json',
        success: response => {
            if(response.success == false){
                Toast.fire({
                    icon: 'error',
                    title: response.data
                });
                return false;
            }

            Toast.fire({
                icon: 'success',
                title: `Token válido para o usuário: ${response.razao_social}`
            });
        }
    });
})

$('#active_inactive').on('click', function () {
    const url       = "<?=base_url('stores/updateStatusStoreInvoice')?>";
    const store_id  = $(this).attr('store_id');
    const status    = $(this).attr('status');

    console.log(store_id, status);

    $.ajax({
        url,
        type: "POST",
        data: { store_id, status },
        dataType: 'json',
        success: response => {
            if(response[0] == false){
                Toast.fire({
                    icon: 'error',
                    title: response[1]
                });
                return false;
            }

            Toast.fire({
                icon: 'success',
                title: response[1]
            });

            $('#viewSetting').modal('hide');
            loadTable();
        }, error: e => {
            console.log(e);
        }
    });
})
</script>