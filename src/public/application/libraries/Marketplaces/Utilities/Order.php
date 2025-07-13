<?php

namespace Marketplaces\Utilities;

use Error;
use Exception;

require_once 'system/libraries/Vendor/autoload.php';
require_once "BaseUtility.php";

class Order extends BaseUtility
{
    /**
     * Instantiate a new Store instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function updateToPaid(int $order_id, string $paid_date, string $ship_company_name_preview = null, int $ship_estimate_date = null)
    {
        $itens = $this->model_orders->getOrdersItemData($order_id);

        $order_cross_docking = 0;
        // Calcula o tempo de crossdocking
        foreach ($itens as $item) {
            // Guarda o maior tempo de crossdocking dos produtos.
            if ($item['prazo_operacional_extra'] && (int)$item['prazo_operacional_extra'] > $order_cross_docking) {
                $order_cross_docking = (int)$item['prazo_operacional_extra'];
            }
        }

        $store = $this->model_stores->getStoresData($itens[0]['store_id']);
        $additional_operational_deadline = $store['additional_operational_deadline'] ?? 0;
        $order_cross_docking += $additional_operational_deadline;

        $updateOrder = array(
            'data_pago'                 => $paid_date,
            'data_limite_cross_docking' => somar_dias_uteis($paid_date, $order_cross_docking),
            'paid_status'               => $this->model_orders->PAID_STATUS['awaiting_billing'],
        );

        if (!is_null($ship_company_name_preview)) {
            $updateOrder['ship_companyName_preview'] = $ship_company_name_preview;
        }
        if (!is_null($ship_estimate_date)) {
            $updateOrder['shipping_estimate_date'] = $ship_estimate_date;
        }

        $this->model_orders->updateByOrigin($order_id, $updateOrder);

        // Recupera qual foi o método e classe que chamou.
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        $backtrace = $backtrace[0];
        $log_name = "$backtrace[class]/$backtrace[function]";

        get_instance()->log_data('batch', $log_name, "Pedido $order_id pago.\ndata=".json_encode($updateOrder, JSON_UNESCAPED_UNICODE));

        try {
            $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
            if ($external_marketplace_integration) {
                $this->setExternalIntegration($external_marketplace_integration);
                $this->external_integration->notifyOrder($order_id, 'paid');
            }
        } catch (Exception | Error $exception) {
            get_instance()->log_data('batch', $log_name, "Não foi possível notificar o integrador externo sobre o pagamento para o pedido $order_id. {$exception->getMessage()}", "E");
        }
    }

    /**
     * @param int $order_id
     * @param array $arrPayments
     * @return void
     * @throws Exception
     */
    public function createPayment(int $order_id, array $arrPayments)
    {
        $settingSensitivePayment = $this->model_settings->getSettingDatabyName('get_sensitive_payment_data');
        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
        $arrPaymentToCreate = array();

        foreach ($arrPayments as $arrPayment) {
            if (!$settingSensitivePayment || $settingSensitivePayment['status'] != 1) {
                unset($arrPayment["first_digits"]);
                unset($arrPayment["last_digits"]);
            }

            try {
                if ($external_marketplace_integration) {
                    $this->setExternalIntegration($external_marketplace_integration);
                    $this->external_integration->checkDataPayment($arrPayment);
                }
                $arrPaymentToCreate[] = $arrPayment;
            } catch (Exception|Error $exception) {
                throw new Exception("Pedido $order_id - ".$exception->getMessage());
            }
        }

        // Tudo certo para inserir.
        foreach ($arrPaymentToCreate as $payment) {
            if(empty($payment['parcela'])){
                $payment['parcela'] = 1;
            }
            $this->model_orders->insertParcels($payment);
        }

        // Recupera qual foi o método e classe que chamou.
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        $backtrace = $backtrace[0];

        get_instance()->log_data('batch', "$backtrace[class]/$backtrace[function]", "Pagamento para o pedido $order_id criado.\ndata=".json_encode($arrPayments, JSON_UNESCAPED_UNICODE));
    }

    public function updateToInvoiceSentToMarketplace(array $order)
    {
        // Recupera qual foi o método e classe que chamou.
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        $backtrace = $backtrace[0];

        get_instance()->log_data('batch', "$backtrace[class]/$backtrace[function]", "Enviou NFe \nPEDIDO=$order[id]");

        $order_to_update = array();

        if ($order['paid_status'] != $this->model_orders->PAID_STATUS['checking_invoice']) {
            // agora tudo certo para contratar frete
            $order_to_update['paid_status'] = $order['is_pickup_in_point']
                ? $this->model_orders->PAID_STATUS['awaiting_withdrawal']
                : $this->model_orders->PAID_STATUS['nfe_sent_to_marketplace'];
        }
        $order_to_update['envia_nf_mkt'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);

        $this->model_orders->updateByOrigin($order['id'], $order_to_update);
    }

    /**
     * @param int $order_id
     * @param bool $freight_seller
     * @param float $value_refund
     * @param float $value_refund_shipping
     * @param string $description_legal_panel
     * @param array $order_values_to_refund
     * @return void
     * @throws Exception
     */
    public function updateToRefunded(int $order_id, bool $freight_seller, float $value_refund, float $value_refund_shipping, string $description_legal_panel = '', $order_values_to_refund = array())
    {
        if ($freight_seller) {
            $description_legal_panel .= " Frete de: " . money($value_refund_shipping);
        }

        // Criar débito no extrato.
        $this->model_legal_panel->createDebit(
            $order_id,
            "Devolução de produto.",
            'Chamado Aberto',
            "Devolução de produto. $description_legal_panel",
            $freight_seller ? $value_refund : ($value_refund - $value_refund_shipping),
            'Rotina API'
        );

        $order_value_refund_on_gateway = $this->model_order_value_refund_on_gateways->getByOrderId($order_id)[0] ?? array();
        // Se já teve alguma devolução de valor manualmente, não deve considerar o valor da devolução de produto como referência.
        if (!empty($order_value_refund_on_gateway)) {
            if ($order_value_refund_on_gateway['is_product_return'] && $order_value_refund_on_gateway['value'] != $value_refund) {
                $this->model_order_value_refund_on_gateways->update(array(
                    'value' => $value_refund
                ), $order_value_refund_on_gateway['id']);
            }
        }
        $this->model_orders->updateByOrigin($order_id, array('paid_status' => $this->model_orders->PAID_STATUS['refunded']));
        $this->model_product_return->updateByOrderId($order_id, array('returned_at' => dateNow()->format(DATETIME_INTERNATIONAL)));

        foreach ($order_values_to_refund as $skumkt => $order_value_to_refund) {
            $this->model_product_return->updateByOrderIdAndSkuMkt($order_id, $skumkt, array(
                'shipping_value_returned' => $order_value_to_refund['shipping_value'],
                'product_value_returned' => $order_value_to_refund['product_value']
            ));
        }

        try {
            $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
            if ($external_marketplace_integration) {
                $this->setExternalIntegration($external_marketplace_integration);
                $this->external_integration->notifyOrder($order_id, 'refund');
            }
        } catch (Exception|Error $exception) {
            throw new Exception("Pedido $order_id - ".$exception->getMessage());
        }
    }
}