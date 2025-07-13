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
 * @property Model_vtex_ult_envio $model_vtex_ult_envio
 * @property Model_freights $model_freights
 * @property Model_integrations $model_integrations
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_log_integration_order_marketplace $model_log_integration_order_marketplace
 * @property Model_orders_pickup_store $model_orders_pickup_store
 * @property Model_orders_occ $model_orders_occ
 * @property Model_campaigns_v2 $model_campaigns_v2
 * @property Model_campaigns_v2_occ_campaigns $model_campaigns_v2_occ_campaigns
 *
 * @property CalculoFrete $calculofrete
 * @property OrdersMarketplace $ordersmarketplace
 * @property Slack $slack
 */

class Orders extends FreteConectala {

    private $mkt;

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
        $this->load->model('model_orders_pickup_store');
        $this->load->model('model_orders_occ');
        $this->load->library('CalculoFrete');
        $this->load->library('ordersMarketplace');

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            $this->load->model('model_campaigns_v2');
            $this->load->model('model_campaigns_v2_occ_campaigns');
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
                "code"      => "1",
                "message"   => "O verbo 'GET' não é compatível com essa rota",
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
        $this->mkt = $this->tiraAcentos($mkt);

        $data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));
        // update payment order
        if ($orderId) {
            // $this->log_data('api', 'PaymentVTEX', json_encode($data) . "\n - URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
             $returnOCC =$this->formatResponsePayment($data, $orderId);
             $this->log_data('api', 'PaymentOCC - return', 'received='.json_encode($data).' return='.json_encode($returnOCC) . "\n - URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
             return $this->response($returnOCC, REST_Controller::HTTP_OK);
         }

        // new order
        $this->log_data('api', 'CheckoutOCC - Received', 'received='.json_encode($data));
        $returnOCC= $this->formatResponseCheckout($data);
        $this->log_data('api', 'CheckoutOCC - Return', 'received='.json_encode($data).' return='.json_encode($returnOCC));
        $this->response($returnOCC, REST_Controller::HTTP_OK);
    }

    public function cancel_post($mkt, $orderId = null) {
        $this->mkt = $this->tiraAcentos($mkt);;

        $data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));

        $this->log_data('api', 'Orders Cancel OCC - POST', json_encode($data));

        // update cancel order
        if ($orderId) {
            $returnCancel = $this->formatResponseCancellation($mkt, $data, $orderId);
            $this->log_data('api', 'CancellationOCC', 'received='.json_encode($data).' return='.json_encode($returnCancel) . "\n\n URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']} \n\n METHOD= {$_SERVER['REQUEST_METHOD']}");
            return $this->response($returnCancel, REST_Controller::HTTP_OK);
        }
    }

    public function process_post($mkt, $orderId = null) {
        $this->mkt = $this->tiraAcentos($mkt);;

        $data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));

        $this->log_data('api', 'Orders Process OCC - POST', json_encode($data));

        // update process order
        if ($orderId) {
            $orderProcess = [
                "json" => json_encode($data),
                "date_created" => (new DateTime())->format("Y-m-d H:i:s"),
                "status" => 0,
                "order_id" => $orderId
            ];
            $returnOrdersOcc = $this->model_orders_occ->create($orderProcess);
            if($returnOrdersOcc){                
                $this->log_data('api', 'ProcessOcc', 'received='.json_encode($data).' return='.json_encode($returnOrdersOcc) . "\n\n URL= {$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']} \n\n METHOD= {$_SERVER['REQUEST_METHOD']}");
                return $this->response(true, REST_Controller::HTTP_OK);
            }
            return $this->response(json_encode(['message' => "received error"]), REST_Controller::HTTP_OK);

        }

        return $this->response(json_encode(['message' => "invalid url: missing Order Id"]), REST_Controller::HTTP_OK);
    }

    private function formatResponseCheckout($pedido)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        $returnCheckout = array();
        $arrDatas = [];
        $returnObject = false;
        $arrOrderId = array();
        $order = $pedido['order'];


        // Inicia transação
        $this->db->trans_begin();
        $create = $this->createOrder($order, $order['id']);

        if (!$create[0]) {
            $this->db->trans_rollback();
            $this->log_data('api',$log_name,$create[1] . ' - Pedido = ' . json_encode($order),"E");
            return [
                "error" => [
                "code"      => "1",
                "message"   => $create[1],
                "exception" => null
                ]
            ];
        }
        $this->db->trans_commit();
        return true;
    }

    private function formatResponseCancellation($int_to, $data, $orderId)
    {
        $return = array();
        $isSplit = explode('-', $orderId);
        if(count($isSplit) == 1){
            $orders = $this->model_orders->getAllOrdersDatabyBill($this->mkt, $orderId);
        }else{
            $single_order = $this->model_orders->getOrdersDatabyNumeroMarketplace($orderId); 
            $orders = [];
            array_push($orders, $single_order);
        }
        
        $return = [];
        foreach($orders as $order){
            
            $this->markCancelled($int_to, $order['numero_marketplace']);
            array_push($return, array(
                "date"                  => (new DateTime())->format("Y-m-d H:i:s"),
                "marketplaceOrderId"    => $order['numero_marketplace'],
                "orderId"               => $order['id'],
                "receipt"               => null
            ));
        }

        return $return;
    }

    private function markCancelled($int_to, $orderId)
	{
        if ($order_exist = $this->model_orders->getOrdersDatabyNumeroMarketplace($orderId)) {
			if (!in_array($order_exist['paid_status'], [95, 96, 97, 98])) {
                $this->ordersmarketplace->cancelOrder($order_exist['id'], false, false);
                return true;
			}
		}
		else return false;
    }

    private function notifyPriceAndStockChange($product_id, $int_to) //verificar se vamos utilizar
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        //echo "Buscando produtos com estoque alterado para notificar.\n";
        $products = $this->model_products->getProductsToIntegrationById($product_id, $int_to);
        
        if (!$products) {
            $notice = "Não foi encontrado nenhum produto com estoque alterado.";
            //echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"I");
            return false;
        }

        //echo "Encontramos ".count($products)." produto(s) com alteração de estoque para notificar.\n";
        foreach ($products as $key => $product) {
            $data = [];

            $sellerId =  $product['seller_id']; 
            if (strlen ($sellerId) < 3) {
                $sellerId = substr('000'.$sellerId,-3);
            }

            $bodyParams = json_encode($data);
            $endPoint = 'api/catalog_system/pvt/skuSeller/changenotification/'.$sellerId.'/'.$product['ref_id'];
            
            //echo "Verificando se o ".($key+1)."º produto existe no marketplace ".$product['int_to']." para o seller ".$product['seller_id'].".\n";
            $skuExist = $this->process($product['int_to'], $endPoint, 'POST', $bodyParams);

            if ($this->responseCode != 404) {
                $notice = "Notificação de alteração de estoque concluída para o ".($key+1)."º produto.";
                //echo $notice."\n";
                $this->log_data('batch', $log_name, $notice,"I");
                $this->model_products->updateProductIntegrationStatus($product['prdIntegration_id'], 2);
                continue;
            }

            $notice = "O ".($key+1)."º produto não está cadastrado no marketplace ".$product['int_to']." para o seller ".$product['seller_id'].".";
            //echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
            $this->model_products->updateProductIntegrationStatus($product['prdIntegration_id'], 90);
        }
    }

    private function createOrder(array $pedido, string $orderId)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->model_orders->getOrdersDatabyBill($this->mkt, $pedido['id'])) {
            return [false, "Pedido ( {$pedido['id']} ) já existente!"];
        }

        // Verifico se todos os skus estão certos e são das mesmas empresas
        $company_id         = '';
        $store_id           = '';
        $cross_docking_default = 0;
        $cross_docking = $cross_docking_default;
        $totalOrder = 0;
        $totalDiscount      = 0;
        $totalDiscountPriceTags = 0;
        $isPickup = false;
        $discountBoleto = null;
        $discountPix = null;

        $zema_status = $pedido['zema_status'];
        $clientProfileData  = json_decode($pedido['paymentGroups'][0]['paymentProps']['customProperties']);
        $preAdress = json_decode($clientProfileData->shippingGroups);
        $shippingAddress    = (array) $preAdress[0]->shippingAddress;
        $paymentData        = $pedido['paymentGroups'][0];
        $document = $clientProfileData->clientDocument;
        // if(isset($clientProfileData->creditCardHolderCPF)){
        //     $document =   $clientProfileData->creditCardHolderCPF;
        // }
        $date_of_birth = $clientProfileData->dateOfBirth;
        $data_quote_id = json_decode($pedido['quote_id'], true) ?? [];



        // PRIMEIRO INSERE O CLIENTE
        $clients = array();

        $clients['customer_name']   = $clientProfileData->customerFirstName . ' ' . $clientProfileData->customerLastName;
        $clients['cpf_cnpj']        = preg_replace("/[^0-9]/", "", $document);
        $clients['rg']              = '';

        $adress = explode("|",$shippingAddress['address1']);
        $street = $adress[0];
        $number = $adress[1];
        $clients['phone_1']             = isset($shippingAddress['phoneNumber']) ? preg_replace("/[^0-9]/", "", str_replace('+55', '',  $shippingAddress['phoneNumber'])) : '';
        $clients['email']               = $this->getEmailClient($shippingAddress['email'] ?? '', $pedido['id'], $this->mkt);
        $clients['customer_address']    = $street ?? '';
        $clients['addr_num']            = $number ?? '';
        $clients['addr_compl']          = $shippingAddress['address3'] ?? '';
        $clients['addr_neigh']          = $shippingAddress['address2'] ?? '';
        $clients['addr_city']           = $shippingAddress['city'] ?? '';
        $clients['addr_uf']             = $shippingAddress['state'] ?? '';
        $clients['country']             = $shippingAddress['country'] ?? '';
        $clients['zipcode']             = preg_replace("/[^0-9]/", "", $shippingAddress['postalCode']);
        $clients['origin']              = $this->mkt; // Entender melhor como encontrar esse info
        $clients['origin_id']           = 1; // Entender melhor como encontrar esse info
        $clients['birth_date']          = $date_of_birth;

        $client_id = $this->model_clients->insert($clients);
        if (!$client_id) {
            return [false, "Ocorreu um problema para gravar o cliente!"];
        }

        //$items = json_decode($pedido['payments'][0]['customProperties']['cartItems']);
        $isPickup = false;
        $preItems = json_decode($pedido['paymentGroups'][0]['paymentProps']['customProperties']);
        $items = json_decode($preItems->shippingGroups);

        foreach($items as $item) {

            $productItem = $item->items[0];
            $sku                = $productItem->catRefId;
            $discountInternal   = 0;
            $discountItem       = 0;
            $returnToOriginal = false;


            $sql = "SELECT * FROM occ_last_post WHERE skulocal = ?  AND int_to = ?";
            $query = $this->db->query($sql, array($sku, $this->mkt));
            $prf = $query->row_array();
            if (empty($prf))
                return [false, "Produto ( {$sku} ) não encontrado!"];

            $stores_multi_cd = false;
            $settingStoresMultiCd = $this->model_settings->getSettingDatabyName('stores_multi_cd');
            if ($settingStoresMultiCd && $settingStoresMultiCd['status'] == 1) {
                $stores_multi_cd = true;
            }
            if (!isset($prdBkp)){
                $prdBkp = ['prd_id' => null, 'store_id' => null];
            }
            $prdBkp['store_id'] = null;
            if($stores_multi_cd){
                $prdBkp  =  $prf;  
                $prf = $this->calculofrete->setDataMultiCd($prf,"qty",$clients['zipcode']);
            }

            if ($prf['variant'] !== null && $prf['variant'] !== '') { // é variação
                $dataPrdVar = $this->model_products->getVariants($prf['prd_id'], $prf['variant']);
            } else {
                $dataPrdVar = $this->model_products->getProductData(0, $prf['prd_id']);
            }

            $dataMkt = $this->model_products_marketplace->getDataByUniqueKey($this->mkt, $prf['prd_id'], $prf['variant'] === null ? '' : $prf['variant']);

            if (!$dataPrdVar){
                return [false, "Produto ( {$sku} ) não encontrado!"];
            }



            
            // existe preço/estoque para marketplace
            if ($dataMkt) {
                $qtyPrd     = $dataMkt['same_qty'] == 1 ? $dataPrdVar['qty'] : ($dataMkt['qty'] === '' ? $dataPrdVar['qty'] : $dataMkt['qty']);
                $pricePrd   = $dataMkt['same_price'] == 1 ? $dataPrdVar['price'] : ($dataMkt['price'] === '' ? $dataPrdVar['price'] : $dataMkt['price']);
            } else {
                $qtyPrd     = $dataPrdVar['qty'];
                $pricePrd   = $dataPrdVar['price'];
            }

            if ($productItem->quantity > $qtyPrd){
                //validação extra do principal
                if($prdBkp['store_id'] != $prf['store_id'] && $productItem->quantity <= $prdBkp['qty']){
                    $prf = $prdBkp; 
                    $returnToOriginal = true;
                }else{
                    return [false, "Produto ( {$sku} ) sem estoque!"];
                }
            }


            // join with promotion_group and get product and mkt
            $queryPromotion = $this->db->query("SELECT p.* FROM promotions AS p JOIN promotions_group AS pr ON p.lote = pr.lote WHERE p.product_id = ? AND p.active = 1 AND pr.ativo = 1 AND (pr.marketplace = ? || pr.marketplace = 'Todos')", array($prf['prd_id'], $this->mkt));
            $promotion = $queryPromotion->row_array();
            if ($promotion) {
                $discountInternal   = $pricePrd - $promotion['price'];
                $discountItem       += $discountInternal * $item->quantity;
                $totalDiscount      += $discountInternal * $item->quantity;

            // somar desconto da promoção para pegar o valor bruto do item.
                $item->price += $discountInternal;
            }
            $totalDiscountPriceTags = -($productItem->discountAmount);
            $payment_order = $pedido['paymentGroups'][0]['paymentProps'];
            $payment_order_amount = $pedido['paymentGroups'][0]['amount'];

            $totalItens = $item->priceInfo->amount;
            $grossAmount = $item->priceInfo->total;
            $discountPayment = 0;
            $isPix = $this->model_settings->getSettingDatabyName('pix_payment_discount');
            if($isPix && $payment_order['paymentMethod'] == 'pix'){
                $discountPayment = $item->priceInfo->amount*($isPix['value']/100);
                $discountPayment = round($discountPayment, 2, PHP_ROUND_HALF_UP);
                $totalDiscountPriceTags = $totalDiscountPriceTags+$discountPayment;
                $totalItens = $item->priceInfo->amount-$discountPayment;
                $grossAmount = $totalItens+$item->priceInfo->shipping;
            }

            $isBoleto = $this->model_settings->getSettingDatabyName('boleto_payment_discount');
            if($isBoleto && $payment_order['paymentMethod'] == 'boleto'){
                $discountPayment = $item->priceInfo->amount*($isBoleto['value']/100);
                $discountPayment = round($discountPayment, 2, PHP_ROUND_HALF_UP);
                $totalDiscountPriceTags = $totalDiscountPriceTags+$discountPayment;
                $totalItens = $item->priceInfo->amount-$discountPayment;
                $grossAmount = $totalItens+$item->priceInfo->shipping;
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
            $totalOrder = $item->priceInfo->total;
            $coupon = null;
            if($item->discountInfo->discountDescList){
                $coupon = json_encode($item->discountInfo->discountDescList);
            }

            // Leio a Loja para pegar o service_charge_value
            $store = $this->model_stores->getStoresData($store_id);
            $storeMultiCd = false;
            if($store['type_store'] == '1' || $store['type_store'] == '2'){
                $storeMultiCd = true;
            }

            // pedido
            $orders = Array();

            //$orders['freight_seller'] = $store['freight_seller'];
            // gravo o novo pedido
            $paid_status = 1; // sempre chegará como não pago

            $totalShip = 0;
            $deadline  = 0;
            if(isset($item->shippingMethod->shippingDeliveryDays)){
                $deadline =      $item->shippingMethod->shippingDeliveryDays;
            }
            $shippingLogistic   = $item->shippingMethod->value;
            // verificação do frete -
            $pickupstoreDescription = $this->model_settings->getSettingDatabyName('occ_pickupstore');
            //if(!str_contains($item->shippingMethod->shippingMethodDescription, $pickupstoreDescription['value'])){
            if($item->shippingMethod->shippingMethodDescription != $pickupstoreDescription['value']){
                $provider   = in_array(strtoupper($shippingLogistic), array('PAC', 'SEDEX', 'MINI')) ? 'CORREIOS' : 'Transportadora';
                $provider   =   preg_match('/' . str_replace('%', '.*?', '%correios%') . '/', strtolower($shippingLogistic)) > 0 ||
                                preg_match('/' . str_replace('%', '.*?', '%pac_%') . '/', strtolower($shippingLogistic)) > 0 ||
                                preg_match('/' . str_replace('%', '.*?', '%sedex_%') . '/', strtolower($shippingLogistic)) > 0 ||
                                preg_match('/' . str_replace('%', '.*?', '%mini_%') . '/', strtolower($shippingLogistic)) > 0
                    ? 'CORREIOS' : $provider;

                $logistic = $this->calculofrete->getLogisticStore(array(
                    'freight_seller' 		=> $store['freight_seller'],
                    'freight_seller_type' 	=> $store['freight_seller_type'],
                    'store_id'				=> $store['id']
                ));

                if ($logistic['type'] === 'sellercenter' && $logistic['sellercenter']) {

                    $rowSettingsLogisticDefault = $this->model_settings->getSettingDatabyName('quote_default_sellercenter');
                    if (
                        $rowSettingsLogisticDefault &&
                        $rowSettingsLogisticDefault['status'] == 1 &&
                        $rowSettingsLogisticDefault['value'] == 'sgp'
                    ) $provider = 'CORREIOS';
                }

                $sellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

                $service    = $shippingLogistic;
                $service = str_replace('-'.$sku, '', $service);

            }else{
                $provider = $item->shippingMethod->shippingMethodDescription;
                $service = $item->shippingMethod->shippingMethodDescription;
                $isPickup = true;
            }

            $orders['ship_company_preview']     = $provider;
            $orders['ship_service_preview']     = $service;
            $orders['ship_time_preview']        = $deadline;

            $orders['customer_name']            = $clients['customer_name'];
            $orders['customer_address']         = $clients['customer_address'];
            $orders['customer_address_num']     = $clients['addr_num'];
            $orders['customer_address_compl']   = $clients['addr_compl'];
            $orders['customer_address_neigh']   = $clients['addr_neigh'];
            $orders['customer_address_city']    = $clients['addr_city'];
            $orders['customer_address_uf']      = $clients['addr_uf'];
            $orders['customer_address_zip']     = $clients['zipcode'];
            $orders['customer_reference']       =  '';

            $order_mkt                      = $pedido['id'].'-'.$item->shippingGroupId;
            $orders['bill_no']              = $pedido['id'];
            $orders['numero_marketplace']   = $order_mkt;
            $orders['date_time']            = date('Y-m-d H:i:s');
            $orders['customer_id']          = $client_id;
            $orders['customer_phone']       = $clients['phone_1'];

            $orders['total_order']          = $totalItens;
            $orders['total_ship']           = $item->priceInfo->shipping;
            $orders['gross_amount']         = $grossAmount;
            $orders['service_charge_rate']  = $store['service_charge_value'];
            $orders['service_charge_freight_value']  = $store['service_charge_freight_value'];
            $orders['service_charge']       = number_format(($orders['total_order'] * $store['service_charge_value'] / 100) + ($orders['total_ship'] * $store['service_charge_freight_value'] / 100), 2, '.', '');
            $orders['vat_charge_rate']      = 0; //pegar na tabela de empresa — Não seja usado.....
            $orders['vat_charge']           = number_format(($orders['gross_amount'] - $orders['total_ship']) * $orders['vat_charge_rate'] / 100, 2, '.', ''); //pegar na tabela de empresa - Não está sendo usado.....
            $orders['discount']             = $totalDiscountPriceTags;

            $netAmount = $orders['gross_amount'];

            $orders['net_amount']   = number_format($netAmount, 2, '.', '');

            $orders['paid_status']  = $paid_status;
            $orders['company_id']   = $company_id;
            $orders['store_id']     = $store_id;
            $orders['origin']       = $this->mkt;
            $orders['user_id']      = 1;
            $orders['coupon']       = $coupon;
            $orders['sales_model']  = $zema_status;
            $orders['multi_channel_fulfillment_store_id']  = $prdBkp['store_id'];

            $orders['data_limite_cross_docking'] = null;

            $order_id = $this->model_orders->insertOrder($orders);
            if (!$order_id) {
                return [false, "Não foi possível gravar o pedido ( {$order_mkt} )!"];
            }
            $quote_id[$order_id] = null;



            $arrPayment = array(
                'order_id'          => $order_id,
                'parcela'           => $payment_order['installmentQuantity'],
                'bill_no'           => $orders['bill_no'],
                'data_vencto'       => date('Y-m-d'),
                'valor'             => $payment_order_amount,
                'forma_id'          => $payment_order['paymentMethod'],
                'forma_desc'        => isset($payment_order['creditCardType']) ? $payment_order['creditCardType'] : $payment_order['paymentMethod'],
            );

            $paymentSuccess = $this->model_orders->insertParcels($arrPayment);
            if (!$paymentSuccess) {
                return [false, "Não foi possível gravar os dados de pagamento do pedido ( {$order_mkt} )!"];
            }

            // adicionando addons aos itens
            foreach($item->items as $addons){

                if(isset($addons->childItems)){
                    foreach($addons->childItems as $addon){
                        array_push($item->items, $addon);
                    }
                }

            }

            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                $allow_campaign_payment_method = $this->model_settings->getValueIfAtiveByName('allow_occ_campaign_payment_method');
            }

            foreach($item->items as  $OrdersItem) {
                //$OrdersItem['childItems']
                $OrdersItem = (array) $OrdersItem;
                //OrdersItems
                $itensIds = array();
                $sku        = $OrdersItem['catRefId'];
                $discount = 0;
                $discountOccCampaigns = 0;
                if(isset($OrdersItem['discountAmount'])){
                    $discount = -($OrdersItem['discountAmount']);
                }
                if($discountPayment){
                    if($isPix && $payment_order['paymentMethod'] == 'pix'){
                        $discountPayment = $OrdersItem['amount']*($isPix['value']/100);
                        $discountPayment = round($discountPayment, 2, PHP_ROUND_HALF_UP);
                    }

                    if($isBoleto && $payment_order['paymentMethod'] == 'boleto'){
                        $discountPayment = $OrdersItem['amount']*($isBoleto['value']/100);
                        $discountPayment = round($discountPayment, 2, PHP_ROUND_HALF_UP);
                    }
                    $discount = $discount+$discountPayment;
                }


                // na grade de item grava o valor do desconto unitário, mas da VTEX tem o valor multiplicado
                if ($discount) {
                    $discount = (float)(($discount) / (int)$OrdersItem['quantity']);
                }


                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                    if ($allow_campaign_payment_method) {
                        if (isset($OrdersItem['discountInfo']) && $OrdersItem['discountInfo']){
                            foreach ($OrdersItem['discountInfo'] as $discountInfo){
                                $discountInfoArray = (array) $discountInfo;
                                if (isset($discountInfoArray['promotionId'])
                                    && $discountInfoArray['promotionId']
                                    && $this->model_campaigns_v2_occ_campaigns->occCampaignIdExists($discountInfoArray['promotionId'])){
                                    if (!$this->model_campaigns_v2_occ_campaigns->occProductCampaignExists($discountInfoArray['promotionId'], $sku)) {
                                        return [false, "Item pedido ( {$sku} ) de promoção occ, não está na campanha sellercenter"];
                                    }
                                    OrdersMarketplace::$orderItemHasMarketplaceCampaign[$sku] = $discountInfoArray['promotionId'];
                                    OrdersMarketplace::$orderItemMarketplaceCampaignDiscount[$sku] = (float)$discountInfoArray['totalAdjustment'];
                                    $discountOccCampaigns += (float)$discountInfoArray['totalAdjustment'];
                                }
                            }
                        }
                    }
                }

                $sql    = "SELECT * FROM occ_last_post WHERE skulocal = ? AND int_to = ?";
                $query  = $this->db->query($sql, array($sku,  $this->mkt));
                $prf    = $query->row_array();

                $prdBkp['prd_id'] = $prf['prd_id'];
                if($stores_multi_cd && !$returnToOriginal){
                    $prf = $this->calculofrete->setDataMultiCd($prf,"qty",$clients['zipcode']);
                }

                if(!$storeMultiCd){
                    $prdBkp = ['prd_id' => null, 'store_id' => null];
                }

                $dataPrdVar = $dataPrd = $this->model_products->getProductData(0, $prf['prd_id']);

                if ($prf['variant'] !== null && $prf['variant'] !== '') { // é variação
                    $dataPrdVar = $this->model_products->getVariants($prf['prd_id'], $prf['variant']);
                }

                $dataMkt = $this->model_products_marketplace->getDataByUniqueKey($this->mkt, $prf['prd_id'], $prf['variant'] === null ? '' : $prf['variant']);

                // existe preço/estoque para marketplace
                if ($dataMkt) {
                    $qtyPrd     = $dataMkt['same_qty'] == 1 ? $dataPrdVar['qty'] : ($dataMkt['qty'] === '' ? $dataPrdVar['qty'] : $dataMkt['qty']);
                    $pricePrd   = $dataMkt['same_price'] == 1 ? $dataPrdVar['price'] : ($dataMkt['price'] === '' ? $dataPrdVar['price'] : $dataMkt['price']);
                } else {
                    $qtyPrd     = $dataPrdVar['qty'];
                    $pricePrd   = $dataPrdVar['price'];
                }

                if ($OrdersItem['quantity'] > $qtyPrd)
                    return [false, "Produto ( {$sku} ) sem estoque!"];

                if ($dataPrd['is_kit'] == 0) {
                    $OrdersItems = array();
                    $OrdersItems['skumkt']        = $sku;
                    $OrdersItems['order_id']      = $order_id; // ID da order incluida
                    $OrdersItems['product_id']    = $prf['prd_id'];
                    $OrdersItems['sku']           = $prf['sku'];

                    $variant='';
                    if (!is_null($prf['variant'])) {
                        $variant = $prf['variant'];
                    }
                    $OrdersItems['variant']       = $variant;
                    $OrdersItems['name']          = $dataPrd['name'];
                    $OrdersItems['qty']           = (int)$OrdersItem['quantity'];

                    // Somado o preço recebido da VTEX com o preço de desconto da promoção.
                    // Assim conseguimos saber o valor real do produto e o valor aplicado de promoção.
                    $OrdersItems['rate']          = ($OrdersItem['salePrice']) - $discount;
                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                        //Como o desconto já vem total, precisamos descontar o desconto de campanha para não entender como cupom
                        $discount-= $discountOccCampaigns;
                    }

                    $OrdersItems['amount']        = (float)$OrdersItem['salePrice'] * $OrdersItems['qty'];
                    $OrdersItems['discount']      = $discount > 0 ? (float)number_format($discount, 3, '.', '') : $discount;
                    $OrdersItems['company_id']    = $prf['company_id'];
                    $OrdersItems['store_id']      = $prf['store_id'];
                    $OrdersItems['un']            = 'un';
                    $OrdersItems['pesobruto']     = $dataPrd['peso_bruto'];  // Não tem na vtex
                    $OrdersItems['largura']       = $dataPrd['largura']; // Não tem na vtex
                    $OrdersItems['altura']        = $dataPrd['altura']; // Não tem na vtex
                    $OrdersItems['profundidade']  = $dataPrd['profundidade']; // Não tem na vtex
                    $OrdersItems['unmedida']      = 'cm'; // não tem na vtex
                    $OrdersItems['kit_id']        = null;
                    $OrdersItems['fulfillment_product_id'] = $prdBkp['prd_id'];

                    $item_id = $this->model_orders->insertItem($OrdersItems);
                    if (!$item_id) {
                        return [false, "Ocorreu um problema para gravar o item ( {$sku} )!"];
                    }

                    $this->model_products->reduzEstoque($prf['prd_id'], $OrdersItems['qty'], $variant, $order_id);
                    $this->model_vtex_ult_envio->reduzEstoque($prf['int_to'], $prf['prd_id'], $OrdersItems['qty']);
                    $this->model_promotions->updatePromotionByStock($prf['prd_id'], $OrdersItems['qty'], $OrdersItem['salePrice']);
                }
                else {
                    $productsKit = $this->model_products->getProductsKit($prf['prd_id']);
                    foreach ($productsKit as $productKit){
                        $prd_kit = $this->model_products->getProductData(0,$productKit['product_id_item']);
                        $OrdersItemsKit = array();
                        $OrdersItemsKit['order_id'] = $order_id; // ID da order incluida
                        $OrdersItemsKit['skumkt'] =$sku;
                        $OrdersItemsKit['kit_id'] = $productKit['product_id'];
                        $OrdersItemsKit['product_id'] = $prd_kit['id'];
                        $OrdersItemsKit['sku'] = $prd_kit['sku'];
                        $variant = '';
                        $OrdersItemsKit['name'] = $prd_kit['name'];
                        $OrdersItemsKit['qty'] = (int)$OrdersItem['quantity'] * (int)$productKit['qty'];
                        if($discount > 0){
                         $discount = $discount / $OrdersItemsKit['qty'];
                          $discount  = (float)number_format($discount, 2, '.', '');
                        }
                        $OrdersItemsKit['rate'] = $productKit['price'] - $discount ;  // pego o preço do KIT em vez do item
                        $OrdersItemsKit['amount'] = (float)$OrdersItemsKit['rate'] * $OrdersItemsKit['qty'];
                        $OrdersItemsKit['discount'] =  $discount; // Tiro o desconto do primeiro item .
                        $discount = 0;
                        $OrdersItemsKit['company_id'] = $prd_kit['company_id'];
                        $OrdersItemsKit['store_id'] = $prd_kit['store_id'];
                        $OrdersItemsKit['un'] =  $iten['measurementUnit'] ?? 'un';
                        $OrdersItemsKit['pesobruto'] = $prd_kit['peso_bruto'];  // Não tem na SkyHub
                        $OrdersItemsKit['largura'] = $prd_kit['largura']; // Não tem na SkyHub
                        $OrdersItemsKit['altura'] = $prd_kit['altura']; // Não tem na SkyHub
                        $OrdersItemsKit['profundidade'] = $prd_kit['profundidade']; // Não tem na SkyHub
                        $OrdersItemsKit['unmedida'] = 'cm'; // não tem na skyhub
                        $OrdersItemsKit['fulfillment_product_id'] = $prdBkp['prd_id'];
                        //var_dump($OrdersItemsKit);
                        $item_id = $this->model_orders->insertItem($OrdersItemsKit);
                        if (!$item_id) {

                            $this->model_orders->remove($order_id);
                            $this->model_clients->remove($client_id);
                            $this->log_data('api',$log_name,'Erro ao incluir item. pedido mkt = '.$pedido['code'].' order_id ='.$order_id.' removendo para receber novamente',"E");
                            return [false, "Ocorreu um problema para gravar o item ( {$sku} )!"];
                        }
                        $itensIds[]= $item_id;
                        // Acerto o estoque do produto filho

                        $this->model_products->reduzEstoque($prd_kit['id'],$OrdersItems['qty'],$variant,$order_id);

                    }
                    $this->model_vtex_ult_envio->reduzEstoque($prf['int_to'], $prf['prd_id'], $OrdersItems['qty']);
                }
                if($isPickup){
                    $pickinfo = json_decode($preItems->pickupStoreAddress);
                    $document = $preItems->pickupStoreContactInfoDocument;
                    if(isset($pedido['x_pickupStoreContactInfoDocument'])){
                        $document = $pedido['x_pickupStoreContactInfoDocument'];
                    }
                    $clientName = $preItems->pickupStoreContactInfoName;
                    if(isset($pedido['x_pickupStoreContactInfoName'])){
                        $clientName = $pedido['x_pickupStoreContactInfoName'];
                    }
                    $data = Array(
                        "order_id" => $order_id,
                        "marketplace_order_id" => $order_mkt,
                        "client_document" => $document,
                        "client_name" => $clientName,
                        "store_pickup_id"=> $pickinfo->locationId
                    );
                    $this->model_orders_pickup_store->create($data);
                    $isPickup = false;
                }else{
                    $this->calculofrete->createQuoteShipRegister($order_id, 'OCC');
                }

                if (!$quote_id[$order_id]) {
                    $result_quote_id = array_filter($data_quote_id, function($item) use ($sku){
                        return $sku == $item['sku'];
                    });
                    rsort($result_quote_id);
                    if (!empty($result_quote_id[0])) {
                        $quote_id[$order_id] = $result_quote_id[0]['quoteId'];
                    }
                }
            }

            foreach ($quote_id as $order_id_quote => $quote_id_value) {
                if ($quote_id_value) {
                    $this->model_orders->updateOrderById($order_id_quote, array('quote_id' => $quote_id_value));
                }
            }

            // Gravando o log do pedido
            $data_log = array(
                'int_to' 	=> $this->mkt,
                'order_id'	=> $order_id,
                'received'	=> json_encode($pedido)
            );
            $this->model_log_integration_order_marketplace->create($data_log);
        }



        return [true, $order_id];
    }

    private function formatResponsePayment($data, $orderId)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        
        $orders = $this->model_orders->getAllOrdersDatabyBill($this->mkt, $orderId);
        
        if (!$orders) {
            $this->log_data('api', 'PaymentOCC', 'Pedido não encontrado - '.json_encode($data));
            return false; // não encontrou pedido
        }
        $response = [];
        foreach($orders as $order){
            $marketplaceOrderId = $order['numero_marketplace'];
            if ($order['paid_status'] != 1 && $order['paid_status'] != 2) { // pedido já está pago ou com pagamento em processamento
                $this->log_data('api', 'PaymentOCC', 'Pedido já foi atualizado - '.json_encode($data));
                array_push($response, array(
                    "date"                  => $order['data_pago'],
                    "marketplaceOrderId"    => $orderId,
                    "orderId"               => $order['id'],
                    "receipt"               => null,
                ));
            }else{
                
                if($data['status'] == 'AUTHORIZED'){   
                    
                    $payment = $this->model_orders->getOrdersParcels($order['id']);
                    if($payment){
                        $arrPayment = array(
                            'order_id'          => $order['id'],
                            'parcela'           => $data['number_installments'],
                            'bill_no'           => $orderId,
                            'data_vencto'       => date('Y-m-d'),
                            'valor'             => $data['amount'],
                            'forma_id'          => $data['payment_type'],
                            'payment_id'        => $data['paymentId'],
                        );
        
                        $paymentSuccess = $this->model_orders->updateOrdersPayment($arrPayment, $payment[0]['id']);

                    }else{
                        $arrPayment = array(
                            'order_id'          => $order['id'],
                            'parcela'           => $data['number_installments'],
                            'bill_no'           => $orderId,
                            'data_vencto'       => date('Y-m-d'),
                            'valor'             => $data['amount'],
                            'forma_id'          => $data['payment_type'],
                            'payment_id'        => $data['paymentId'],
                        );
        
                        $paymentSuccess = $this->model_orders->insertParcels($arrPayment);

                    }

                    if($paymentSuccess){
                        $this->model_orders->updatePaidStatus($order['id'],3);
                    }else{
                        $this->log_data('api', 'PaymentOCC', 'Erro ao salvar pagamento - '.json_encode($data));
                    }
                    
                }else{
                    $this->log_data('api', 'PaymentOCC', 'Erro ao salvar pagamento - '.json_encode($data));
                }
                array_push($response, array(
                    "date"                  => date("Y-m-d H:i:s"),
                    "marketplaceOrderId"    => $marketplaceOrderId,
                    "orderId"               => $order['id'],
                    "receipt"               => null,
                ));
            }
            
        }   
        return $response;
    }

}