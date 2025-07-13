<?php
use App\Libraries\Enum\PaymentGatewayEnum;
?>
<div class="row" v-if="selectedGateway == <?= PaymentGatewayEnum::PAGARME ?>">
    <div class="col-md-12 col-xs-12">

        <div class="col-md-3">
            <div class="form-group">
                <label>Versão da API de subcontas</label>
                <select class="form-control" v-model.trim="pagarmeSettings.pagarme_subaccounts_api_version.value" @change="selectApiVersion">
                    <option :selected="pagarmeSettings.pagarme_subaccounts_api_version.value == '4'" value="4">4</option>
                    <option :selected="pagarmeSettings.pagarme_subaccounts_api_version.value == '5'" value="5">5</option>
                </select>
            </div>
        </div>

        <div class="col-md-5">
            <div class="form-group">
                <label>API URL V4 (*)</label>
                <input class="form-control" type="text" value="https://api.pagar.me/core/v5/" readonly v-model.trim="pagarmeSettings.url_api_v1.value">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>API URL V5 (*)</label>
                <input class="form-control" type="text" value="https://api.pagar.me/1" readonly v-model.trim="pagarmeSettings.url_api_v5.value">
            </div>
        </div>

        <div class="row"></div>

        <div class="col-md-3">
            <div class="form-group">
                <label>App Key V4 (*)</label>
                <input class="form-control" type="password" value="" placeholder="ak_test_xxXXXxxxXXXxxxxxxXXxxx" v-model.trim="pagarmeSettings.app_key.value" :disabled="pagarmeSettings.data_validated_pagarme_v4.value == 1">
                <small>App Key da versão 4 da pagarme.</small>
            </div>
        </div>
        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label>App Key V5 (*)</label>
                <input class="form-control" type="password" value="" placeholder="ak_test_xxXXXxxxXXXxxxxxxXXxxx" v-model.trim="pagarmeSettings.app_key_v5.value" :disabled="pagarmeSettings.data_validated_pagarme_v5.value == 1">
                <small>App Key da versão 5 da pagarme.</small>
            </div>
        </div>

        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label>Código da conta primária V4 (*)</label>
                <input class="form-control" type="password" value="" placeholder="re_xxxxxxxxxxxx" v-model.trim="pagarmeSettings.primary_account.value" :disabled="pagarmeSettings.data_validated_pagarme_v4.value == 1">
                <small>Código da conta primária V4 da sua conta pagarme.</small>
            </div>
        </div>

        <div class="col-md-3 col-xs-3">
            <div class="form-group">
                <label>Código da conta primária V5 (*)</label>
                <input class="form-control" type="password" value="" placeholder="re_xxxxxxxxxxxx" v-model.trim="pagarmeSettings.primary_account_v5.value" :disabled="pagarmeSettings.data_validated_pagarme_v5.value == 1">
                <small>Código da conta primária V5 da sua conta pagarme.</small>
            </div>
        </div>

        <div class="row"></div>
        <div class="col-md-4 col-xs-4">
            <div class="form-group">
                <label>Permite transferências entre contas? (*)</label>
                <select class="form-control" v-model.trim="pagarmeSettings.allow_transfer_between_accounts.value" required>
                    <option :selected="pagarmeSettings.allow_transfer_between_accounts.value == '1'" value="1">Sim</option>
                    <option :selected="pagarmeSettings.allow_transfer_between_accounts.value != '1'" value="0">Não</option>
                </select>
            </div>
        </div>
        <div class="col-md-4 col-xs-4">
            <div class="form-group">
                <label>Cobrar taxa de saque do lojista? (*)</label>
                <select class="form-control" v-model.trim="pagarmeSettings.charge_seller_tax_pagarme.value" required>
                    <option :selected="pagarmeSettings.charge_seller_tax_pagarme.value == '1'" value="1">Sim</option>
                    <option :selected="pagarmeSettings.charge_seller_tax_pagarme.value != '1'" value="0">Não</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Custo de taxa de transferência (*)</label>
                <input class="form-control" type="text" value="" placeholder="0.00" @keyup="formatValue()"
                       v-model.trim="pagarmeSettings.cost_transfer_tax_pagarme.value"
                       required>
            </div>
        </div>

        <div class="row"></div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Bancos com Taxa 0%</label>
                <input class="form-control" type="text" value="" placeholder="" v-model.trim="pagarmeSettings.banks_with_zero_fee.value">
                <small>Nome dos bancos separados por ";" (ponto e virgula).</small>
            </div>
        </div>

    </div>
</div>