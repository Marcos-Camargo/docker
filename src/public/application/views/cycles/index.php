<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "";
    $data['page_now'] = 'parameter_payment_cycles';
    $this->load->view('templates/content_header', $data); ?>

    <div id="appMarketplace" class="cycle-tabs">
        <!-- Main content -->
        <section class="content">
            <!-- Small boxes (Stat box) -->
            <div class="row">
                <div class="col-md-12 col-xs-12">

                    <div id="messages"></div>

                    <?php if ($this->session->flashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <?php echo $this->session->flashdata('success'); ?>
                        </div>
                    <?php elseif ($this->session->flashdata('error')): ?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <?php echo $this->session->flashdata('error'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-12">

                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                    <li
                                            class="<?php echo (isset($_GET['type']) && $_GET['type'] == 'marketplace') || !isset($_GET['type']) ? 'active' : '' ?>">
                                        <a href="#tab_cycle_marketplace" data-toggle="tab"
                                           aria-expanded="true"
                                           @click="vOpenedTab = 'mkt'; form.vStores = ''; !vTabMktOpened ? loadingAllCyclesByMarketplace() : {}"><?= $this->lang->line('cycles_by_marketplace') ?></a>
                                    </li>
                                    <li class="<?php echo (isset($_GET['type']) && $_GET['type'] == 'loja') ? 'active' : '' ?>">
                                        <a href="#tab_cycle_store" data-toggle="tab" aria-expanded="false"
                                           @click="vOpenedTab = 'store'; !vTabStoreOpened ? loadingAllCyclesByStore() : {}"><?= $this->lang->line('cycles_by_store') ?></a>
                                    </li>
                                    <li class=""><a href="#tab_all_cycles" data-toggle="tab" aria-expanded="false"
                                                    @click="loadingAllCycles()"><?= $this->lang->line('all_cycles_registered') ?></a>
                                    </li>

                                    <li class=""><a href="#tab_model_cycles" data-toggle="tab" aria-expanded="false"
                                                    @click="loadingCyclesModels()"><?= $this->lang->line('cycle_models') ?></a>
                                    </li>

                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane <?php echo (isset($_GET['type']) && $_GET['type'] == 'marketplace') || !isset($_GET['type']) ? 'active' : '' ?>"
                                         id="tab_cycle_marketplace">
                                        <?php get_instance()->load->view('cycles/marketplace_cycles'); ?>
                                    </div>

                                    <div class="tab-pane <?php echo (isset($_GET['type']) && $_GET['type'] == 'loja') /*|| !isset($_GET['type'])*/ ? 'active' : '' ?>"
                                         id="tab_cycle_store">
                                        <?php get_instance()->load->view('cycles/store_cycles'); ?>
                                    </div>

                                    <div class="tab-pane" id="tab_all_cycles">
                                        <?php get_instance()->load->view('cycles/all_cycles'); ?>
                                    </div>

                                    <div class="tab-pane" id="tab_model_cycles">
                                        <?php get_instance()->load->view('cycles/model_cycles'); ?>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>
                </div>
                <!-- col-md-12 -->
            </div>
            <!-- /.row -->
        </section>
        <!-- /.content -->

        <?php get_instance()->load->view('cycles/modal_register_cycle'); ?>
    </div>

</div>
<!-- /.content-wrapper -->

<!--https://github.com/mengxiong10/vue2-datepicker-->
<link rel="stylesheet" href="<?php echo base_url('assets/vue/datetime_picker/index.css') ?>">

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"
        type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/datetime_picker/index.min.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/datetime_picker/pt-br.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue-resource.min.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/dist/js/datatable/dataTable.rowGroup.min.js') ?>" type="text/javascript"></script>

<script type="text/javascript">

    const base_url = "<?php echo base_url(); ?>";

    $(document).ready(function () {

    });

    const app = new Vue({
        el: '#appMarketplace',
        data: {
            base_url: "<?php echo base_url(); ?>",
            search: {
                vStore: '',
                vInicio: '',
                vFim: '',
                vDataPagamento: '',
                vDataPagamentoConectala: ''
            },
            form: {
                vDataInicio: '',
                vDataFim: '',
                vDataPagamentoMkt: '',
                vDataPagamentoConectala: '',
                vMarketplace: '',
                vStores: '',
                vDateCut: '<?= isset($cutDates) ? $cutDates[0]->id : '' ?>',
                vHiddenId: 0
            },
            model: {
                vHiddenId: 0,
                vModelDataInicio: '',
                vModelDataFim: '',
                vModelDataPagamentoMkt: '',
            },
            storesSelectedLines: [],
            tableCyclesByMarketplace: '',
            tableCyclesByStore: '',
            tableModelCycles: '',
            saving: false,
            checkinCycleChoice: false,
            vShowCyclesExisting: false,
            vCutDates: [],
            vMarketplaces: [],
            vStores: [],
            vCyclesList: [],
            vModelCyclesList: [],
            vCyclesListMarketplace: [],
            vAllCyclesList: [],
            vCyclesListStore: [],
            vCyclesCutDates: [],
            vLoadingTables: false,
            vMessageLoading: '',
            vTabStoreOpened: false,
            vTabMktOpened: false,
            vOpenedTab: 'mkt'
        },
        computed: {},
        mounted() {
            this.loadingCycles();
            this.loadingCutDates();
            <?php echo (isset($_GET['type']) && $_GET['type'] == 'marketplace') || !isset($_GET['type']) ? 'this.loadingAllCyclesByMarketplace();' : 'this.loadingAllCyclesByStore();' ?>
            // this.loadingCyclesModels();
            this.loadingStores();
            this.loadingMarketplaces();
        },
        ready: function () {
            // setTimeOut(() => {
            //     $('.selectpicker').selectpicker('refresh');
            // }, 3000);
        },
        methods: {

            submitSearch() {
                this.vMessageLoading = '<?= $this->lang->line('loading_cycles_by_store') ?>'
                this.vLoadingTables = true
                let reqURL = base_url + 'cycles/getAllCyclesRegisteredByStore';
                this.$http.post(reqURL, this.search).then(response => {
                    this.vCyclesListStore = response.body;
                    $('#store_cycles').DataTable().destroy();
                    Vue.nextTick(() => {
                        this.tableCyclesByStore = $('#store_cycles').DataTable({
                            "pageLength": 10,
                            "lengthChange": true,
                            "lengthMenu": [ 25, 50, 75, "Todos" ],
                            "searching": false,
                            order: [[2, 'asc']],
                            rowGroup: {
                                dataSrc: [2]
                            },
                            columnDefs: [
                                {
                                    targets: [2],
                                    visible: false,
                                },
                                {
                                    orderable: false, targets: [0, -1]
                                }
                            ],
                        });
                    });
                    this.vMessageLoading = ''
                    this.vLoadingTables = false
                }, response => {
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            openNewTab(url) {
                window.open(url, '_blank', 'noreferrer');
            },
            exportXls() {
                this.vMessageLoading = '<?= $this->lang->line('export_cycles_by_store') ?>'
                let reqURL = base_url + 'cycles/exportXls';
                this.$http.post(reqURL, this.search).then(response => {
                    this.vMessageLoading = ''
                }, response => {
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            cleanForm() {
                this.form.vDataInicio = ''
                this.form.vDataFim = ''
                this.form.vDataPagamentoMkt = ''
                this.form.vDataPagamentoConectala = ''
                this.form.vHiddenId = 0
                this.vShowCyclesExisting = false
            },
            showCyclesExisting() {
                if (this.form.vMarketplace === '') {
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', 'Selecione um marketplace primeiro!', 'warning', null);
                    return
                }
                this.vShowCyclesExisting = !this.vShowCyclesExisting;
            },
            editThisCycle(cycle) {
                this.saving = true
                this.cleanForm();
                this.form.vStores = cycle.store_id ?? 0
                $(".select-lojas").attr('disabled',true);
                $(".select-lojas").val(this.form.vStores);
                $('.select-lojas').selectpicker('refresh');
                this.form.vHiddenId = cycle.pmc_id
                this.form.vDataInicio = cycle.data_inicio
                this.form.vDataFim = cycle.data_fim
                this.form.vDataPagamentoMkt = cycle.data_pagamento
                this.form.vDataPagamentoConectala = cycle.data_pagamento_conecta ?? ""
                this.form.vMarketplace = cycle.integ_id
                this.form.vDateCut = cycle.cut_id
                this.saving = false
                $('#register-cycle').modal('toggle');
            },
            editModelCycle(cycle) {
                this.saving = true
                this.model.vHiddenId = cycle.id
                this.model.vModelDataInicio = cycle.data_inicio
                this.model.vModelDataFim = cycle.data_fim
                this.model.vModelDataPagamentoMkt = cycle.data_pagamento
                this.saving = false
            },
            getThisCycle(cycle) {
                this.form.vDataInicio = cycle.data_inicio
                this.form.vDataFim = cycle.data_fim
                this.form.vDataPagamentoMkt = cycle.data_pagamento
                this.form.vDataPagamentoConectala = cycle.data_pagamento_conecta
                this.showCyclesExisting();
                this.checkinCycleChoice = !this.checkinCycleChoice;
            },
            checkCycleUsed(cycle) {
                this.checkinCycleChoice = !this.checkinCycleChoice;
                cycle.mktplace = this.form.vMarketplace;
                cycle.store_id = null;
                if(this.vOpenedTab === 'store') {
                    cycle.store_id = this.form.vStores
                }
                let reqURL = base_url + 'cycles/checkCycleUsed';
                this.$http.post(reqURL, cycle).then(response => {
                    if (response.body === "1") {
                        this.getThisCycle(cycle);
                    } else {
                        this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('register_cycle_error') ?>', 'warning', null);
                        this.checkinCycleChoice = !this.checkinCycleChoice;
                    }
                }, response => {
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                    this.checkinCycleChoice = !this.checkinCycleChoice;
                });

            },
            submitModel() {
                this.saving = true
                let reqURL = base_url + 'cycles/saveModel';
                this.$http.post(reqURL, this.model).then(response => {
                    if (response.body === 1) {
                        this.model.vModelDataInicio = ''
                        this.model.vModelDataFim = ''
                        this.model.vModelDataPagamentoMkt = ''
                        this.alertResponses('<?= $this->lang->line('success_cycle') ?>', '<?= $this->lang->line('save_new_model_cycle_success') ?>', 'success', 'reload');

                        this.saving = false;
                        $('#model-cycle').modal('toggle');
                    } else {
                        this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('register_model_cycles_error') ?>', 'warning', null);
                        this.saving = false;
                    }
                }, response => {
                    this.saving = false;
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            submitCycle() {
                this.saving = true
                this.checkinCycleChoice = !this.checkinCycleChoice;
                let reqURL = base_url + 'cycles/saveCycle';
                this.$http.post(reqURL, this.form).then(response => {
                    if (response.body === 'success') {
                        this.cleanForm();
                        this.alertResponses('<?= $this->lang->line('success_cycle') ?>', '<?= $this->lang->line('save_new_cycle_success') ?>', 'success', 'reload');
                        this.saving = false;
                        $('#register-cycle').modal('toggle');
                    } else {
                        this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('register_cycle_error') ?>', 'warning', null);
                        this.saving = false;
                    }
                    this.checkinCycleChoice = !this.checkinCycleChoice;
                }, response => {
                    this.saving = false;
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                    this.checkinCycleChoice = !this.checkinCycleChoice;
                });
            },
            loadingCyclesModels() {
                this.vOpenedTab = 'models'
                this.vMessageLoading = '<?= $this->lang->line('loading_cycles_models') ?>'
                this.vLoadingTables = true
                let reqURL = base_url + 'cycles/getModelCycles';
                this.$http.get(reqURL).then(response => {
                    this.vModelCyclesList = response.body;
                    this.tableModelCycles = $('#model_cycles').DataTable().destroy();
                    Vue.nextTick(() => {
                        $("#model_cycles").DataTable({
                            "lengthChange": true,
                            "lengthMenu": [ 25, 50, 75, "Todos" ],
                            "pageLength": 5,
                            "bFilter": false,
                            //"lengthChange": false,
                        });
                    });
                    this.vMessageLoading = ''
                    this.vLoadingTables = false
                }, response => {
                    this.vMessageLoading = ''
                    this.vLoadingTables = false
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            loadingStores() {
                let reqURL = base_url + 'cycles/getAllStores';
                this.$http.get(reqURL).then(response => {
                    this.vStores = response.body;
                    this.$nextTick(() => {
                        $(".select-lojas").selectpicker();
                        $('.select-lojas').selectpicker('refresh');
                    })
                    // this.form.vStores = this.vStores[0].id

                }, response => {
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            loadingMarketplaces() {
                let reqURL = base_url + 'cycles/getAllMktPlace';
                this.$http.get(reqURL).then(response => {
                    this.vMarketplaces = response.body;
                    this.form.vMarketplace = this.vMarketplaces[0].id
                }, response => {
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            loadingCutDates() {
                let reqURL = base_url + 'cycles/getCyclesCutDates';
                this.$http.get(reqURL).then(response => {
                    this.vCutDates = response.body;
                    this.form.vDateCut = this.vCutDates[0].id
                }, response => {
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            loadingCycles() {
                let reqURL = base_url + 'cycles/getCyclesRegistered';
                this.$http.get(reqURL).then(response => {
                    this.vCyclesList = response.body;
                    Vue.nextTick(() => {
                        $("#cycles_registered").DataTable({
                            "pageLength": 5,
                            "bFilter": true,
                            "lengthChange": false,
                        });
                    });
                }, response => {
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            loadingAllCycles() {
                this.storesSelectedLines = [];
                this.vMessageLoading = 'carregando ciclos...'
                this.vLoadingTables = true
                let reqURL = base_url + 'cycles/getAllCycles';
                this.$http.get(reqURL).then(response => {
                    this.vAllCyclesList = response.body;
                    $('#all_cycles').DataTable().destroy();
                    Vue.nextTick(() => {
                        this.tableCyclesByMarketplace = $('#all_cycles').DataTable({
                            "lengthChange": true,
                            "lengthMenu": [ 25, 50, 75, "Todos" ],
                            "pageLength": 10,
                            // "lengthChange": false
                        });
                    });
                    this.vTabMktOpened = true
                    this.vMessageLoading = ''
                    this.vLoadingTables = false
                }, response => {
                    this.vMessageLoading = ''
                    this.vLoadingTables = false
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            loadingAllCyclesByMarketplace() {
                this.vOpenedTab = 'mkt';
                this.vMessageLoading = '<?= $this->lang->line('loading_cycles_by_marketplace') ?>'
                this.vLoadingTables = true
                let reqURL = base_url + 'cycles/getAllCyclesRegisteredByMarketplace';
                this.$http.get(reqURL).then(response => {
                    this.vCyclesListMarketplace = response.body;
                    $('#marketplaces_cycles').DataTable().destroy();
                    Vue.nextTick(() => {
                        this.tableCyclesByMarketplace = $('#marketplaces_cycles').DataTable({
                            "pageLength": 10,
                            "lengthChange": true,
                            "lengthMenu": [ 25, 50, 75, "Todos" ],
                            dom: 'Blfrtip',
                            columnDefs: [
                                {orderable: false, targets: -1}
                            ]
                        });
                    });
                    this.vTabMktOpened = true
                    this.vMessageLoading = ''
                    this.vLoadingTables = false
                }, response => {
                    this.vMessageLoading = ''
                    this.vLoadingTables = false
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            loadingAllCyclesByStore() {
                this.vMessageLoading = '<?= $this->lang->line('loading_cycles_by_store') ?>'
                this.vLoadingTables = true
                let reqURL = base_url + 'cycles/getAllCyclesRegisteredByStore';
                this.$http.get(reqURL).then(response => {
                    this.vCyclesListStore = response.body;
                    this.$nextTick(() => {
                        $(".select-lojas").selectpicker();
                        $('.select-lojas').selectpicker('refresh');
                    })
                    $('#store_cycles').DataTable().destroy();
                    Vue.nextTick(() => {
                        this.tableCyclesByStore = $('#store_cycles').DataTable({
                            "pageLength": 10,
                            "lengthChange": true,
                            "lengthMenu": [ 25, 50, 75, "Todos" ],
                            "searching": false,
                            order: [[2, 'asc']],
                            rowGroup: {
                                dataSrc: [2]
                            },
                            columnDefs: [
                                {
                                    targets: [2],
                                    visible: false,
                                },
                                {
                                    orderable: false, targets: [0, -1]
                                }
                            ],
                        });
                    });
                    this.vTabStoreOpened = true
                    this.vMessageLoading = ''
                    this.vLoadingTables = false
                }, response => {
                    this.vMessageLoading = ''
                    this.vLoadingTables = false
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                });
            },
            selectCycleLine(cycle) {
                let row = '.linha_' + cycle.pmc_id;
                let id = cycle.pmc_id;
                if (!this.storesSelectedLines.includes(id)) {
                    $(row).addClass('red-bg');
                    this.storesSelectedLines.push(id);
                } else {
                    this.storesSelectedLines = this.storesSelectedLines.filter(item => item !== id)
                    $(row).removeClass('red-bg');
                }
            },
            removeCycle(cycle, table, cycles = null) {

                if (this.storesSelectedLines.length === 0 && table === 'store' && cycle == null) {
                    this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('select_line_table') ?>', 'warning', null);
                    return;
                }

                Swal.fire({
                    title: "<?= $this->lang->line('attention_cycle') ?>",
                    text: cycles == null ? '<?= $this->lang->line('remove_cycle') ?>' : '<?= $this->lang->line('remove_cycles') ?>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim',
                    cancelButtonText: 'Não'
                }).then((result) => {
                    if (result.value) {
                        this.vMessageLoading = '<?= $this->lang->line('removing_cycle') ?>'
                        this.vLoadingTables = true

                        let reqURL = base_url + 'cycles/removeCycles';
                        let request = this.$http.post(reqURL, this.storesSelectedLines);

                        if (table === 'models') {
                            reqURL = base_url + 'cycles/removeModel/' + cycle.id;
                            request = this.$http.get(reqURL);
                        } else if (cycles == null) {
                            reqURL = base_url + 'cycles/removeCycle/' + cycle.pmc_id;
                            request = this.$http.get(reqURL);
                        }

                        request.then(response => {
                            this.vMessageLoading = ''
                            this.vLoadingTables = false

                            if (response.body === 'success') {

                                if (table === 'models') {
                                    this.tableModelCycles.row('.linha_' + cycle.id).remove().draw(false);
                                } else if (cycles == null) {
                                    if (table === 'store') {
                                        this.tableCyclesByStore.row('.linha_' + cycle.pmc_id).remove().draw(false);
                                    } else if (table === 'mkt') {
                                        this.tableCyclesByMarketplace.row('.linha_' + cycle.pmc_id).remove().draw(false);
                                    }
                                } else {
                                    for (i = 0; i < this.storesSelectedLines.length; i++) {
                                        this.tableCyclesByStore.row('.linha_' + this.storesSelectedLines[i]).remove().draw(false);
                                    }
                                }

                                this.alertResponses('<?= $this->lang->line('success_cycle') ?>', '<?= $this->lang->line('remove_cycle_success') ?>', 'success', 'reload');
                            } else {
                                this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                            }


                        }, response => {
                            this.vMessageLoading = ''
                            this.vLoadingTables = false
                            this.alertResponses('<?= $this->lang->line('attention_cycle') ?>', '<?= $this->lang->line('general_error_cycle') ?>', 'danger', null);
                        });
                    }
                })
            },
            alertResponses(title, message, icon, actionClick, showCancelButton = false) {
                Swal.fire({
                    title: title,
                    text: message,
                    icon: icon,
                    showCancelButton: showCancelButton,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ok',
                    cancelButtonText: 'Fechar'
                }).then((result) => {
                    if (result.value) {
                        if (actionClick === 'reload') {
                            if (this.vOpenedTab === 'store') {
                                this.loadingAllCyclesByStore();
                            } else if (this.vOpenedTab === 'mkt') {
                                this.loadingAllCyclesByMarketplace();
                            } else if (this.vOpenedTab === 'models') {
                                this.loadingCyclesModels();
                            }

                        }
                    }
                })
            }
        }
    });


</script>