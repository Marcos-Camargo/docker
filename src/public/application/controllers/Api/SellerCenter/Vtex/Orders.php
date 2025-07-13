<?php

require APPPATH . "controllers/Api/FreteConectala.php";
require APPPATH."libraries/Marketplaces/Vtex.php";

/**
 * @property CI_Loader $load
 * @property CI_Router $router
 * @property CI_DB_driver $db
 *
 * @property Model_settings $model_settings
 * @property Model_stores $model_stores
 * @property Model_orders $model_orders
 * @property Model_clients $model_clients
 * @property Model_products $model_products
 * @property Model_promotions $model_promotions
 * @property Model_vtex_ult_envio $model_vtex_ult_envio
 * @property Model_freights $model_freights
 * @property Model_integrations $model_integrations
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_log_integration_order_marketplace $model_log_integration_order_marketplace
 * @property Model_campaigns_v2 $model_campaigns_v2
 * @property Model_campaigns_v2_vtex_campaigns $model_campaigns_v2_vtex_campaigns
 * @property Model_orders_payment $model_orders_payment
 * @property Model_queue_payments_orders_marketplace $model_queue_payments_orders_marketplace
 * @property CalculoFrete $calculofrete
 * @property OrdersMarketplace $ordersmarketplace
 * @property Slack $slack
 * @property CI_Lang $lang
 * @property \Marketplaces\Vtex $marketplace_vtex
 */
class Orders extends FreteConectala
{

    private $mkt;
    private $enable_log_slack = false;
    public $result;
    public $responseCode;
    public $accountName;
    public $header;
    public $suffixDns;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->model('model_stores');
        $this->load->model('model_orders');
        $this->load->model('model_clients');
        $this->load->model('model_products');
        $this->load->model('model_promotions');
        $this->load->model('model_vtex_ult_envio');
        $this->load->model('model_freights');
        $this->load->model('model_integrations');
        $this->load->model('model_products_marketplace');
        $this->load->model('model_log_integration_order_marketplace');
        $this->load->model('model_campaigns_v2');
        $this->load->model('model_campaigns_v2_vtex_campaigns');
        $this->load->model('model_queue_payments_orders_marketplace');
        $this->load->model('model_orders_payment');
        $this->load->library('calculoFrete');
        $this->load->library('ordersMarketplace');
        $this->load->library("Marketplaces\\Vtex", array(), 'marketplace_vtex');

        if ($this->model_settings->getValueIfAtiveByName('enable_log_slack')) {
            $this->enable_log_slack = true;
        }

