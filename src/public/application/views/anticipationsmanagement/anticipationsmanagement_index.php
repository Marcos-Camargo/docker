<!--https://github.com/mengxiong10/vue2-datepicker-->
<link rel="stylesheet" href="<?php echo base_url('assets/vue/datetime_picker/index.css') ?>">

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/money/v-money.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue-resource.min.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/datetime_picker/index.min.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/datetime_picker/pt-br.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/bower_components/bootstrap/dist/js/pipeline.js') ?>" type="text/javascript"></script>

<style type="text/css">
    .dropdown-menu.open {
        max-width: 100%;
    }
     .bg-blue-light {
         border: 1px solid #eee;
     }
    a:hover {
        cursor:pointer;
    }
</style>


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php
    $this->load->view('templates/content_header', $data);
    ?>

    <!-- Main content -->
    <section class="content" id="app">

        <div id="messages"></div>

        <?php
        include('anticipationsmanagement_simulation_modal.php');
        include('anticipationsmanagement_order_anticipated_modal.php');
        ?>

        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title pull-left">
                            <?= $this->lang->line('application_payment_anticipation_management_title'); ?>
                            <small>{{getCurrentStoreName()}}</small>
                        </h3>
                    </div>

                    <div class="box-body">

                        <div class="row">

                            <div class="form-group col-md-12">

                                <div class="form-group col-md-4 col-xs-4" v-show="stores.length > 1">
                                    <label for="stores"><?= $this->lang->line('application_store'); ?> *</label>
                                    <select class="form-control selectpicker show-tick"
                                            data-live-search="true"
                                            data-actions-box="true"
                                            id="stores"
                                            v-model="entry.store"
                                            @keyup="changeStore(false)" @change="changeStore(false)">
                                        <option v-for="store in stores" :value="store.id">{{store.name}}</option>
                                    </select>
                                </div>

                                <label for="anticipated_only" style="" class="checkbox mt-2 mb-0 pull-right">
                                    <?= $this->lang->line('application_payment_anticipation_management_anticipations_history'); ?>
                                </label>
                                <input type="checkbox"
                                       id="anticipated_only"
                                       v-model="entry.anticipated_only"
                                       true-value="1"
                                       false-value="0"
                                       class="form-check-input pull-right mr-2"
                                       @keyup="changeStore(false)" @change="changeStore(false)"
                                       style="width: 25px; height: 25px;"
                                >
                            </div>

                        </div>


                        <div class="row">

                            <div class="form-group col-md-12 col-xs-12" v-show="entry.store">

                                <nav class="navbar">

                                    <div class="row">
                                        <div class="col-md-12">
                                            <h4><?php echo lang('application_filters'); ?></h4>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="order_id">Nº Pedido</label>
                                            <input v-model.trim="entry.order_id" type="text" class="form-control" id="order_id" autocomplete="off" placeholder="ID Pedido / Pedido Marketplace"
                                                   @keyup="changeStore(true)" @change="changeStore(true)" />
                                        </div>
                                        <div class="col-md-3" v-if="entry.anticipated_only == 0">
                                            <label for="filter_status">Status</label>
                                            <select class="form-control selectpicker show-tick"
                                                    id="filter_status"
                                                    v-model="entry.status"
                                                    @keyup="changeStore(true)" @change="changeStore(true)">
                                                <option v-for="(name, value) in filterable_status" :value="value">{{name}}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="order_id">Nª Parcelas</label>

                                            <select class="form-control selectpicker show-tick"
                                                    id="order_id"
                                                    v-model="entry.installments_number"
                                                    multiple="multiple" data-actions-box="true"
                                                    @keyup="changeStore(true)" @change="changeStore(true)">
                                                <option v-for="installment in installmentsFilterOptions" :value="installment">{{installment}}</option>
                                            </select>

                                        </div>
                                        <div class="col-md-2">
                                            <label for="order_id">Data Pedido Início</label>
                                            <date-picker id="start_date"
                                                         v-model.trim="entry.order_date.start"
                                                         type="date"
                                                         value-type="YYYY-MM-DD"
                                                         format="DD/MM/YYYY"
                                                         @keyup="changeStore(true)" @change="changeStore(true)"
                                            ></date-picker>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="order_id">Data Pedido Fim</label>
                                            <date-picker id="end_date"
                                                         v-model.trim="entry.order_date.end"
                                                         type="date"
                                                         value-type="YYYY-MM-DD"
                                                         format="DD/MM/YYYY"
                                                         @keyup="changeStore(true)" @change="changeStore(true)"
                                            ></date-picker>
                                        </div>
                                    </div>

                                </nav>

                            </div>

                        </div>

                        <div class="alert alert-warning" role="alert" v-if="entry.store && orders_result.length == 0 && !loading">
                            <strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>
                            <span>Nenhum Pedido Encontrado</span>
                        </div>

                        <div v-if="loading" class="text-center">
                            <i class="fa fa-spinner fa-spin fa-fw fa-4x"></i><span class="sr-only">Loading...</span>
                        </div>

                        <div class="row" v-if="orders_result.length > 0">

                            <div class="form-group col-md-3 col-xs-3" v-if="entry.anticipated_only == 0">
                                <div class="panel panel-default">
                                    <div class="">
                                        <h2 class="text-center text-bold" style="font-size:2vw;">{{storeLimits.valueReceiveNextCycle | money}}</h2>
                                    </div>
                                    <div class="panel-footer text-right"><?=lang('application_payment_anticipation_management_box_value_receive_next_cicle');?></div>
                                </div>
                            </div>

                            <div class="form-group col-md-3 col-xs-3" v-if="entry.anticipated_only == 0">
                                <div class="panel panel-default">
                                    <div class="">
                                        <h2 class="text-center text-bold" style="font-size:2vw;">{{storeLimits.totalValueNotPaid | money}}</h2>
                                    </div>
                                    <div class="panel-footer text-right"><?=lang('application_payment_anticipation_management_box_total_receive');?></div>
                                </div>
                            </div>

                            <div class="form-group col-md-3 col-xs-3">
                                <div class="panel panel-default">
                                    <div class="">
                                        <h2 class="text-center text-bold" style="font-size:2vw;">{{storeLimits.totalsAnticipated.total_anticipated | money}}</h2>
                                    </div>
                                    <div class="panel-footer text-right"><?=lang('application_payment_anticipation_management_box_total_antecipated');?></div>
                                </div>
                            </div>

                            <div class="form-group col-md-3 col-xs-3">
                                <div class="panel panel-default">
                                    <div class="">
                                        <h2 class="text-center text-bold" style="font-size:2vw;">{{storeLimits.totalsAnticipated.total_taxes | money}}</h2>
                                    </div>
                                    <div class="panel-footer text-right"><?=lang('application_payment_anticipation_management_box_anticipation_taxes');?></div>
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div v-if="orders_result.length > 0 && entry.anticipated_only == 0">

                                <?php
                                if (in_array('createAnticipationSimulation', $this->permission)){
                                ?>
                                    <div class="form-group col-md-3 col-xs-3">
                                        <div class="panel panel-default">
                                            <div class="">
                                                <h2 class="text-center text-bold" style="font-size:2vw;">{{sumTotalNotPaidSelectedOrders() | money}}</h2>
                                            </div>
                                            <div class="panel-footer text-right"><?=lang('application_total_selected');?></div>
                                        </div>
                                    </div>

                                <?php
                                }
                                ?>

                                <div class="form-group col-md-3 col-xs-3">
                                    <div class="panel panel-default">
                                        <div class="">
                                            <h2 class="text-center text-bold" style="font-size:2vw;">{{storeLimits.minimum_amount | money}}</h2>
                                        </div>
                                        <div class="panel-footer text-right"><?=lang('application_minimal_anticipation_available');?></div>
                                    </div>
                                </div>

                                <div class="form-group col-md-3 col-xs-3">
                                    <div class="panel panel-default">
                                        <div class="">
                                            <h2 class="text-center text-bold" style="font-size:2vw;">{{storeLimits.maximum_amount | money}}</h2>
                                        </div>
                                        <div class="panel-footer text-right"><?=lang('application_maximun_anticipation_available');?></div>
                                    </div>
                                </div>

                                <?php
                                if (in_array('createAnticipationSimulation', $this->permission)){
                                ?>
                                    <div class="form-group col-md-2 col-xs-2" v-show="entry.orders.length > 0">
                                        <a href="#"
                                           v-on:click="simulateAnticipation()"
                                           :disabled="!isSimulateAnticipationAllowed()"
                                           class="btn btn-success btn-large show-tooltip"
                                           data-toggle="modal" data-target="#simulationModal"
                                           style="margin-top: 8rem !important;"
                                           data-placement="top" title="<?=lang('application_simulate_ancitipation_button_title');?>"
                                           ><i class="fa fa-money"></i> <?=lang('application_simulate_ancitipation');?></a>
                                    </div>
                                <?php
                                }
                                ?>

                            </div>

                            <div class="form-group col-md-12 col-xs-12">

                                <div v-show="orders_result.length > 0 && !loading">

                                    <div class="box">
                                        <div class="box-body">

                                            <div class="form-group col-md-12 no-padding mr-0 text-right">
                                                <form role="form" action="<?php echo base_url('anticipationsManagement/export_orders') ?>" method="post">
                                                    <input type="hidden" name="entry" v-model="entryJson()">
                                                    <input type="submit" value="<?=lang('application_export')?>" class="btn btn-default">
                                                </form>
                                            </div>

                                            <data-table :orders_result="filtered_orders"></data-table>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>

