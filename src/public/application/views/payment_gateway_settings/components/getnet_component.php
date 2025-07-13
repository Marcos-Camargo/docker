<?php
use App\Libraries\Enum\PaymentGatewayEnum;
?>
<div class="row" v-if="selectedGateway == <?= PaymentGatewayEnum::GETNET ?>">
    <div class="col-md-12 col-xs-12">

        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Seller Id  (*)</label>
                <input class="form-control" type="password" value="" placeholder=""
                       v-model.trim="getNetSettings.seller_id.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Merchant Id  (*)</label>
                <input class="form-control" type="password" value="https://api.pagar.me/1"
                       v-model.trim="getNetSettings.merchant_id.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>

        <div class="col-md-10 col-xs-10">
            <h4>MGM</h4>
            <hr>
        </div>

        <div class="row"></div>
        <div class="col-md-10 col-xs-10">
            <div class="form-group">
                <label>API Url MGM  (*)</label>
                <input class="form-control" type="text" value="" readonly placeholder=""
                       v-model.trim="getNetSettings.url_api_v1.value">
                <small>Url da versão 1 da Getnet.</small>
            </div>
        </div>

        <div class="row"></div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Cliente Id MGM  (*)</label>
                <input class="form-control" type="password" value="" placeholder=""
                       v-model.trim="getNetSettings.client_id_mgm.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Client Secret MGM  (*)</label>
                <input class="form-control" type="password" value="" placeholder=""
                       v-model.trim="getNetSettings.client_secret_id_mgm.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>

        <div class="row"></div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>App Key MGM</label>
                <input class="form-control" type="password" value="" placeholder=""
                       v-model.trim="getNetSettings.app_key_mgm.value" :readonly="true">
                <small>Preenchido Automáticamente</small>
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Access Token MGM</label>
                <input class="form-control" type="password" value="" placeholder=""
                       v-model.trim="getNetSettings.access_token_mgm.value" :readonly="true">
                <small>Preenchido Automáticamente</small>
            </div>
        </div>

        <div class="col-md-10 col-xs-10">
            <h4>OOB</h4>
            <hr>
        </div>

        <div class="row"></div>
        <div class="col-md-10 col-xs-10">
            <div class="form-group">
                <label>API Url OOB  (*)</label>
                <input class="form-control" type="text" readonly value="" placeholder=""
                       v-model.trim="getNetSettings.url_api_v2.value">
                <small>Url da versão 2 da Getnet.</small>
            </div>
        </div>

        <div class="row"></div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Client Id OOB  (*)</label>
                <input class="form-control" type="password" value="" placeholder=""
                       v-model.trim="getNetSettings.client_id_oob.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Client Secret OOB  (*)</label>
                <input class="form-control" type="password" value="" placeholder=""
                       v-model.trim="getNetSettings.client_secret_id_oob.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>

        <div class="row"></div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>App Key OOB</label>
                <input class="form-control" type="password" value="" placeholder=""
                       v-model.trim="getNetSettings.app_key_oob.value" :readonly="true">
                <small>Preenchido Automáticamente</small>
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Access Token OOB</label>
                <input class="form-control" type="password" value="" placeholder=""
                       v-model.trim="getNetSettings.access_token_oob.value" :readonly="true">
                <small>Preenchido Automáticamente</small>
            </div>
        </div>


    </div>
</div>