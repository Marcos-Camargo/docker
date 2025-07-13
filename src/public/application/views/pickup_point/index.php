<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content" id="app-pickup">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <?php if (in_array('createPickUpPoint', $this->permission)): ?>
                    <a href="<?= base_url('PickupPoint/create') ?>" class="btn btn-primary">
                        Adicionar ponto de retirada
                    </a>
                    <br/> <br/>
                <?php endif; ?>


                <div class="box box-primary">
                    <div class="box-body" id="filters">
                        <div class="row d-flex flex-wrap">
                            <div class="form-group col-md-3">
                                <label for="stores"
                                       class="normal"><?= $this->lang->line('application_search_for_store') ?></label>
                                <select class="form-control selectpicker show-tick" id="stores" data-live-search="true"
                                        required @change="loadPickupPoints()">
                                    <option value=""></option>
                                    <?php foreach ($stores as $store) { ?>
                                        <option value="<?= $store->id ?>"><?= $store->name ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="normal" style="display: block;">&nbsp; </label>
                                <button type="button" @click="clearFilters()" class=" btn btn-primary"
                                        :disabled="loading"><i
                                            class="fa fa-eraser"></i> <?= $this->lang->line('application_clear') ?>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>


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

                <div class="box">
                    <div class="box-body">
                        <div class="overlay-wrapper" v-show="loading">
                            <div class="overlay" style="position:relative;"><i
                                        class="fas fa-3x fa-sync-alt fa-spin"></i>
                                <div class="text-bold pt-2">Carregando pontos de retirada...</div>
                            </div>
                        </div>
                        <table id="manageTable" class="table table-bordered table-striped" v-show="!loading"
                               style="width: 100%">
                            <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Rua</th>
                                <th>Bairro</th>
                                <th>Cidade</th>
                                <th>Estado</th>
                                <th>Status</th>
                                <?php if (!$this->model_settings->getValueIfAtiveByName('use_ms_shipping')): ?>
                                    <th><?=$this->lang->line('application_store')?></th>
                                <?php endif; ?>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>

                            </tr>
                            </tbody>

                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->

    </section>
    <!-- /.content -->

</div>
<!-- /.content-wrapper -->

<!-- Modal -->

<style>
    .select2-container--open {
        z-index: 9999999
    }

    .modal-open .select2-container {
        z-index: 9999;
    }
</style>

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
        $("#mainLogisticsNav").addClass('active');
        $("#pickupPointRulesNav").addClass('active');

        const base_url = "<?php echo base_url(); ?>";

        const app = new Vue({
            el: '#app-pickup',
            data: {
                redirect: '',
                loading: true,
                buttonDisable: false,
                modalForm: {
                    store: '',
                    nome: '',
                    status: '',
                    cep: '',
                    rua: '',
                    bairro: '',
                    cidade: '',
                    estado: '',
                    pais: '',
                    numero: '',
                    complemento: ''
                },
            },
            components: {},
            computed: {},
            mounted() {

                $('#add-pickup-point').on('shown.bs.modal', e => {
                    $('.select2').each(function () {
                        $(this).select2({dropdownParent: $(this).parent()});
                    })
                    $('.stores').on('change', () => {
                        this.modalForm.store = $('.stores').find(":selected").val();
                    });

                    $('.paises').on('change', (e) => {
                        console.log(e)
                        this.modalForm.pais = $('.paises').find(":selected").val();
                    });
                });

                this.loadPickupPoints()

            },
            ready: function () {

            },
            methods: {
                clearFilters() {
                    $('#stores').val('');
                    $('#stores').trigger('change');
                    this.loadPickupPoints()
                },
                loadPickupPoints() {


                    if ($.fn.DataTable.isDataTable('#manageTable')) {
                        $('#manageTable').DataTable().destroy();
                    }

                    this.loading = true;

                    let store_id = $('#stores').val();

                    $('#manageTable').DataTable({
                        "language": {"url": "<?=base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
                        "processing": true,
                        "serverSide": true,
                        "serverMethod": "post",
                        "ajax": $.fn.dataTable.pipeline({
                            url: "<?=base_url('PickupPoint/getPickupPoints')?>",
                            data: {store_id},
                            pages: 2
                        }),
                        'fnDrawCallback': function () {
                            app.loading = false;
                        }
                    });

                },
                save() {
                    this.buttonDisable = true
                    let reqURL = base_url + 'PickupPoint/saveAddress';
                    this.$http.post(reqURL, this.modalForm).then(response => {
                        this.buttonDisable = false
                        if (response.body.status === false && response.body.reason === 'form_empty') {
                            this.alertResponses("Atenção", response.body.message, "warning")
                        } else {
                            this.redirect = response.body.id
                            $('#add-pickup-point').modal('toggle');
                            this.alertResponses("Sucesso", response.body.message, "success", 'reload')
                        }
                    }, response => {
                        console.log(response.body)
                    });
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
                                window.location.href = '<?= base_url('PickupPoint/edit/') ?>' + this.redirect;
                            }
                        }
                    })
                }
            }


        });
    });
</script>