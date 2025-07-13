<?php

require APPPATH."libraries/Marketplaces/Vtex.php";

/**
 * @property \Marketplaces\Vtex $marketplace_vtex
 * @property Model_orders_to_process_commission $model_orders_to_process_commission
 * @property Model_orders $model_orders
 * @property Model_integrations $model_integrations
 * @property Model_products $model_products
 * @property Model_commissioning_products $model_commissioning_products
 * @property Model_commissioning_trade_policies $model_commissioning_trade_policies
 * @property Model_commissioning_categories $model_commissioning_categories
 * @property Model_commissioning_brands $model_commissioning_brands
 * @property Model_commissioning_orders_items $model_commissioning_orders_items
 * @property Model_commissioning_stores $model_commissioning_stores
 * @property Model_orders_conciliation_installments $model_orders_conciliation_installments
 * @property Model_stores $model_stores
 * @property Model_vtex_trade_policy $model_vtex_trade_policy
 * @property Model_settings $model_settings
 */
class CommissioningProcess extends BatchBackground_Controller
{
    private $promissory_payments_method_id = array();

    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);


        $this->load->model([
            'model_orders_to_process_commission',
            'model_orders',
            'model_integrations',
            'model_products',
            'model_commissioning_products',
            'model_commissioning_trade_policies',
            'model_commissioning_categories',
            'model_commissioning_brands',
            'model_commissioning_orders_items',
            'model_commissioning_stores',
            'model_stores',
            'model_vtex_trade_policy',
            'model_orders_conciliation_installments',
            'model_settings'
        ]);
        $this->load->library("Marketplaces\\Vtex", array(), 'marketplace_vtex');
    }

    function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = __CLASS__.'/'.__FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)).__CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }
        $this->log_data('batch', $log_name, 'start '.trim($id." ".$params));

        $this->process();

        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    public function process()
    {

        $log_name = __CLASS__.'/'.__FUNCTION__;
        $last_id = 0;
        $integrations = array();

        $tradePolicies = $this->model_vtex_trade_policy->findAll();
        $tradePoliciesIds = [];
        if ($tradePolicies) {
            foreach ($tradePolicies as $tradePolicy) {
                $tradePoliciesIds[$tradePolicy['int_to']][$tradePolicy['trade_policy_id']] = $tradePolicy['id'];
            }
        }

        $get_promissory_payments_method_id = $this->model_settings->getValueIfAtiveByName('promissory_payment_method_id');
        if ($get_promissory_payments_method_id) {
            $this->promissory_payments_method_id = array_map('intval', explode(',', $get_promissory_payments_method_id));
        }

        while ($data = $this->model_orders_to_process_commission->getNextRow($last_id)) {

            $this->db->trans_begin();

            $last_id = $data['id'];
            $order_id = $data['order_id'];

            //Validando se já foi importado anteriormente
            $hasAnyCommision = $this->model_commissioning_orders_items->getCommissionByOrder($order_id);

            if ($hasAnyCommision) {
                echo "Já processado pedido $order_id".PHP_EOL;
            }else{

                $order = $this->model_orders->getOrdersData(0, $order_id);
                $order_items = $this->model_orders->getOrdersItemData($order_id);

                if (!array_key_exists($order['origin'], $integrations)) {
                    $integrations[$order['origin']] = $this->model_integrations->getIntegrationByIntTo($order['origin'],
                        0);
                }

                if (!$integrations[$order['origin']] || $integrations[$order['origin']]['mkt_type'] !== 'vtex') {
                    $this->model_orders_to_process_commission->remove($last_id);
                    $this->db->trans_commit();
                    continue;
                }

                try {
                    $this->marketplace_vtex->setCredentials($order['origin']);
                    $order_marketplace = $this->marketplace_vtex->getOrder($order['numero_marketplace']);

                    $sales_channel = $order_marketplace->salesChannel;
                    if (!$sales_channel) {
                        echo "Não recebemos a política comercial".PHP_EOL;
                        $this->db->trans_commit();
                        continue;
                    }
                    //Sempre usando o id do canal de venda do banco e não o que vem da vtex
                    $sales_channel = $tradePoliciesIds[$order['origin']][$sales_channel] ?? null;
                    if (!$sales_channel) {
                        echo "Não encontramos a política comercial".PHP_EOL;
                        $this->db->trans_commit();
                        continue;
                    }

                    $store_id = $order['store_id'];
                    $order_date = $order['date_time'];
                    $method_payment = $this->getMethodsOrder($order_marketplace);
                    if (!$method_payment) {
                        echo "Não recebemos o método de pagamento utilizado".PHP_EOL;
                        $this->db->trans_commit();
                        continue;
                    }
                    $commissioning_trade_policies = false;
                    $has_commission = false;
                    $new_commission_value = 0;
                } catch (Exception $exception) {
                    $error_days = 5;
                    if (strtotime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)) > strtotime(addDaysToDate($data['created_at'],
                            $error_days))) {
                        $this->model_orders_to_process_commission->remove($last_id);
                        echo "Pedido $order_id não conseguiu processar por mais de $error_days dias.\n";
                        $this->log_data('batch', $log_name,
                            "Pedido $order_id não conseguiu processar por mais de $error_days dias. {$exception->getMessage()}",
                            "E");
                        $this->db->trans_commit();
                        continue;
                    }

                    echo "Pedido ou pagamento para o pedido $order_id não encontrado. {$exception->getMessage()}\n";
                    $this->db->trans_commit();
                    continue;
                }

                // Ler os itens para saber se tem regra de comissão
                foreach ($order_items as $order_item) {
                    $product_id = $order_item['product_id'];
                    $item_id = $order_item['id'];

                    $comission = $this->getComissionProduct(
                        $product_id,
                        $method_payment,
                        $store_id,
                        $order_date,
                        $sales_channel,
                        $commissioning_trade_policies,
                        $order['origin']
                    );

                    // Existe regra de comissão.
                    if (isset($comission['id'])) {
                        $has_commission = true;
                    }

                    $this->model_commissioning_orders_items->create(array(
                        'order_id' => $order_id,
                        'item_id' => $item_id,
                        'commissioning_id' => $comission['id'] ?? null,
                        'comission' => $comission['comission'],
                        'total_comission' => roundDecimal($order_item['amount'] * ($comission['comission'] / 100)),
                        'product_price' => $order_item['rate'],
                        'product_quantity' => $order_item['qty'],
                        'total_product_price' => $order_item['amount']
                    ));

                    $new_commission_value += ($order_item['amount'] * ($comission['comission'] / 100));

                }

                // Calcular nova comissão.
                if ($has_commission) {

                    $new_commission_value = roundDecimal($new_commission_value);

                    $new_commission_rate = ($new_commission_value / $order['total_order']) * 100;

                    $this->model_orders->updateByOrigin($order_id, array(
                        'service_charge_rate' => $new_commission_rate,
                        'service_charge' => $new_commission_value
                    ));

                    $this->log_data('batch', $log_name,
                        "Pedido $order_id alterado. [service_charge_rate: $order[service_charge_rate] => $new_commission_rate] - [service_charge: $order[service_charge] => $new_commission_value]");
                    echo "Pedido $order_id alterado. [service_charge_rate: $order[service_charge_rate] => $new_commission_rate] - [service_charge: $order[service_charge] => $new_commission_value]\n";
                }
            }

            $this->model_orders_to_process_commission->remove($last_id);

            //Removendo do installments
            $this->model_orders_conciliation_installments->deleteByOrderId($order_id);
            $this->model_orders_conciliation_installments->deleteFiscalByOrderId($order_id);
            $this->model_orders->deletePaymentDate($order_id);

            $this->db->trans_commit();

        }

    }

    /**
     * @param  object  $order_marketplace
     * @return array
     * @throws Exception
     */
    private function getMethodsOrder(object $order_marketplace): array
    {
        $description_note = null;

        if (empty($order_marketplace->paymentData->transactions)) {
            throw new Exception("Payment not found");
        }

        $name_payments = array();
        foreach ($order_marketplace->paymentData->transactions as $transaction) {
            if (empty($transaction->payments)) {
                continue;
            }
            foreach ($transaction->payments as $payment) {
                $payment_system = (int)$payment->paymentSystem;
                if (in_array($payment_system, $this->promissory_payments_method_id)) {
                    if (is_null($description_note)) {
                        $description_note = $this->marketplace_vtex->getPromissoryPaymentMethod($order_marketplace->orderId);
                    }

                    $name_payments[] = trim($description_note['institutionName']);
                } else {
                    $name_payments[] = $payment->paymentSystemName;
                }
            }
        }

        return $name_payments;
    }

    /**
     * @param  int  $product_id
     * @param  array  $method_payment
     * @param  int  $store_id
     * @param  string  $order_date
     * @param  int  $sales_channel
     * @param  bool|null|array  $commissioning_trade_policies
     * @param  string  $int_to
     * @return  array|null
     */
    private function getComissionProduct(
        int $product_id,
        array $method_payment,
        int $store_id,
        string $order_date,
        int $sales_channel,
        &$commissioning_trade_policies,
        string $int_to
    ): ?array {

        // commissioning_products
        $commissioning_products = $this->model_commissioning_products->getCommissionByProductAndPaymentMethodAndStoreAndDateRange(
            $int_to,
            $product_id,
            $method_payment,
            $sales_channel,
            $store_id,
            $order_date
        );

        if ($commissioning_products) {
            return $commissioning_products;
        }

        // commissioning_trade_policies - não precisa ser por produto.
        if ($commissioning_trade_policies === false) {
            $commissioning_trade_policies = $this->model_commissioning_trade_policies->getCommissionByTradePolicyAndStoreAndDateRangeAndIntToToOrder(
                $sales_channel,
                $store_id,
                $order_date,
                $int_to
            );
        }

        if ($commissioning_trade_policies) {
            return $commissioning_trade_policies;
        }

        $product_data = $this->model_products->getProductData(0, $product_id);
        $category_id = str_replace('"]', '', str_replace('["', '', $product_data['category_id']));
        $brand_id = str_replace('"]', '', str_replace('["', '', $product_data['brand_id']));

        // commissioning_categories
        if (!empty($category_id)) {
            $commissioning_categories = $this->model_commissioning_categories->getCommissionByCategoryAndStoreAndDateRange(
                $int_to,
                $category_id,
                $store_id,
                $order_date
            );

            if ($commissioning_categories) {
                return $commissioning_categories;
            }
        }

        // commissioning_brands
        if (!empty($brand_id)) {
            $commissioning_brands = $this->model_commissioning_brands->getCommissionByBrandAndStoreAndDateRange(
                $int_to,
                $brand_id,
                $store_id,
                $order_date
            );

            if ($commissioning_brands) {
                return $commissioning_brands;
            }
        }

        // commissioning_stores
        $commissioning_stores = $this->model_commissioning_stores->getCommissionByStoreAndDateRange(
            $int_to,
            $store_id,
            $order_date
        );

        if ($commissioning_stores) {
            return $commissioning_stores;
        }

        //Por padrão, sempre retorna o da loja
        $store_data = $this->model_stores->getStoresData($store_id);

        return ['comission' => $store_data['service_charge_value']];

    }
}