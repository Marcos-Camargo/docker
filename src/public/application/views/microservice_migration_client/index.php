<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="alert alert-warning">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="alert-msg">
                        Caso o cliente continue a usar o microsserviço, ative o parâmetro <u>use_ms_shipping_replica</u> e mantenha o parâmetro <u>use_ms_shipping</u> ativo.<br><br>
                        Caso o cliente não continue a usar o microsserviço, ative o parâmetro <u>use_ms_shipping_replica</u> e inative o parâmetro <u>use_ms_shipping</u>.
                    </h4>
                </div>
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title"><?=$this->lang->line('application_migration')?></h3>
                    </div>
                    <div class="box-body no-padding mt-3">
                        <div class="nav-tabs-custom">
                            <ul class="nav nav-tabs">
                                <li class="active"><a href="#quote" data-toggle="tab"><?=$this->lang->line('application_logistics')?></a></li>
                            </ul>
                            <div class="tab-content col-md-12">
                                <div class="tab-pane active" id="quote">
                                    <table class="table table-bordered" id="tableQuote">
                                        <thead>
                                            <th><?=$this->lang->line('application_name');?></th>
                                            <th><?=$this->lang->line('application_action');?></th>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-aqua mr-2">1</span> RulesSellerConditions</span>
                                                    <small class="col-md-12 no-padding">Regras de leilão do marketplace</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-index="1" data-key-clean="RulesSellerConditions"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_run_now');?></button>
                                                    <button class="btn btn-info" data-key-view-log="RulesSellerConditions"><i class="fa fa-eye"></i> <?=$this->lang->line('iugu_filter_option_view_last');?></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-aqua mr-2">1</span> ApiIntegrations</span>
                                                    <small class="col-md-12 no-padding">Integração logística de ERP, HUB e Plataforma</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-index="1" data-key-clean="ApiIntegrations"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_run_now');?></button>
                                                    <button class="btn btn-info" data-key-view-log="ApiIntegrations"><i class="fa fa-eye"></i> <?=$this->lang->line('iugu_filter_option_view_last');?></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-aqua mr-2">1</span> ShippingCompany</span>
                                                    <small class="col-md-12 no-padding">Transportadoras</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-index="1" data-key-clean="ShippingCompany"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_run_now');?></button>
                                                    <button class="btn btn-info" data-key-view-log="ShippingCompany"><i class="fa fa-eye"></i> <?=$this->lang->line('iugu_filter_option_view_last');?></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-aqua mr-2">1</span> PickupPoint</span>
                                                    <small class="col-md-12 no-padding">Pontos de retirada</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-index="1" data-key-clean="PickupPoint"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_run_now');?></button>
                                                    <button class="btn btn-info" data-key-view-log="PickupPoint"><i class="fa fa-eye"></i> <?=$this->lang->line('iugu_filter_option_view_last');?></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-yellow mr-2">2</span> IntegrationLogistic</span>
                                                    <small class="col-md-12 no-padding">Integração logística de transportadora e gateway logpistico</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-index="2" data-key-clean="IntegrationLogistic"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_run_now');?></button>
                                                    <button class="btn btn-info" data-key-view-log="IntegrationLogistic"><i class="fa fa-eye"></i> <?=$this->lang->line('iugu_filter_option_view_last');?></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-yellow mr-2">2</span> TableShipping</span>
                                                    <small class="col-md-12 no-padding">Tabelas de frete (CSV)</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-index="2" data-key-clean="TableShipping"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_run_now');?></button>
                                                    <button class="btn btn-info" data-key-view-log="TableShipping"><i class="fa fa-eye"></i> <?=$this->lang->line('iugu_filter_option_view_last');?></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-yellow mr-2">2</span> FreteRegiaoProvider</span>
                                                    <small class="col-md-12 no-padding">Tabela de frete simplificada</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-index="2" data-key-clean="FreteRegiaoProvider"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_run_now');?></button>
                                                    <button class="btn btn-info" data-key-view-log="FreteRegiaoProvider"><i class="fa fa-eye"></i> <?=$this->lang->line('iugu_filter_option_view_last');?></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-yellow mr-2">2</span> ProvidersToSeller</span>
                                                    <small class="col-md-12 no-padding">Relacionado entre transportadora e loja</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-index="2" data-key-clean="ProvidersToSeller"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_run_now');?></button>
                                                    <button class="btn btn-info" data-key-view-log="ProvidersToSeller"><i class="fa fa-eye"></i> <?=$this->lang->line('iugu_filter_option_view_last');?></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-yellow mr-2">2</span> IntegrationLogisticApiParameters</span>
                                                    <small class="col-md-12 no-padding">Autenticação de integradores logísticos (específico integração API)</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-index="2" data-key-clean="IntegrationLogisticApiParam"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_run_now');?></button>
                                                    <button class="btn btn-info" data-key-view-log="IntegrationLogisticApiParam"><i class="fa fa-eye"></i> <?=$this->lang->line('iugu_filter_option_view_last');?></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-red mr-2">3</span> CSVToVerification</span>
                                                    <small class="col-md-12 no-padding">Tabelas de frete enviadas para processamento</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-index="3" data-key-clean="CSVToVerification"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_run_now');?></button>
                                                    <button class="btn btn-info" data-key-view-log="CSVToVerification"><i class="fa fa-eye"></i> <?=$this->lang->line('iugu_filter_option_view_last');?></button>
                                                </td>
                                            </tr>
                                            <tr class="bg-gray">
                                                <td class="text-center font-weight-bold text-uppercase" colspan="2">
                                                    Processo final
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="d-flex align-items-center flex-wrap">
                                                    <span class="col-md-12 no-padding"><span class="badge bg-green mr-2">4</span> Finalizar migração</span>
                                                    <small class="col-md-12 no-padding">As lojas receberão o novo endpoint fulfillment</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary" data-key-finish><i class="fa fa-cog"></i> <?=$this->lang->line('application_end_migration_seller');?></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewLog">
    <div class="modal-dialog" role="document">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modal_title">Logs</h4>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12"><pre class="content-log"></pre></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
    let manageTable;
    const base_url = "<?=base_url()?>";

    $(document).ready(function () {
        manageTable = $('#tableQuote').dataTable({
            "ordering": false,
            "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },
            "pageLength": 100
        });
    });

    $('[data-key-clean]').on('click', function() {
        const btn = $(this);
        const tag = $(this).data('key-clean');
        const index = parseInt($(this).data('key-index'));
        const name_job = $(this).closest('tr').find('td:eq(0) span:eq(0)').text().substring(2);
        const content_tr = $(this).closest('tr');

        if (index !== 1) {
            const index_before = index - 1;
            const btn_before = $(`[data-key-index="${index_before}"]`).is(':disabled');
            if (!btn_before) {
                Swal.fire({
                    icon: 'error',
                    title: 'Alerta',
                    html: `O passo ${index_before} ainda não foi concluído`
                });
                return;
            }
        }

        btn.prop('disabled', true);

        Swal.fire({
            title: `Deseja iniciar a migração?`,
            html: `<h4>Job: <b>${name_job}</b></h4>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#008d4c',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Iniciar Migração',
            cancelButtonText: 'Cancelar Operação'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: `${base_url}/MicroserviceMigrationClient/startMigration/${tag}`,
                    type: 'get',
                    dataType: 'json',
                    success: response => {
                        Swal.fire({
                            icon: response.success ? 'success' : 'error',
                            title: 'Alerta',
                            html: response.message
                        });

                        if (!response.success) {
                            btn.prop('disabled', false);
                        } else {
                            content_tr.css({'background-color': '#fdd3a7'});
                        }
                    }, error: () => {
                        btn.prop('disabled', false);
                        Swal.fire({
                            icon: 'error',
                            title: 'Alerta',
                            html: 'Erro desconhecido'
                        });
                    }
                });
            }
        });
    });

    $('[data-key-view-log]').on('click', function() {
        const tag = $(this).data('key-view-log');

        $.ajax({
            url: `${base_url}/MicroserviceMigrationClient/getLogLastExecution/${tag}`,
            type: 'get',
            dataType: 'json',
            success: response => {
                if (!response.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        html: response.message
                    });
                    return;
                }

                $('#viewLog .content-log').empty().html(response.file_content);
                $('#viewLog').modal();
            }, error: () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    html: 'Erro desconhecido'
                });
            }
        });
    });

    $('[data-key-finish]').on('click', async function() {
        const disabled_buttons = parseInt($(`[data-key-index]:not(:disabled)`).length);
        if (disabled_buttons !== 0) {
            Swal.fire({
                icon: 'error',
                title: 'Alerta',
                html: `Os passos anteriores ainda não foram concluídos`
            });
            return;
        }

        $.ajax({
            url: `${base_url}/MicroserviceMigrationClient/finishMigration`,
            type: 'get',
            dataType: 'json',
            success: response => {
                Swal.fire({
                    icon: response.success ? 'success' : 'error',
                    title: 'Alerta',
                    html: response.message
                });
            }, error: () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Alerta',
                    html: 'Erro desconhecido'
                });
            }
        });
    });
</script>

