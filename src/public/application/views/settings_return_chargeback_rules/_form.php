<div class="modal-body">

    <div class="form-group">
        <label for="marketplace_int_to">
            <?= $this->lang->line('application_marketplace'); ?>
            <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="" aria-hidden="true" data-original-title="<?=lang('application_select_the_marketplace_corresponding_to_the_configuration');?>"></i>
        </label>
        <select class="form-control marketplace_int_to" id="marketplace_int_to" name="marketplace_int_to" <?php echo $inputsDisabled ? 'disabled="disabled" readonly' : ''; ?>>
            <option disabled="disabled" selected="selected"><?=lang('application_comission_select_filter');?></option>
            <?php
            foreach ($marketplaces as $marketplace){
                ?>
                <option value="<?=$marketplace['int_to'];?>"><?=$marketplace['name'];?></option>
                <?php
            }
            ?>
        </select>
    </div>

    <div class="form-group">
        <label for="full_refund">
            <?= $this->lang->line('application_full_refund'); ?>
            <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="" aria-hidden="true" data-original-title="<?=lang('application_full_refund_info');?>"></i>
        </label>
    </div>

    <div class="form-group">
        <label for="rule_full_refund_inside_cicle">
            <?= $this->lang->line('application_chargeback_rule_orders_returned_within_payment_cycle'); ?>
            <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="" aria-hidden="true" data-original-title="<?=lang('application_chargeback_rule_orders_returned_within_payment_cycle_info');?>"></i>
        </label>
        <select class="form-control rule_full_refund_inside_cicle" id="rule_full_refund_inside_cicle" name="rule_full_refund_inside_cicle" <?php echo $inputsDisabled ? 'disabled="disabled" readonly' : ''; ?>>
            <option disabled="disabled" selected="selected"><?=lang('application_comission_select_filter');?></option>
            <?php
            foreach (\App\libraries\Enum\RuleFullRefundInsideCicleEnum::generateList() as $key => $name){
                ?>
                <option value="<?=$key;?>"><?=$name;?></option>
                <?php
            }
            ?>
        </select>
    </div>

    <div class="form-group">
        <label for="rule_full_refund_outside_cicle">
            <?= $this->lang->line('application_chargeback_rule__orders_returned_after_payment_cycle'); ?>
            <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="" aria-hidden="true" data-original-title="<?=lang('application_chargeback_rule__orders_returned_after_payment_cycle_info');?>"></i>
        </label>
        <select class="form-control rule_full_refund_outside_cicle" id="rule_full_refund_outside_cicle" name="rule_full_refund_outside_cicle" <?php echo $inputsDisabled ? 'disabled="disabled" readonly' : ''; ?>>
            <option disabled="disabled" selected="selected"><?=lang('application_comission_select_filter');?></option>
            <?php
            foreach (\App\libraries\Enum\RuleFullRefundOutsideCicleEnum::generateList() as $key => $name){
                ?>
                <option value="<?=$key;?>"><?=$name;?></option>
                <?php
            }
            ?>
        </select>
    </div>

    <div class="form-group">
        <label for="partial_return">
            <?= $this->lang->line('application_partial_return'); ?>
            <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="" aria-hidden="true" data-original-title="<?=lang('application_partial_return_info');?>"></i>
        </label>
    </div>

    <div class="form-group">
        <label for="rule_partial_refund_inside_cicle">
            <?= $this->lang->line('application_chargeback_rule_orders_returned_within_payment_cycle'); ?>
            <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="" aria-hidden="true" data-original-title="<?=lang('application_chargeback_rule_orders_returned_within_payment_cycle_info');?>"></i>
        </label>
        <select class="form-control rule_partial_refund_inside_cicle" id="rule_partial_refund_inside_cicle" name="rule_partial_refund_inside_cicle" <?php echo $inputsDisabled ? 'disabled="disabled" readonly' : ''; ?>>
            <option disabled="disabled" selected="selected"><?=lang('application_comission_select_filter');?></option>
            <?php
            foreach (\App\libraries\Enum\RulePartialRefundInsideCicleEnum::generateList() as $key => $name){
                ?>
                <option value="<?=$key;?>"><?=$name;?></option>
                <?php
            }
            ?>
        </select>
    </div>

    <div class="form-group">
        <label for="rule_partial_refund_outside_cicle">
            <?= $this->lang->line('application_chargeback_rule_orders_returned_after_payment_cycle'); ?>
            <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="" aria-hidden="true" data-original-title="<?=lang('application_chargeback_rule_orders_returned_after_payment_cycle_info');?>"></i>
        </label>
        <select class="form-control rule_partial_refund_outside_cicle" id="rule_partial_refund_outside_cicle" name="rule_partial_refund_outside_cicle" <?php echo $inputsDisabled ? 'disabled="disabled" readonly' : ''; ?>>
            <option disabled="disabled" selected="selected"><?=lang('application_comission_select_filter');?></option>
            <?php
            foreach (\App\libraries\Enum\RulePartialRefundOutsideCicleEnum::generateList() as $key => $name){
                ?>
                <option value="<?=$key;?>"><?=$name;?></option>
                <?php
            }
            ?>
        </select>
    </div>

</div>