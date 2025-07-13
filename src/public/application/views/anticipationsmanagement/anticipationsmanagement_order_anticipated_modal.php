<div class="modal fade" tabindex="-1" role="dialog" id="orderAnticipated">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=lang('application_anticipation_simulation_modal_order_details_title');?></h4>
            </div>
            <div v-if="orderDetails.length == 0" class="text-center">
                <i class="fa fa-spinner fa-spin fa-fw fa-4x"></i><span class="sr-only">Loading...</span>
            </div>
            <div class="alert alert-warning" role="alert" v-if="orderDetails.error">
                <strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>
                <span>{{orderDetails.error}}</span>
            </div>
            <div v-if="orderDetails.details">
                <div class="modal-body">

                    <div class="row">

                        <div class="form-group col-md-4 col-xs-4">
                            <div class="panel panel-default">
                                <div class="">
                                    <h2 class="text-center text-bold">{{orderDetails.details.total_paid | money}}</h2>
                                </div>
                                <div class="panel-footer text-right"><?=lang('application_total_paid');?></div>
                            </div>
                        </div>

                        <div class="form-group col-md-4 col-xs-4" v-show="orderDetails.mode == 'anticipation'">
                            <div class="panel panel-default">
                                <div class="">
                                    <h2 class="text-center text-bold">{{orderDetails.details.taxes | money}}</h2>
                                </div>
                                <div class="panel-footer text-right">Taxas de Antecipação</div>
                            </div>
                        </div>

                        <div class="form-group col-md-4 col-xs-4">
                            <div class="panel panel-default">
                                <div class="">
                                    <h2 class="text-center text-bold">{{orderDetails.details.amount_pending | money}}</h2>
                                </div>
                                <div class="panel-footer text-right">Pagamento Pendente</div>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <p class="text-bold ml-4">Dados do Pedido</p>

                        <div class="form-group col-md-2 col-xs-2">
                            <div class="panel panel-default">
                                <div class="panel-heading no-padding"><div class="text-center">Pedido</div></div>
                                <div class="text-center" v-bind:title="orderDetails.details.marketplace_order_id">
                                    {{orderDetails.details.order_id}}
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-2 col-xs-2">
                            <div class="panel panel-default">
                                <div class="panel-heading no-padding"><div class="text-center">Valor Pedido</div></div>
                                <div class="text-center ">
                                    {{orderDetails.details.total_order | money}}
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-2 col-xs-2">
                            <div class="panel panel-default">
                                <div class="panel-heading no-padding"><div class="text-center">Repasse Inicial</div></div>
                                <div class="text-center ">
                                    {{orderDetails.details.inicial_transfer_value | money}}
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-2 col-xs-2">
                            <div class="panel panel-default">
                                <div class="panel-heading no-padding"><div class="text-center">Data Pedido</div></div>
                                <div class="text-center ">
                                    {{orderDetails.details.order_date}}
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-2 col-xs-2">
                            <div class="panel panel-default">
                                <div class="panel-heading no-padding"><div class="text-center">Data Entrega</div></div>
                                <div class="text-center ">
                                    {{orderDetails.details.order_delivered_date}}
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-2 col-xs-2">
                            <div class="panel panel-default">
                                <div class="panel-heading no-padding"><div class="text-center">Status Fluxo</div></div>
                                <div class="text-center ">
                                    {{orderDetails.details.status}}
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <div class="form-group col-md-2 col-xs-2">
                            Empresa
                            <br>
                            <b>
                                <?php if ($only_admin) { ?>
                                    <a v-bind:href="'<?=base_url('company/update/');?>'+orderDetails.details.company_id" target="_blank" title="Ver Detalhes da Empresa">{{orderDetails.details.company_name}}</a>
                                <?php } else { ?>
                                    {{orderDetails.details.company_name}}
                                <?php } ?>
                            </b>
                        </div>
                        <div class="form-group col-md-2 col-xs-2">
                            Loja
                            <br>
                            <b>
                                <?php if ($only_admin) { ?>
                                        <a v-bind:href="'<?=base_url('stores/update/');?>'+orderDetails.details.store_id" target="_blank" title="Ver Detalhes da Loja">{{orderDetails.details.store_name}}</a>
                                <?php } else { ?>
                                    {{orderDetails.details.store_name}}
                                <?php } ?>
                            </b>
                        </div>
                        <div class="form-group col-md-3 col-xs-3" v-if="orderDetails.details.user_name">
                            Responsável Pela Antecipação
                            <br>
                            <b>
                                {{orderDetails.details.user_name}}
                            </b>
                        </div>

                    </div>

                    <div class="row">

                        <p class="text-bold ml-4">Dados de Pagamento</p>

                        <div class="form-group col-md-12 col-xs-12">
                            <table class="table table-condensed table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th class="text-center">Data do Repasse</th>
                                        <th class="text-center">Parcela</th>
                                        <th class="text-center">Valor Pagamento</th>
                                        <th>Status Pagamento</th>
                                        <th v-show="orderDetails.mode == 'anticipation'">Data Antecipação</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="(installment, key) in orderDetails.details.installments">
                                        <td class="text-center">{{installment.data_ciclo}}</td>
                                        <td class="text-center">{{installment.current_installment}}/{{installment.total_installments}}</td>
                                        <td class="text-center">{{installment.installment_value | money }}</td>
                                        <td>{{installment.payment_status}}</td>
                                        <td v-show="orderDetails.mode == 'anticipation'">{{installment.anticipation_date}}</td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-default btn-sm" data-dismiss="modal">VOLTAR</button>
                </div>

            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->