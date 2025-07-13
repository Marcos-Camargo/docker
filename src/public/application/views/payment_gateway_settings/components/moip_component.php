<?php
use App\Libraries\Enum\PaymentGatewayEnum;
?>
<div class="row" v-if="selectedGateway == <?= PaymentGatewayEnum::MOIP ?>">
    <div class="col-md-12 col-xs-12">

        <div class="col-md-8 col-xs-8" v-show="!canEdit">
            <div class="alert alert-warning alert-dismissible" style="font-size: 16px;">
                <h4><i class="icon fas fa-exclamation-triangle"></i> Atenção!</h4>
                Não é possível editar as informações abaixo pois já existem clientes utilizando o gateway. Por favor,
                abra um chamado para atualizar os dados!
            </div>
        </div>

        <div class="row"></div>

        <div class="col-md-4">
            <div class="form-group">
                <label>Api URL</label>
                <input class="form-control" readonly type="text" value="https://api.pagar.me/1"
                       v-model.trim="moipSettings.api_url.value">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>App Id</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="moipSettings.app_id.value" :readonly="!canEdit">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>App Token</label>
                <input class="form-control" type="text" value="https://api.pagar.me/1"
                       v-model.trim="moipSettings.app_token.value" :readonly="!canEdit">
            </div>
        </div>


        <div class="row"></div>
        <div class="col-md-6">
            <div class="form-group">
                <label>App Bank Id</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="moipSettings.app_bank_id.value" :readonly="!canEdit">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>App Account</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="moipSettings.app_account.value" :readonly="!canEdit">
            </div>
        </div>

        <div class="row"></div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Ymi Token</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="moipSettings.ymi_token.value" :readonly="!canEdit">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label>Ymi URL</label>
                <input class="form-control" type="text" readonly value="" placeholder=""
                       v-model.trim="moipSettings.ymi_url.value">
            </div>
        </div>

    </div>
</div>