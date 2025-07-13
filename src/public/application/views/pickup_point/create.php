<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content" id="appSettings">
        <!-- Small boxes (Stat box) -->
        <div class="row">
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
                        <form action="<?= base_url('PickupPoint/create') ?>" @submit.prevent="save" method="POST">
                            <h3>Editar Ponto de Retirada</h3>
                            <div class="row">

                                <div class="mb-3 col-md-12">
                                    <label for="store" class="form-label">Loja:</label>
                                    <select class="form-control stores select2" id="store"
                                            v-model.trim="modalForm.store"
                                            required>
                                        <?php foreach ($stores as $store) { ?>
                                            <option value="<?= $store->id ?>"><?= $store->name ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="mb-3 col-md-8">
                                    <label for="nome" class="form-label">Nome:</label>
                                    <input type="text" class="form-control" v-model.trim="modalForm.nome" value=""
                                           id="nome"
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
                                    <input type="text" class="form-control" @keyup="loadAddress()"
                                           v-model.trim="modalForm.cep" id="cep"
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
                                <table class="table table-bordered table-striped" id="tableTimes" style="width: 75%">
                                    <thead>
                                    <tr>
                                        <th style="width: 20%">Dia da semana</th>
                                        <th style="width: 20%">Horário de Início</th>
                                        <th style="width: 20%">Horário de Fim</th>
                                        <th style="width: 15%">Loja Fechada</th>
                                        <th style="width: 15%">Ação</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr class="segunda">
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
                                        <td><button type="button" class="btn btn-link" @click="copyTimes()"><i class="fa fa-regular fa-copy"></i> Copiar em todos</button></td>
                                    </tr>
                                    <tr class="terca">
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
                                        <td></td>
                                    </tr>
                                    <tr class="quarta">
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
                                        <td></td>
                                    </tr>
                                    <tr class="quinta">
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
                                        <td></td>
                                    </tr>
                                    <tr class="sexta">
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
                                        <td></td>
                                    </tr>
                                    <tr class="sabado">
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
                                        <td></td>
                                    </tr>
                                    <tr class="domingo">
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
                                        <td></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row">
                                <div class="col-md-12 no-padding d-flex justify-content-between">
                                    <a href="<?= base_url('PickupPoint') ?>" class="col-md-3">
                                        <button type="button" class="btn btn-default btn-block">Voltar</button>
                                    </a>
                                    <button type="submit" class="btn btn-primary col-md-3" :disabled="buttonDisable">
                                        Salvar
                                        <div class="overlay" v-show="buttonDisable" style="background: transparent">
                                            <i class="fas fa-1x fa-sync-alt fa-spin"></i>
                                        </div>
                                    </button>
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

        const app = new Vue({
            el: '#app-pickup',
            data: {
                buttonDisable: false,
                showLoading: false,
                modalForm: {
                    store: '',
                    nome: '',
                    status: '1',
                    cep: '',
                    rua: '',
                    bairro: '',
                    cidade: '',
                    estado: '',
                    country: 'BRA',
                    numero: '',
                    complemento: '',
                    semana:
                        {
                            domingo: {
                                inicio: '',
                                fim: '',
                                fechada: ''
                            },
                            segunda: {
                                inicio: '',
                                fim: '',
                                fechada: ''
                            },
                            terca: {
                                inicio: '',
                                fim: '',
                                fechada: ''
                            },
                            quarta: {
                                inicio: '',
                                fim: '',
                                fechada: ''
                            },
                            quinta: {
                                inicio: '',
                                fim: '',
                                fechada: ''
                            },
                            sexta: {
                                inicio: '',
                                fim: '',
                                fechada: ''
                            },
                            sabado: {
                                inicio: '',
                                fim: '',
                                fechada: ''
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

                $('.stores').on('change', () => {
                    this.modalForm.store = $('.stores').find(":selected").val();
                });

                $('.paises').on('change', (e) => {
                    this.modalForm.pais = $('.paises').find(":selected").val();
                });

                $('#store').trigger("change");
                $('#mySelect2').trigger('change');
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
                                    app.modalForm.estado = response.uf;
                                    app.modalForm.cidade = response.localidade;
                                    app.modalForm.bairro = response.bairro;
                                    app.modalForm.rua = response.logradouro;
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
                    let reqURL = base_url + 'PickupPoint/savePickupPoint';
                    this.$http.post(reqURL, this.modalForm).then(response => {
                        console.log(this.modalForm.semana)
                        if (response.body.status === false) {
                            this.alertResponses("Atenção", response.body.message, "warning")
                        } else {
                            $('#add-pickup-point').modal('toggle');
                            this.alertResponses("Sucesso", response.body.message, "success", 'reload')
                        }
                        this.buttonDisable = false
                    }, response => {
                        this.buttonDisable = false
                        console.log(response.body)
                    });
                },
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
                                window.location.href = '<?=base_url('PickupPoint') ?>';
                            }
                        }
                    })
                },
                copyTimes() {
                    const first_input = $('form table#tableTimes tbody tr:eq(0)');
                    const start_time = first_input.find('td:eq(1) input').val();
                    const end_time = first_input.find('td:eq(2) input').val();

                    if (start_time === "" || end_time === "") {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Informe o horário de início e fim.'
                        });
                        return;
                    }

                    $('form table#tableTimes tbody tr:not(:eq(0))').each(function(){
                        app.modalForm.semana[$(this).attr('class')].inicio = start_time;
                        app.modalForm.semana[$(this).attr('class')].fim = end_time;
                    });
                },
            }


        });

    });
</script>