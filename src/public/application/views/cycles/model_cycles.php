<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">

            <button type="button" class="btn btn-outline-primary float-right" style="margin-right: 5px;"
                    data-toggle="modal" data-target="#model-cycle" @click="cleanForm()"><i
                        class="fas fa-plus-circle"></i> Adicionar um modelo de ciclo
            </button>
        </div>

        <div class="col-md-12 mt-5">
            <div class="overlay-wrapper">
                <div class="overlay" v-show="vLoadingTables">
                    <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                    <div class="text-bold pt-2">Aguarde, {{vMessageLoading}}</div>
                </div>
                <table id="model_cycles" class="table table-hover table-bordered"
                       style="width:100%; padding-top:15px">
                    <thead>
                    <tr class="selected">
                        <th>ID</th>
                        <th>Dia - Inicio ciclo</th>
                        <th>Dia - Fim ciclo</th>
                        <th>Data de pagamento</th>
                        <th style="width: 5%;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="cycle in vModelCyclesList" :key="cycle.id" :class="'linha_' + cycle.id"
                        class="linha-ciclo-loja">
                        <td>{{cycle.id}}</td>
                        <td>{{cycle.data_inicio}}</td>
                        <td>{{cycle.data_fim}}</td>
                        <td>{{cycle.data_pagamento}}</td>
                        <td style="width: 8%">
                            <button type="button" class="btn btn-primary btn-xs row-action edit-cycle"
                                    data-toggle="modal" data-target="#model-cycle" @click="editModelCycle(cycle)"><i
                                        class="fa fa-pen"></i></button>
                            <button type="button" class="btn btn-primary btn-xs row-action delete-cycle"
                                    @click="removeCycle(cycle, 'models')"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="model-cycle" style="display: none" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="width:100%">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Adicionar novo modelo de ciclo</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            <form role="form" id="formCycle" method="post" @submit.prevent="submitModel">
                <input type="hidden" v-model="model.vHiddenId">
                <div class="modal-body">

                    <div id="modal-insert" class="container-fluid">

                        <div class="overlay-wrapper">

                            <div class="overlay" v-show="saving">
                                <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                                <div class="text-bold pt-2">Aguarde, tentando salvar o modelo...</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label for="">Dia - INÍCIO ciclo</label>
                                    <input class="form-control" type="number" placeholder=""
                                           v-model.trim="model.vModelDataInicio" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="">Dia - FIM ciclo</label>
                                    <input class="form-control" type="number" placeholder=""
                                           v-model.trim="model.vModelDataFim" required>
                                </div>
                            </div>

                            <div class="row" style="margin-top:15px">
                                <div class="col-md-6">
                                    <label for="">Data de pagamento</label>
                                    <input class="form-control" type="number" placeholder=""
                                           v-model.trim="model.vModelDataPagamentoMkt" required>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal"
                            :disabled="saving">
                        Fechar
                    </button>
                    <button type="submit" class="btn btn-primary"
                            :disabled="saving">Adicionar
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>