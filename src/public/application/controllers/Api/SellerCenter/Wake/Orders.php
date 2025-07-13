<?php

require APPPATH . "controllers/Api/FreteConectala.php";

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
 * @property Model_sellercenter_last_post $model_sellercenter_last_post
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
        $this->load->model('model_sellercenter_last_post');
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
                die;
                // $returnCancel = array(
                //     //                "marketplaceOrderId"    => $data['marketplaceOrderId'],
                //     "orderId" => $data['marketplaceOrderId'],
                //     "date" => (new DateTime())->format("Y-m-d H:i:s.z"),
                //     "receipt" => null
                // );
                // $this->log_data('api', 'CancelWake - return', 'received=' . json_encode($data) . ' return=' . json_encode($returnCancel) . "\n - URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
                // return $this->response($returnCancel, REST_Controller::HTTP_OK);
            }

            // update payment order
            if ($orderId) {
                // $returnWake = $this->formatResponsePayment($data);
                // $this->log_data('api', 'PaymentWake - return', 'received=' . json_encode($data) . ' return=' . json_encode($returnWake) . "\n - URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
                // return $this->response($returnWake, REST_Controller::HTTP_OK);
            }

            // new order
            $returnWake = $this->formatResponseCheckout($data);
            $this->log_data('api', 'CheckoutWake - Return', 'received=' . json_encode($data) . ' return=' . json_encode($returnWake));
            $this->response($returnWake, REST_Controller::HTTP_OK);
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
        die;
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
        $this->response($returnVTEX, REST_Controller::HTTP_OK);
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

            $arrOrderId[] = [
                'order_id' => $create
                //'is_pickup_in_point' => $isPickUpPoint comentado pois em breve sera implementado na wake
            ];



            if (!$create) {
                $returnCheckout[] = array(
                    "success" => false,
                );
            } else {
                $returnCheckout[] = array(
                    "success" => true,
                    "ordeId"  => $create
                );
            }

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

        foreach ($arrOrderId as $order) {
            try {
               // if (!$order['is_pickup_in_point']) {
                    $this->calculofrete->createQuoteShipRegister($order['order_id']);
               // }
            } catch (Error $exception) {
                $this->log_data('api', 'CheckoutVTEX - quoteShip', "order_id=$order[order_id]\n{$exception->getMessage()}", 'E');
            }
            //$this->savePaymantsOrder($data['marketplaceOrderId'], $order['order_id']);
        }

        return $returnObject ? $returnCheckout[0] : $returnCheckout;
    }

    private function formatResponseCancellation($data, $orderId = null): array
    {
        die;
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
        die;
        if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($orderId)) {
            if (in_array($order_exist['paid_status'], [1, 2])) {
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
                $order['paid_status'] = 99;
                $this->model_orders->updateByOrigin($orderId, $order);
            }
        }
    }

    private function createOrder(array $pedido): array
    {
      
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $frete_100_canal_seller_centers_vtex = $this->model_settings->getStatusbyName('frete_100_canal_seller_centers_vtex');

        if ($this->model_orders->getOrdersDatabyNumeroMarketplace($pedido['pedidoId'])) {
            return [false, "Pedido ( {$pedido['pedidoId']} ) já existente!"];
        }
       
        // Verifico se todos os skus estão certos e são das mesmas empresas
        $company_id = '';
        $store_id = '';
        $cross_docking_default = 0;
        $cross_docking = $cross_docking_default;
        $totalOrder = 0;
        $totalDiscount = 0;
        $totalDiscountOrder = 0;
        $totalDiscountPriceTags = 0;
        $isPickUpPoint = false;
     

        // VERIFICA SE UM DOS PRODUTOS DO PEDIDO POSSUI O VALOR ZERADO
        $reject_order_without_price = $this->model_settings->getSettingDatabyName('reject_order_without_price');

        foreach ($pedido['itens'] as $item) {
            if ($reject_order_without_price && $reject_order_without_price['status'] == '1') {
                if (isset($item['precoVenda']) && $item['precoVenda'] == 0) {
                    $this->log_data('api', 'CancelVTEX - return', 'received=' . json_encode($item) . "\n - URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
                    return [false, $this->lang->line('api_item_zero_value_not_accetable')];
                }
            }
        }
       
        //$allow_campaign_payment_method = $this->model_settings->getValueIfAtiveByName('allow_campaign_payment_method');
        //$allow_campaign_trade_policies = $this->model_settings->getValueIfAtiveByName('allow_campaign_trade_policies');

        //Valor total de desconto order
        $totalDiscountOrder = $pedido['valorDesconto'];

        foreach ($pedido['itens'] as $item) {

            $item['price'] = $item['precoVenda'];
            $sku = $item['sku'];
            //$seller_id = $item['seller'];
            $discountItem = $item['desconto'];
            $discountVtexCampaigns = 0;

            /* 
                if (isset($item['priceTags'])) {
                    foreach ($item['priceTags'] as $priceTag) {

                        $isPricetags = true;
                        if (($allow_campaign_payment_method || $allow_campaign_trade_policies) && isset($priceTag['name']) && strstr($priceTag['name'], 'discount@price-') && strstr($priceTag['name'], '#')) {
                            $campaignId = explode('discount@price-', $priceTag['name']);
                            $campaignId = explode('#', $campaignId[1]);
                            $campaignId = $campaignId[0];
                            if ($campaignId && $this->model_campaigns_v2_vtex_campaigns->vtexCampaignIdExists($campaignId)) {
                                
                                // * Produto de campanha existente na vtex + sellercenter,
                                // * mas o produto não está cadastrado na campanha sellercenter,
                                // * então precisamos abortar o recebimento do pedido
                                
                                if (!$this->model_campaigns_v2_vtex_campaigns->vtexProductCampaignExists($campaignId, $item['id'])) {
                                    return [false, "Item pedido ( {$item['id']} ) de promoção vtex, não está na campanha sellercenter"];
                                }
                                OrdersMarketplace::$orderItemHasVtexCampaign[$item['id']] = $campaignId;
                                $isPricetags = false;
                                $discountVtexCampaigns += (float)(($priceTag['value'] * (-1) / 100) / (int)$item['quantity']);
                            }
                        }

                        if ($isPricetags) {
                            $discountItem += (float)(($priceTag['value'] / 100) * (-1));
                        }

                    }
                    $totalDiscount += $discountItem;
                } 
             */

            $totalDiscountPriceTags += $discountItem;

            $sql = "SELECT * FROM sellercenter_last_post WHERE skulocal = ? AND int_to = ?";
            $query = $this->db->query($sql, array($sku, $this->mkt));
            $prf = $query->row_array();
          
            if (empty($prf)) {
                return [false, "Produto ( {$sku} ) não encontrado!"];
            }
            $seller_id = $prf['store_id'];
            if ($prf['variant'] !== null && $prf['variant'] !== '') { // é variação
                $dataPrdVar = $this->model_products->getVariants($prf['prd_id'], $prf['variant']);
            } else {
                $dataPrdVar = $this->model_products->getProductData(0, $prf['prd_id']);
            }
          
            $dataMkt = $this->model_products_marketplace->getDataByUniqueKey($this->mkt, $prf['prd_id'], $prf['variant'] === null ? '' : $prf['variant']);

            if (!$dataPrdVar)
                return [false, "Produto ( {$sku} ) não encontrado!"];

            // existe preço/estoque para marketplace
            if ($dataMkt) {
                $qtyPrd = $dataMkt['same_qty'] == 1 ? $dataPrdVar['qty'] : ($dataMkt['qty'] === '' ? $dataPrdVar['qty'] : $dataMkt['qty']);
                $pricePrd = $dataMkt['same_price'] == 1 ? $dataPrdVar['price'] : ($dataMkt['price'] === '' ? $dataPrdVar['price'] : $dataMkt['price']);
            } else {
                $qtyPrd = $dataPrdVar['qty'];
                $pricePrd = $dataPrdVar['price'];
            }

            /*if ($item['quantidade'] > $qtyPrd)
                return [false, "Produto ( {$sku} ) sem estoque!"];*/

            // join with promotion_group and get product and mkt
            $queryPromotion = $this->db->query("SELECT p.* FROM promotions AS p JOIN promotions_group AS pr ON p.lote = pr.lote WHERE p.product_id = ? AND p.active = 1 AND pr.ativo = 1 AND (pr.marketplace = ? || pr.marketplace = 'Todos')", array($prf['prd_id'], $this->mkt));
            $promotion = $queryPromotion->row_array();
            if ($promotion) {
                $discountInternal = $pricePrd - $promotion['price'];
                $discountItem += $discountInternal * $item['quantidade'];
                $totalDiscount += $discountInternal * $item['quantidade'];

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
            $totalOrder += ($item['precoVenda'] * $item['quantidade']) - $discountItem - $discountVtexCampaigns;
        }

        // Leio a Loja para pegar o service_charge_value
        $store = $this->model_stores->getStoresData($store_id);

        // pedido
        $orders = array();

        //$orders['freight_seller'] = $store['freight_seller'];

        $paid_status = 1; // sempre chegará como não pago

        // gravo o novo pedido
        $clientProfileData = $pedido['usuario'];
        $shippingAddress = $pedido['pedidoEndereco'][0];
        $shippingLogistic = $pedido['frete'];
        $provider = null;
        $service = null;
        $paymentData = $pedido['pagamento'];

        $totalShip = 0;
        $deadline = 0;
        if ($pedido['frete']['freteContrato'] === 'pickup-in-point') {
            $isPickUpPoint = true;
        }

        $totalShip += $pedido['frete']['valorFreteCliente'];
        $deadlineTemp = filter_var($pedido['frete']['prazoEnvio'], FILTER_SANITIZE_NUMBER_INT);
        if ($deadlineTemp > $deadline) {
            $deadline = $deadlineTemp;
        }
        

        // PRIMEIRO INSERE O CLIENTE
        $clients = array();
        if ($clientProfileData['cnpj']) {
            $clients['customer_name'] = $clientProfileData['razaoSocial'];
            $clients['cpf_cnpj'] = preg_replace("/[^0-9]/", "", $clientProfileData['cnpj']);
            $clients['ie'] = $clientProfileData['inscricaoEstadual'] == "" ? "Isento" : preg_replace("/[^0-9]/", "", $clientProfileData['stateInscription']);
        } else {
            $clients['customer_name'] = $clientProfileData['nome'];
            $clients['cpf_cnpj'] = preg_replace("/[^0-9]/", "", $clientProfileData['cpf']);
            $clients['rg'] = '';
        }
        $clients['phone_1'] = isset($clientProfileData['telefoneResidencial']) ? preg_replace("/[^0-9]/", "", str_replace('+55', '', $clientProfileData['telefoneResidencial'])) : '';
        $clients['phone_2'] = isset($clientProfileData['telefoneCelular']) ? preg_replace("/[^0-9]/", "", str_replace('+55', '', $clientProfileData['telefoneCelular'])) : '';
        $clients['email'] = $this->getEmailClient($clientProfileData['email'] ?? '', $pedido['pedidoId'], $this->mkt);
        $clients['customer_address'] = $shippingAddress['logradouro'] ?? '';
        $clients['addr_num'] = $shippingAddress['numero'] ?? '';
        $clients['addr_compl'] = $shippingAddress['complemento'] ?? '';
        $clients['addr_neigh'] = $shippingAddress['bairro'] ?? '';
        $clients['addr_city'] = $shippingAddress['cidade'] ?? '';
        $clients['addr_uf'] = $shippingAddress['estado'] ?? '';
        $clients['country'] = $shippingAddress['pais'] ?? '';
        $clients['zipcode'] = preg_replace("/[^0-9]/", "", $shippingAddress['cep']);
        $clients['origin'] = $this->mkt; // Entender melhor como encontrar esse info
        $clients['origin_id'] = 1; // Entender melhor como encontrar esse info

        $client_id = $this->model_clients->insert($clients);
        if (!$client_id) {
            return [false, "Ocorreu um problema para gravar o cliente!"];
        }


        foreach ($shippingLogistic['centrosDistribuicao'] as $centroDistribuicao) {
            $centroDistribuicaoId = $centroDistribuicao['centroDistribuicaoId'];
        
            // Itera sobre as cotações filhas do centro de distribuição atual
            foreach ($centroDistribuicao['cotacoesFilhas'] as $cotacaoFilha) {
                if ($cotacaoFilha['centroDistribuicaoId'] === $centroDistribuicaoId) {
                    $provider = $cotacaoFilha['freteContrato'];
                    $service = $cotacaoFilha['freteContrato']; 
                    break 2; 
                }
            }
        }

        if ($provider === null) {
            // Se não encontrou dentro de cotacoesFilhas, mantém o freteContrato original
            $provider = $shippingLogistic['freteContrato'];
        }
        
        if ($service === null) {
            // Se não encontrou dentro de cotacoesFilhas, mantém o freteContrato original para service
            $service = $shippingLogistic['freteContrato'];
        }

        if (!$isPickUpPoint) {
            $provider = in_array(strtoupper($service), array('PAC', 'SEDEX', 'MINI')) ? 'CORREIOS' : 'Transportadora';
            $provider = preg_match('/' . str_replace('%', '.*?', '%correios%') . '/', strtolower($service)) > 0 ||
            preg_match('/' . str_replace('%', '.*?', '%pac_%') . '/', strtolower($service)) > 0 ||
            preg_match('/' . str_replace('%', '.*?', '%sedex_%') . '/', strtolower($service)) > 0 ||
            preg_match('/' . str_replace('%', '.*?', '%mini_%') . '/', strtolower($service)) > 0
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

       
        //$deadline   = filter_var($shippingLogistic['shippingEstimate'], FILTER_SANITIZE_NUMBER_INT);

        $orders['ship_company_preview'] = $provider;
        $orders['ship_service_preview'] = $service;
        $orders['ship_time_preview'] = $deadline;

        $orders['customer_name'] = $clients['customer_name'];
        $orders['customer_address'] = $clients['customer_address'];
        $orders['customer_address_num'] = $clients['addr_num'];
        $orders['customer_address_compl'] = $clients['addr_compl'];
        $orders['customer_address_neigh'] = $clients['addr_neigh'];
        $orders['customer_address_city'] = $clients['addr_city'];
        $orders['customer_address_uf'] = $clients['addr_uf'];
        $orders['customer_address_zip'] = $clients['zipcode'];
        $orders['customer_reference'] = $shippingAddress['reference'] ?? '';

        $order_mkt = $pedido['pedidoId'];
        $orders['bill_no'] = $order_mkt;
        $orders['numero_marketplace'] = $order_mkt;
        $orders['date_time'] = date('Y-m-d H:i:s');
        $orders['customer_id'] = $client_id;
        $orders['customer_phone'] = $clients['phone_1'];

        $orders['total_order'] = $totalOrder;
        $orders['total_ship'] = $totalShip == 0 ? 0 : ($totalShip);
        $orders['gross_amount'] = $orders['total_order'] + $orders['total_ship'] - $totalDiscountOrder;
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
        $orders['discount'] = $totalDiscountOrder;

        $netAmount = $orders['gross_amount'];
        $orders['net_amount'] = number_format($netAmount, 2, '.', '');

        $orders['paid_status'] = 1;
        $orders['company_id'] = $company_id;
        $orders['store_id'] = $store_id;
        $orders['origin'] = $this->mkt;
        $orders['user_id'] = 1;
        $orders['data_limite_cross_docking'] = null;
        $orders['is_pickup_in_point'] = $isPickUpPoint;

        $order_id = $this->model_orders->insertOrder($orders);
        if (!$order_id) {
            return [false, "Não foi possível gravar o pedido ( {$order_mkt} )!"];
        }
        foreach($paymentData as $pag) {
            $bandeira = $pag['informacoesAdicionais']['1']['valor'];
           
            if(isset($pag['cartaoCredito'][0]['bandeira'])){
                $bandeira = $pag['cartaoCredito'][0]['bandeira'];
            }

            $arrPayment = array(
                'order_id'          => $order_id,
                'parcela'           => $pag['numeroParcelas'],
                'bill_no'           => $orders['bill_no'],
                'data_vencto'       => date('Y-m-d'),
                'valor'             => $pag['valorTotal'],
                'forma_id'          => $pag['informacoesAdicionais']['1']['valor'],
                'forma_desc'        => $bandeira,
                'payment_id'        => $pag['formaPagamentoId']
            );

            $paymentSuccess = $this->model_orders->insertParcels($arrPayment);
            if (!$paymentSuccess) {
                return [false, "Não foi possível gravar os dados de pagamento do pedido ( {$order_mkt} )!"];
            }
    }

        // $allow_campaign_payment_method = $this->model_settings->getValueIfAtiveByName('allow_campaign_payment_method');
        // $allow_campaign_trade_policies = $this->model_settings->getValueIfAtiveByName('allow_campaign_trade_policies');

        // Itens
        $itensIds = array();
        $orderItems = [];

        foreach ($pedido['itens'] as $item) {

           $sku = $item['sku'];
           $discount = 0;

           $arrayAjustes = $item['ajustes'];
          /*  $informaDescontoItem = array_filter($arrayAjustes, function($desconto) {
               return $desconto['valor'] < 0;
           });
           rsort($informaDescontoItem);
           
           $descontoItem = -$informaDescontoItem[0]['valor']; */

            // Filtrar os itens do tipo 6 e com valores negativos
            $informaDescontoItem = array_filter($arrayAjustes, function($ajuste) {
                return $ajuste['tipo'] === 6 && $ajuste['valor'] < 0;
            });

            // Somar os valores negativos e atribuir ao descontoItem
            $descontoItem = array_reduce($informaDescontoItem, function($carry, $ajuste) {
                return $carry + $ajuste['valor'];
            }, 0);

            // Transformar o valor negativo em positivo
            $descontoItem = -$descontoItem;

            // $discountVtexCampaigns = 0;
            // if (isset($item['priceTags'])) {
            //     foreach ($item['priceTags'] as $priceTag) {

            //         $isPricetags = true;
            //         if (($allow_campaign_payment_method || $allow_campaign_trade_policies) && isset($priceTag['name']) && strstr($priceTag['name'], 'discount@price-') && strstr($priceTag['name'], '#')) {
            //             $campaignId = explode('discount@price-', $priceTag['name']);
            //             $campaignId = explode('#', $campaignId[1]);
            //             $campaignId = $campaignId[0];
            //             if ($campaignId && $this->model_campaigns_v2_vtex_campaigns->vtexCampaignIdExists($campaignId)) {
            //                 /**
            //                  * Produto de campanha existente na vtex + sellercenter,
            //                  * mas o produto não está cadastrado na campanha sellercenter,
            //                  * então precisamos abortar o recebimento do pedido
            //                  */
            //                 if (!$this->model_campaigns_v2_vtex_campaigns->vtexProductCampaignExists($campaignId, $item['id'])) {
            //                     return [false, "Item pedido ( {$item['id']} ) de promoção vtex, não está na campanha sellercenter"];
            //                 }
            //                 OrdersMarketplace::$orderItemHasVtexCampaign[$item['id']] = $campaignId;
            //                 $isPricetags = false;
            //                 $discountVtexCampaigns += (float)(($priceTag['value'] * (-1) / 100) / (int)$item['quantity']);
            //             }
            //         }

            //         if ($isPricetags) {
            //             $discount += $priceTag['value'] * (-1);
            //         }

            //     }
            // }

            // na grade de item grava o valor do desconto unitário, mas da VTEX tem o valor multiplicado
            if ($descontoItem) {
                $discount = (float)(($descontoItem) / (int)$item['quantidade']);
            }
            

            $sql = "SELECT * FROM sellercenter_last_post WHERE skulocal = ? AND int_to = ?";
            $query = $this->db->query($sql, array($sku, $this->mkt));
            $prf = $query->row_array();

            $dataPrd = $this->model_products->getProductData(0, $prf['prd_id']);
            /*

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

            if ($item['quantidade'] > $qtyPrd)
                return [false, "Produto ( {$sku} ) sem estoque!"];
            */

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
                $orderItem['qty'] = (int)$item['quantidade'];

                // Assim conseguimos saber o valor real do produto e o valor aplicado de promoção.
                $orderItem['rate'] = $item['precoVenda'] - $discount - $discountVtexCampaigns;

                $orderItem['amount'] = (float)$orderItem['rate'] * $orderItem['qty'];
                $orderItem['discount'] = $discount > 0 ? (float)number_format($discount, 3, '.', '') : $discount;
                $orderItem['company_id'] = $dataPrd['company_id'];
                $orderItem['store_id'] = $dataPrd['store_id'];
                $orderItem['un'] = 'un';
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
            $this->model_sellercenter_last_post->reduzEstoque($prf['int_to'], $prf['prd_id'], $orderItem['qty']);

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
        die;
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
        die;
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

    public function savePaymantsOrder($numero_marketplace, $order_id)
    {
        return true;
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $orderQueue = array(
            'order_id' => $order_id,
            'numero_marketplace' => $numero_marketplace,
            'status' => 0
        );

        //$this->model_queue_payments_orders_marketplace->create($orderQueue);

        $this->log_data('batch', $log_name, 'Pagamento do pedido: ' . $order_id . ' cadastrado na fila');
    }

}