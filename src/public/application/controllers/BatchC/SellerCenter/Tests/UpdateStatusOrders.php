<?php
require APPPATH . "libraries/Marketplaces/Utilities/Order.php";

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_DB_driver $db
 * @property CI_Router $router
 *
 * @property Model_orders $model_orders
 * @property Model_integrations $model_integrations
 * @property Model_products $model_products
 * @property Model_product_return $model_product_return
 * @property Model_legal_panel $model_legal_panel
 *
 * @property OrdersMarketplace $ordersmarketplace
 * @property \Marketplaces\Utilities\Order $marketplace_order
 */
class UpdateStatusOrders extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_orders');
        $this->load->model('model_integrations');
        $this->load->model('model_products');
        $this->load->model('model_product_return');
        $this->load->model('model_legal_panel');
        $this->load->model('model_legal_panel_fiscal');
        $this->load->library('ordersMarketplace');
        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params));

        if (!is_null($params) && ($params != 'null')) {
            $integration = $this->model_integrations->getIntegrationsbyCompIntType(1, $params, "CONECTALA", "DIRECT");
            if (!$integration) {
                echo "Marketplace $params não encontrado!";
                die;
            }
        } else {
            $params = null;
        }
        echo $params . "\n";

        if (in_array(ENVIRONMENT, array(
            'local',
            'development',
            'development_gcp',
        ))) {
            $this->mandaTracking($params);
            $this->mandaNfe($params);
            $this->mandaOcorrencia($params);
            $this->mandaEntregue($params);
            $this->mandaCancelados($params);
            $this->sendRefunded($params);

        }
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    private function mandaTracking($int_to = null)
    {
        $paid_status = '51';

        if (is_null($int_to)) {
            $ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
        } else {
            $ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
        }

        foreach ($ordens_andamento as $order) {
            if (!$order['order_manually']) {
                continue;
            }

            $this->model_orders->updatePaidStatus($order['id'], 53);
            echo "Pedido $order[id] atualizado para Tracking enviado\n";
        }

    }

    private function mandaNfe($int_to = null)
    {
        $paid_status = '52';

        if (is_null($int_to)) {
            $ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
        } else {
            $ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
        }

        foreach ($ordens_andamento as $order) {
            if (!$order['order_manually']) {
                continue;
            }

            try {
                $this->marketplace_order->updateToInvoiceSentToMarketplace($order);
                echo "Pedido $order[id] atualizado para NFe enviada\n";
            } catch (Exception $exception) {
                echo "Erro para atualizar a NFe do pedido {$order['id']}. {$exception->getMessage()}\n";
            }
        }

    }

    private function mandaOcorrencia($int_to = null)
    {
        $paid_status = 55;
        if (is_null($int_to)) {
            $ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
        } else {
            $ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
        }

        foreach ($ordens_andamento as $order) {
            if (!$order['order_manually']) {
                continue;
            }

            $order['paid_status'] = 5; // agora tudo certo para contratar frete
            $order['data_mkt_sent'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);

            $this->model_orders->updateByOrigin($order['id'], $order);
            echo "Pedido $order[id] atualizado para Enviado\n";
        }

    }

    private function mandaEntregue($int_to = null)
    {
        $paid_status = '60';

        if (is_null($int_to)) {
            $ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
        } else {
            $ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
        }

        foreach ($ordens_andamento as $order) {
            if (!$order['order_manually']) {
                continue;
            }

            $order['paid_status'] = 6; // agora tudo certo para contratar frete
            $order['data_mkt_delivered'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);

            $this->model_orders->updateByOrigin($order['id'], $order);
            echo "Pedido $order[id] atualizado para Entregue\n";
        }

    }

    private function mandaCancelados($int_to = null)
    {
        $paid_status = '99';

        if (is_null($int_to)) {
            $ordens_andamento = $this->model_orders->getOrdensByPaidStatus($paid_status);
        } else {
            $ordens_andamento = $this->model_orders->getOrdensByOriginPaidStatus($int_to, $paid_status);
        }

        foreach ($ordens_andamento as $order) {
            if (!$order['order_manually']) {
                continue;
            }

            $this->ordersmarketplace->cancelOrder($order['id'], false);
            echo "Pedido $order[id] atualizado para Cancelado\n";
        }
    }

    private function sendRefunded(string $int_to = null)
    {
        if (is_null($int_to)) {
            $orders = $this->model_orders->getOrdensByPaidStatus([81,7]);
        } else {
            $orders = $this->model_orders->getOrdensByOriginPaidStatus($int_to, [81,7]);
        }

        foreach ($orders as $order) {
            if (!$order['order_manually']) {
                continue;
            }

            $items_canceled = 0;
            $value_refund = 0;
            $value_commission = 0;
            $description_legal_panel = array();
            $data_product_return = array();
            $itens = $this->model_product_return->getByOrderId($order['id']);

            foreach ($itens as $item) {
                // Não foi devolvido.
                if ($item['status'] !== Model_product_return::REFUNDED) {
                    $items_canceled++;
                    continue;
                }
                $data_product_return = $item;

                $description_legal_panel_str = "(Producto ($item[product_id]";
                if (!is_null($item['variant'])) {
                    $description_legal_panel_str .= " - Variação ($item[variant])";
                }
                $description_legal_panel_str .= ") ";

                $description_legal_panel[] = $description_legal_panel_str;

                //Busca a comissão a ser aplicada no item para descontar do valor total
                $commission = $this->ordersmarketplace->checkComissionOrdersItem($order['id'], $item['product_id']);

                $value_refund += (float) ( $item['return_total_value']  -  ( $item['return_total_value'] * ($commission['commission']/100) )) + ( ($commission['total_channel'] * $item['quantity_requested']) - ( ($commission['total_channel'] * $item['quantity_requested']) * ($commission['commission'] / 100 ) )  );
                $value_commission += (float) ( ( $item['return_total_value'] * ($commission['commission']/100) )) + ( ( ($commission['total_channel'] * $item['quantity_requested']) * ($commission['commission'] / 100 ) )  ) - ($commission['total_channel'] * $item['quantity_requested']);
            }

            if (empty($data_product_return)) {
                echo "Não localizado informações de devolução para o pedido $order[id]. Items=" . json_encode($itens) . "\n";
                // Se todos os itens da devolução foram cancelados, completa a devolução.
                if ($items_canceled === count($itens)) {
                    if($order['paid_status'] == 81){
                        $this->model_orders->updateByOrigin($order['id'], array('paid_status' => $this->model_orders->PAID_STATUS['refunded']));
                    }
                 }
                continue;
            }

            $quantity_items = 0;
            foreach ($this->model_orders->getOrdersItemData($order['id']) as $item) {
                $quantity_items += $item['qty'];
            }

            $value_refund_shipping = $order['total_ship'] > 0 ? ($order['total_ship'] / $quantity_items) : 0;
            $value_refund_shipping = roundDecimal($value_refund_shipping * $items_canceled);
            
            // Nova conta de frete para calcular o % de comissão de frete e diminuir do valor total
            $value_refund_shipping_fiscal = roundDecimal(($value_refund_shipping * ( $order['service_charge_freight_value']/100 ) ) );
            $value_refund_shipping = roundDecimal($value_refund_shipping - ($value_refund_shipping * ( $order['service_charge_freight_value']/100 ) ) );

            // Adiciona valor do frete.
            $value_refund += $value_refund_shipping;
            $value_commission = $value_commission * -1;

            if ($order['freight_seller']) {
                $description_legal_panel[] = " Frete de: " . money($value_refund_shipping);
            }

            try {

                // Criar débito no extrato.
                $this->model_legal_panel_fiscal->createDebit(
                    $order['id'],
                    "Devolução de produto.",
                    'Chamado Aberto',
                    "Devolução de produto. ".implode(', ', $description_legal_panel),
                    $order['freight_seller'] ? $value_commission : ($value_commission + $value_refund_shipping_fiscal),
                    'Rotina API'
                );

                $this->marketplace_order->updateToRefunded($order['id'], $order['freight_seller'], $value_refund, $value_refund_shipping, implode(', ', $description_legal_panel));
                echo "Pedido $order[id] atualizado para Devolvido\n";
            } catch (Exception $exception) {
                echo "Erro para devolver o pedido {$order['id']}. {$exception->getMessage()}\n";
            }
        }
    }
}