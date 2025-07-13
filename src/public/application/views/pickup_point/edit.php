<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content" id="appSettings">
        <!-- Small boxes (Stat box) -->
        <div class="row" v-show="!showLoading">
            <div id="app-pickup" class="col-md-12 col-xs-12">

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

                <?php echo validation_errors('<div class="error">', '</div>'); ?>

                <div class="box" style="padding:15px">
                    <div class="box-body">
                        <form action="<?= base_url('PickupPoint/edit/' . $pickup_point->id) ?>" @submit.prevent="save"
                              method="POST">
                            <h3><?=$can_update ? 'Editar Ponto de Retirada' : 'Visualizar Ponto de Retirada'?></h3>
                            <div class="row">
                                <?php /*echo '<pre>';
                                print_r($pickup_point);
                                echo '</pre>';*/ ?>
                                <div class="mb-3 col-md-12">
                                    <label for="store" class="form-label">Loja:</label>
                                    <select class="form-control stores select2" id="store" v-model.trim="modalForm.store" disabled>
                                        <?php foreach ($stores as $store) { ?>
                                            <option <?= $store->id == $pickup_point->store_id ? 'selected' : '' ?>
                                                    value="<?= $store->id ?>"><?= $store->name ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="mb-3 col-md-8">
                                    <label for="nome" class="form-label">Nome:</label>
                                    <input type="text" class="form-control" v-model.trim="modalForm.nome"
                                           value="<?= $pickup_point->name ?>" id="nome"
                                           required>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="status" class="form-label">Status:</label>
                                    <select class="form-control" id="status" v-model.trim="modalForm.status" required>
                                        <option value="1">Ativo
                                        </option>
                                        <option value="0">
                                            Inativo
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3 col-md-2">
                                    <label for="cep" class="form-label">CEP:</label>
                                    <input type="text" class="form-control" @keyup="loadAddress()" v-model.trim="modalForm.cep" id="cep"
                                           required>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="rua" class="form-label">Rua:</label>
                                    <input type="text" class="form-control" id="rua"
                                           v-model.trim="modalForm.rua" required>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="rua" class="form-label">Número:</label>
                                    <input type="text" class="form-control" id="numero"
                                           v-model.trim="modalForm.numero" required>
                                </div>


                                <div class="mb-3 col-md-4">
                                    <label for="bairro" class="form-label">Bairro:</label>
                                    <input type="text" class="form-control" id="bairro"
                                           v-model.trim="modalForm.bairro" required>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="cidade" class="form-label">Cidade:</label>
                                    <input type="text" class="form-control" id="cidade"
                                           v-model.trim="modalForm.cidade" required>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="estado" class="form-label">Estado:</label>
                                    <select class="form-control" id="estado"
                                            v-model.trim="modalForm.estado" required>
                                        <?php foreach ($states as $state):
                                            echo "<option value='$state[Uf]'>$state[Nome]</option>";
                                        endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-8">
                                    <label for="complemento" class="form-label">Complemento:</label>
                                    <input type="text" class="form-control" id="complemento"
                                           v-model.trim="modalForm.complemento">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="pais" class="form-label">País:</label>
                                    <?php $this->load->view('pickup_point/country_component'); ?>
                                </div>
                            </div>
                            <h3>Horário de retirada</h3>

                            <div class="d-flex justify-content-center">
                                <div class="overlay-wrapper" style="margin-top:30px">
                                    <div class="overlay" v-show="showLoading"><i
                                                class="fas fa-3x fa-sync-alt fa-spin"></i>
                                        <div class="text-bold pt-2">Buscando horários...</div>
                                    </div>
                                </div>
                                <table class="table table-bordered table-striped" style="width: 75%">
                                    <thead>
                                    <tr>
                                        <th style="width: 15%">Dia da semana</th>
                                        <th style="width: 20%">Horário de Início</th>
                                        <th style="width: 20%">Horário de Fim</th>
                                        <th style="width: 5%">Loja Fechada</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>
                                            <h4>Segunda-feira</h4>
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.segunda.inicio"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.segunda.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.segunda.fim"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.segunda.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="checkbox" class="form-check"
                                                   v-model="modalForm.semana.segunda.fechada">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h4>Terça-feira</h4>
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.terca.inicio"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.terca.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.terca.fim"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.terca.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="checkbox" class="form-check"
                                                   v-model="modalForm.semana.terca.fechada">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h4>Quarta-feira</h4>
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.quarta.inicio"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.quarta.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.quarta.fim"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.quarta.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="checkbox" class="form-check"
                                                   v-model="modalForm.semana.quarta.fechada">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h4>Quinta-feira</h4>
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.quinta.inicio"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.quinta.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.quinta.fim"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.quinta.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="checkbox" class="form-check"
                                                   v-model="modalForm.semana.quinta.fechada">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h4>Sexta-feira</h4>
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.sexta.inicio"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.sexta.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.sexta.fim"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.sexta.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="checkbox" class="form-check"
                                                   v-model="modalForm.semana.sexta.fechada">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h4>Sábado</h4>
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.sabado.inicio"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.sabado.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.sabado.fim"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.sabado.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="checkbox" class="form-check"
                                                   v-model="modalForm.semana.sabado.fechada">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h4>Domingo</h4>
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.domingo.inicio"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.domingo.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="time" v-model.trim="modalForm.semana.domingo.fim"
                                                   class="form-control"
                                                   id="" :disabled="modalForm.semana.domingo.fechada == '1'">
                                        </td>
                                        <td>
                                            <input type="checkbox" v-model="modalForm.semana.domingo.fechada"
                                                   class="form-check">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row">
                                <div class="col-md-12 no-padding d-flex justify-content-between">
                                    <a href="<?= base_url('PickupPoint') ?>" class="col-md-3">
                                        <button type="button" class="btn btn-default btn-block">Voltar</button>
                                    </a>
                                    <?php if($can_update): ?>
                                    <button type="submit" class="btn btn-primary col-md-3" :disabled="buttonDisable">
                                        Salvar
                                        <div class="overlay" v-show="buttonDisable" style="background: transparent">
                                            <i class="fas fa-1x fa-sync-alt fa-spin"></i>
                                        </div>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
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

<style>
    .swal2-html-container ul {
        padding-inline-start: 24px;
    }
    .swal2-html-container li{
        text-align: left;
        margin-left: 8px;
        list-style: square;
        margin-bottom: 4px;
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

    var base_url = "<?= base_url() ?>";

    $(document).ready(function () {
        $("#mainLogisticsNav").addClass('active');
        $("#pickupPointRulesNav").addClass('active');
        const can_update = <?=$can_update ? 'true' : 'false'?>;
        if (!can_update) {
            $('form').find('select, input').prop('disabled', true);
        }

        const app = new Vue({
            el: '#app-pickup',
            data: {
                buttonDisable: false,
                showLoading: false,
                modalForm: {
                    id: '<?= $pickup_point->id ?>',
                    nome: '<?= $pickup_point->name ?>',
                    status: '<?= $pickup_point->status ?>',
                    cep: '<?= $pickup_point->cep ?>',
                    rua: '<?= $pickup_point->street ?>',
                    bairro: '<?= $pickup_point->district ?>',
                    cidade: '<?= $pickup_point->city ?>',
                    estado: '<?= $pickup_point->state ?>',
                    country: '<?= $pickup_point->country ?>',
                    numero: '<?= $pickup_point->number ?>',
                    complemento: '<?= $pickup_point->complement ?>',
                    store: '<?= $pickup_point->store_id ?>',
                    semana:
                        {
                            domingo: {
                                inicio: '<?= $pickup_point->withdrawal_times[0]->start_hour ?? '' ?>',
                                fim: '<?= $pickup_point->withdrawal_times[0]->end_hour ?? '' ?>',
                                fechada: <?= $pickup_point->withdrawal_times[0]->closed_store ?? 0 ?>
                            },
                            segunda: {
                                inicio: '<?= $pickup_point->withdrawal_times[1]->start_hour ?? '' ?>',
                                fim: '<?= $pickup_point->withdrawal_times[1]->end_hour ?? '' ?>',
                                fechada: <?= $pickup_point->withdrawal_times[1]->closed_store ?? 0 ?>
                            },
                            terca: {
                                inicio: '<?= $pickup_point->withdrawal_times[2]->start_hour ?? '' ?>',
                                fim: '<?= $pickup_point->withdrawal_times[2]->end_hour ?? '' ?>',
                                fechada: <?= $pickup_point->withdrawal_times[2]->closed_store ?? 0 ?>
                            },
                            quarta: {
                                inicio: '<?= $pickup_point->withdrawal_times[3]->start_hour ?? '' ?>',
                                fim: '<?= $pickup_point->withdrawal_times[3]->end_hour ?? '' ?>',
                                fechada: <?= $pickup_point->withdrawal_times[3]->closed_store ?? 0 ?>
                            },
                            quinta: {
                                inicio: '<?= $pickup_point->withdrawal_times[4]->start_hour ?? '' ?>',
                                fim: '<?= $pickup_point->withdrawal_times[4]->end_hour ?? '' ?>',
                                fechada: <?= $pickup_point->withdrawal_times[4]->closed_store ?? 0 ?>
                            },
                            sexta: {
                                inicio: '<?= $pickup_point->withdrawal_times[5]->start_hour ?? '' ?>',
                                fim: '<?= $pickup_point->withdrawal_times[5]->end_hour ?? '' ?>',
                                fechada: <?= $pickup_point->withdrawal_times[5]->closed_store ?? 0 ?>
                            },
                            sabado: {
                                inicio: '<?= $pickup_point->withdrawal_times[6]->start_hour ?? '' ?>',
                                fim: '<?= $pickup_point->withdrawal_times[6]->end_hour ?? '' ?>',
                                fechada: <?= $pickup_point->withdrawal_times[6]->closed_store ?? 0 ?>
                            },

                        },

                },
            },
            components: {}
            ,
            computed: {}
            ,
            mounted() {
                $('.select2').each(function () {
                    $(this).select2({dropdownParent: $(this).parent()});
                })
                $('#store').val("<?= $pickup_point->store_id ?>");
                $('#store').trigger("change");
                $('#mySelect2').val('<?= $pickup_point->country ?>');
                $('#mySelect2').trigger('change');

                $('.paises').on('change', (e) => {
                    this.modalForm.country = $('.paises').find(":selected").val();
                });

            }
            ,
            ready: function () {

            }
            ,
            methods: {
                loadAddress() {
                    var cep = document.getElementById("cep").value;
                    if (cep.length >= 8) {
                        var xhr = new XMLHttpRequest();
                        xhr.open("GET", "https://viacep.com.br/ws/" + cep + "/json/");
                        xhr.onload = function () {
                            if (xhr.status === 200) {
                                var response = JSON.parse(xhr.responseText);
                                if (!response.erro) {
                                    document.getElementById("estado").value = app.modalForm.estado = response.uf;
                                    document.getElementById("cidade").value = app.modalForm.cidade = response.localidade;
                                    document.getElementById("bairro").value = app.modalForm.bairro = response.bairro;
                                    document.getElementById("rua").value = app.modalForm.rua = response.logradouro;
                                } else {
                                    alert("CEP não encontrado. Verifique se o CEP está correto.");
                                }
                            } else {
                                alert("Erro ao consultar o CEP. Por favor, tente novamente mais tarde.");
                            }
                        };
                        xhr.send();
                    }
                },
                save() {
                    this.buttonDisable = true
                    let reqURL = base_url + 'PickupPoint/savePickupPoint/' + this.modalForm.id;
                    this.$http.post(reqURL, this.modalForm).then(response => {
                        if (response.body.status === false) {
                            this.alertResponses("Atenção", response.body.message, "warning")
                        } else {
                            $('#add-pickup-point').modal('toggle');
                            this.alertResponses("Sucesso", response.body.message, "success", 'reload')
                        }
                        this.buttonDisable = false
                    }, response => {
                        this.buttonDisable = false
                    });
                }
                ,

                alertResponses(title, message, icon, actionClick = '', showCancelButton = false) {
                    Swal.fire({
                        title: title,
                        // text: message,
                        html: message,
                        icon: icon,
                        showCancelButton: showCancelButton,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ok',
                        cancelButtonText: 'Fechar'
                    }).then((result) => {
                        if (result.value) {
                            if (actionClick === 'reload') {
                                window.location.href = "<?=base_url('PickupPoint')?>"
                            }
                        }
                    })
                }
            }


        });

    })
    ;
</script>