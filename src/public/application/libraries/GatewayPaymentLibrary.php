<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */
use App\Libraries\Enum\LegalPanelNotificationType;

defined('BASEPATH') or exit('No direct script access allowed');

class GatewayPaymentLibrary
{
    public $_CI;
    protected $gateway_name = '';
    protected $gateway_id = '';

    public function __construct()
    {
        $this->_CI = &get_instance();

        $this->_CI->load->model('model_gateway');
        $this->_CI->load->model('model_gateway_settings');
    }

    /**
     * @param array $transfers_array
     * @return array
     */
    public function generateArraySumByTransfers(array $transfers_array, array $valid_transfers = null): array
    {
        $transfers_sum = [];

        if (empty($valid_transfers)) {
            $valid_transfers = [21, 25];
        }

        foreach ($transfers_array as $key => $transfer) {

            if ($transfer['valor_seller'] < 0 && $transfer['refund'] !== null) {
                $transfers_sum[$transfer['store_id']]['cancel_id'] = $transfer['id'];
                $transfers_sum[$transfer['store_id']]['cancel_value'] = $transfer['valor_seller'];
                $transfers_sum[$transfer['store_id']]['cancel_status'] = $transfer['status_repasse'];
            }

            if ($transfer['valor_seller'] < 0 && $transfer['refund'] === null)
			{
				$legal_panel_id = ($transfer['legal_panel_id']) ?? null;
				$legal_panel_data = [];

				if (!$legal_panel_id)
				{
					$legal_panel_data = $this->_CI->model_legal_panel->getDataByLotAndValue($transfer['lote'], $transfer['valor_seller']);
					$legal_panel_id = ($legal_panel_data['id']) ?? null;
				}

//                if ($legal_panel_data || ENVIRONMENT == 'development')
                if ($legal_panel_id > 0)
				{
                    $transfers_sum[$transfer['store_id']]['legal_id'][$key] = $transfer['id'];
//                    $transfers_sum[$transfer['store_id']]['legal_panel_id'][$key] = (isset($legal_panel_data['id'])) ? $legal_panel_data['id'] : null;
//                    $transfers_sum[$transfer['store_id']]['legal_panel_id'][$key] = $transfer['legal_panel_id'];
                    $transfers_sum[$transfer['store_id']]['legal_panel_id'][$key] = $legal_panel_id;
                    $transfers_sum[$transfer['store_id']]['legal_value'][$key] = $transfer['valor_seller'];
                    $transfers_sum[$transfer['store_id']]['legal_status'][$key] = $transfer['status_repasse'];
                }
            }

            if ($transfer['valor_seller'] >= 0) {
                $transfers_sum[$transfer['store_id']]['orders_id'] = $transfer['id'];
            }

            //mudando o comportamento do pagamento para poder dar baixa nos juridicos positivos e em multiplos positivos
            if (floatVal($transfer['valor_seller']) > 0 && $transfer['refund'] == null) {
                $legal_panel_data = $this->_CI->model_legal_panel->getDataByLotAndValue($transfer['lote'], $transfer['valor_seller']);

                if ($legal_panel_data || ENVIRONMENT == 'development') {
                    $transfer['legal_panel_id'] = (isset($legal_panel_data['id'])) ? $legal_panel_data['id'] : null;
                }

                $transfers_sum[$transfer['store_id']]['legal_panel_positives'][] = $transfer;
            } else if (floatVal($transfer['valor_seller']) > 0 && $transfer['refund'] !== null) {
                $transfers_sum[$transfer['store_id']]['multiple_positives'][] = $transfer;
//				$transfers_sum[$transfer['store_id']]['positive_status_repasse'] = $transfer['status_repasse'];
            }

            //quando o valor é zero nao marca status 23 ocasionando uma conciliacao com falhas, mesmo tendo somente sucessos
            if ($transfer['valor_seller'] == 0) {
                $this->_CI->model_repasse->updateTransferStatus(true, $transfer['id']);
            }

            $transfers_sum[$transfer['store_id']]['lote'] = $transfer['lote'];
            $transfers_sum[$transfer['store_id']]['conciliacao_id'] = $transfer['conciliacao_id'];
            $transfers_sum[$transfer['store_id']]['store_id'] = $transfer['store_id'];
            $transfers_sum[$transfer['store_id']]['name'] = $transfer['name'];
            $transfers_sum[$transfer['store_id']]['responsavel'] = $transfer['responsavel'];
            $transfers_sum[$transfer['store_id']]['status_repasse'] = $transfer['status_repasse'];

            if (!isset($transfers_sum[$transfer['store_id']]['orders_value'])) {
                $transfers_sum[$transfer['store_id']]['orders_value'] = 0;
            }

//            if (in_array($transfer['status_repasse'], $valid_transfers))
            if (in_array($transfer['status_repasse'], $valid_transfers) || $transfer['valor_seller'] < 0) {
                $transfers_sum[$transfer['store_id']]['orders_value'] += $transfer['valor_seller'];    //evita status que ja estao pagos, com 23, ou possiveis outros status fora do 21 e 25
            }
        }

        return $transfers_sum;

    }

