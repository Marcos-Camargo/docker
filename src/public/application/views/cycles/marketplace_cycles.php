<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <a href="<?php echo base_url('cycles/add_massive/marketplace') ?>"
               class="btn btn-outline-primary float-right">
                <i class="fas fa-plus-circle"></i>  <?= $this->lang->line('massive_add_cycle') ?>
            </a>
            <button type="button" class="btn btn-outline-primary float-right" style="margin-right: 5px;"
                    data-toggle="modal" data-target="#register-cycle" @click="cleanForm()"><i
                        class="fas fa-plus-circle"></i>  <?= $this->lang->line('insert_cycle_by_marketplace') ?>
            </button>
        </div>
        <div class="col-md-12 mt-5">
            <div class="overlay-wrapper">
                <div class="overlay" v-show="vLoadingTables">
                    <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                    <div class="text-bold pt-2"><?= $this->lang->line('wait_cycle') ?>, {{vMessageLoading}}</div>
                </div>
                <table id="marketplaces_cycles" class="table table-hover table-bordered"
                       style="width:100%; padding-top:15px">
                    <thead>
                    <tr class="selected">
                        <th>ID</th>
                        <th>Nome</th>
                        <th><?= $this->lang->line('start_date_cycle') ?></th>
                        <th><?= $this->lang->line('end_date_cycle') ?></th>
                        <th><?= $this->lang->line('payment_date_cycle') ?></th>
                        <?= $settingSellerCenter == 'conectala' ? '<th>'.$this->lang->line('conecta_payment_date_cycle').'</th>' : '' ?>
                        <th><?= $this->lang->line('cut_date_cycle') ?></th>
                        <th style="width: 5%;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="cycle in vCyclesListMarketplace" :key="cycle.pmc_id" :class="'linha_' + cycle.pmc_id">
                        <td style="width: 5%">{{cycle.pmc_id}}</td>
                        <td>{{cycle.descloja}}</td>
                        <td>{{cycle.data_inicio}}</td>
                        <td>{{cycle.data_fim}}</td>
                        <td>{{cycle.data_pagamento}}</td>
                        <?php if ($settingSellerCenter == 'conectala') { ?>
                            <td>{{cycle.data_pagamento_conecta}}</td>
                        <?php } ?>
                        <td>{{cycle.data_usada}}</td>
                        <td style="width: 8%">
                            <button type="button" class="btn btn-primary btn-xs row-action edit-cycle"
                                    @click="editThisCycle(cycle)"><i
                                        class="fa fa-pen"></i></button>
                            <button type="button" class="btn btn-primary btn-xs row-action delete-cycle"
                                    @click="removeCycle(cycle, 'mkt')"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>




