<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";
require APPPATH . "libraries/Marketplaces/Utilities/Order.php";
require APPPATH . "libraries/Marketplaces/Vtex.php";

/**
 * @property Model_orders $model_orders
 * @property Model_freights $model_freights
 * @property Model_integrations $model_integrations
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_providers $model_providers
 * @property Model_stores $model_stores
 * @property Model_payment $model_payment
 * @property Model_settings $model_settings
 * @property Model_clients $model_clients
 * @property Model_products $model_products
 * @property Model_vtex_ult_envio $model_vtex_ult_envio
 * @property Model_promotions $model_promotions
 * @property Model_order_payment_transactions $model_order_payment_transactions
 * @property Model_orders_payment $model_orders_payment
 *
 * @property OrdersMarketplace $ordersmarketplace
 * @property \Marketplaces\Utilities\Order $marketplace_order
 * @property \Marketplaces\Vtex $marketplace_vtex
 *
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property CI_Router $router
 */

class VtexOrders extends Main {
    var $int_to='';
    var $apikey='';
    var $site='';
    var $appToken='';
    var $accountName='';
    var $environment='';

    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_orders');
        $this->load->model('model_freights');
        $this->load->model('model_integrations');
        $this->load->model('model_frete_ocorrencias');
        $this->load->model('model_stores');
        $this->load->model('model_payment');
        $this->load->model('model_settings');
        $this->load->model('model_clients');
        $this->load->model('model_products');
        $this->load->model('model_vtex_ult_envio');
        $this->load->model('model_promotions');
        $this->load->model('model_order_payment_transactions');
        $this->load->model('model_orders_payment');