    public function createLegalItem(
		$store_id,
		$amount,
		$conciliacao_id = null,
		$msg_title = 'Cancelamento sem Saldo',
		$msg_description = 'Reembolso de Cancelamento sem Saldo',
		$msg_accountable = 'Rotina de Repasse',
		$msg_update = null,
		$lote = null,
		$notification_id = null
	)
    {

        echo "Criando Legal Item no valor de $amount para loja $store_id, conciliação $conciliacao_id".PHP_EOL;

        $date = getdate();
        $amount = round(($amount / 100), 2);

        //Antes de cadastrar, para evitar duplicação, vamos validar se ele já foi cadastrado automaticamente
        if ($conciliacao_id && $this->_CI->model_legal_panel->getDataByStoreAmountConciliation($store_id, $conciliacao_id, $amount)){
            echo "Já foi efetuado a criação de um jurídico para a mesma loja na mesma liberação de pagamento, não duplicaremos.";
            return;
        }

        $data = array(
            'notification_type' => LegalPanelNotificationType::OTHERS,
            'notification_title' => $msg_title,
            'orders_id' => 0,
            'store_id' => $store_id,
            'notification_id' => ($notification_id) ? $notification_id : $date['mon'] . '-' . $date['year'],
            'status' => 'Chamado Aberto',
            'description' => $msg_description,
            'balance_paid' => $amount,
            'balance_debit' => $amount,
            'creation_date' => date_create()->format('Y-m-d H:i:s'),
            'update_date' => date_create()->format('Y-m-d H:i:s'),
            'accountable_opening' => $msg_accountable,
			'accountable_update' => $msg_update,
			'conciliacao_id' => $conciliacao_id,
			'lote' => $lote
        );

        $this->_CI->model_legal_panel->create($data);

        echo "Jurídico cadastrado com sucesso";

    }

    public function runBatch($module_path, $module_method, $params = null)
    {
        if ($module_path && $module_method) {
            $event = $this->_CI->model_gateway->getCalendarEvent($module_path, $module_method);

            if (!$event) {
                return false;
            }

            $save_job_array = [
                'module_path' => $event['module_path'],
                'module_method' => $event['module_method'],
                'params' => ($params) ? $params : $event['params'],
                'status' => 0,
                'finished' => 0,
                'error' => NULL,
                'error_count' => 0,
                'error_msg' => NULL,
                'date_start' => date('Y-m-d H:i:s', (time() + 5)),
                'date_end' => NULL,
                'server_id' => 1,
                'alert_after' => $event['alert_after']
            ];

            return $this->_CI->model_gateway->saveNewJob($save_job_array);
        }

        return false;
    }

    /**
     * @param array $transfer
     * @param int $amount
     * @todo melhorar, não deveria estar aqui, temos a model Model_iugu_repasse para fazer exatamente isso
     */
    protected function saveOrdersStatements(array $transfer, int $amount): void
    {

        $this->_CI->load->model('model_iugu_repasse');
        $this->_CI->load->model('model_legal_panel');

        if (!$transfer || !$amount) {
            return;
        }

        $orders = $this->_CI->model_repasse->getOrdersFromTransfer($transfer['lote'], $transfer['store_id']);

        if (!$orders) {
            return;
        }

        foreach ($orders as $order) {

            $totalPaidUntilNow = $this->_CI->model_iugu_repasse->getTotalPaidByOrder($order['order_id']);
            $totalPaidUntilNow += $order['valor_repasse'];

            $statement_array = [
                'order_id' => $order['order_id'],
                'numero_marketplace' => $order['numero_marketplace'],
                'data_split' => $order['data_pedido'],
                'conciliacao_id' => $transfer['conciliacao_id'],
                'valor_parceiro' => $order['valor_repasse'],
                'valor_afiliado' => null,
                'current_installment' => $order['current_installment'],
                'total_installments' => $order['total_installments'],
                'total_paid' => $totalPaidUntilNow,
            ];

            if (in_array($transfer['status_repasse'], [21, 25])) {
                $this->_CI->model_repasse->saveStatement($statement_array);
            }

        }

    }

    protected function loadSettings(): void
    {
        $this->_CI->load->model('model_gateway_settings');

        $this->gateway_id = $this->_CI->model_gateway->getGatewayId($this->gateway_name);

        $api_settings = $this->_CI->model_gateway_settings->getSettings($this->gateway_id);

        if (!empty($api_settings) && is_array($api_settings)) {
            foreach ($api_settings as $setting) {
                $this->{$setting['name']} = $setting['value'];
            }
        }

    }

    public function createLegalItemFiscal(
		$store_id,
		$amount,
		$conciliacao_id = null,
		$msg_title = 'Cancelamento sem Saldo',
		$msg_description = 'Reembolso de Cancelamento sem Saldo',
		$msg_accountable = 'Rotina de Repasse',
		$msg_update = null,
		$lote = null
	)
    {

        $this->_CI->load->model('model_legal_panel_fiscal');

        echo "Criando Legal Item no valor de $amount para loja $store_id, conciliação $conciliacao_id".PHP_EOL;

        $date = getdate();
        $amount = round(($amount / 100), 2);

        //Antes de cadastrar, para evitar duplicação, vamos validar se ele já foi cadastrado automaticamente
        if ($conciliacao_id && $this->_CI->model_legal_panel_fiscal->getDataByStoreAmountConciliation($store_id, $conciliacao_id, $amount)){
            echo "Já foi efetuado a criação de um jurídico para a mesma loja na mesma liberação de pagamento, não duplicaremos.";
            return;
        }

        $data = array(
            'notification_type' => LegalPanelNotificationType::OTHERS,
            'notification_title' => $msg_title,
            'orders_id' => 0,
            'store_id' => $store_id,
            'notification_id' => $date['mon'] . '-' . $date['year'],
            'status' => 'Chamado Aberto',
            'description' => $msg_description,
            'balance_paid' => $amount,
            'balance_debit' => $amount,
            'creation_date' => date_create()->format('Y-m-d H:i:s'),
            'update_date' => date_create()->format('Y-m-d H:i:s'),
            'accountable_opening' => $msg_accountable,
			'accountable_update' => $msg_update,
			'conciliacao_id' => $conciliacao_id,
			'lote' => $lote
        );

        $this->_CI->model_legal_panel_fiscal->create($data);

        echo "Jurídico Fiscal cadastrado com sucesso";

    }
}