<?php
use App\Libraries\Enum\PaymentGatewayEnum;
?>
<div class="row" v-if="selectedGateway == <?= PaymentGatewayEnum::MAGALUPAY ?>">
    <div class="col-md-12 col-xs-12">

        <div class="col-md-10 col-xs-10">
            <h4>Onboarding</h4>
            <hr>
        </div>

        <div class="row"></div>
        <div class="col-md-10 col-xs-10">
            <div class="form-group">
                <label>API Url Onboarding Autenticação (*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.url_autenticar_api_onboarding.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>

        <div class="col-md-10 col-xs-10">
            <div class="form-group">
                <label>API Url Onboarding Acesso (*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.url_access_api_onboarding.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>


        <div class="col-md-10 col-xs-10">
            <h4>Gestão Carteira</h4>
            <hr>
        </div>

        <div class="row"></div>
        <div class="col-md-10 col-xs-10">
            <div class="form-group">
                <label>API Url Gestão Carteira Autenticação (*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.url_autenticar_api_gestao_carteira.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>

        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>API Url Gestão Carteira Acesso - Consulta Pedidos/Saldos (*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.url_access_getinfo_api_gestao_carteira.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>

        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>API Url Gestão Carteira Acesso - Repasses (*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.url_access_payment_api_gestao_carteira.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
        
        

        <div class="col-md-10 col-xs-10">
            <h4>Dados Autenticação</h4>
            <hr>
        </div>
        <div class="row"></div>

        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>API Url Gestão Carteira Acesso - Produto (*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.product.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Cliente Id  (*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.client_id.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Client Secret  (*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.client_secret.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Grant Type</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.grant_type.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Scope</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.scope.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Tenant Id</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="magaluPaySettings.tenant_id.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>

    </div>
</div>