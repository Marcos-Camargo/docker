<?php
use App\Libraries\Enum\PaymentGatewayEnum;
?>
<style>
    #appGatewaySettings .overlay {
        width: 100%;
        left: 0;
        height: 100%;
        top: 0;
        margin-top: 0;
        position: absolute;
        z-index: 9999;
    }
</style>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row" id="appGatewaySettings">
            <div class="col-md-12 col-xs-12">

                <div class="box">
                    <div class="box-body card-body" style="padding-bottom:36px">

                        <div class="overlay-wrapper" v-show="showLoading">
                            <div class="overlay" style="padding-top: 5%;"><i
                                        class="fas fa-3x fa-sync-alt fa-spin"></i>
                                <div class="text-bold pt-2">Buscando parâmetros...</div>
                            </div>
                        </div>

                        <div class="col-md-12 col-xs-12">
                            <h4>Configuração de credenciais</h4>
                        </div>

                        <div class="col-md-4 col-xs-4 mt-1">

                            <div class="form-group">
                                <label>Selecione o Gateway de Pagamento</label>
                                <select class="form-control" @change="selectGateway" v-model.trim="selectedGateway" :readonly="dataValidated.value == '1'" :disabled="dataValidated.value == '1'">
                                    <option></option>
                                    <?php 
                                    foreach ($gateways as $gateway) {
                                        if(in_array($gateway['gateway_id'], PaymentGatewayEnum::generateListValues())){
                                        ?>
                                            <option value="<?= $gateway['gateway_id'] ?>"><?= PaymentGatewayEnum::generateList()[$gateway['gateway_id']] ?? $gateway['gateway_id'] ?></option>
                                    <?php }
                                        }
                                    ?>
                                </select>
                                <small>Selecione o gateway de pagamento</small>
                            </div>
                        </div>

                        <div class="col-md-10 col-xs-10">
                            <hr>
                        </div>

                        <form role="form" method="post" @submit.prevent="saveSetting">
                            <?php $this->load->view('payment_gateway_settings/components/pagarme_component'); ?>
                            <?php $this->load->view('payment_gateway_settings/components/getnet_component'); ?>
                            <?php $this->load->view('payment_gateway_settings/components/magalupay_component'); ?>
                            <?php $this->load->view('payment_gateway_settings/components/tuna_component'); ?>
                            <?php $this->load->view('payment_gateway_settings/components/externo_component'); ?>
                            <?php $this->load->view('payment_gateway_settings/components/moip_component'); ?>
                            <div class="col-md-10 col-xs-10 mt-4" v-if="selectedGateway != ''">

                                <button type="button" class="btn btn-primary mr-3" @click="validateData()" v-if="dataValidated.value != '1'">
                                    <i class="fa fa-key"></i> Validar Dados
                                </button>

                                <span class="alert alert-info" v-if="selectedGateway == '<?= PaymentGatewayEnum::PAGARME ?>' && dataValidated.value === '0'">
                                    Ao validar os dados, será validado apenas os dados da versão selecionada, caso tiver preenchido dados de outra versão será ignorado.
                                </span>

                                <button type="submit" class="btn btn-primary mr-3" v-if="dataValidated.value == '1' && canSave">
                                    Salvar Alterações
                                    <div class="overlay" v-show="showLoading">
                                        <i class="fas fa-1x fa-sync-alt fa-spin"></i>
                                    </div>
                                </button>
                            </div>
                        </form>


                    </div>
                </div>

            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>