<script type="text/javascript">

    $(document).ready(function () {

        $("#paraMktPlaceNav").addClass('active');
        $("#anticipationsSimulation").addClass('active');

    });

    var base_url = "<?php echo base_url(); ?>";

    //https://codepen.io/stwilson/pen/oBRePd
    Vue.component('data-table', {
        template: '<table class="table table-striped table-hover responsive table-condensed" style="width: 100%;"></table>',
        props: ['orders_result'],
        data() {
            return {
                headers: [
                    <?php
                    if (in_array('createAnticipationSimulation', $this->permission)){
                    ?>
                    { title: '<input type="checkbox" onclick=\'checkAllOrders(this)\'>', 'sortable': false },
                    <?php
                    }
                    ?>
                    { title: '<?= $this->lang->line('application_order'); ?>' },
                    { title: '<?= $this->lang->line('application_store'); ?>' },
                    { title: '<?= $this->lang->line('application_parcels'); ?>' },
                    { title: '<?= $this->lang->line('application_date_next_payment'); ?>' },
                    { title: '<?= $this->lang->line('application_value_next_payment'); ?>' },
                    { title: '<?= $this->lang->line('application_installment_value'); ?>' },
                    { title: '<?= $this->lang->line('application_transfer_realized'); ?>' },
                    { title: '<?= $this->lang->line('application_total_to_receive'); ?>' },
                    { title: '<?= $this->lang->line('application_initial_transfer'); ?>' },
                    { title: '<?= $this->lang->line('application_anticipation_taxes'); ?>' },
                    { title: 'Status Fluxo' },
                ],
                rows: [] ,
                dtHandle: null
            }
        },
        watch: {
            orders_result(val, oldVal) {
                this.showRows(val, oldVal);
            }
        },
        mounted() {
            let vm = this;
            // Instantiate the datatable and store the reference to the instance in our dtHandle element.
            vm.dtHandle = $(this.$el).DataTable({
                // Specify whatever options you want, at a minimum these:
                columns: vm.headers,
                data: vm.rows,
                searching: true,
                paging: true,
                info: true
            });
        },
        methods: {
            showRows(val, oldVal){
                let vm = this;
                vm.rows = [];
                // You should _probably_ check that this is changed data... but we'll skip that for this example.
                val.forEach(function (item) {

                    // Fish out the specific column data for each item in your data set and push it to the appropriate place.
                    // Basically we're just building a multi-dimensional array here. If the data is _already_ in the right format you could
                    // skip this loop...
                    let row = [];

                    // row.push('<input type="checkbox" class="checkbox" v-model="entry.orders[]" />');
                    <?php
                    if (in_array('createAnticipationSimulation', $this->permission)){
                    ?>
                    row.push('<input class="order_checkbox" type="checkbox" onclick="selectOrder(this, \''+item.id+'\')" />');
                    <?php
                    }
                    ?>
                    row.push(item.order_id_link);
                    row.push(item.store);
                    if (item.status_code == 'normal'){
                        row.push(item.current_installment+'/'+item.total_installments);
                    }else{
                        row.push(item.total_installments+'/'+item.total_installments);
                    }
                    row.push(item.next_payment_date);
                    row.push(item.value_next_payment_formated);
                    row.push(item.installment_value_formated);
                    row.push(item.value_paid_formated);
                    row.push(item.value_not_paid_formated);
                    row.push(item.initial_transfer_formated);
                    row.push(item.anticipation_taxes_formated);
                    row.push(item.status);

                    vm.rows.push(row);

                    app.installmentsFilterOptions.push(parseInt(item.total_installments));

                });

                //Remove duplicated options
                app.installmentsFilterOptions = [...new Set(app.installmentsFilterOptions)];

                app.installmentsFilterOptions.sort(function(a, b) {
                    return a - b;
                });

                //@todo paginação, precisa analisar como fazer, mas é possível:
                //@todo https://willvincent.com/2016/04/08/making-vuejs-and-datatables-play-nice/
                // Here's the magic to keeping the DataTable in sync.
                // It must be cleared, new rows added, then redrawn!
                vm.dtHandle.clear();
                vm.dtHandle.rows.add(vm.rows);
                vm.dtHandle.draw();

                setTimeout(function(){
                    $('.selectpicker').selectpicker('refresh');
                },100);

            }
        }
    });

    $.extend($.fn.dataTable.defaults, {
        language: {
            url: base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'
        }
    });

    Vue.filter('money', function (value) {
        var formatter = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        return formatter.format(value);
    });

    const simulationDefault = {simulation:'',error:'',confirmed:false};

    var app = new Vue({
        el: '#app',
        data: {
            /** Data From PHP - Start */
            entry : <?php echo json_encode($entry); ?>,
            stores : <?php echo json_encode($filter_stores); ?>,
            filterable_status : <?php echo json_encode($filterable_status); ?>,
            storeLimits: [],
            simulation: structuredClone(simulationDefault),
            orderDetails: [],
            /** Data From PHP - END */
            /** Data Loaded Dinamicaly - START */
            orders_result: [],
            /** Data Loaded Dinamicaly - END */
            money: { //https://github.com/vuejs-tips/v-money
                decimal: ',',
                thousands: '.',
                prefix: 'R$ ',
                suffix: '',
                precision: 2,
                masked: false
            },
            submitResponse: '',
            saving: false,
            uploadingStores: false,
            uploadingProducts: false,
            uploadingApprovementProducts: false,
            uploadingImportProductsSeller: false,
            loading: false,
            showFilters: false,
            installmentsFilterOptions: [],
        },
        computed: {
            filtered_orders: function () {
                let self = this
                return self.orders_result.filter(function (order) {
                    return true;
                })
            },
        },
        mounted() {
            if (this.entry.store){
                this.changeStore(false);
            }
        },
        ready: function() {
        },
        methods: {
            selectOrder: async function (orderId) {

                for (let i = 0; i < this.orders_result.length; i++) {

                    let order = this.orders_result[i];

                    if (order.id == orderId){

                        indexFound = null;

                        for (let index_selected_orders = 0; index_selected_orders < this.entry.orders.length; index_selected_orders++) {
                            if (this.entry.orders[index_selected_orders].id == orderId){
                                indexFound = index_selected_orders;
                            }
                        }

                        if (indexFound != null){
                            this.entry.orders.splice(indexFound, 1);
                        }else{
                            this.entry.orders.push(order);
                        }

                    }

                }

            },
            sumTotalNotPaidSelectedOrders () {

                let total = 0;

                for (let index_selected_orders = 0; index_selected_orders < this.entry.orders.length; index_selected_orders++) {
                    total = parseFloat(total) + parseFloat(this.entry.orders[index_selected_orders].value_not_paid);
                    total = parseFloat(total).toFixed(2);
                }

                return total;

            },
            simulateAnticipation () {

                this.simulation = structuredClone(simulationDefault);

                if (this.hasAnticipatedOrderSelected()){

                    this.simulation.error = "Você não pode realizar uma simulação com pedidos que já foram antecipados.";
                    console.log(this.simulation.error);

                }else{

                    /**
                     * Load orders by store
                     */
                    let reqURL = base_url + 'anticipationsManagement/simulate_anticipation';

                    let entry = JSON.parse(JSON.stringify(this.entry))

                    this.$http.post(reqURL, JSON.stringify(entry)).then(response => {

                        this.simulation = response.body;

                    }, response => {
                        this.simulation.error = "Ocorreu um erro ao buscar os dados da loja selecionada";
                    });

                }

            },
            hasAnticipatedOrderSelected() {
                for (let index_selected_orders = 0; index_selected_orders < this.entry.orders.length; index_selected_orders++) {
                    if (parseInt(this.entry.orders[index_selected_orders].anticipated) === 1){
                        return true;
                    }
                }
                return false;
            },
            isSimulateAnticipationAllowed() {

                let canContinue = !this.hasAnticipatedOrderSelected();

                let sumTotalNotPaidSelectedOrders = this.sumTotalNotPaidSelectedOrders()

                if (canContinue && !(sumTotalNotPaidSelectedOrders >= parseFloat(this.storeLimits.minimum_amount) && sumTotalNotPaidSelectedOrders <= parseFloat(this.storeLimits.maximum_amount))){
                    canContinue = false;
                }

                $(function () {
                    $('.show-tooltip').tooltip();
                })

                return canContinue;

            },
            confirmSimulateAnticipation () {

                if (!this.isSimulateAnticipationAllowed()){
                    return false;
                }

                let reqURL = base_url + 'anticipationsManagement/confirm_simulate_anticipation';

                let simulation = JSON.parse(JSON.stringify(this.simulation))

                this.$http.post(reqURL, JSON.stringify(simulation)).then(response => {

                    if (response.body.success === true){
                        this.simulation.confirmed = true;
                    }else{
                        this.simulation.error = response.body.error;
                    }

                }, response => {
                    this.simulation.error = "Ocorreu um erro ao confirmar a simulação";
                });

            },
            cancelSimulateAnticipation () {

                if (this.simulation){

                    let reqURL = base_url + 'anticipationsManagement/cancel_simulate_anticipation';

                    let simulation = JSON.parse(JSON.stringify(this.simulation))

                    this.$http.post(reqURL, JSON.stringify(simulation)).then(response => {
                        if (response.body.success === true){
                            this.closeSimulationModal();
                        }else{
                            this.simulation.error = response.body.error;
                        }
                    }, response => {
                        this.simulation.error = "Ocorreu um erro ao cancelar a simulação";
                    });

                }else{
                    this.closeSimulationModal();
                }

            },
            closeSimulationModal (changeStore = false, resetSelectedOrders = false) {
                this.simulation = structuredClone(simulationDefault);
                $('#simulationModal').modal('hide');
                if (changeStore){
                    this.changeStore(false);
                }
                if (resetSelectedOrders){
                    this.entry.orders = [];
                }
            },
            closeSimulationModalAndShowOrders (simulationId) {
                this.simulation = structuredClone(simulationDefault);
                $('#simulationModal').modal('hide');
                this.entry.simulation_id = simulationId;
                this.entry.orders = [];
                this.entry.order_date = [];
                this.entry.status = '';
                this.entry.installments_number = [];
                this.changeStore(false, false);
            },
            changeStore (showFilters=false, emptySimulationId=true) {

                if (this.entry.anticipated_only == 0 && this.entry.simulation_id == '' && this.entry.store == ''){
                    this.orders_result = [];
                    this.entry.orders = [];
                    this.showFilters = false;
                    return false;
                }

                if (!showFilters){
                    this.showFilters = false;
                }
                this.loading = true;
                this.orders_result = [];
                this.entry.orders = [];
                if (emptySimulationId){
                    this.entry.simulation_id = '';
                }

                /**
                 * Load orders by store
                 */
                let reqURL = base_url + 'anticipationsManagement/load_index_data';

                let entry = JSON.parse(JSON.stringify(this.entry))

                this.$http.post(reqURL, JSON.stringify(entry)).then(response => {

                    this.orders_result = response.body.orders;
                    this.storeLimits = response.body.storeLimits;

                    this.loading = false;

                    if (!showFilters){
                        this.showFilters = response.body.orders.length > 0;
                    }

                }, response => {
                    alert('Ocorreu um erro ao buscar os dados da loja selecionada');
                    this.loading = false;
                    if (!showFilters){
                        this.showFilters = this.orders_result.length > 0;
                    }
                });

            },
            getOrderDetailsSimulated (orderId) {

                this.orderDetails = [];

                let reqURL = base_url + 'anticipationsManagement/load_order_details_simulated/'+orderId;

                this.$http.get(reqURL).then(response => {

                    this.orderDetails = response.body;

                }, response => {
                    alert('Ocorreu um erro ao buscar os dados do pedido selecionada');
                });

            },
            getOrderDetailsNotSimulated (orderId) {

                this.orderDetails = [];

                let reqURL = base_url + 'anticipationsManagement/load_order_details_not_simulated/'+orderId;

                this.$http.get(reqURL).then(response => {

                    this.orderDetails = response.body;

                }, response => {
                    alert('Ocorreu um erro ao buscar os dados do pedido selecionada');
                });

            },
            getCurrentStoreName () {
                let store_name = '';
                if (this.entry.store && this.stores && this.stores.length > 0){
                    const obj = this;
                    this.stores.forEach(function (store){
                        if (store.id == obj.entry.store){
                            store_name = store.name;
                        }
                    });
                }
                return store_name;
            },
            entryJson () {
                return JSON.stringify(this.entry);
            }
        }
    });

    function selectOrder(obj, orderId){
        app.selectOrder(orderId);
    }

    function checkAllOrders(obj){

        $('.order_checkbox').each(function(index, element){
            if (obj.checked){
                if (!element.checked){
                    $(element).click();
                }
            }else{
                if (element.checked){
                    $(element).click();
                }
            }
        });

    }

    function money(number){
        new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(number)
    }

</script>