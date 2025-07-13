<?php

    if (!isset($gateway_id) || !isset($gateways_with_payment_report))
	{
		$gateway_id = $this->model_settings->getSettingDatabyName('payment_gateway_id')['value'];
		$setting_gateways_with_payment_report = $this->model_settings->getSettingDatabyName('payment_gateways_with_payment_report');

		if (isset($setting_gateways_with_payment_report['value']))
		{
			$gateways_with_payment_report = explode(';', $setting_gateways_with_payment_report['value']);
		}
    }

?>


<?php if(in_array('viewAnticipationSimulation', $user_permission)): ?>
    <li id="anticipationsSimulation">
        <a class="menuhref" href="<?php echo base_url('anticipationsManagement') ?>">
            <i class="fas fa-hand-holding-usd"></i>
            <span><?=$this->lang->line('application_payment_anticipation_management_title');?></span>
        </a>
    </li>
<?php endif; ?>

<?php if(in_array('viewIuguPlans', $user_permission)): ?>
    <li id="viewIuguPlans">
        <a class="menuhref" href="<?php echo base_url('iugu/listPlans') ?>">
            <i class="fa fa-cogs"></i>
            <span><?=$this->lang->line('application_iugu_plans');?></span> 
        </a>
    </li>
<?php endif; ?>


<?php if(in_array('createPaymentReport', $user_permission) && isset($gateway_id) && isset($gateways_with_payment_report) && in_array($gateway_id, $gateways_with_payment_report)): ?>
    <li id="createPaymentReport">
        <a class="menuhref" href="<?php echo base_url('payment/paymentReports') ?>">
            <i class="fa fa-cogs"></i>
            <span><?=$this->lang->line('application_payment_report');?></span>
        </a>
    </li>
<?php endif; ?>

<?php if(in_array('createTransferReport', $user_permission)): ?>
    <li id="createPaymentReport">
        <a class="menuhref" href="<?php echo base_url('payment/transferReports') ?>">
            <i class="fa fa-cogs"></i>
            <span><?=$this->lang->line('application_transfer_report');?></span>
        </a>
    </li>
<?php endif; ?>
