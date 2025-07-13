<?php

/** @noinspection DuplicatedCode */

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . "libraries/GatewayPaymentLibrary.php";

/**
 * @property Model_gateway $model_gateway
 * @property Model_settings $model_settings
 * @property Model_gateway_settings $model_gateway_settings
 * @property Model_anticipation_limits_store $model_anticipation_limits_store
 * @property Model_orders_conciliation_installments $model_orders_conciliation_installments
 */
class ExternalGatewayLibrary extends GatewayPaymentLibrary
{

    public $_CI;

    public function __construct()
    {
        $this->_CI = &get_instance();

		$this->_CI->load->model('model_transfer');
		$this->_CI->load->model('model_repasse');
		$this->_CI->load->model('model_conciliation');
		$this->_CI->load->model('model_settings');
        $this->_CI->load->model('model_legal_panel_fiscal');

        $this->balance_transfers_valid_updated_minutes = intVal($this->_CI->model_settings->getValueIfAtiveByName('balance_transfers_valid_updated_minutes'));

        $this->gateway_name = Model_gateway::EXTERNO;
        $this->loadSettings();
    }

    public function processNegativePayments($transfer_type = null, string $chargeback_object = null, $user_command = false): void
    {

        echo date('Y-m-d H:i:S').' - Início do processamento processTransfers'.PHP_EOL;

        //Sempre mostrar todos os erros, estamos dentro de um ob_start, o log será salvo no banco de dados para consulta posterior
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $conciliations = $this->_CI->model_conciliation->getOpenConciliations($this->gateway_id, null, $user_command);

        //Se não tem nenhum registro, não tem mais nada a fazer
        if (!$conciliations) {
			echo "Nenhuma conciliação para efetuar".PHP_EOL;
            return;
        }

        $current_day = date("j");

        foreach ($conciliations as $conciliation) {

            echo "INICIO do loop de conciliations".PHP_EOL;

            $transfer_error = 0;
            $conciliation_status = 23; // code for 100% success

            //Processando apenas pagamentos que devem ser feitos no mesmo dia ou o status do repasse for 25
            if (!($conciliation['data_pagamento'] == $current_day || $conciliation['status_repasse'] == 25) && !$user_command)
			{
                continue;
            }

			echo "\n\r \n\r Processando conciliação: {$conciliation['conciliacao_id']}" . PHP_EOL;

            $transfers_array = $this->_CI->model_transfer->getTransfers($conciliation['conciliacao_id']);

            if (!$transfers_array)
			{
                $this->_CI->model_conciliation->updateConciliationStatus($conciliation['conciliacao_id'], $conciliation_status);
                continue;
            }

            $transfers_sum = $this->generateArraySumByTransfers($transfers_array);

			echo "INICIO do loop de transfers_sum".PHP_EOL;

            foreach ($transfers_sum as $transfer)
			{
                echo "Processando store_id={$transfer['store_id']}".PHP_EOL;

				if ($transfer['orders_value'] == 0)
				{
					echo "Repasse zerado, status 52".PHP_EOL;
					$this->_CI->model_repasse->updatePositiveNegativeStatus($transfer['conciliacao_id'], $transfer['store_id'], 52);
					continue;
				}

                if ($transfer['orders_value'] < 0)
				{
                    echo "Valor total negativo".PHP_EOL;
					echo "Criar um painel jurídico".PHP_EOL;

					$payment_user = $this->_CI->model_repasse->getPaymentUsers($transfer['conciliacao_id'], $transfer['store_id'], $conciliation['users_id']);
					
					$this->createLegalItem(
						$transfer['store_id'],
						(abs($transfer['orders_value']) * 100),
						$transfer['conciliacao_id'],
						'Débito do Seller com o Marketplace',
						'Débito do Seller com o Marketplace',
						$msg_open = 'Liberação de pagamento',
						$msg_update =  $payment_user,
						$conciliation['lote'],
						$conciliation['ano_mes']
					);

					echo "Marcando Repasses como transferência não executada".PHP_EOL;
					$this->_CI->model_repasse->updatePositiveNegativeStatus($conciliation['conciliacao_id'], $transfer['store_id'], 51);
                }
				else
				{
                    echo "Valores nos pedidos são positivos".PHP_EOL;
					$this->_CI->model_repasse->updatePositiveNegativeStatus($conciliation['conciliacao_id'], $transfer['store_id'], 50);
                }

//                echo "Chamando saveOrdersStatements".PHP_EOL;
                print_r($transfer);
                echo "---------------".PHP_EOL;
//                $this->saveOrdersStatements($transfer, $transfer['orders_value']);

            } //foreach $transfers_sum

			echo "FIM do loop de transfers_sum".PHP_EOL;
			$this->_CI->model_conciliation->updateConciliationStatus($conciliation['conciliacao_id'], $conciliation_status);
            echo "Marcando conciliação id {$conciliation['conciliacao_id']} com o status $conciliation_status".PHP_EOL;

        }//foreach conciliacoes

		echo "FIM do loop de conciliations".PHP_EOL;

        echo date('Y-m-d H:i:S')." - FIM DO PROCESSAMENTO de processNegativePayments".PHP_EOL;
    }

