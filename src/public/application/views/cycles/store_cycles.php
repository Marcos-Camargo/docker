<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <a href="<?php echo base_url('cycles/add_massive/loja') ?>" class="btn btn-outline-primary float-right">
                <i class="fas fa-plus-circle"></i>
                <?= $this->lang->line('massive_add_cycle') ?>
            </a>
            <button type="button" class="btn btn-outline-primary float-right" style="margin-right: 5px;"
                    data-toggle="modal" data-target="#register-cycle" @click="cleanForm()"><i
                        class="fas fa-plus-circle"></i> <?= $this->lang->line('insert_cycle_by_store') ?>
            </button>
        </div>

        <div class="row" style="margin-top:20px;width:100%;float:left;padding:15px">
            <form role="form" id="formStoreSearch" method="post" @submit.prevent="submitSearch">
                <div class="col-md-3">
                    <label for=""><?= $this->lang->line('search_by_store_cycle') ?></label>
                    <select v-model="search.vStore" class="form-control select-lojas" data-live-search="true"

                            style="width: 100%;" tabindex="-1" aria-hidden="true">
                        <option value=""></option>
                        <option v-for="item in vStores" :value="item.id">{{item.name}}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for=""><?= $this->lang->line('start_date_cycle') ?></label>
                    <input class="form-control" type="text" placeholder="" v-model.trim="search.vInicio">
                </div>
                <div class="col-md-2">
                    <label for=""><?= $this->lang->line('end_date_cycle') ?></label>
                    <input class="form-control" type="text" placeholder="" v-model.trim="search.vFim">
                </div>
                <div class="col-md-2">
                    <label for=""><?= $this->lang->line('payment_date_cycle') ?></label>
                    <input class="form-control" type="text" placeholder="" v-model.trim="search.vDataPagamento">
                </div>
                <div class="col-md-2">
                    <label for=""><?= $this->lang->line('conecta_payment_date_cycle') ?></label>
                    <input class="form-control" type="text" placeholder=""
                           v-model.trim="search.vDataPagamentoConectala">
                </div>
                <div class="col-md-1">
                    <div class="row" style="padding-top: 20px;">
                        <button type="submit" class="btn btn-outline-primary"
                                style="margin-top:5px;"
                        ><i class="fa fa-search"></i> <?= $this->lang->line('search_cycle') ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-md-12 mt-5">
            <div class="overlay-wrapper">
                <div class="overlay" v-show="vLoadingTables">
                    <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                    <div class="text-bold pt-2"><?= $this->lang->line('wait_cycle') ?>, {{vMessageLoading}}</div>
                </div>
                <table id="store_cycles" class="table table-hover table-bordered"
                       style="width:100%; padding-top:15px">
                    <thead>
                    <tr class="selected">
                        <th></th>
                        <th>ID</th>
                        <th><?= $this->lang->line('store_cycle') ?></th>
                        <th><?= $this->lang->line('marketplace_cycle') ?></th>
                        <th><?= $this->lang->line('start_date_cycle') ?></th>
                        <th><?= $this->lang->line('end_date_cycle') ?></th>
                        <th><?= $this->lang->line('payment_date_cycle') ?></th>
                        <?= $settingSellerCenter == 'conectala' ? '<th>'.$this->lang->line('conecta_payment_date_cycle').'</th>' : '' ?>
                        <th><?= $this->lang->line('cut_date_cycle') ?></th>
                        <th style="width: 5%;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="cycle in vCyclesListStore" :key="cycle.pmc_id" :class="'linha_' + cycle.pmc_id"
                        class="linha-ciclo-loja">
                        <td style="text-align:center">
                            <input type="checkbox" id="primary" @click="selectCycleLine(cycle)" style="cursor:pointer;margin-top:5px;">
                        </td>
                        <td style="width: 5%">{{cycle.pmc_id}}</td>
                        <td>{{cycle.name}}</td>
                        <td style="width: 15%">{{cycle.descloja}}</td>
                        <td>{{cycle.data_inicio}}</td>
                        <td>{{cycle.data_fim}}</td>
                        <td>{{cycle.data_pagamento}}</td>
                        <?php if ($settingSellerCenter == 'conectala') { ?>
                            <td>{{cycle.data_pagamento_conecta}}</td>
                        <?php } ?>
                        <td>{{cycle.data_usada}}</td>
                        <td style="width: 10%">

                            <button type="button" class="btn btn-primary btn-xs row-action edit-cycle"
                                    @click="editThisCycle(cycle)"><i
                                        class="fa fa-pen"></i></button>
                            <button type="button" class="btn btn-primary btn-xs row-action delete-cycle"
                                    @click.stop="removeCycle(cycle, 'store')"><i class="fa fa-trash"></i></button>

                            <!--<input type="checkbox" id="primary" @click="selectCycleLine(cycle)" style="cursor:pointer;margin-top:5px;">-->

                        </td>
                    </tr>
                    </tbody>
                </table>

                <div class="btn-group" style="margin-top:15px">
                    <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-icon"
                            data-toggle="dropdown"><?= $this->lang->line('action_cycle') ?> <i class="fas fa-caret-down"></i></button>
                    <div class="dropdown-menu" role="menu" style="">
                        <a class="dropdown-item" href="javascript:;"
                           @click="openNewTab(base_url + 'cycles/exportXls?search=' +  encodeURIComponent(JSON.stringify(search)))"><i
                                    class="fas fa-download"></i> <?= $this->lang->line('dowload_cycle') ?></a>
                        <a class="dropdown-item" href="javascript:;"  @click="removeCycle(null, 'store', storesSelectedLines)"><i class="fas fa-trash" style="color:#000"></i> <?= $this->lang->line('delete_cycle') ?></a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>