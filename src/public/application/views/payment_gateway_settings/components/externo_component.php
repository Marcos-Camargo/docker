<?php
use App\Libraries\Enum\PaymentGatewayEnum;
?>
<div class="row" v-if="selectedGateway == <?= PaymentGatewayEnum::EXTERNO ?>">
    <div class="col-md-12 col-xs-12">

        <div class="col-md-10 col-xs-10">
            <input id="reset_negative" type="checkbox" style="float: left;"
                   v-model.trim="externoSettings.reset_negative.value" :readonly="dataValidated.value == '1'">
            <div style="margin-left: 25px;">
				<?= $this->lang->line('application_payment_reset_negative'); ?>
            </div>
        </div>
            {{externoSettings.reset_negative.value}}
        <div class="col-md-10 col-xs-10">
            <hr>
        </div>


        <div class="row"></div>
        <div class="col-md-6 col-xs-6">
            <div class="form-group">
                <label>E-mail do responsável</label>
                <input class="form-control" type="text" value="" placeholder=""
                       v-model.trim="externoSettings.email.value" :readonly="dataValidated.value == '1'">
            </div>
        </div>
    </div>
</div>