        if ($this->enable_log_slack) {
            $this->load->library('Logging/Slack', array(), 'slack');
            $this->slack->endpoint = 'https://hooks.slack.com/services/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';
            $this->slack->channel = ['log_order_vtex'];
        }
    }

    /**
     * Get All Data from this method.
     *
     * @return void
     */
    public function index_get($mkt, $orderId = null)
    {
        $this->mkt = $this->tiraAcentos($mkt);
        //  $this->log_data('WebHook', 'PaymentVTEX - GET', "URL={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']} - MKT={$mkt} - GET=".json_encode($_GET));
        $this->response([
            "error" => [
                "code" => "1",
                "message" => "O verbo 'GET' não é compatível com essa rota",
                "exception" => null
            ]
        ], REST_Controller::HTTP_OK);
    }

    /**
     * Post All Data from this method.
     *
     * @return void
     */
    public function index_post($mkt = null, $orderId = null, $operation = null)
    {
        try {
            set_error_handler('customErrorHandler', E_ALL ^ E_NOTICE);

            $this->mkt = $this->tiraAcentos($mkt);

            $data = json_decode(file_get_contents('php://input'), true);
            $this->log_data('api', 'OrderIn', 'received=' . json_encode($data));

            // Método cancel order
            if ($operation === 'cancel') {
                $returnCancel = array(
                    //                "marketplaceOrderId"    => $data['marketplaceOrderId'],
                    "orderId" => $data['marketplaceOrderId'],
                    "date" => (new DateTime())->format("Y-m-d H:i:s.z"),
                    "receipt" => null
                );
                $this->log_data('api', 'CancelVTEX - return', 'received=' . json_encode($data) . ' return=' . json_encode($returnCancel) . "\n - URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
                return $this->response($returnCancel, REST_Controller::HTTP_OK);
            }

            // update payment order
            if ($orderId) {
                $returnVTEX = $this->formatResponsePayment($data);
                $this->log_data('api', 'PaymentVTEX - return', 'received=' . json_encode($data) . ' return=' . json_encode($returnVTEX) . "\n - URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
                return $this->response($returnVTEX, REST_Controller::HTTP_OK);
            }

            // new order
            $returnVTEX = $this->formatResponseCheckout($data);
            $this->log_data('api', 'CheckoutVTEX - Return', 'received=' . json_encode($data) . ' return=' . json_encode($returnVTEX));
            $this->response($returnVTEX, REST_Controller::HTTP_OK);
        } catch (Throwable $exception) {

            if ($this->enable_log_slack) {
                $message = "Error={$exception->getMessage()}\nTrace={$exception->getTraceAsString()}\nBody=" . file_get_contents('php://input') . "\nUrl={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
                $this->slack->send($message);
            }
            return $this->response(array(
                "fields" => [],
                "error" => [
                    "code" => "ERROR",
                    "message" => $exception->getMessage(),
                    "exception" => null
                ],
                "operationId" => null
            ), REST_Controller::HTTP_OK);
        }
    }

    public function cancel_post($mkt, $orderId = null)
    {
        $this->mkt = $this->tiraAcentos($mkt);;

        $data = json_decode(file_get_contents('php://input'), true);

        // update cancel order
        if ($orderId) {
            $returnCancel = $this->formatResponseCancellation($data, $orderId);
            $this->log_data('api', 'CancellationVTEX', 'received=' . json_encode($data) . ' return=' . json_encode($returnCancel) . "\n\n URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']} \n\n METHOD= {$_SERVER['REQUEST_METHOD']}");
            return $this->response($returnCancel, REST_Controller::HTTP_OK);
        }

        // cancel order
        $returnVTEX = $this->formatResponseCancellation($data);
        $this->log_data('api', 'CheckoutVTEX - Return', 'received=' . json_encode($data) . ' return=' . json_encode($returnVTEX));
        return $this->response($returnVTEX, REST_Controller::HTTP_OK);
    }

    private function formatResponseCheckout($datas)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $returnCheckout = array();
        $arrDatas = [];
        $returnObject = false;
        $arrOrderId = array();

        if (!isset($datas[0])) {
            $returnObject = true;
            $arrDatas[0] = $datas;
        } else {
            $arrDatas = $datas;
        }

        // Inicia transação
        $this->db->trans_begin();

        foreach ($arrDatas as $data) {
            $create = $this->createOrder($data);

            if (!$create[0]) {
                $this->db->trans_rollback();
                $this->log_data('api', $log_name, $create[1] . ' - Pedido = ' . json_encode($datas), "E");

                if ($this->enable_log_slack) {
                    $message = "Error={$create[1]}\nBody=" . file_get_contents('php://input') . "\nUrl={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
                    $this->slack->send($message);
                }

                return [
                    "error" => [
                        "code" => "1",
                        "message" => $create[1],
                        "exception" => null
                    ]
                ];
            }

            $create = $create[1];

            $dataLogisticsInfo = [];
            $dataItems = [];
            $isPickUpPoint = false;
            foreach ($data['shippingData']['logisticsInfo'] as $logisticsInfo) {
                $dataLogisticsInfo[] = array(
                    'itemIndex' => $logisticsInfo['itemIndex'],
                    'selectedSla' => $logisticsInfo['selectedSla'],
                    'lockTTL' => $logisticsInfo['lockTTL'],
                    'shippingEstimate' => $logisticsInfo['shippingEstimate'],
                    'price' => $logisticsInfo['price'],
                    'deliveryWindow' => $logisticsInfo['deliveryWindow']
                );

                if ($logisticsInfo['selectedDeliveryChannel'] === 'pickup-in-point') {
                    $isPickUpPoint = true;
                }
            }

            $shippingData = [
                'id' => "shippingData",
                'address' => $data['shippingData']['selectedAddresses'][0],
                'logisticsInfo' => $dataLogisticsInfo
            ];

            $clientProfileData['id'] = 'clientProfileData';
            $clientProfileData = array_merge($clientProfileData, $data['clientProfileData']);


            foreach ($data['items'] as $item) {
                $dataItems[] = array(
                    "id" => $item['id'],
                    "quantity" => $item['quantity'],
                    "Seller" => $item['seller'],
                    "commission" => $item['commission'],
                    "freightCommission" => $item['freightCommission'],
                    "price" => $item['price'],
                    "bundleItems" => $item['bundleItems'],
                    "priceTags" => $item['priceTags'],
                    "measurementUnit" => $item['measurementUnit'],
                    "unitMultiplier" => $item['unitMultiplier'],
                    "isGift" => $item['isGift']
                );
            }

            if (!$create) {
                $returnCheckout[] = null;
            } else {
                $returnCheckout[] = array(
                    "marketplaceOrderId" => $data['marketplaceOrderId'],
                    "orderId" => "$create", //** - identificador do pedido inserido no Seller
                    "followUpEmail" => $data['clientProfileData']['email'] ?? '',
                    "items" => $dataItems,
                    "clientProfileData" => $clientProfileData,
                    "shippingData" => $shippingData,
                    "paymentData" => null
                );
            }

            $arrOrderId[] = [
                'order_id' => $create,
                'is_pickup_in_point' => $isPickUpPoint
            ];
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->log_data('api', $log_name, 'Não foi possível se comunicar com a base de dados - Pedido = ' . json_encode($datas), "E");
            return [
                "error" => [
                    "code" => '1',
                    "message" => 'Não foi possível se comunicar com a base de dados',
                    "exception" => null
                ]
            ];
        }

        $this->db->trans_commit();


        /*FIN-385 :: start*/
        /*@todo fix
         * $this->load->model('model_campaigns_v2');
        foreach($arrOrderId as $order_id){
            $items = $this->model_campaigns_v2->getCampaignsProductsByOrder($order_id);
            log_message('error', json_encode($items));
            foreach($items as $item){
                $data = array(                
                    'product_id' => $item->product_id,
                    'campaign_v2_id' => $item->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                );            
                $this->db->insert('campaign_v2_orders_products', $data);
            }
        }*/
        /*FIN-385 :: end*/

        foreach ($arrOrderId as $order) {
            try {
                if (!$order['is_pickup_in_point']) {
                    $this->calculofrete->createQuoteShipRegister($order['order_id']);
                }
            } catch (Error $exception) {
                $this->log_data('api', 'CheckoutVTEX - quoteShip', "order_id=$order[order_id]\n{$exception->getMessage()}", 'E');
            }
            $this->saveQueuePaymantsOrder($data['marketplaceOrderId'], $order['order_id']);
        }

        return $returnObject ? $returnCheckout[0] : $returnCheckout;
    }

    private function formatResponseCancellation($data, $orderId = null): array
    {
        $order_id = $data['marketplaceOrderId'];
        $this->markCancelled($order_id);
        return array(
            "date" => (new DateTime())->format("Y-m-d H:i:s"),
            "marketplaceOrderId" => $data['marketplaceOrderId'],
            "orderId" => $orderId,
            "receipt" => null
        );
    }

    private function markCancelled($orderId)
    {
        if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($orderId)) {
            if ($order_exist['paid_status'] == 1) {
                $incomplete = false;

                try {
                    $this->process($order_exist['origin'], '/api/oms/pvt/orders/' . $order_exist['numero_marketplace']);

                    if ($this->responseCode == 200) {
                        $orderVtex = json_decode($this->result, true);
                        if (array_key_exists('isCompleted', $orderVtex) && !$orderVtex['isCompleted']) {
                            $incomplete = true;
                        }

                        if (!empty($orderVtex['cancellationData']['Reason']) && empty($this->model_orders->getReasonsCancelOrder($order_exist['id']))) {
                            $cancellation_date = $orderVtex['cancellationData']['CancellationDate'];

                            $this->model_orders->insertPedidosCancelados([
                                'order_id' => $order_exist['id'],
                                'reason' => $orderVtex['cancellationData']['Reason'],
                                'date_update' => !empty($cancellation_date) ? dateFormat($cancellation_date, DATETIME_INTERNATIONAL) : date("Y-m-d H:i:s"),
                                'status' => 1,
                                'penalty_to' => $this->model_orders->PENALTY_CANCEL['no_penalty'],
                                'user_id' => 0
                            ]);
                        }
                    }
                } catch (Throwable $exception) {
                }

                return $this->ordersmarketplace->cancelOrder($order_exist['id'], false, false, $incomplete);
            }
            if (!in_array($order_exist['paid_status'], [95, 96, 97, 98, 99])) {
                $this->model_orders->updatePaidStatus($orderId, 99);
            }
        }

        return true;
    }

    private function createOrder(array $pedido): array
    {

        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $frete_100_canal_seller_centers_vtex = $this->model_settings->getStatusbyName('frete_100_canal_seller_centers_vtex');

        if ($this->model_orders->getOrdersDatabyNumeroMarketplace($pedido['marketplaceOrderId'])) {
            return [false, "Pedido ( {$pedido['marketplaceOrderId']} ) já existente!"];
        }

        // Verifico se todos os skus estão certos e são das mesmas empresas
        $company_id = '';
        $store_id = '';
        $cross_docking_default = 0;
        $cross_docking = $cross_docking_default;
        $totalOrder = 0;
        $totalDiscount = 0;
        $totalDiscountPriceTags = 0;
        $isPickUpPoint = false;

        // VERIFICA SE UM DOS PRODUTOS DO PEDIDO POSSUI O VALOR ZERADO
        $reject_order_without_price = $this->model_settings->getSettingDatabyName('reject_order_without_price');

        foreach ($pedido['items'] as $item) {
            if ($reject_order_without_price && $reject_order_without_price['status'] == '1') {
                if (isset($item['price']) && $item['price'] == 0) {
                    $this->log_data('api', 'CancelVTEX - return', 'received=' . json_encode($item) . "\n - URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
                    return [false, $this->lang->line('api_item_zero_value_not_accetable')];
                }
            }
        }

        $allow_campaign_payment_method = $this->model_settings->getValueIfAtiveByName('allow_campaign_payment_method');
        $allow_campaign_trade_policies = $this->model_settings->getValueIfAtiveByName('allow_campaign_trade_policies');

        foreach ($pedido['items'] as $item) {

            $item['price'] = (float)($item['price'] / 100);
            $sku = $item['id'];
            $seller_id = $item['seller'];
            $discountItem = 0;
            $discountVtexCampaigns = 0;

            if (isset($item['priceTags'])) {
                foreach ($item['priceTags'] as $priceTag) {

                    $isPricetags = true;
                    if (($allow_campaign_payment_method || $allow_campaign_trade_policies) && isset($priceTag['name']) && strstr($priceTag['name'], 'discount@price-') && strstr($priceTag['name'], '#')) {
                        $campaignId = explode('discount@price-', $priceTag['name']);
                        $campaignId = explode('#', $campaignId[1]);
                        $campaignId = $campaignId[0];
                        if ($campaignId && $this->model_campaigns_v2_vtex_campaigns->vtexCampaignIdExists($campaignId)) {
                            /**
                             * Produto de campanha existente na vtex + sellercenter,
                             * mas o produto não está cadastrado na campanha sellercenter,
                             * então precisamos abortar o recebimento do pedido
                             */
                            if (!$this->model_campaigns_v2_vtex_campaigns->vtexProductCampaignExists($campaignId, $item['id'])) {
                                return [false, "Item pedido ( {$item['id']} ) de promoção vtex, não está na campanha sellercenter"];
                            }
                            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                                OrdersMarketplace::$orderItemHasMarketplaceCampaign[$item['id']] = $campaignId;
                                OrdersMarketplace::$orderItemMarketplaceCampaignDiscount[$item['id']] = $priceTag['rawValue'];
                            }else{
                                //@todo deprecated
                                OrdersMarketplace::$orderItemHasVtexCampaign[$item['id']] = $campaignId;
                                OrdersMarketplace::$orderItemVtexCampaign[$item['id']] = $priceTag;
                            }
                            $isPricetags = false;
                            $discountVtexCampaigns += (float)(($priceTag['value'] * (-1) / 100));
                        }
                    }

                    if ($isPricetags) {
                        $discountItem += (float)(($priceTag['value'] / 100) * (-1));
                    }

                }
                $totalDiscount += $discountItem;
            }

            $totalDiscountPriceTags += $discountItem;

            $prf = $this->db->get_where('vtex_ult_envio', array(
                'skumkt'    => $sku,
                'seller_id' => $seller_id,
                'int_to'    => $this->mkt
            ))->row_array();

            if (empty($prf)) {
                return [false, "Produto ( $sku ) não encontrado!"];
            }

            if ($prf['variant'] !== null && $prf['variant'] !== '') { // é variação
                $dataPrdVar = $this->model_products->getVariants($prf['prd_id'], $prf['variant']);
            } else {
                $dataPrdVar = $this->model_products->getProductData(0, $prf['prd_id']);
            }

            $dataMkt = $this->model_products_marketplace->getDataByUniqueKey($this->mkt, $prf['prd_id'], $prf['variant'] === null ? '' : $prf['variant']);

            if (!$dataPrdVar)
                return [false, "Produto ( $sku ) não encontrado!"];

            // existe preço/estoque para marketplace
            if ($dataMkt) {
                $qtyPrd = $dataMkt['same_qty'] == 1 ? $dataPrdVar['qty'] : ($dataMkt['qty'] === '' ? $dataPrdVar['qty'] : $dataMkt['qty']);
                $pricePrd = $dataMkt['same_price'] == 1 ? $dataPrdVar['price'] : ($dataMkt['price'] === '' ? $dataPrdVar['price'] : $dataMkt['price']);
            } else {
                $qtyPrd = $dataPrdVar['qty'];
                $pricePrd = $dataPrdVar['price'];
            }

            if ($item['quantity'] > $qtyPrd) {
                return [false, "Produto ( $sku ) sem estoque!"];
            }

            // join with promotion_group and get product and mkt
            $promotion = $this->db->select('p.*')
                ->join('promotions_group AS pr', 'p.lote = pr.lote')
                ->where('p.product_id', $prf['prd_id'])
                ->where('p.active', 1)
                ->where('pr.ativo', 1)
                ->group_start()
                    ->or_where('pr.marketplace', $this->mkt)
                    ->or_where('pr.marketplace', 'Todos')
                ->group_end()
                ->get('promotions p')
                ->row_array();

            if ($promotion) {
                $discountInternal = $pricePrd - $promotion['price'];
                $discountItem += $discountInternal * $item['quantity'];
                $totalDiscount += $discountInternal * $item['quantity'];

                // somar desconto da promoção para pegar o valor bruto do item.
                $item['price'] += $discountInternal;
            }

            $company_id = $prf['company_id'];
            $store_id = $prf['store_id'];

            // Tempo de crossdocking
            if ($prf['crossdocking']) {  // pega o pior tempo de crossdocking dos produtos
                if ((int)$prf['crossdocking'] + $cross_docking_default > $cross_docking) {
                    $cross_docking = $cross_docking_default + (int)$prf['crossdocking'];
                }
            }

            //$discountItem = $discountInternal + $discountExternal;
            $totalOrder += ($item['price'] * $item['quantity']) - $discountItem - $discountVtexCampaigns;
        }

        // Leio a Loja para pegar o service_charge_value
        $store = $this->model_stores->getStoresData($store_id);

        // pedido
        $orders = array();

        //$orders['freight_seller'] = $store['freight_seller'];

        $paid_status = 1; // sempre chegará como não pago

        // gravo o novo pedido
        $clientProfileData = $pedido['clientProfileData'];
        $shippingAddress = $pedido['shippingData']['selectedAddresses'][0];
        $shippingLogistic = $pedido['shippingData']['logisticsInfo'][0];
        $paymentData = $pedido['paymentData'];
        $invoiceAddress = $pedido['invoiceData']['address'] ?? $shippingAddress;

        $totalShip = 0;
        $deadline = 0;
        foreach ($pedido['shippingData']['logisticsInfo'] as $logisticsInfo) {
            if ($logisticsInfo['selectedDeliveryChannel'] === 'pickup-in-point') {
                $isPickUpPoint = true;
            }

            $totalShip += $logisticsInfo['price'];
            $deadlineTemp = filter_var($logisticsInfo['shippingEstimate'], FILTER_SANITIZE_NUMBER_INT);
            if ($deadlineTemp > $deadline) {
                $deadline = $deadlineTemp;
            }
        }

        // PRIMEIRO INSERE O CLIENTE
        $clients = array();
        if ($clientProfileData['isCorporate'] == true) {
            // Caso seja passado algum valor como null, trata como isento.
            if (!$clientProfileData['stateInscription']) {
                $clientProfileData['stateInscription'] = "Isento";
            }
            
            $clients['customer_name'] = $clientProfileData['corporateName'];
            $clients['cpf_cnpj'] = onlyNumbers($clientProfileData['corporateDocument']);
            $clients['ie'] = $clientProfileData['stateInscription'] == "Isento" ? $clientProfileData['stateInscription'] : onlyNumbers($clientProfileData['stateInscription']);
        } else {
            $clients['customer_name'] = $clientProfileData['firstName'] . ' ' . $clientProfileData['lastName'];
            $clients['cpf_cnpj'] = onlyNumbers($clientProfileData['document']);
            $clients['rg'] = '';
        }
        $clients['phone_1'] = isset($clientProfileData['phone']) ? onlyNumbers(str_replace('+55', '', $clientProfileData['phone'])) : '';
        $clients['phone_2'] = isset($clientProfileData['corporatePhone']) ? onlyNumbers(str_replace('+55', '', $clientProfileData['corporatePhone'])) : '';
        $clients['email'] = $this->getEmailClient($clientProfileData['email'] ?? '', $pedido['marketplaceOrderId'], $this->mkt);
        $clients['customer_address'] = $invoiceAddress['street'] ?? '';
        $clients['addr_num'] = $invoiceAddress['number'] ?? '';
        $clients['addr_compl'] = $invoiceAddress['complement'] ?? '';
        $clients['addr_neigh'] = $invoiceAddress['neighborhood'] ?? '';
        $clients['addr_city'] = $invoiceAddress['city'] ?? '';
        $clients['addr_uf'] = $invoiceAddress['state'] ?? '';
        $clients['country'] = $shippingAddress['country'] ?? '';
        $clients['zipcode'] = onlyNumbers($invoiceAddress['postalCode']);
        $clients['origin'] = $this->mkt; // Entender melhor como encontrar esse info
        $clients['origin_id'] = 1; // Entender melhor como encontrar esse info

        $client_id = $this->model_clients->insert($clients);
        if (!$client_id) {
            return [false, "Ocorreu um problema para gravar o cliente!"];
        }

        // verificação do frete -
        $provider = $shippingLogistic['selectedSla'];
        if (!$isPickUpPoint) {
            $provider = in_array(strtoupper($shippingLogistic['selectedSla']), array('PAC', 'SEDEX', 'MINI')) ? 'CORREIOS' : 'Transportadora';
            $provider = preg_match('/' . str_replace('%', '.*?', '%correios%') . '/', strtolower($shippingLogistic['selectedSla'])) > 0 ||
            preg_match('/' . str_replace('%', '.*?', '%pac_%') . '/', strtolower($shippingLogistic['selectedSla'])) > 0 ||
            preg_match('/' . str_replace('%', '.*?', '%sedex_%') . '/', strtolower($shippingLogistic['selectedSla'])) > 0 ||
            preg_match('/' . str_replace('%', '.*?', '%mini_%') . '/', strtolower($shippingLogistic['selectedSla'])) > 0
                ? 'CORREIOS' : $provider;

            $logistic = $this->calculofrete->getLogisticStore(array(
                'freight_seller' => $store['freight_seller'],
                'freight_seller_type' => $store['freight_seller_type'],
                'store_id' => $store['id']
            ));

            if ($logistic['type'] === 'sellercenter' && $logistic['sellercenter']) {

                $rowSettingsLogisticDefault = $this->model_settings->getSettingDatabyName('quote_default_sellercenter');
                if (
                    $rowSettingsLogisticDefault &&
                    $rowSettingsLogisticDefault['status'] == 1 &&
                    in_array($rowSettingsLogisticDefault['value'], array('sgp', 'sgpweb'))
                ) $provider = 'CORREIOS';
            }
        }

        $sellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

        $service = $shippingLogistic['selectedSla'];
        //$deadline   = filter_var($shippingLogistic['shippingEstimate'], FILTER_SANITIZE_NUMBER_INT);

        $orders['ship_company_preview'] = $provider;
        $orders['ship_service_preview'] = $service;
        $orders['ship_time_preview'] = $deadline;

        $orders['customer_name']            = $clients['customer_name'];
        $orders['customer_address']         = $shippingAddress['street'] ?? '';
        $orders['customer_address_num']     = $shippingAddress['number'] ?? '';
        $orders['customer_address_compl']   = $shippingAddress['complement'] ?? '';
        $orders['customer_address_neigh']   = $shippingAddress['neighborhood'] ?? '';
        $orders['customer_address_city']    = $shippingAddress['city'] ?? '';
        $orders['customer_address_uf']      = $shippingAddress['state'] ?? '';
        $orders['customer_address_zip']     = onlyNumbers($shippingAddress['postalCode']);
        $orders['customer_reference']       = $shippingAddress['reference'] ?? '';

        $order_mkt = $pedido['marketplaceOrderId'];
        $orders['bill_no'] = $order_mkt;
        $orders['numero_marketplace'] = $order_mkt;
        $orders['date_time'] = date('Y-m-d H:i:s');
        $orders['customer_id'] = $client_id;
        $orders['customer_phone'] = $clients['phone_1'];

        $orders['total_order'] = $totalOrder;
        if ($sellerCenter['value'] == 'somaplace') {
            $orders['total_order'] += $totalDiscount;
        }
        $orders['total_ship'] = $totalShip == 0 ? 0 : ($totalShip / 100);
        $orders['gross_amount'] = $orders['total_order'] + $orders['total_ship'];
        $orders['service_charge_rate'] = $store['service_charge_value'];

        // IF (freight_seller = 0 AND service_charge_freight_value = 100)
//        if($frete_100_canal_seller_centers_vtex == 1) {
//            if ($store['freight_seller'] == 0 && $store['service_charge_freight_value'] == 100) {
//                $store['service_charge_freight_value'] = 0;
//            }
//        }

        $orders['service_charge_freight_value'] = $store['service_charge_freight_value'];
        $orders['service_charge'] = number_format(($orders['total_order'] * $store['service_charge_value'] / 100) + ($orders['total_ship'] * $store['service_charge_freight_value'] / 100), 2, '.', '');
        $orders['vat_charge_rate'] = 0; //pegar na tabela de empresa — Não seja usado.....
        $orders['vat_charge'] = number_format(($orders['gross_amount'] - $orders['total_ship']) * $orders['vat_charge_rate'] / 100, 2, '.', ''); //pegar na tabela de empresa - Não está sendo usado.....
        $orders['discount'] = $totalDiscountPriceTags;

        $netAmount = $orders['gross_amount'];
        if ($sellerCenter['value'] == 'somaplace') {
            $netAmount -= $orders['discount'];
        }
        $orders['net_amount'] = number_format($netAmount, 2, '.', '');

        $orders['paid_status'] = $paid_status;
        $orders['company_id'] = $company_id;
        $orders['store_id'] = $store_id;
        $orders['origin'] = $this->mkt;
        $orders['user_id'] = 1;

        // se for grupo soma, subtrai também o desconto do total do pedido
        if ($sellerCenter['value'] == 'somaplace') {
            $orders['service_charge'] = number_format(((($orders['total_order'] - $totalDiscount) * $store['service_charge_value'] / 100) + ($orders['total_ship'] * $store['service_charge_freight_value'] / 100)), 2, '.', '');
        }

        $orders['data_limite_cross_docking'] = null;
        $orders['is_pickup_in_point'] = $isPickUpPoint;
        $orders['is_incomplete'] = 1; // sempre nasce como incompleto.

        try {

            //Buscando dados do pedido na hora
            $this->marketplace_vtex->setCredentials($this->mkt);
            $order_marketplace = $this->marketplace_vtex->getOrder($order_mkt);
            $sales_channel = $order_marketplace->salesChannel ?? null;

            $orders['sales_channel'] = $sales_channel;

        }catch (Throwable $exception){
        }

        $order_id = $this->model_orders->insertOrder($orders);
        if (!$order_id) {
            return [false, "Não foi possível gravar o pedido ( {$order_mkt} )!"];
        }

        $allow_campaign_payment_method = $this->model_settings->getValueIfAtiveByName('allow_campaign_payment_method');
        $allow_campaign_trade_policies = $this->model_settings->getValueIfAtiveByName('allow_campaign_trade_policies');

        // Itens
        $itensIds = array();
        $orderItems = [];

        foreach ($pedido['items'] as $item) {

            $sku = $item['id'];
            $seller_id = $item['seller'];

            $discount = 0;
            $discountVtexCampaigns = 0;
            if (isset($item['priceTags'])) {
                foreach ($item['priceTags'] as $priceTag) {

                    $isPricetags = true;
                    if (($allow_campaign_payment_method || $allow_campaign_trade_policies) && isset($priceTag['name']) && strstr($priceTag['name'], 'discount@price-') && strstr($priceTag['name'], '#')) {
                        $campaignId = explode('discount@price-', $priceTag['name']);
                        $campaignId = explode('#', $campaignId[1]);
                        $campaignId = $campaignId[0];
                        if ($campaignId && $this->model_campaigns_v2_vtex_campaigns->vtexCampaignIdExists($campaignId)) {
                            /**
                             * Produto de campanha existente na vtex + sellercenter,
                             * mas o produto não está cadastrado na campanha sellercenter,
                             * então precisamos abortar o recebimento do pedido
                             */
                            if (!$this->model_campaigns_v2_vtex_campaigns->vtexProductCampaignExists($campaignId, $item['id'])) {
                                return [false, "Item pedido ( {$item['id']} ) de promoção vtex, não está na campanha sellercenter"];
                            }
                            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

                                OrdersMarketplace::$orderItemHasMarketplaceCampaign[$item['id']] = $campaignId;
                                OrdersMarketplace::$orderItemMarketplaceCampaignDiscount[$item['id']] = $priceTag['rawValue'];

                            }else{
                                OrdersMarketplace::$orderItemHasVtexCampaign[$item['id']] = $campaignId;
                                OrdersMarketplace::$orderItemVtexCampaign[$item['id']] = $priceTag;
                            }

                            $isPricetags = false;
                            $discountVtexCampaigns += (float)(($priceTag['value'] * (-1) / 100) / (int)$item['quantity']);
                        }
                    }

                    if ($isPricetags) {
                        $discount += $priceTag['value'] * (-1);
                    }

                }
            }

            // na grade de item grava o valor do desconto unitário, mas da VTEX tem o valor multiplicado
            if ($discount) {
                $discount = (float)(($discount / 100) / (int)$item['quantity']);
            }

            $prf = $this->db->get_where('vtex_ult_envio', array(
                'skumkt'    => $sku,
                'seller_id' => $seller_id,
                'int_to'    => $this->mkt
            ))->row_array();

            $dataPrdVar = $dataPrd = $this->model_products->getProductData(0, $prf['prd_id']);

            if ($prf['variant'] !== null && $prf['variant'] !== '') { // é variação
                $dataPrdVar = $this->model_products->getVariants($prf['prd_id'], $prf['variant']);
            }

            $dataMkt = $this->model_products_marketplace->getDataByUniqueKey($this->mkt, $prf['prd_id'], $prf['variant'] === null ? '' : $prf['variant']);

            // existe preço/estoque para marketplace
            if ($dataMkt) {
                $qtyPrd = $dataMkt['same_qty'] == 1 ? $dataPrdVar['qty'] : ($dataMkt['qty'] === '' ? $dataPrdVar['qty'] : $dataMkt['qty']);
                $pricePrd = $dataMkt['same_price'] == 1 ? $dataPrdVar['price'] : ($dataMkt['price'] === '' ? $dataPrdVar['price'] : $dataMkt['price']);
            } else {
                $qtyPrd = $dataPrdVar['qty'];
                $pricePrd = $dataPrdVar['price'];
            }

            if ($item['quantity'] > $qtyPrd)
                return [false, "Produto ( {$sku} ) sem estoque!"];

            if ($dataPrd['is_kit'] == 0) {
                $orderItem = array();

                //Temporário para inserir no final
                $orderItem['prf'] = $prf;

                $orderItem['skumkt'] = $sku;
                $orderItem['order_id'] = $order_id; // ID da order incluida
                $orderItem['product_id'] = $prf['prd_id'];
                $orderItem['sku'] = $prf['sku'];

                $variant = '';
                if (!is_null($prf['variant'])) {
                    $variant = $prf['variant'];
                }
                $orderItem['variant'] = $variant;
                $orderItem['name'] = $dataPrd['name'];
                $orderItem['qty'] = (int)$item['quantity'];

                // Somado o preço recebido da VTEX com o preço de desconto da promoção.
                // Assim conseguimos saber o valor real do produto e o valor aplicado de promoção.
                $orderItem['rate'] = ($item['price'] / 100) - $discount - $discountVtexCampaigns;

                $orderItem['amount'] = (float)$orderItem['rate'] * $orderItem['qty'];
                $orderItem['discount'] = $discount > 0 ? (float)number_format($discount, 3, '.', '') : $discount;
                $orderItem['company_id'] = $dataPrd['company_id'];
                $orderItem['store_id'] = $dataPrd['store_id'];
                $orderItem['un'] = $iten['measurementUnit'] ?? 'un';
                $orderItem['pesobruto'] = $dataPrd['peso_bruto'];  // Não tem na vtex
                $orderItem['largura'] = $dataPrd['largura']; // Não tem na vtex
                $orderItem['altura'] = $dataPrd['altura']; // Não tem na vtex
                $orderItem['profundidade'] = $dataPrd['profundidade']; // Não tem na vtex
                $orderItem['unmedida'] = 'cm'; // não tem na vtex
                $orderItem['kit_id'] = null;

                //Se o código do produto e os seus dados são o mesmo, iremos duplicar a quantidade
                $indexOfItemAlreadyPopulated = $this->getIndexOfItemAlreadyPopulated($orderItem, $orderItems);
                if ($indexOfItemAlreadyPopulated !== null) {
                    // Se o item já existe, atualiza a quantidade e o valor total.
                    $orderItems[$indexOfItemAlreadyPopulated]['qty'] += $orderItem['qty'];
                    $orderItems[$indexOfItemAlreadyPopulated]['amount'] = $orderItems[$indexOfItemAlreadyPopulated]['rate'] * $orderItems[$indexOfItemAlreadyPopulated]['qty'];
                } else {
                    // Se o item não existe, adiciona ao array.
                    $orderItems[] = $orderItem;
                }

            } else {

                $productsKit = $this->model_products->getProductsKit($prf['prd_id']);

                foreach ($productsKit as $productKit) {
                    $prd_kit = $this->model_products->getProductData(0, $productKit['product_id_item']);
                    $orderItem = array();

                    //Temporário para inserir no final
                    $orderItem['prf'] = $prf;

                    $orderItem['order_id'] = $order_id; // ID da order incluida
                    $orderItem['skumkt'] = $sku;
                    $orderItem['kit_id'] = $productKit['product_id'];
                    $orderItem['product_id'] = $prd_kit['id'];
                    $orderItem['sku'] = $prd_kit['sku'];
                    $variant = '';
                    $orderItem['variant'] = $variant;  // Kit não pega produtos com variantes
                    $orderItem['name'] = $prd_kit['name'];
                    $orderItem['qty'] = (int)$orderItem['quantity'] * $productKit['qty'];
                    $orderItem['rate'] = $productKit['price'];  // pego o preço do KIT em vez do item
                    $orderItem['amount'] = ((float)$orderItem['rate'] * $orderItem['qty']) - $discount;
                    $orderItem['discount'] = $discount > 0 ? (float)number_format($discount, 2, '.', '') : $discount;// Tiro o desconto do primeiro item .
                    $discount = 0;
                    $orderItem['company_id'] = $prd_kit['company_id'];
                    $orderItem['store_id'] = $prd_kit['store_id'];
                    $orderItem['un'] = $iten['measurementUnit'] ?? 'un';
                    $orderItem['pesobruto'] = $prd_kit['peso_bruto'];  // Não tem na SkyHub
                    $orderItem['largura'] = $prd_kit['largura']; // Não tem na SkyHub
                    $orderItem['altura'] = $prd_kit['altura']; // Não tem na SkyHub
                    $orderItem['profundidade'] = $prd_kit['profundidade']; // Não tem na SkyHub
                    $orderItem['unmedida'] = 'cm'; // não tem na skyhub

                    //Se o código do produto e os seus dados são o mesmo, iremos duplicar a quantidade
                    $indexOfItemAlreadyPopulated = $this->getIndexOfItemAlreadyPopulated($orderItem, $orderItems);
                    if ($indexOfItemAlreadyPopulated !== null) {
                        // Se o item já existe, atualiza a quantidade e o valor total.
                        $orderItems[$indexOfItemAlreadyPopulated]['qty'] += $orderItem['qty'];
                        $orderItems[$indexOfItemAlreadyPopulated]['amount'] = $orderItems[$indexOfItemAlreadyPopulated]['rate'] * $orderItems[$indexOfItemAlreadyPopulated]['qty'];
                    } else {
                        // Se o item não existe, adiciona ao array.
                        $orderItems[] = $orderItem;
                    }

                }

            }

            /**
             * Atualizando o desconto caso entrou em campanha
             * Precisamos atualizar o valor do item, desconto e também no pedido
             */
            if ($sellerCenter['value'] == 'somaplace') {

                $campaignV2OrdersModel = $this->model_campaigns_v2->getCampaignV2OrderByOrderId($order_id);

                if ($campaignV2OrdersModel && $campaignV2OrdersModel['total_campaigns']) {

                    $orders['total_order'] += $campaignV2OrdersModel['total_campaigns'];
                    $orders['net_amount'] -= $campaignV2OrdersModel['total_campaigns'];
                    $orders['gross_amount'] += $campaignV2OrdersModel['total_campaigns'];
                    $orders['service_charge'] = number_format(((($orders['total_order'] - $totalDiscount) * $store['service_charge_value'] / 100) + ($orders['total_ship'] * $store['service_charge_freight_value'] / 100)), 2, '.', '');

                    $this->model_orders->updateOrderById($order_id, $orders);

                }

            }

        }

        //Cadastrando agora de fato os itens no banco de dados
        foreach ($orderItems as $orderItem){

            $prf = $orderItem['prf'];
            unset($orderItem['prf']);

            $item_id = $this->model_orders->insertItem($orderItem);
            if (!$item_id) {
                return [false, "Ocorreu um problema para gravar o item ( {$sku} )!"];
            }

            $this->model_promotions->updatePromotionByStock($prf['prd_id'], $orderItem['qty'], ($orderItem['price'] / 100));
            $this->model_products->reduzEstoque($prf['prd_id'], $orderItem['qty'], $orderItem['variant'], $order_id);
            $this->model_vtex_ult_envio->reduzEstoque($prf['int_to'], $prf['prd_id'], $orderItem['qty']);

        }

        // Gravando o log do pedido
        $data_log = array(
            'int_to' => $this->mkt,
            'order_id' => $order_id,
            'received' => json_encode($pedido)
        );
        $this->model_log_integration_order_marketplace->create($data_log);

        return [true, $order_id];
    }

    private function getIndexOfItemAlreadyPopulated($currentItem, array $items): ?int
    {

        if (!$items) {
            return null;
        }

        $varsToCompare = [
            'skumkt',
            'product_id',
            'sku',
            'variant',
            'company_id',
            'store_id',
            'largura',
            'altura',
            'profundidade',
            'rate',
            'kit_id',
            'pesobruto',
        ];

        foreach ($items as $key => $item) {
            $allMatch = true;
            foreach ($varsToCompare as $var) {
                if ($item[$var] !== $currentItem[$var]) {
                    $allMatch = false;
                    break;
                }
            }

            if ($allMatch) {
                return $key;
            }
        }

        return null;
    }

    private function formatResponsePayment($data)
    {
        $marketplaceOrderId = $data['marketplaceOrderId'];
        $order = $this->model_orders->getOrdersDatabyNumeroMarketplace($marketplaceOrderId);

        if (!$order) {
            $this->log_data('api', 'PaymentVTEX', 'Pedido não encontrado - ' . json_encode($data));
            return false; // não encontrou pedido
        }

        if ($order['paid_status'] != 1) { // pedido já está pago ou com pagamento em processamento
            $this->log_data('api', 'PaymentVTEX', 'Pedido já foi pago/cancelado - ' . json_encode($data));
            return array(
                "date" => $order['data_pago'],
                "marketplaceOrderId" => $marketplaceOrderId,
                "orderId" => $order['id'],
                "receipt" => null,
            );
        }

        // Pedido foi aprovado, mudo o status ver na VTEX se realmente já foi pago. Não precisa alterar o estoque
        $this->model_orders->updatePaidStatus($order['id'], 2);

        return array(
            "date" => date("Y-m-d H:i:s"),
            "marketplaceOrderId" => $marketplaceOrderId,
            "orderId" => $order['id'],
            "receipt" => null,
        );
    }

    public function process($int_to, $endPoint, $method = 'GET', $data = null)
    {
        $integrationData = $this->model_integrations->getIntegrationByIntTo($int_to, 0);

        if (!$integrationData) {
            return;
        }

        $separateIntegrationData = json_decode($integrationData['auth_data']);

        if (property_exists($separateIntegrationData, 'X_VTEX_API_AppKey')) {
            $this->accountName = $separateIntegrationData->accountName;

            if (property_exists($separateIntegrationData, 'suffixDns')) {
                if (!is_null($separateIntegrationData->suffixDns)) {
                    $this->suffixDns = $separateIntegrationData->suffixDns;
                }
            }

            $this->header = [
                'content-type: application/json',
                'accept: application/json',
                "x-vtex-api-appkey: $separateIntegrationData->X_VTEX_API_AppKey",
                "x-vtex-api-apptoken: $separateIntegrationData->X_VTEX_API_AppToken"
            ];


            // Se não chegar o endpoint completo, monto aqui.
            if (!preg_match('/https:/', $endPoint)) {
                $url = 'https://' . $this->accountName . '.' . $separateIntegrationData->environment . $this->suffixDns . '/' . $endPoint;
            } else {
                $url = $endPoint;
            }
        } else {   // Vertem com linkApi

            $this->header = [
                'content-type: application/json',
                'accept: application/json'
            ];
            if (substr($endPoint, 0, 1) == '/') {
                $endPoint = substr($endPoint, 1);
            }
            if ((strpos($endPoint, "?")) === false) {
                $url = $separateIntegrationData->site . '/' . $endPoint . '?apiKey=' . $separateIntegrationData->apiKey;
            } else {
                $url = $separateIntegrationData->site . '/' . $endPoint . '&apiKey=' . $separateIntegrationData->apiKey;
            }


            // var_dump($url);
            // var_dump($this->header);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        $this->result = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $err = curl_errno($ch);
        $errmsg = curl_error($ch);

        curl_close($ch);

        return;
    }

    public function saveQueuePaymantsOrder($numero_marketplace, $order_id)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $orderQueue = array(
            'order_id' => $order_id,
            'numero_marketplace' => $numero_marketplace,
            'status' => 0
        );

        $this->model_queue_payments_orders_marketplace->create($orderQueue);

        $this->log_data('batch', $log_name, 'Pagamento do pedido: ' . $order_id . ' cadastrado na fila');
    }

}