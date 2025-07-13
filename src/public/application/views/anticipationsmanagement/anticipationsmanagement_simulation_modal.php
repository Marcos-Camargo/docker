<div class="modal fade" tabindex="-1" role="dialog" id="simulationModal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document" style="width: 325px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" v-if="simulation.simulation && !simulation.error && !simulation.confirmed" v-on:click="cancelSimulateAnticipation()" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <button type="button" class="close" v-if="simulation.confirmed" v-on:click="closeSimulationModal(true, true)" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <button type="button" class="close" v-if="simulation.error" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=lang('application_anticipation_simulation_title');?></h4>
            </div>
            <div v-if="simulation.simulation.length == 0 && simulation.error.length == 0" class="text-center">
                <i class="fa fa-spinner fa-spin fa-fw fa-4x"></i><span class="sr-only">Loading...</span>
            </div>
            <div class="alert alert-warning" role="alert" v-if="simulation.error.length > 0">
                <strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>
                <span>{{simulation.error}}</span>
            </div>
            <div v-if="simulation.simulation && simulation.error.length == 0 && !simulation.confirmed">
                <div class="modal-body">
                    <p class="text-center"><i class="fas fa-hand-holding-usd fa-2x"></i></p>
                    <p class="text-center">Você Recebe:</p>
                    <h2 class="text-center text-bold">{{simulation.simulation.amount - simulation.simulation.anticipation_fee - simulation.simulation.fee | money}}</h2>
                    <p></p>
                    <p class="text-center">Você Paga:</p>
                    <h3 class="text-center text-bold">{{simulation.simulation.total_with_tax | money}}</h3>
                    <p></p>
                    <p class="text-center">Sendo:</p>
                    <div class="row">
                        <div class="col-md-6 col-xs-6 text-right">
                            Taxa de Antecipação:
                        </div>
                        <div class="col-md-6 col-xs-6 text-bold">
                            {{simulation.simulation.anticipation_fee | money}}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-xs-6 text-right">
                            Taxa de MDR:
                        </div>
                        <div class="col-md-6 col-xs-6 text-bold">
                            {{simulation.simulation.fee | money}}
                        </div>
                    </div>
                    <p>&nbsp;</p>
                    <p class="text-center">Depois de confirmar, não será possível desfazer a antecipação</p>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-success btn-lg btn-block" v-on:click="confirmSimulateAnticipation()">CONFIRMAR</button>
                    <button class="btn btn-default btn-lg btn-block" v-on:click="cancelSimulateAnticipation()">CANCELAR</button>
                </div>

            </div>
            <div v-if="simulation.confirmed">
                <div class="modal-body">
                    <p class="text-center"><i class="fas fa-check fa-2x"></i></p>
                    <h3 class="text-center text-bold">Pronto!</h3>
                    <p class="text-center">Você antecipou</p>
                    <h2 class="text-center text-bold">{{simulation.simulation.amount - simulation.simulation.anticipation_fee - simulation.simulation.fee | money}}</h2>
                    <p></p>
                    <p class="text-center">e pagou:</p>
                    <h3 class="text-center text-bold">{{simulation.simulation.total_with_tax | money}}</h3>
                    <p></p>
                    <div class="row">
                        <div class="col-md-6 col-xs-6 text-right">
                            O valor estará disponível a partir de
                        </div>
                        <div class="col-md-6 col-xs-6 text-bold">
                            {{simulation.simulation.payment_date}}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success btn-lg btn-block" v-on:click="closeSimulationModal(true, true)" data-dismiss="modal">OK</button>
                    <button class="btn btn-default btn-lg btn-block" v-on:click="closeSimulationModalAndShowOrders(simulation.simulation.id)" data-dismiss="modal">VER PEDIDOS</button>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->