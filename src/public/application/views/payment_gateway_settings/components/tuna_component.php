<?php
use App\Libraries\Enum\PaymentGatewayEnum;
?>
<div class="row" v-if="selectedGateway == <?= PaymentGatewayEnum::TUNA ?>">
    <div class="col-md-12 col-xs-12">

        <div class="col-md-10 col-xs-10">
            <h4>Autenticação</h4>
            <hr>
        </div>

        <div class="row"></div>
        <div class="col-md-10 col-xs-10">
            <div class="form-group">
                <label>API Url Endpoint(*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="tunaSettings.url_endpoint.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>

        <div class="row"></div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Tuna Account (*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="tunaSettings.tuna_account.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
        <div class="col-md-5 col-xs-5">
            <div class="form-group">
                <label>Tuna App Token(*)</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="tunaSettings.tuna_apptoken.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
    </div>
</div>