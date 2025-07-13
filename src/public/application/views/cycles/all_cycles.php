<div class="container-fluid">
    <div class="row">

        <div class="col-md-12 mt-5">
            <div class="overlay-wrapper">
                <div class="overlay" v-show="vLoadingTables">
                    <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                    <div class="text-bold pt-2"><?= $this->lang->line('wait_cycle') ?>, {{vMessageLoading}}</div>
                </div>
                <table id="all_cycles" class="table table-hover table-bordered"
                       style="width:100%; padding-top:15px">
                    <thead>
                    <tr class="selected">
                        <th><?= $this->lang->line('start_date_cycle') ?></th>
                        <th><?= $this->lang->line('end_date_cycle') ?></th>
                        <th><?= $this->lang->line('payment_date_cycle') ?></th>
                        <th><?= $this->lang->line('stores_using_cycle') ?></th>
                        <th><?= $this->lang->line('cut_date_cycle') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="cycle in vAllCyclesList" :key="cycle.pmc_id" :class="'linha_' + cycle.pmc_id"
                        class="linha-ciclo-loja">
                        <td>{{cycle.data_inicio}}</td>
                        <td>{{cycle.data_fim}}</td>
                        <td>{{cycle.data_pagamento}}</td>
                        <td>{{cycle.name}}</td>
                        <td>{{cycle.corte}}</td>
                    </tr>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>