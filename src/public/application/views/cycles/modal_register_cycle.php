<div class="modal fade" id="register-cycle" style="display: none" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="width:100%">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Adicionar ciclo por {{vOpenedTab == 'mkt' ? 'Marketplace' :
                    'Loja'}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            <form role="form" id="formCycle" method="post" @submit.prevent="submitCycle">
                <input type="hidden" v-model="form.vHiddenId">
                <div class="modal-body">

                    <div id="modal-insert" v-show="!vShowCyclesExisting" class="container-fluid">

                        <div class="overlay-wrapper">
                            <div class="overlay" v-show="saving">
                                <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                                <div class="text-bold pt-2">Aguarde, tentando salvar o ciclo...</div>
                            </div>

                            <div class="row">
                                <div :class="vOpenedTab == 'store' ? 'col-md-4':'col-md-8'">
                                    <label for="">Marketplace</label>
                                    <select class="form-control" v-model="form.vMarketplace"
                                            style="width: 100%;" tabindex="-1" aria-hidden="true" required
                                            :disabled="form.vHiddenId > 0">
                                        <option v-for="item in vMarketplaces" :value="item.id">
                                            {{item.mkt_place}}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4" v-if="vOpenedTab == 'store' ? true:false">
                                    <label for="">Loja</label>
                                    <select v-model="form.vStores" class="form-control select-lojas"
                                            data-live-search="true"
                                            style="width: 100%;" tabindex="-1" aria-hidden="true" required
                                            :disabled="form.vHiddenId > 0">
                                        <option v-for="item in vStores" :value="item.id">{{item.name}}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for=""><?= $this->lang->line('cut_date_cycle') ?></label>
                                    <select class="form-control" style="width: 100%;" tabindex="-1"
                                            aria-hidden="true" v-model="form.vDateCut" required >
                                        <option v-for="item in vCutDates" :value="item.id">{{item.cut_date}}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <span style="color:#000;margin-top: 24px;font-size:18px;width: 100%;float: left;margin-bottom: 12px;">Período do ciclo</span>

                            <div class="row">
                                <div class="col-md-4">
                                    <label for=""><?= $this->lang->line('start_date_cycle') ?></label>
                                    <input class="form-control" type="number" placeholder=""
                                           v-model.trim="form.vDataInicio" required :disabled="form.vStores.length == 0 && vOpenedTab == 'store'">
                                </div>
                                <div class="col-md-4">
                                    <label for=""><?= $this->lang->line('end_date_cycle') ?></label>
                                    <input class="form-control" type="number" placeholder=""
                                           v-model.trim="form.vDataFim" required :disabled="form.vStores.length == 0 && vOpenedTab == 'store'">
                                </div>
                                <div class="col-md-4">
                                    <div class="row" style="padding-top: 20px;">
                                        ou
                                        <button type="button" class="btn btn-outline-primary"
                                                style="margin-top:5px;margin-left:5px"
                                                @click="showCyclesExisting()"
                                                :disabled="checkinCycleChoice || (form.vStores.length == 0 && vOpenedTab == 'store')"><i
                                                    class="fas fa-plus-circle"></i> <?= $this->lang->line('add_existing_cycle') ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <span style="color:#000;margin-top: 24px;font-size:18px;width: 100%;float: left;margin-bottom: 12px;"><?= $this->lang->line('payment_date_cycle') ?></span>

                            <div class="row">
                                <div class="col-md-6">
                                    <label for=""><?= $this->lang->line('payment_date_cycle') ?></label>
                                    <input class="form-control" type="number" placeholder=""
                                           v-model.trim="form.vDataPagamentoMkt" required :disabled="form.vStores.length == 0 && vOpenedTab == 'store'">
                                </div>
                                <div class="col-md-6">
                                    <label for=""><?= $this->lang->line('conecta_payment_date_cycle') ?></label>
                                    <input class="form-control" type="number" placeholder=""
                                           v-model.trim="form.vDataPagamentoConectala" :disabled="form.vStores.length == 0 && vOpenedTab == 'store'">
                                </div>
                            </div>

                        </div>

                    </div>

                    <div id="modal-existing" v-show="vShowCyclesExisting" class="container-fluid">


                        <div class="overlay-wrapper">
                            <div class="overlay" v-show="checkinCycleChoice">
                                <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                                <div class="text-bold pt-2">Aguarde, checando ciclo escolhido...</div>
                            </div>

                            <table class="table table-hover table-bordered" id="cycles_registered">
                                <thead>
                                <tr>
                                    <th><?= $this->lang->line('start_date_cycle') ?></th>
                                    <th><?= $this->lang->line('end_date_cycle') ?></th>
                                    <th><?= $this->lang->line('payment_date_cycle') ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="cycle in vCyclesList" :key="cycle.pmc_id">
                                    <td style="width: 30%">{{cycle.data_inicio}}</td>
                                    <td style="width: 30%">{{cycle.data_fim}}</td>
                                    <td style="width: 30%">{{cycle.data_pagamento}}</td>
                                    <td style="width: 10%">
                                        <button type="button" class="btn btn-outline-primary"
                                                :disabled="checkinCycleChoice"
                                                style="margin-top:5px;margin-left:5px"
                                                @click="checkCycleUsed(cycle)"><i
                                                    class="fas fa-plus-circle"></i> <?= $this->lang->line('use_this_cycle') ?>
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-default float-right" style="margin-top:15px;"
                                @click="showCyclesExisting()" :disabled="checkinCycleChoice"><i
                                    class="fa fa-arrow-left"></i> <?= $this->lang->line('back_cycle') ?>
                        </button>
                    </div>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal"
                            :disabled="checkinCycleChoice">
                        <?= $this->lang->line('close_cycle') ?>
                    </button>
                    <button type="submit" class="btn btn-primary"
                            :disabled="checkinCycleChoice">{{ form.vHiddenId > 0 ? 'Editar' : 'Adicionar' }}
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>