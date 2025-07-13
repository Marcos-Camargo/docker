<!--
SW Serviços de Informática 2019

Listar Lojas

Obs:
cada usuario so pode ver lojas da sua empresa.
Agencias podem ver todos as lojas das suas empresas
Admin pode ver lojas de todas as empresas e agencias

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
                <div id="showActions">
                    <a class="pull-right btn btn-primary exportIntegration" href="<?php echo base_url('export/integrationsCsv') ?>/"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_integration_export');?></a>
                    <br><br>
                </div>
                <div class="box">
                    <div class="box-body">
                        <table id="manageTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><?=$this->lang->line('application_store');?> ID</th>
                                    <th><?=$this->lang->line('application_store');?></th>
                                    <th><?=$this->lang->line('application_company');?></th>
                                    <th><?=$this->lang->line('application_date_requested');?></th>
                                    <th><?=$this->lang->line('application_status');?></th>
                                    <th>ERP</th>
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

<div class="modal fade" tabindex="-1" role="dialog" id="viewIntegration">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_integration');?></h4>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                <button type="button" class="btn btn-success col-md-3" id="check-credentials" id-integration=""><?=$this->lang->line('application_validate_credentials')?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewCallback">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Chave de Callback</h4>
            </div>
            <div class="modal-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row inputToken">
                        <div class="col-md-12 form-group">
                            <label>Callback</label>
                            <input type="text" class="form-control callback" name="callback" disabled>
                        </div>
                    </div>
                    <div class="row newToken">
                        <div class="col-md-12 d-flex justify-content-center form-group">
                            <input type="submit" class="col-md-6 btn btn-success" value="Criar Chave de Callback">
                        </div>
                    </div>
                    <input type="hidden" name="store_id" value="">
                    <input type="hidden" name="update_token_callback" value="true">
                </form>
            </div>
            <div class="modal-footer d-flex">
                <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="statusIntegration">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration');?></h4>
                </div>
                <div class="modal-body"> 
                    <div class="row mb-5">
                        <div class="col-md-12 text-center form-group">
                            <h3 class="store_name no-margin">Loja Teste</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group text-center">
                            <input type="radio" name="status" value="0" id="status_0" required> <label for="status_0">Pendente</label>
                        </div>
                        <div class="col-md-4 form-group">
                            <input type="radio" name="status" value="1" id="status_1" required> <label for="status_1">Concluído</label>
                        </div>
                        <div class="col-md-4 form-group">
                            <input type="radio" name="status" value="2" id="status_2" required> <label for="status_2">Com Problema</label>
                        </div>
                    </div>
                    <div class="row div_description_integration mt-3">
                        <div class="col-md-12 form-group">
                            <label>Descrição do Problema</label>
                            <textarea class="form-control" name="description_integration"></textarea>
                        </div>
                    </div>
                    <div class="row div_description_integration">
                        <div class="col-md-12 form-group text-center">
                            <input type="checkbox" name="resolvido" id="resolvido">
                            <label for="resolvido">Resolvido?</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-3"><?=$this->lang->line('application_save');?></button>
                </div>
                <input type="hidden" name="id_integration" value="">
            </div>
        </form>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="removeIntegration">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_integration');?></h4>
                </div>
                <div class="modal-body">
                    <div class="row mb-5">
                        <div class="col-md-12 text-center form-group">
                            <h3 class="store_name no-margin">Atenção</h3>
                        </div>

                        <div class="col-md-12 text-center form-group">
                            <h4>Para concluir a exclusão da integração, você deve selecionar uma das ações abaixo, definindo o que acontecerá com os produtos atuais do seller.</h4>
                            <h5>Ação a ser executada para o logista que possui a interação:</h5>
                            <select class="form-control" name="action_performed" id="action_performed" aria-describedby="action_performed_help" required>
                                <option value="">Selecione a ação a ser executada</option>
                                <option value="1">Zerar o estoque de todos os produtos da base do seller</option>
                                <option value="2">Inativar todos os produtos da base do seller</option>
                                <option value="3">Zerar o estoque e inativar os produtos da base do seller</option>
                                <option value="4">Zerar o estoque e enviar todos os produtos da base do seller para a lixeira</option>
                                <option value="5">Apenas excluir a integração</option>
                            </select>
                            <h5>Para confirmar a ação, escreva a frase a seguir: "realizar a exclusão da integração"</h5>
                            <input type="text" class="form-control" id="input_phrase_confirm" name="phrase_confirm" aria-describedby="phrase_confirm_help" placeholder="Realizar a exclusão da integração" onpaste="return false" ondrop="return false" autocomplete="off" required>
                            <h4><br>Ao realizar a exclusão será removido todos os fluxos dessa integração.</h4>
                            <spam id="alert_trash" style="display: none">
                                <h3>Atenção</h3>
                                <h5> Essa ação não pode ser desfeita.<br>
                                O kit relacionado ao produto também será movido para a lixeira.<br>
                                Produto com pedidos em aberto não será movido para a lixeira.<br>
                                Será possível clonar o produto a partir da lixeira.</h5>
                            </spam>                          
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-danger col-md-3" id="button_application_delete" Disabled><?=$this->lang->line('application_delete');?></button>
                </div>
                <input type="hidden" name="integration_id">
                <input type="hidden" name="remove_integration" value="true">
            </form>
        </div>
    </div>