    public function processNegativePaymentsFiscal($transfer_type = null, string $chargeback_object = null, $user_command = false): void
    {

        echo date('Y-m-d H:i:S').' - Início do processamento processTransfersFiscal'.PHP_EOL;

        //Sempre mostrar todos os erros, estamos dentro de um ob_start, o log será salvo no banco de dados para consulta posterior
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $conciliations = $this->_CI->model_conciliation->getOpenConciliationsFiscal($this->gateway_id, null, $user_command);

        //Se não tem nenhum registro, não tem mais nada a fazer
        if (!$conciliations) {
			echo "Nenhuma conciliação Fiscal para efetuar".PHP_EOL;
            return;
        }

        $current_day = date("j");

        foreach ($conciliations as $conciliation) {

            echo "INICIO do loop de conciliations Fiscal".PHP_EOL;

            $transfer_error = 0;
            $conciliation_status = 23; // code for 100% success

            //Processando apenas pagamentos que devem ser feitos no mesmo dia ou o status do repasse for 25
            if (!($conciliation['data_pagamento'] == $current_day || $conciliation['status_repasse'] == 25) && !$user_command)
			{
                continue;
            }

			echo "\n\r \n\r Processando conciliação Fiscal: {$conciliation['conciliacao_id']}" . PHP_EOL;

            $transfers_array = $this->_CI->model_transfer->getTransfersFiscal($conciliation['conciliacao_id']);

            if (!$transfers_array)
			{
                $this->_CI->model_conciliation->updateConciliationStatusFiscal($conciliation['conciliacao_id'], $conciliation_status);
                continue;
            }

            $transfers_sum = $this->generateArraySumByTransfers($transfers_array);

			echo "INICIO do loop de transfers_sum".PHP_EOL;

            foreach ($transfers_sum as $transfer)
			{
                
                echo "Processando store_id={$transfer['store_id']}".PHP_EOL;

				if ($transfer['orders_value'] == 0)
				{
					echo "Repasse zerado, status 52".PHP_EOL;
					$this->_CI->model_repasse->updatePositiveNegativeStatusFiscal($transfer['conciliacao_id'], $transfer['store_id'], 52);
					continue;
				}

                if ($transfer['orders_value'] < 0)
				{
                    echo "Valor total negativo".PHP_EOL;
					echo "Criar um painel jurídico Fiscal".PHP_EOL;

					$payment_user = $this->_CI->model_repasse->getPaymentUsersFiscal($transfer['conciliacao_id'], $transfer['store_id']);

					$this->createLegalItemFiscal(
						$transfer['store_id'],
						(abs($transfer['orders_value']) * 100) *-1,
						$transfer['conciliacao_id'],
						'Débito do Seller com o Marketplace',
						'Débito do Seller com o Marketplace',
						$msg_open = 'Liberação de pagamento',
						$msg_update =  $payment_user,
						$conciliation['lote']
					);

					echo PHP_EOL."Marcando Repasses como transferência não executada".PHP_EOL;
					$this->_CI->model_repasse->updatePositiveNegativeStatusFiscal($conciliation['conciliacao_id'], $transfer['store_id'], 51);
                }
				else
				{
                    echo "Valores nos pedidos são positivos".PHP_EOL;
					$this->_CI->model_repasse->updatePositiveNegativeStatusFiscal($conciliation['conciliacao_id'], $transfer['store_id'], 50);
                }

                // Encerra os paineis juridicos abertos da liberação que foi tratada
                $this->_CI->model_legal_panel_fiscal->encerraJuridicoFiscalPelaConciliacao($transfer['lote'], $transfer['store_id']);
                
//                echo "Chamando saveOrdersStatements".PHP_EOL;
                print_r($transfer);
                echo "---------------".PHP_EOL;
//                $this->saveOrdersStatements($transfer, $transfer['orders_value']);

            } //foreach $transfers_sum

			echo "FIM do loop de transfers_sum Fiscal".PHP_EOL;
			$this->_CI->model_conciliation->updateConciliationStatusFiscal($conciliation['conciliacao_id'], $conciliation_status);
            echo "Marcando conciliação Fiscal id {$conciliation['conciliacao_id']} com o status $conciliation_status".PHP_EOL;

        }//foreach conciliacoes

		echo "FIM do loop de conciliations Fiscal".PHP_EOL;

        echo date('Y-m-d H:i:S')." - FIM DO PROCESSAMENTO de processNegativePaymentsFiscal".PHP_EOL;
    }
}
