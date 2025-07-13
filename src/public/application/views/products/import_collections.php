<?php
$data['pageinfo'] = "application_import_attributes";
$this->load->view('templates/content_header', $data);
?>
<div class="content-wrapper" id="appCollections">
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 col-xs-12">

                    <div class="box box-primary">
                        <div class="box-body">

                            <div class="white-background-content">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h3 style="color: #007CFF;">
                                            Passo <span class="badge"
                                                        style="background-color: #007CFF !important;">1</span>
                                        </h3>
                                        <h4>Baixar planilhas de exemplo</h4>
                                        <p>Modelo da planilha necessária para cada tipo de cadastro ou atualização de
                                            produtos em massa.</p>
                                        <div>
                                            <hr>
                                        </div>
                                        <a href="<?= base_url('assets/files/sample_collections_import.csv') ?>"
                                           class="text-decoration-none"><i class="fa fa-download"
                                                                           aria-hidden="true"></i> Baixar exemplo de
                                            adição de navegações</a>
                                    </div>
                                    <div class="col-md-4">
                                        <h3 style="color: #007CFF;">Passo <span class="badge"
                                                                                style="background-color: #007CFF !important;">2</span>
                                        </h3>
                                        <h4>Baixar dados cadastrados</h4>
                                        <p>Exporte os dados já cadastrados na loja que você poderá precisar ao preencher
                                            aplanilha que será importada </p>
                                        <div>
                                            <hr>
                                        </div>
                                        <div class="mb-5">
                                            <a href="<?= base_url('collection/downloadCollections') ?>"
                                               class="text-decoration-none">
                                                <i class="fa fa-download" aria-hidden="true"></i>
                                                Exportar navegações </a>
                                        </div>
                                    </div>
                                    <div class="col-md-4">


                                        <h3 style="color: #007CFF;">
                                            Passo <span class="badge"
                                                        style="background-color: #007CFF !important;">3</span>
                                        </h3>
                                        <h4>Importar arquivos</h4>
                                        <p>Importe a planilha que você criou para atualização ou cadastro dos seus
                                            produtos em massa no sistema.</p>
                                        <div>
                                            <hr>
                                        </div>

                                        <form method="post" enctype="multipart/form-data"
                                              @submit.prevent="uploadFile" style="position: relative;">

                                            <div class="overlay-wrapper">
                                                <div class="overlay" v-show="loadingMockup">
                                                    <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                                                    <div class="text-bold pt-2">
                                                        {{messageLoading}}
                                                    </div>
                                                </div>
                                                <div class="col-md-12 pb-3">
                                                    <div class="row">
                                                        <small>Envie um arquivo de até 100000 (cem mil) linhas.</small>
                                                    </div>
                                                </div>
                                                <div class="file-drop-area small-shadow-top mb-4">
                                                    <span class="file-message">Arraste e solte os arquivos aqui...</span>
                                                    <input class="file-input-hidden" type="file" name="csv_file"
                                                           id="csvFile">
                                                </div>

                                                <button type="button" class="btn btn-select-file btn-primary"
                                                        onclick="selectFile()">
                                                    <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                                    Selecionar arquivo
                                                </button>
                                                <button type="submit" class="btn btn-primary btn-validate-file ml-1"
                                                        style="display: none;">Validar arquivo
                                                </button>
                                            </div>
                                        </form>


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

<script src="<?php echo base_url('assets/vue/vue.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue-resource.min.js') ?>" type="text/javascript"></script>
<script type="text/javascript">
    function selectFile() {
        $('.file-input-hidden').click();
    }

    $(document).on('change', '.file-input-hidden', function () {

        var filesCount = $(this)[0].files.length;

        var textbox = $('.file-message');

        if (filesCount === 1) {
            var fileName = $(this).val().split('\\').pop();
            textbox.html('<h4>Arquivo Carregado</h4><span class="text-black">' + fileName + '</span>');
            $('.btn-select-file').removeClass('btn-primary').addClass('btn-default');
        } else {
            textbox.text(filesCount + ' <?= $this->lang->line('selected_files'); ?>');
        }
        $('.btn-validate-file').show();
    });

    const base_url = "<?php echo base_url(); ?>";

    $(document).ready(function () {

    });

    const app = new Vue({
        el: '#appCollections',
        data: {
            base_url: base_url,
            messageLoading: '',
            loadingMockup: false,
            form: {
                csvFile: ''
            },
            form_data: {},
        },
        computed: {},
        mounted() {

        },
        ready: function () {

        },
        methods: {
            uploadFile() {
                this.messageLoading = 'Tentando realizar upload do arquivo'
                this.loadingMockup = true
                this.form.csvFile = $('#csvFile').prop('files')[0];
                this.form_data = new FormData();
                this.form_data.append('file', this.form.csvFile);
                let reqURL = base_url + 'collection/uploadfile';
                this.$http.post(reqURL, this.form_data).then(response => {
                    const json = JSON.parse(response.body);
                    if (json['status']) {
                        this.alertResponses("Sucesso", "Arquivo carregado com sucesso para o servidor!", "success", "reload");
                    } else {
                        this.alertResponses("Atenção", json['message'], "warning", "");
                        // this.alertResponses("Atenção", "O carregamento do arquivo não finalizou. Por favor, confira o arquivo e tente novamente!", "warning", "");
                    }
                    this.messageLoading = ''
                    this.loadingMockup = false
                }, response => {

                });
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
                            window.location.reload(true);
                        }
                    }
                })
            }

        }
    });


</script>