        $this->load->library('ordersMarketplace');
        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');
        $this->load->library("Marketplaces\\Vtex", array(), 'marketplace_vtex');
    }

    function setInt_to($int_to) {
        $this->int_to = $int_to;
    }
    function getInt_to() {
        return $this->int_to;
    }
    function setApikey($apikey) {
        $this->apikey = $apikey;
    }
    function getApikey() {
        return $this->apikey;
    }
    function setAppToken($appToken) {
        $this->appToken = $appToken;
    }
    function getAppToken() {
        return $this->appToken;
    }
    function setAccoutName($accountName) {
        $this->accountName = $accountName;
    }
    function getAccoutName() {
        return $this->accountName;
    }
    function setEnvironment($environment) {
        $this->environment = $environment;
    }
    function getEnvironment() {
        return $this->environment;
    }

    function run($id=null,$params=null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        /* faz o que o job precisa fazer */
        // verificar se tem integration com store_id=0 e $params se não for null. 
        if (!is_null($params) && ($params != 'null')) {
        	$integration = $this->model_integrations->getIntegrationsbyCompIntType(1,$params,"CONECTALA","DIRECT",0);
			if (!$integration) {
                $this->log_data('batch',$log_name,"Marketplace $params não encontrado!","E");
                $this->gravaFimJob();
				return;
			}
			echo " Buscando pedidos da ".$params."\n";
        }
		else {
			$params = null; 
		}

        $this->syncPayment($params);
        $this->syncProgressPayment($params);
        $this->syncListCanceled($params);
        $this->cancelOrdersIncomplete($params);

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    function runfull($id=null,$params=null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        /* faz o que o job precisa fazer */
        $this->syncOrdersInProgress();

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    function setkeys($int_to) {
        $this->setInt_to($int_to);

        //pega os dados da integração. Por enquanto só a conectala faz a integração direta
        $integration = $this->model_integrations->getIntegrationsbyCompIntType(1,$this->getInt_to(),"CONECTALA","DIRECT",0);
        $api_keys = json_decode($integration['auth_data'],true);
        $this->setApikey($api_keys['X_VTEX_API_AppKey'] ?? null);
        $this->setAppToken($api_keys['X_VTEX_API_AppToken'] ?? null);
        $this->setAccoutName($api_keys['accountName'] ?? null);
        $this->setEnvironment($api_keys['environment'] ?? null);
    }

    function syncOrdersInProgress() {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        $orders_in_progress = $this->model_orders->getFullOrdersInProgress();
        
        foreach ($orders_in_progress as $order_in_progress) {
            $integration = $this->model_integrations->getIntegrationByIntTo($order_in_progress['origin'], 0);
            if (!is_null($integration)) {
                if ($integration['name'] != 'VTEX') {
                    continue ;
                }
            }
            else {
                continue ;
            }
            
            $endPoint = '/api/oms/pvt/orders/'. $order_in_progress['numero_marketplace'];
            $this->process($order_in_progress['origin'], $endPoint);

            $order = json_decode($this->result, true);
            if ($order['status'] == 'canceled') {
                $this->markCancelled($order_in_progress['origin'], $order);
            } 
            else if ($order['status'] == 'payment-approved' || $order['status'] == "approve-payment") {
                $this->markApproved($order_in_progress['origin'], $order);
            }
        }
    }

    function syncListCanceled($int_to = null) {
        echo dateNow()->format(DATETIME_INTERNATIONAL) . ' ' . __FUNCTION__ . "\n";
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

		if (is_null($int_to)) {
			 $main_integrations = $this->model_integrations->getIntegrationsbyStoreId(0);

	        foreach ($main_integrations as $integrationName) {
	            //remover apos testes
	            /*if ($integrationName['name'] != 'VTEX') {
	                continue ;
	            }*/
	
	            echo 'Sync Canceled: '. $integrationName['int_to']. PHP_EOL;
	            $this->syncCanceledIntTo($integrationName['int_to']);
	        }
		}
		else {
			$this->syncCanceledIntTo($int_to);
		}

    }

    function syncOrders() {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $main_integrations = $this->model_integrations->getIntegrationsbyStoreId(0);

        foreach ($main_integrations as $integrationName) {
            echo 'Sync Orders: '. $integrationName['int_to']. PHP_EOL;
            $this->syncOrdersIntTo($integrationName['int_to'], 1, 30);
        }
    }

    function syncOrdersIntTo($int_to, $page, $per_page) {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $endPoint = '/api/oms/pvt/orders?page='.$page.'&per_page='.$per_page.'&orderBy=creationDate,desc';
        $this->process($int_to, $endPoint);

        $content = json_decode($this->result, true);

        $orders = $content['list'];
        $paging = $content['paging'];

        foreach ($orders as $order) {
            $this->importOrder($int_to, $order);
        }

        $pages = 100; //$paging['pages'];

        if ($page < $pages) {
            $this->syncOrdersIntTo($int_to, $page + 1, $per_page);
        }

        echo  'Amout '. count($orders);
    }

    function importOrder($int_to, $order) {
        $endPoint = '/api/oms/pvt/orders/'. $order['orderId'];
        $this->process($int_to, $endPoint);

        $content = json_decode($this->result, true);

        $this->createOrder($int_to, $content);

        //echo $this->result;
    }

    private function createOrder($int_to, $pedido)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->model_orders->getOrdersDatabyNumeroMarketplace($pedido['marketplaceOrderId']))
            return [false, "Pedido ( {$pedido['marketplaceOrderId']} ) já existente!"];

        // Verifico se todos os skus estão certos e são das mesmas empresas
        $company_id ='';
        $store_id = '';
        $erro = false;
        $cross_docking_default = 0;
        $cross_docking = $cross_docking_default;
        $cancelar_pedido = false;
        $totalOrder = 0;

        foreach($pedido['items'] as $item) {

            $sku        = $item['id'];
            $seller_id   = $item['seller'];
            echo $sku .  ' - ' .  $seller_id ;
            $sql = 
            "select p.* from company c ".
            "    join stores s on s.company_id = c.id ".
            "    join products p on p.store_id = s.id and p.company_id = c.id ".
            "where  ".
                "p.sku = ? and     ".
                "c.import_seller_id  = ? ";
            $query = $this->db->query($sql, array($sku, $seller_id));
            $prf = $query->row_array();
            if (empty($prf)) {
                echo "Produto ( {$sku} ) não encontrado!" . PHP_EOL;
                return [false, "Produto ( {$sku} ) não encontrado!"];
            }

            echo "Produto ( {$sku} ) encontrado!" . PHP_EOL;

            $company_id = $prf['company_id'];
			$store_id = $prf['store_id'];
            
            $crossdoking = $prf['prazo_operacional_extra'];
            if (is_null($crossdoking))
                $crossdoking = 0;

            // Tempo de crossdocking
            if ($crossdoking)  // pega o pior tempo de crossdocking dos produtos
                if ((int)$crossdoking + $cross_docking_default > $cross_docking)
                    $cross_docking = $cross_docking_default + (int)$crossdoking;

            $totalOrder += moneyVtexToFloat($item['price']) * $item['quantity'];
			
        }

        // Leio a Loja para pegar o service_charge_value
        $store = $this->model_stores->getStoresData($store_id);

        // pedido
        $orders = Array();

        //$orders['freight_seller'] = $store['freight_seller'];

        $paid_status = 1; // sempre chegará como não pago

        // gravo o novo pedido
        $clientProfileData  = $pedido['clientProfileData'];
        $shippingAddress    = $pedido['shippingData']['selectedAddresses'][0];
        $shippingLogistic   = $pedido['shippingData']['logisticsInfo'][0];
        $paymentData        = $pedido['paymentData'];

        // PRIMEIRO INSERE O CLIENTE
        $clients = array();
        $clients['customer_name']       = $clientProfileData['firstName'].' '.$clientProfileData['lastName'];
        $clients['phone_1']             = $clientProfileData['phone'] ?? '';
        $clients['phone_2']             = $clientProfileData['corporatePhone'] ?? '';
        $clients['email']               = $clientProfileData['email'] ?? '';
        $clients['cpf_cnpj']            = preg_replace("/[^0-9]/", "", $clientProfileData['document']);
        $clients['customer_address']    = $shippingAddress['street'] ?? '';
        $clients['addr_num']            = $shippingAddress['number'] ?? '';
        $clients['addr_compl']          = $shippingAddress['complement'] ?? '';
        $clients['addr_neigh']          = $shippingAddress['neighborhood'] ?? '';
        $clients['addr_city']           = $shippingAddress['city'] ?? '';
        $clients['addr_uf']             = $shippingAddress['state'] ?? '';
        $clients['country']             = $shippingAddress['country'] ?? '';
        $clients['zipcode']             = preg_replace("/[^0-9]/", "", $shippingAddress['postalCode']);
        $clients['origin']              = $int_to; // Entender melhor como encontrar esse info
        $clients['origin_id']           = 1; // Entender melhor como encontrar esse info

        // campos que não tem na VTEX
        $clients['ie'] = '';
        $clients['rg'] = '';

        $client_id = $this->model_clients->insert($clients);
        if (!$client_id)
            return [false, "Ocorreu um problema para gravar o cliente!"];

        $orders['customer_name']            = $shippingAddress['receiverName'];
        $orders['customer_address']         = $clients['customer_address'];
        $orders['customer_address_num']     = $clients['addr_num'];
        $orders['customer_address_compl']   = $clients['addr_compl'];
        $orders['customer_address_neigh']   = $clients['addr_neigh'];
        $orders['customer_address_city']    = $clients['addr_city'];
        $orders['customer_address_uf']      = $clients['addr_uf'];
        $orders['customer_address_zip']     = $clients['zipcode'];
        $orders['customer_reference']       = $shippingAddress['reference'] ?? '';

        $order_mkt                      = $pedido['orderId'];
        $orders['bill_no']              = $order_mkt;
        $orders['numero_marketplace']   = $order_mkt;
        $orders['date_time']            = date('Y-m-d H:i:s');
        $orders['customer_id']          = $client_id;
        $orders['customer_phone']       = $clients['phone_1'];

        $orders['total_order']          = $totalOrder;
        $orders['total_ship']           = moneyVtexToFloat($shippingLogistic['price']);
        $orders['gross_amount']         = moneyVtexToFloat($pedido['marketplacePaymentValue']);
        $orders['service_charge_rate']  = $store['service_charge_value'];
		$orders['service_charge_freight_value']  = $store['service_charge_freight_value'];
        $orders['service_charge']       = number_format(($orders['total_order'] * $store['service_charge_value'] / 100) + ($orders['total_ship'] * $store['service_charge_freight_value'] / 100), 2, '.', '');
        $orders['vat_charge_rate']      = 0; //pegar na tabela de empresa - Não está sendo usado.....
        $orders['vat_charge']           = number_format($orders['gross_amount'] * $orders['vat_charge_rate'] / 100, 2, '.', ''); //pegar na tabela de empresa - Não está sendo usado.....
        $orders['discount']             = 0; // não achei no pedido VTEX
        $orders['net_amount']           = number_format($orders['gross_amount'] - $orders['discount'] - $orders['service_charge'] - $orders['vat_charge'] - $orders['total_ship'], 2, '.', '');

        $orders['paid_status']  = $paid_status;
        $orders['company_id']   = $company_id;
        $orders['store_id']     = $store_id;
        $orders['origin']       = $int_to;
        $orders['user_id']      = 1;

        $orders['data_limite_cross_docking'] = null;

        $order_id = $this->model_orders->insertOrder($orders);
        if (!$order_id)
            return [false, "Não foi possível gravar o pedido ( {$order_mkt} )!"];

        // Itens
        $itensIds = array();

        foreach($pedido['items'] as $item) {

            $sku        = $item['id'];
            $seller_id   = $item['seller'];

            $sql = 
            "select p.* from company c ".
            "    join stores s on s.company_id = c.id ".
            "    join products p on p.store_id = s.id and p.company_id = c.id ".
            "where  ".
                "p.sku = ? and     ".
                "c.import_seller_id  = ? ";
            $query = $this->db->query($sql, array($sku, $seller_id));
            $prf    = $query->row_array();

            $company_id = $prf['company_id'];
            $prd = $this->model_products->getProductData(0,$prf['prd_id']);

            if (!$prd)
                return [false, "Produto ( {$sku} ) não encontrado!"];

            $items = array();
            $items['skumkt']        = $sku;
            $items['order_id']      = $order_id; // ID da order incluida
            $items['product_id']    = $prd['id'];
            $items['sku']           = $sku;

            $variant='';

            $items['variant']       = $variant;
            $items['name']          = $prd['name'];
            $items['qty']           = $item['quantity'];
            $items['rate']          = moneyVtexToFloat($item['price']);
            $items['amount']        = (float)$items['rate'] * (float)$item['quantity'];
            $items['discount']      = 0; // não encontrei no item
            $items['company_id']    = $prd['company_id'];
            $items['store_id']      = $prd['store_id'];
            $items['un']            = 'Un';
            $items['pesobruto']     = $prd['peso_bruto'];  // Não tem na SkyHub
            $items['largura']       = $prd['largura']; // Não tem na SkyHub
            $items['altura']        = $prd['altura']; // Não tem na SkyHub
            $items['profundidade']  = $prd['profundidade']; // Não tem na SkyHub
            $items['unmedida']      = 'cm'; // não tem na skyhub
            $items['kit_id']        = null;

            $item_id = $this->model_orders->insertItem($items);
            if (!$item_id)
                return [false, "Ocorreu um problema para gravar o item ( {$sku} )!"];

            $this->model_products->reduzEstoque($prd['id'], $items['qty'], $variant, $order_id);
            $this->model_vtex_ult_envio->reduzEstoque($prf['int_to'], $prd['id'], $items['qty']);
            $this->model_promotions->updatePromotionByStock($prd['id'], $items['qty'], moneyVtexToFloat($item['price']));
        }

        // verificação do frete -
        /*if ($store['freight_seller'] == 1) {
            $this->model_orders->setShipCompanyPreview($order_id,'Logística Própria','Logística Própria',7);
        }
        else {*/
            $transportadora = in_array($shippingLogistic['selectedSla'], array('PAC', 'SEDEX', 'MINI')) ? 'CORREIOS' : 'Transportadora';
            $servico        = $shippingLogistic['selectedSla'];
            $prazo          = filter_var($shippingLogistic['shippingEstimate'], FILTER_SANITIZE_NUMBER_INT);
            $estimateDate   = $shippingLogistic['shippingEstimateDate'];
            $this->model_orders->setShipCompanyPreview($order_id, $transportadora, $servico, $prazo, $estimateDate);
        //}

        return [true, $order_id];
    }

    function syncCanceledIntTo($int_to) {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $per_page = 100;
        $page = 1;
        $have_register=  true;

		echo "  Buscando pedidos cancelados em ".$int_to."\n";

        while ($have_register) {

            $endPoint = "/api/oms/pvt/orders?f_status=canceled&per_page={$per_page}&page={$page}";
            $this->process($int_to, $endPoint);

            if ($this->responseCode != 200) {
                $have_register = false;
                continue;
            }

            $orders = json_decode($this->result, true);

            foreach ($orders['list'] as $order) $this->markCancelled($int_to, $order);

            $page++;
        }
    }

    private function markCancelled($int_to, $order)
	{
		echo '[CANCELLED]['.$this->getInt_to().'] Order: '. $order['orderId'] . "... ";
		if ($order_exist = $this->model_orders->getOrdersDatabyBill($int_to, $order['orderId'])) {
			echo "Ordem Já existe :".$order_exist['id']."  paid_status=".$order_exist['paid_status']."... ";
            $this->ordersmarketplace->cancelOrder($order_exist['id'], false);
		}
		else {
			echo 'Order not found... ';
		}
		echo PHP_EOL;
    }
    
    private function markApproved($int_to, $order) {
        echo PHP_EOL . '[APPROVED]['.$this->getInt_to().'] Order: '. $order['orderId'] . "... ";

		if ($order_exist = $this->model_orders->getOrdersDatabyBill($int_to, $order['orderId'])) {
            $data_pago = (new DateTime($order["lastChange"]))->format("Y-m-d h:m:s.z");

            try {
                $this->marketplace_order->updateToPaid($order_exist['id'], $data_pago);
            } catch (Exception $exception) {
                $this->log_data('batch', __CLASS__.'/'.__FUNCTION__, "Erro para pagar o pedido {$order['id']}.\n{$exception->getMessage()}", 'E');
            }
        }
        else {
			echo 'Order not found... ';
		}
    }

    private function notifyPriceAndStockChange($product_id, $int_to)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        echo "Buscando produtos com estoque alterado para notificar.\n";
        $products = $this->model_products->getProductsToIntegrationById($product_id, $int_to);
        
        if (!$products) {
            $notice = "Não foi encontrado nenhum produto com estoque alterado.";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"I");
            return false;
        }

        echo "Encontramos ".count($products)." produto(s) com alteração de estoque para notificar.\n";
        foreach ($products as $key => $product) {
            $data = [];

            $sellerId =  $product['seller_id']; 
            if (strlen ($sellerId) < 3) {
                $sellerId = substr('000'.$sellerId,-3);
            }

            $bodyParams = json_encode($data);
            $endPoint = 'api/catalog_system/pvt/skuSeller/changenotification/'.$sellerId.'/'.$product['ref_id'];
            
            echo "Verificando se o ".($key+1)."º produto existe no marketplace ".$product['int_to']." para o seller ".$product['seller_id'].".\n";
            $this->process($product['int_to'], $endPoint, 'POST', $bodyParams);

            if ($this->responseCode != 404) {
                $notice = "Notificação de alteração de estoque concluída para o ".($key+1)."º produto.";
                echo $notice."\n";
                $this->log_data('batch', $log_name, $notice,"I");
                $this->model_products->updateProductIntegrationStatus($product['prdIntegration_id'], 2);
                continue;
            }

            $notice = "O ".($key+1)."º produto não está cadastrado no marketplace ".$product['int_to']." para o seller ".$product['seller_id'].".";
            echo $notice."\n";
            $this->log_data('batch', $log_name, $notice,"E");
            $this->model_products->updateProductIntegrationStatus($product['prdIntegration_id'], 90);
        }
    }

    private function syncPayment($int_to = null)
    {
        echo dateNow()->format(DATETIME_INTERNATIONAL) . ' ' . __FUNCTION__ . "\n";
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        $limit = 200;
        $last_id = 0;
        $search_payment_vtex_in_get_order = $this->model_settings->getValueIfAtiveByName('search_payment_vtex_in_get_order');
        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');

        while (true) {
            if ($external_marketplace_integration) {
                $orders = $this->model_orders->getOrdersWithoutPaymentAndNotCancelledAndComplete($int_to, $limit, $last_id);
            } else {
                $orders = $this->model_orders->getOrdersWithoutPayment($int_to, $limit, $last_id);
            }


            if (count($orders) == 0) {
                break;
            }

            echo count($orders) . " pedidos aguardando pagamento\n";

            foreach ($orders as $order) {
                $last_id = $order['id'];

                if ($search_payment_vtex_in_get_order || in_array($order['paid_status'], [1, 96])) {
                    $endPoint = '/api/oms/pvt/orders/' . $order['numero_marketplace'];
                } else {
                    $endPoint = '/api/oms/pvt/orders/' . $order['numero_marketplace'] . '/payment-transaction';
                }
                $this->process($order['origin'], $endPoint);

                if ($this->responseCode != 200) {
                    // Não está disponível o pagamento no endpoint payment-transaction.
                    if (!$search_payment_vtex_in_get_order || likeText('%payment-transaction%', $endPoint) && $this->responseCode == 404) {
                        $endPoint = '/api/oms/pvt/orders/' . $order['numero_marketplace'];
                        $this->process($order['origin'], $endPoint);
                    }

                    if ($this->responseCode != 200) {
                        echo "Status=$this->responseCode, para localizar o pagamento do pedido: $order[id]\n";
                        $this->log_data('batch', $log_name, 'Status=' . $this->responseCode . ', para localizar o pagamento do pedido: ' . $order['id'], "W");
                        continue;
                    }
                }

                $orderVtex = json_decode($this->result, true);

                $ordersPayment = $orderVtex['payments'] ?? array();

                try {
                    $this->marketplace_vtex->setCredentials($order['origin']);
                    $arrPayment = array();
                    // é pedido cancelado ou não pago.
                    if (isset($orderVtex['paymentData']['transactions'])) {
                        foreach ($orderVtex['paymentData']['transactions'] as $transaction) {
                            foreach ($transaction['payments'] as $payments) {
                                $orderVtexAux = array('status' => $orderVtex['status'], 'transactionId' => $transaction['transactionId']);
                                $arrPayment[] = $this->formatDataToPayment($order, $payments, $orderVtexAux);
                            }
                        }
                    }

                    foreach ($ordersPayment as $payment) {
                        $arrPayment[] = $this->formatDataToPayment($order, $payment, $orderVtex);
                    }

                    if (!empty($arrPayment)) {
                        $this->insertPaymentParcel($order['id'], $arrPayment);
                        echo "Pedido $order[id] teve o pagamento salvo.\n";
                    }
                } catch (Exception $exception) {
                    echo $exception->getMessage() . "\n";
                }
            }
        }
    }

    /**
     * @param $order
     * @param $payment
     * @param $orderVtex
     * @return array
     * @throws Exception
     */
    public function formatDataToPayment($order, $payment, $orderVtex): array
    {
        if (empty($payment['group']) || empty($payment['paymentSystemName'])) {
            throw new Exception("Pagamento do pedido $order[id] ainda não está completo no marketplace.");
        }

        $promissory_payment_method_id = $this->model_settings->getValueIfAtiveByName('promissory_payment_method_id');
        if ($promissory_payment_method_id) {
            $promissory_payment_method_id = array_map('intval', explode(',', $promissory_payment_method_id));
            $payment_system = (int)$payment['paymentSystem'];
            if (in_array($payment_system, $promissory_payment_method_id)) {
                $description_note = $this->marketplace_vtex->getPromissoryPaymentMethod($order['numero_marketplace']);

                return array(
                    'order_id'              => $order['id'],
                    'parcela'               => trim($description_note['installments']),
                    'bill_no'               => $order['numero_marketplace'],
                    'data_vencto'           => $description_note['transactionDateTime'],
                    'valor'                 => moneyVtexToFloat(trim($description_note['totalAmount'])),
                    'forma_id'              => trim($description_note['paymentType']),
                    'forma_desc'            => trim($description_note['institutionName']),
                    'payment_method_id'     => trim($description_note['conditionCode']),
                    'gift_card_provider'    => null,
                    'gift_card_id'          => null,
                    "payment_id"            => trim($description_note['paymentId']),
                    "status_payment"        => $orderVtex['status'],
                    "transaction_id"        => $orderVtex['transactionId'],
                    "first_digits"          => trim($description_note['cardBin']),
                    "last_digits"           => trim($description_note['cardLastDigits']),
                    "payment_transaction_id"=> trim($description_note['transactionId']),
                    "autorization_id"       => trim($description_note['transactionDoc']),
                    'nsu'                   => trim($description_note['hostNSU']),
                    "gateway_tid"           => trim($description_note['hostNSU']),
                );
            }
        }

        $authId      = $payment['connectorResponses']['authId'] ?? '';
        $nsu         = $payment['connectorResponses']['nsu'] ?? null;
        $gateway_tid = $payment['connectorResponses']['Tid'] ?? $payment['connectorResponses']['tid'] ?? null;
        return array(
            'order_id'              => $order['id'],
            'parcela'               => $payment['installments'],
            'bill_no'               => $order['numero_marketplace'],
            'data_vencto'           => $payment['dueDate'] ?? date('Y-m-d H:i:s'),
            'valor'                 => moneyVtexToFloat($payment['value']),
            'forma_id'              => $payment['group'],
            'forma_desc'            => $payment['paymentSystemName'],
            'payment_method_id'     => $payment['paymentSystem'],
            'gift_card_provider'    => $payment['giftCardProvider'] ?? null,
            'gift_card_id'          => $payment['giftCardId'] ?? null,
            "payment_id"            => $payment['id'], // payments->id
            "status_payment"        => $orderVtex['status'], // Transaction Status
            "transaction_id"        => $orderVtex['transactionId'], // Transaction Id
            "first_digits"          => $payment['firstDigits'],
            "last_digits"           => $payment['lastDigits'],
            "payment_transaction_id"=> $payment['tid'], // Payment Transaction Id
            "autorization_id"       => $authId,  // Connector Authorization Id
            'nsu'                   => $nsu,
            "gateway_tid"           => $gateway_tid
        );
    }

    /**
     * @param $order_id
     * @param $arrPayment
     * @return void
     * @throws Exception
     */
    private function insertPaymentParcel($order_id, $arrPayment)
    {
        $this->marketplace_order->createPayment($order_id, $arrPayment);
    }

    private function syncProgressPayment($int_to=null)
    {
        echo dateNow()->format(DATETIME_INTERNATIONAL) . ' ' . __FUNCTION__ . "\n";
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $orders = $this->model_orders->getOrdersProgressPayment($int_to);
		
		if (count($orders)==0) {
			echo "  Nenhum pedido com pagamento em progresso \n";
		}

        foreach ($orders as $order) {

            $endPoint = '/api/oms/pvt/orders/' . $order['numero_marketplace'];
            $this->process($order['origin'], $endPoint);

            $orderVtex = json_decode($this->result, true);

            if ($this->responseCode != 200) {
                $this->log_data('batch', $log_name, "Pedido {$order['id']} não encontrado.\nReturn={$this->result}\nStatus={$this->responseCode}","W");
                continue;
            }

            if (
                !empty($orderVtex['status']) &&
                array_key_exists('isCompleted', $orderVtex) &&
                $orderVtex['isCompleted'] &&
                $order['is_incomplete']
            ) {
                $updateOrder = array('is_incomplete' => false);
                $this->model_orders->updateByOrigin($order['id'], $updateOrder);
            }

            if ($orderVtex['status'] == "payment-pending") {
                echo "Pedido {$order['id']} ainda não foi pago.\n";
                continue;
            }

            if (in_array($orderVtex['status'], array("payment-approved", "approve-payment", "waiting-for-seller-decision"))) {
                echo "Pedido {$order['id']} está com o pagamento aprovado, vou mudar o status para 3 e definir as data de pagamento e expedição.\n";
                $data_pago = date("Y-m-d H:i:s");

                $ship_companyName_preview = '';
                foreach ($orderVtex['shippingData']['logisticsInfo'][0]['slas'] as $logisticsInfo) {
                    if ($orderVtex['shippingData']['logisticsInfo'][0]['selectedSla'] == $logisticsInfo['id']) {
                        $ship_companyName_preview = $logisticsInfo['name'];
                    }
                }

                $ship_estimate_date = null;
                foreach ($orderVtex['shippingData']['logisticsInfo'] as $logisticInfo) {
                    if (is_null($ship_estimate_date) || onlyNumbers($logisticInfo['shippingEstimate']) > $ship_estimate_date) {
                        $ship_estimate_date = onlyNumbers($logisticInfo['shippingEstimate']);
                    }
                }

                try {
                    $this->marketplace_order->updateToPaid($order['id'], $data_pago, $ship_companyName_preview, $ship_estimate_date);
                    // Pedido foi cancelado e precisamos notificar a vtex para concluir o cancelamento.
                    if ($orderVtex['status'] == 'waiting-for-seller-decision') {
                        $data = array(
                            'order_id'      => $order['id'],
                            'reason'        => $orderVtex['cancelReason'] ?: '',
                            'date_update'   => dateFormat($orderVtex['cancellationData']['CancellationDate'] ?? '', DATETIME_INTERNATIONAL) ?? dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL),
                            'status'        => 1,
                            'penalty_to'    => $this->ordersmarketplace->getCancelReasonWithoutCommission(),
                            'user_id'       => 1,
                            'store_id'      => $order['store_id']
                        );
                        $this->model_orders->insertPedidosCancelados($data);
                        $this->model_orders->updatePaidStatus($order['id'], 99);
                    }
                } catch (Exception $exception) {
                    $this->log_data('batch', $log_name, "Erro para pagar o pedido {$order['id']}.\n{$exception->getMessage()}", 'E');
                }
            }
        }

    }

    private function syncPaymentInteractions(string $int_to = null)
    {
        $payments = $this->model_orders->getOrdersInProgressInteraction($int_to, 1);

        if (count($payments)==0) {
            echo "Nenhum pedido em andamento para baixar as transações\n";
            return;
        }
        echo count($payments)." pedido em andamento para baixar as transações\n";

        foreach ($payments as $payment) {
            if ($this->int_to != $payment['origin']) {
                $this->setkeys($payment['origin']);
            }

            $endPoint = "https://$this->accountName.vtexpayments.com.br/api/pvt/transactions/{$payment['transaction_id']}/interactions";
            $this->process($payment['origin'], $endPoint);

            $interactionsPayment = json_decode($this->result);

            // Transação não encontrada.
            if ($this->responseCode != 200) {
                echo '[interactions]' . $this->result . "\n";
                continue;
            }

            if (empty($interactionsPayment)) {
                continue;
            }

            $interactionsPayment = array_reverse($interactionsPayment);

            foreach ($interactionsPayment as $interaction) {
                $interactionDate    = dateFormat(dateFormat($interaction->Date, DATETIME_INTERNATIONAL, null).'-00:00', DATETIME_INTERNATIONAL);
                $interactionId      = $interaction->Id;
                $status             = $interaction->Status;
                $description        = $interaction->Message;

                // Código do pagamento não é desse registro.
                // Isso pode acontecer quando um pedido tem mais que um pagamento.
                if ($interaction->PaymentId != $payment['payment_id']) {
                    //echo "[PROCESS] $interactionId com o PaymentId diferente do pagamento {$payment['payment_id']}.\n";
                    continue;
                }

                // Verificar se esse registro já existe no banco.
                if (
                    $this->model_order_payment_transactions->getTransaction(
                        array(
                            'order_id'       => $payment['order_id'],
                            'payment_id'     => $payment['id'],
                            'interaction_id' => $interactionId
                        )
                    )
                ) {
                    //echo "[PROCESS] $interactionId já existe no pedido {$payment['order_id']} e pagamento {$payment['id']}.\n";
                    continue;
                }

                // Registro não existe, cria-lo no banco.
                $this->model_order_payment_transactions->create(
                    array(
                        'order_id'          => $payment['order_id'],
                        'payment_id'        => $payment['id'],
                        'status'            => $status,
                        'description'       => $description,
                        'interaction_id'    => $interactionId,
                        'interaction_date'  => $interactionDate
                    )
                );

                echo "[SUCCESS] $interactionId criado para o pedido {$payment['order_id']} e pagamento {$payment['id']}.\n";
            }

            // https://help.vtex.com/pt/tutorial/fluxo-da-transacao-no-pagamentos--Er2oWmqPIWWyeIy4IoEoQ
            $endPoint = "https://$this->accountName.vtexpayments.com.br/api/pvt/transactions/{$payment['transaction_id']}/payments/{$payment['payment_id']}";
            $this->process($payment['origin'], $endPoint);

            $paymentTransaction = json_decode($this->result);

            if ($this->responseCode != 200) {
                echo '[payments]' . $this->result . "\n";
                continue;
            }

            //if (in_array($paymentTransaction->status, array('Authorizing', 'Authorized', 'Analyzing Risk', 'Risk Approved', 'Approved'))) {
            if (!$this->model_orders_payment->getOrderPaymentByIdAndTransactionStatus($payment['id'], $paymentTransaction->status)) {
                $this->model_orders_payment->update(array('transaction_status' => $paymentTransaction->status), $payment['id']);
            }
            //}
        }
    }

    private function syncPaymentSettlements(string $int_to = null)
    {
        $payments = $this->model_orders->getOrdersWithoutCaptureDate($int_to);

        if (count($payments)==0) {
            echo "Nenhum pedido em andamento para baixar as transações\n";
            return;
        }

        foreach ($payments as $payment) {

            if ($this->int_to != $payment['origin']) {
                $this->setkeys($payment['origin']);
            }

            $endPoint = "https://$this->accountName.vtexpayments.com.br/api/pvt/transactions/{$payment['transaction_id']}/settlements";
            $this->process($payment['origin'], $endPoint);

            $interactionsSettlements = json_decode($this->result);

            // Transação não encontrada.
            if ($this->responseCode != 200) {
                echo '[settlements]' . $this->result . "\n";
                continue;
            }

            if (empty($interactionsSettlements)) {
                continue;
            }

            foreach ($interactionsSettlements->actions as $interaction) {

                $interactionDate = dateFormat(dateFormat($interaction->date, DATETIME_INTERNATIONAL, null).'-00:00', DATETIME_INTERNATIONAL);
                $type = $interaction->type;

                if ($type == 'upon-request' && $interactionDate){

                    $this->model_orders_payment->update([
                            'capture_date' => $interactionDate
                        ],
                        $payment['id']
                    );

                    echo "[SUCCESS] Order Payment ID: {$payment['id']} atualizado data de captura para data $interactionDate.\n";

                }

            }

        }
    }

    private function cancelOrdersIncomplete(string $int_to = null): void
    {
        echo dateNow()->format(DATETIME_INTERNATIONAL) . ' ' . __FUNCTION__ . "\n";
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $filter = array(
            'paid_status' => $this->model_orders->PAID_STATUS['awaiting_payment'],
            'date_time <' => subtractHoursToDatetimeV2(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL), 2400) // ATD-621886
        );

        if (!is_null($int_to)) {
            $filter['origin'] = $int_to;
        }

        $orders = $this->model_orders->getOrderByFilter(array('where' => $filter));

        if (count($orders)==0) {
            echo "Nenhum pedido para cancelOrdersIncomplete\n";
            return;
        }

        echo count($orders) . " pedidos para cancelOrdersIncomplete\n";

        foreach ($orders as $order) {
            $this->process($order['origin'], '/api/oms/pvt/orders/' . $order['numero_marketplace']);

            if ($this->responseCode != 200) {
                continue;
            }

            $orderVtex = json_decode($this->result, true);

            if (array_key_exists('isCompleted', $orderVtex) && !$orderVtex['isCompleted']) {
                $this->ordersmarketplace->cancelOrder($order['id'], false, true, true);
                echo "Pedido $order[id] incompleto \n";
                $this->log_data('batch', $log_name, "Pedido {$order['id']} cancelado.\nOrderVtex=".json_encode($orderVtex, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    public function updateSalesChannel($id = null, $params = null)
    {

        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        $ordersWithoutSalesChannel = $this->model_orders->findOrdersWithoutSalesChannel();

        if ($ordersWithoutSalesChannel){

            foreach ($ordersWithoutSalesChannel as $order){

                $this->setkeys($order['origin']);

                $endPoint = '/api/oms/pvt/orders/' . $order['numero_marketplace'];
                $this->process($order['origin'], $endPoint);

                $orderVtex = json_decode($this->result, true);

                if (isset($orderVtex['salesChannel']) && $orderVtex['salesChannel']){
                    $this->model_orders->updateByOrigin($order['id'], ['sales_channel' => $orderVtex['salesChannel']]);
                }

            }
        }

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();

    }

}