</div>

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

        const base_url = "<?php echo base_url(); ?>";
        const app = new Vue({
            el: '#appGatewaySettings',
            data: {
                dataValidated: {},
                showLoading: false,
                canSave: true,
                selectedGateway: '<?= $selected_gateway_id ?>',
                canEdit: <?= !$has_subaccounts ? 'true' : 'false' ?>,
                pagarmeSettings: {
                    pagarme_subaccounts_api_version: [{value: '', id: ''}],
                    app_key: [{value: '', id: ''}],
                    app_key_v5: [{value: '', id: ''}],
                    url_api_v1: [{value: '', id: ''}],
                    url_api_v5: [{value: '', id: ''}],
                    primary_account: [{value: '', id: ''}],
                    primary_account_v5: [{value: '', id: ''}],
                    // external_id_prefix_v5: [{value: '', id: ''}],
                    allow_transfer_between_accounts: [{value: '', id: ''}],
                    charge_seller_tax_pagarme: [{value: '', id: ''}],
                    cost_transfer_tax_pagarme: [{value: '', id: ''}],
                    banks_with_zero_fee: [{value: '', id: ''}],
                    data_validated_pagarme_v4: [{value: 0, id: ''}],
                    data_validated_pagarme_v5: [{value: 0, id: ''}],
                },
                getNetSettings: {
                    url_api_v1: [{value: '', id: ''}],
                    app_key_mgm: [{value: '', id: ''}],
                    seller_id: [{value: '', id: ''}],
                    client_id_mgm: [{value: '', id: ''}],
                    client_secret_id_mgm: [{value: '', id: ''}],
                    merchant_id: [{value: '', id: ''}],
                    client_id_oob: [{value: '', id: ''}],
                    client_secret_id_oob: [{value: '', id: ''}],
                    access_token_mgm: [{value: '', id: ''}],
                    access_token_oob: [{value: '', id: ''}],
                    app_key_oob: [{value: '', id: ''}],
                    url_api_v2: [{value: '', id: ''}],
                },
                moipSettings: {
                    app_id: [{value: '', id: ''}],
                    app_account: [{value: '', id: ''}],
                    api_url: [{value: '', id: ''}],
                    app_token: [{value: '', id: ''}],
                    ymi_token: [{value: '', id: ''}],
                    ymi_url: [{value: '', id: ''}],
                    app_bank_id: [{value: '', id: ''}],
                },
                magaluPaySettings: {
                    url_autenticar_api_onboarding: [{value: '', id: ''}],
                    url_access_api_onboarding: [{value: '', id: ''}],
                    grant_type: [{value: '', id: ''}],
                    client_secret: [{value: '', id: ''}],
                    client_id: [{value: '', id: ''}],
                    url_autenticar_api_gestao_carteira: [{value: '', id: ''}],
                    url_access_getinfo_api_gestao_carteira: [{value: '', id: ''}],
                    url_access_payment_api_gestao_carteira: [{value: '', id: ''}],
                    scope: [{value: '', id: ''}],
                    tenant_id: [{value: '', id: ''}],
                    product: [{value: '', id: ''}],
                },  
                tunaSettings: {
                    url_endpoint: [{value: '', id: ''}],
                    tuna_account: [{value: '', id: ''}],
                    tuna_apptoken: [{value: '', id: ''}]
                },  
                externoSettings: {
                    email: [{value: '', id: ''}],
                    reset_negative: [{value: '', id: ''}]
                },
            },
            components: {},
            computed: {},
            mounted() {
                this.selectGateway();
                this.selectApiVersion();
            },
            ready: function () {

            },
            methods: {
                formatValue(){
                    this.pagarmeSettings.cost_transfer_tax_pagarme.value = this.pagarmeSettings.cost_transfer_tax_pagarme.value.replace(',', '.')
                },
                saveSetting() {
                    this.showLoading = true
                    let setting = ''
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::PAGARME ?>') {
                        setting = this.pagarmeSettings
                    }
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::GETNET ?>') {
                        setting = this.getNetSettings
                    }
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::MOIP ?>') {
                        setting = this.moipSettings
                    }
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::MAGALUPAY ?>') {
                        setting = this.magaluPaySettings
                    }
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::TUNA ?>') {
                        setting = this.tunaSettings
                    }
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::EXTERNO ?>') {
                        setting = this.externoSettings
                    }
                    this.buttonDisable = true
                    let reqURL = base_url + 'paymentGatewaySettings/saveSetting/' + this.selectedGateway ;
                    this.$http.post(reqURL, setting).then(response => {
                        this.buttonDisable = false
                        if(response.body.result === 'success') {
                            this.alertResponses("Sucesso", "Configurações salvas com sucesso!", "success", 'reload')
                        } else {
                            this.alertResponses("Atenção", response.body.message, "warning")
                        }
                        this.showLoading = false
                    }, response => {
                        this.showLoading = false

                    });
                },
                selectGateway() {
                    if(this.selectedGateway === ''){
                        return
                    }
                    this.showLoading = true;
                    let reqURL = base_url + 'paymentGatewaySettings/getSettingByGatewayId';
                    this.$http.post(reqURL, this.selectedGateway).then(response => {
                        if (this.selectedGateway === '<?= PaymentGatewayEnum::PAGARME ?>') {
                            this.pagarmeSettings = response.body.gateway
                            if (this.pagarmeSettings.pagarme_subaccounts_api_version.value == '5'){
                                this.dataValidated = this.pagarmeSettings.data_validated_pagarme_v5;
                            }else{
                                this.dataValidated = this.pagarmeSettings.data_validated_pagarme_v4;
                            }
                        }
                        if (this.selectedGateway === '<?= PaymentGatewayEnum::GETNET ?>') {
                            this.getNetSettings = response.body.gateway
                            this.dataValidated = this.getNetSettings.data_validated_getnet;
                            if (this.dataValidated.value == 1){
                                this.canSave = false;
                            }
                        }
                        if (this.selectedGateway === '<?= PaymentGatewayEnum::MOIP ?>') {
                            this.moipSettings = response.body.gateway
                            this.dataValidated = this.moipSettings.data_validated_moip;
                        }
                        if (this.selectedGateway === '<?= PaymentGatewayEnum::MAGALUPAY ?>') {
                            this.magaluPaySettings = response.body.gateway
                            //this.dataValidated = this.magaluPaySettings.data_validated_magalupay;
                        }
                        if (this.selectedGateway === '<?= PaymentGatewayEnum::TUNA ?>') {
                            this.tunaSettings = response.body.gateway
                            //this.dataValidated = this.magaluPaySettings.data_validated_magalupay;
                        }
                        if (this.selectedGateway === '<?= PaymentGatewayEnum::EXTERNO ?>') {
                            this.externoSettings = response.body.gateway
                            //this.dataValidated = this.magaluPaySettings.data_validated_magalupay;
                        }
                        this.showLoading = false;
                    }, response => {
                    });
                },
                selectApiVersion() {
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::PAGARME ?>') {
                        if (this.pagarmeSettings.data_validated_pagarme_v5){
                            if (this.pagarmeSettings.pagarme_subaccounts_api_version.value == '5'){
                                this.dataValidated = this.pagarmeSettings.data_validated_pagarme_v5;
                            }else{
                                this.dataValidated = this.pagarmeSettings.data_validated_pagarme_v4;
                            }
                        }
                    }
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
                        if(actionClick === 'reload'){
                            window.location.reload()
                        }
                    })
                },
                validateData() {
                    this.showLoading = true
                    let setting = ''
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::PAGARME ?>') {
                        setting = this.pagarmeSettings
                    }
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::GETNET ?>') {
                        setting = this.getNetSettings
                    }
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::MOIP ?>') {
                        setting = this.moipSettings
                    }
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::MAGALUPAY ?>') {
                        setting = this.magaluPaySettings
                    }
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::TUNA ?>') {
                        setting = this.tunaSettings
                    }
                    if (this.selectedGateway === '<?= PaymentGatewayEnum::EXTERNO ?>') {
                        setting = this.externoSettings
                    }
                    this.buttonDisable = true
                    let reqURL = base_url + 'paymentGatewaySettings/validateData/' + this.selectedGateway ;
                    this.$http.post(reqURL, setting).then(response => {
                        this.buttonDisable = false
                        if(response.body.result === 'success') {
                            this.dataValidated.value = 1;
                            if (this.selectedGateway === '<?= PaymentGatewayEnum::PAGARME ?>') {
                                this.alertResponses("Sucesso", "Configurações da versão "+setting.pagarme_subaccounts_api_version.value+" validadas com sucesso!", "success")
                            }else{
                                this.alertResponses("Sucesso", "Configurações Validadas com sucesso!", "success")
                            }
                        } else {
                            this.alertResponses("Atenção", response.body.message, "warning")
                        }
                        this.showLoading = false
                    }, response => {
                        this.showLoading = false
                    });

                },
            }

        });

    });

</script>