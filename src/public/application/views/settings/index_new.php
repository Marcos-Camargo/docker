<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row" id="appSettings">
            <div class="col-md-12 col-xs-12">

                <div class="box">
                    <div class="box-body">

                        <div class="col-12 col-sm-12">

                            <div class="card card-primary card-outline card-outline-tabs">
                                <div class="card-header p-0 border-bottom-0">
                                    <ul class="nav nav-tabs" id="custom-tabs-four-tab" role="tablist">
                                        <?php $this->load->view('settings/components/tabs', ['categories', $categories]); ?>
                                    </ul>
                                </div>
                                <div class="card-body">

                                    <div class="tab-content" id="custom-tabs-all-tabContent">

                                        <div class="tab-pane fade active in" id="custom-tabs-content-all"
                                             role="tabpanel" aria-labelledby="custom-tabs-all"
                                             style="position: relative;min-height: 150px">
                                            <div v-show="!showLoading">
                                                <?php $this->load->view('settings/components/search_general'); ?>
                                            </div>

                                            <div class="overlay-wrapper" style="margin-top:30px">
                                                <div class="overlay" v-show="showLoading"><i
                                                            class="fas fa-3x fa-sync-alt fa-spin"></i>
                                                    <div class="text-bold pt-2">Buscando parâmetros...</div>
                                                </div>
                                                <listitem
                                                        v-if="sanitizeTitle(settings.category) == sanitizeTitle('all')"
                                                        v-for="(item, index) in items" :el="item"
                                                        :show="showDescription"
                                                        :key="index">
                                                </listitem>
                                            </div>

                                        </div>

                                        <?php
                                        foreach ($categories as $key => $category) {
                                            ?>
                                            <div class="tab-pane fade" id="custom-tabs-content-<?= $category['id'] ?>"
                                                 role="tabpanel" style="position: relative;min-height: 150px"
                                                 aria-labelledby="custom-tabs-<?= $category['id'] ?>">

                                                <?php $this->load->view('settings/components/search_general'); ?>

                                                <div class="overlay-wrapper" style="margin-top:30px">
                                                    <div class="overlay" v-show="showLoading"><i
                                                                class="fas fa-3x fa-sync-alt fa-spin"></i>
                                                        <div class="text-bold pt-2">Buscando parâmetros...</div>
                                                    </div>
                                                    <div v-if="sanitizeTitle(settings.category) == sanitizeTitle('<?= strtolower($category['name']) ?>')"
                                                         v-show="!showLoading">
                                                        <h3> {{settings.catName.toUpperCase()}}</h3>
                                                        <div class="blue-border skin-conectala">
                                                            <h4>Regras de Negócio</h4>
                                                            <table :id="sanitizeTitle(settings.category)"
                                                                   class="table table-hover table-bordered wrap"
                                                                   style="width:100%; padding-top:15px">
                                                                <thead>
                                                                <tr>
                                                                    <th></th>
                                                                    <th>Nome da configuração</th>
                                                                    <th>Descrição</th>
                                                                    <th>Valor</th>
                                                                    <th>Status</th>
                                                                    <th></th>
                                                                </tr>
                                                                </thead>
                                                                <tbody>
                                                                <tr v-for="(item, index) in settings.items"
                                                                    class="linha-ciclo-loja" :key="index">
                                                                    <td>{{item.name}}</td>
                                                                    <td style="width:25%" data-toggle="tooltip"
                                                                        data-placement="top" :data-tooltip="item.name">
                                                                        {{item.friendly_name}}
                                                                    </td>
                                                                    <td style="width:50%;word-break: break-word;%">
                                                                        {{splitString(item.description, 15,
                                                                        5)}}{{splitString(item.description, 15, 0) !==
                                                                        '' && showDescription !=
                                                                        item.id ? '...' : ''}}
                                                                        <div v-if="item.description.length > 0 && splitString(item.description, 15, 0) !== ''"
                                                                             class="anim-block"
                                                                             :class="showDescription == item.id ? 'open' : ''">
                                                                            <div class="content-description">
                                                                                {{splitString(item.description, 10, 0)}}
                                                                            </div>
                                                                        </div>

                                                                        <button @click="resetFn(item.id)"
                                                                                v-if="item.description.length > 0 && splitString(item.description, 15, 0) !== ''"
                                                                                type="button"
                                                                                class="btn btn-block btn-default ver-mais edit"
                                                                                style="border: 0;width: auto">
                                                                            <i class="fa "
                                                                               :class="showDescription != item.id ? 'fa-angle-down' : 'fa-angle-up'"
                                                                               aria-hidden="true"></i> Ver
                                                                            {{showDescription != item.id ? 'mais' :
                                                                            'menos'}}
                                                                        </button>
                                                                    </td>
                                                                    <td style="width:20%;text-align:left;word-break: break-word;">
                                                                        {{item.value}}
                                                                    </td>
                                                                    <td style="width:10%;text-align:left">
                                                                        <span v-if="item.status == 1"
                                                                          class="badge badge-success navbar-badge">Ativo</span>
                                                                        <span v-if="item.status != 1"
                                                                              class="badge badge-danger navbar-badge">Inativo</span>
                                                                    </td>

                                                                    <td style="width:5%">
                                                                        <button type="button" :disabled="buttonDisable"
                                                                                class="btn btn-block btn-default edit skin-conectala"
                                                                                @click="editSetting(item)">
                                                                            <i class="fa fa-pen"></i> Editar valor
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                    </div>
                                                </div>

                                            </div>
                                            <?php
                                        }
                                        ?>

                                    </div>

                                </div>

                            </div>
                        </div>

                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
            <!-- col-md-12 -->
            <?php $this->load->view('settings/components/modal_edit_setting'); ?>
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->