</div>

<style>
    .modal-footer:before,
    .modal-footer:after{
        display: none;
    }
</style>


<script type="text/javascript">
    var manageTable;
    let base_url = "<?=base_url()?>";

    $('#input_phrase_confirm').on('keyup', function () {
        var originalValue = "realizar a exclusão da integração";
        if (originalValue.toUpperCase() != $(this).val().toUpperCase()) {
            $('#button_application_delete').prop('disabled', true);
        } else {
            $('#button_application_delete').prop('disabled', false);
        }
    });
    
    $('#action_performed').on('change', function () {        
        if ($(this).val() == '4') {
            $('#alert_trash').show();
        } else {
            $('#alert_trash').hide();
        }
    }); 

    $(document).ready(function() {

        $("#mainProcessesNav").addClass('active');
        $("#manageIntegrationsNav").addClass('active');

        manageTable = $('#manageTable').DataTable({
            "language": {"url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline( {
                url: base_url +'Stores/fetchStoresIntegratios',
                data: { },
                pages: 2
            }),
            'order': [[4, 'desc']],
            "initComplete": function( settings, json ) {
                $('[data-toggle="tooltip"]').tooltip();
            }
        });
    });

    $(document).on('click', '.updateIntegration', function () {
        const id_integration = $(this).attr('id-integration');
        const url = "<?=base_url('stores/getDataIntegrations')?>";

        $.ajax({
            url,
            type: "POST",
            data: { id_integration },
            dataType: 'json',
            success: response => {
                status = response.status == 4 ? 0 : response.status;
                status = response.status == 3 ? 2 : status;

                $('#statusIntegration .store_name').text(response.name);
                $(`#status_${status}`).attr('checked', true).trigger('change');
                $('#statusIntegration [name="id_integration"]').val(id_integration);
                $('#statusIntegration [name="description_integration"]').val(response.description_integration);
                $('#statusIntegration [name="resolvido"]').attr('checked', response.status == 3);

                $('#statusIntegration').modal();

            }
        })
    });

    $(document).on('click', '.updateKeyCallback', function () {
        const id_integration = $(this).attr('id-integration');
        const url = "<?=base_url('stores/getDataIntegrations')?>";

        $.ajax({
            url,
            type: "POST",
            data: { id_integration },
            dataType: 'json',
            success: response => {

                if (response.token_callback == null) {
                    $('#viewCallback .newToken').show();
                    $('#viewCallback .inputToken').hide();
                } else {
                    $('#viewCallback .newToken').hide();
                    $('#viewCallback .inputToken').show();
                }

                $('#viewCallback .callback').val(response.token_callback);
                $('#viewCallback input[name="store_id"]').val(response.store_id);
                $('#viewCallback').modal();
            }
        })
    });

    $(document).on('click', '#check-credentials', function () {
        const btn = $(this);
        const id_integration = $(this).attr('id-integration');
        const url = "<?=base_url('stores/checkCredentialsIntegartions')?>";
        btn.attr('disabled', true);

        $.ajax({
            url,
            type: "POST",
            data: { id_integration },
            dataType: 'json',
            success: response => {

                console.log(response);

                if (!response.status) {
                    let msgError = '';

                    $.each(response.return, function(i,v){
                        $.each(v, function(_i,_v) {
                            msgError += JSON.stringify(_v) + '<br>';
                        });
                    });

                    Swal.fire(
                        "Ocorreu um erro",
                        msgError,
                        'error'
                    );
                    btn.attr('disabled', false);
                    return false
                }

                Swal.fire(
                    "Credenciais válidas",
                    'Credenciais validada com sucesso',
                    'success'
                );
                btn.attr('disabled', false);

            }, error: e => {
                console.log(e);

                Swal.fire(
                    "Ocorreu um erro",
                    "Ocorreu um erro inesperado",
                    'error'
                );
                btn.attr('disabled', false);
            }
        })
    });

    $('input[name="status"]').on('change', function () {
        const status = $(this).val();
        if(status == 2 || status == 3) {
            $('.div_description_integration').show();
            $('textarea[name="description_integration"]').attr('required', true);
        }
        else {
            $('.div_description_integration').hide();
            $('textarea[name="description_integration"]').attr('required', false);
        }
    });

    $(document).on('click', '.viewIntegration', function () {
        const id_integration = $(this).attr('id-integration');
        const url = "<?=base_url('stores/getDataIntegrations')?>";

        $.ajax({
            url,
            type: "POST",
            data: { id_integration },
            dataType: 'json',
            success: response => {
                let str = `<div class="row">
                               <h3 class="text-center">${response.integration}</h3>
                           </div>`;

                $.each(response.credentials, function( index, value ) {
                    str += `
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label>${index}</label>
                            <input type="text" class="form-control" value="${value}" readonly>
                        </div>
                    </div>
                    `;
                });

                $('#viewIntegration #check-credentials').attr('id-integration', id_integration);
                $('#viewIntegration').modal().find('.modal-body').empty().append(str);
            }
        });
    });

    $(document).on('click', '.removeIntegration', function () {
        const id_integration = $(this).attr('id-integration');
        const name_store = $(this).closest('tr').find('td:eq(0)').text();

        $('#removeIntegration .name_store').text(name_store);
        $('#removeIntegration input[name="integration_id"]').val(id_integration);

        $('#removeIntegration').modal();
    });
</script>