<link rel="stylesheet" href="<?php echo base_url('assets/vue/datetime_picker/index.css') ?>">
<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"
        type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/datetime_picker/index.min.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/datetime_picker/pt-br.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue-resource.min.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/dist/js/datatable/dataTable.rowGroup.min.js') ?>"
        type="text/javascript"></script>

<script type="text/javascript">
    $(document).ready(function () {

        const languageDatatable = '<?php echo base_url('assets/bower_components/datatables.net/i18n/Portuguese_br.lang'); ?>'
        const base_url = "<?php echo base_url(); ?>";
        let table;

        const ListItem = {
            props: [
                'el',
                'show'
            ],
            methods: {
                editSetting(setting) {
                    app.editSetting(setting);
                },
                resetFn(id) {
                    app.resetFn(id)
                },
                sanitizeTitle(string) {
                    return app.sanitizeTitle(string)
                },
                splitString(string, start, end) {
                    return app.splitString(string, start, end)
                }
            },
            template: <?php $this->load->view('settings/components/data_table_settings'); ?>,
        }

        const app = new Vue({
            el: '#appSettings',
            data: {
                openedTab: [],
                buttonDisable: false,
                showLoading: true,
                search: '',
                showDescription: '',
                settingData: '',
                settingAddData: {
                    name: '',
                    value: '',
                    status: '',
                    friendly_name: '',
                    description: ''
                },
                settings: {
                    catName: '',
                    category: 'all',
                    items: []
                },
                items: [],
            },
            components: {
                listitem: ListItem
            },
            computed: {},
            mounted() {
                this.getSettings()
            },
            ready: function () {

            },
            methods: {
                getSettingByTab(category) {
                    for (let i = 0; i < this.items.length; i++) {
                        if (category === 'all') {
                            this.settings.items = this.items[i].settings
                            this.searchData()
                            return
                        } else if (this.items[i].category === category) {
                            this.settings.items = this.items[i].settings
                            Vue.nextTick(() => {
                                table = $('#' + this.sanitizeTitle(category)).DataTable({
                                    order: [[1, 'asc']],
                                    language: {
                                        url: languageDatatable,
                                    },
                                    columnDefs: [
                                        {"visible": false, "targets": 0, "searchable": true,},
                                    ],
                                    paging: false,
                                    searching: true,
                                    info: false,
                                    "dom": 'rtip'
                                });
                            });
                            return
                        }
                    }
                },
                editSetting(setting) {
                    this.settingData = setting
                    $('#edit-setting').modal('toggle');
                },
                addSetting() {
                    this.settingAddData.name = ''
                    this.settingAddData.value = ''
                    this.settingAddData.status = ''
                    this.settingAddData.friendly_name = ''
                    this.settingAddData.description = ''
                    $('#add-setting').modal('toggle');
                },
                resetFn(id) {
                    if (this.showDescription !== id) {
                        this.showDescription = id
                    } else {
                        this.showDescription = ''
                    }
                },
                splitString(string, start, end) {
                    let s = string.split(' ')
                    if (end === 0) {
                        return s.slice(start).join(' ');
                    }
                    return s.slice(0, start).join(' ');
                },
                searchData() {
                    if (this.settings.category === 'all') {
                        for (let i = 0; i < this.items.length; i++) {
                            const category = this.items[i].category;
                            if ($.fn.dataTable.isDataTable('#' + this.sanitizeTitle(category) + '-all')) {
                                Vue.nextTick(() => {
                                    $('#' + this.sanitizeTitle(category) + '-all').DataTable({
                                        order: [[1, 'asc']],
                                        retrieve: true,
                                        language: {
                                            url: languageDatatable,
                                        },
                                        paging: false,
                                        "columnDefs": [
                                            {"visible": false, "targets": 0, "searchable": true},
                                        ],
                                    }).draw();
                                })
                            } else {
                                Vue.nextTick(() => {
                                    $('#' + this.sanitizeTitle(category) + '-all').DataTable({
                                        order: [[1, 'asc']],
                                        language: {
                                            url: languageDatatable,
                                        },
                                        paging: false,
                                        "columnDefs": [
                                            {"visible": false, "targets": 0, "searchable": true},
                                        ],
                                    }).draw();
                                })
                            }
                        }
                    } else {
                        table.draw();
                    }
                },
                insertSetting() {
                    this.buttonDisable = true
                    let reqURL = base_url + 'settings/insertSetting';
                    this.$http.post(reqURL, this.settingAddData).then(response => {
                        this.buttonDisable = false
                        console.log(response.body)
                        if (response.body.status === false) {
                            this.alertResponses("Atenção", response.body.message, "warning")
                        } else {
                            $('#add-setting').modal('toggle');
                            this.alertResponses("Sucesso", "Parãmetro salvo com sucesso!", "success", 'reload');
                        }
                    }, response => {
                        console.log(response.body)
                    });
                },
                saveSetting() {
                    this.buttonDisable = true
                    let reqURL = base_url + 'settings/saveSetting';
                    this.$http.post(reqURL, this.settingData).then(response => {
                        this.buttonDisable = false
                        $('#edit-setting').modal('toggle');
                        this.alertResponses("Sucesso", "Parãmetro salvo com sucesso!", "success")
                    }, response => {
                        console.log(response.body)
                    });
                },
                checkItemsArray(category) {
                    for (let i = 0; i < this.items.length; i++) {
                        if (this.items.length > 0 && this.items[i].category === category) {
                            return true;
                        }
                    }
                    return false;
                },
                getSettings() {
                    this.showLoading = true
                    let reqURL = base_url + 'settings/getSettings';
                    this.$http.post(reqURL, this.settings).then(response => {
                        let items = response.body
                        if (this.settings.category === 'all') {
                            for (let i = 0; i < items.length; i++) {
                                let category = this.sanitizeTitle(items[i].category);
                                if (!this.checkItemsArray(category)) {
                                    items[i].category = category
                                    this.items.push(items[i])
                                }
                            }
                            this.searchData()
                        }
                        this.showLoading = false
                    }, response => {
                        console.log(response.body)
                    });
                },
                sanitizeTitle: function (value) {
                    value = value.toLowerCase();
                    value = value.replace(/[á|ã|â|à]/gi, "a");
                    value = value.replace(/[é|ê|è]/gi, "e");
                    value = value.replace(/[í|ì|î]/gi, "i");
                    value = value.replace(/[õ|ò|ó|ô]/gi, "o");
                    value = value.replace(/[ú|ù|û]/gi, "u");
                    value = value.replace(/[ç]/gi, "c");
                    value = value.replace(/[ñ]/gi, "n");
                    value = value.replace(/[á|ã|â]/gi, "a");
                    value = value.replace(/\W/gi, "-");
                    value = value.replace(/(\-)\1+/gi, "-");
                    return value;
                },
                alertResponses(title, message, icon, actionClick = '', showCancelButton = false) {
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
                                location.reload();
                            }
                        }
                    })
                }
            }

        });


        $.fn.dataTable.ext.search.push(
            function (settings, searchData, index, rowData, counter) {
                var _search = $('#search' + app.sanitizeTitle(app.settings.category)).val();
                if (_search.length === 0) {
                    return true;
                }

                var hasMatch1 = false;
                if (searchData[0].includes(_search)) {
                    hasMatch1 = true;
                }

                var hasMatch2 = false;
                if (searchData[1].includes(_search)) {
                    hasMatch2 = true;
                }

                var hasMatch3 = false;
                if (searchData[2].includes(_search)) {
                    hasMatch3 = true;
                }

                var hasMatch4 = false;
                if (searchData[3].includes(_search)) {
                    hasMatch4 = true;
                }

                return hasMatch1 || hasMatch2 || hasMatch3 || hasMatch4;

            });
    });

</script>
