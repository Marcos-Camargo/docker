<?php

require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Main.php";
require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Utils/Product.php";

class SyncOrders extends Main {

    const REGISTERS = 100;

    var $total_orders = 0;
    var $int_to='';
    var $apikey='';
    var $site='';
    var $appToken='';
    var $accountName='';
    var $environment='';
    var $product_util;
    var $amount = 0;

    var $dates_diff = array(
        // '[2021-03-01T02:00:00.000Z+TO+2021-04-01T01:59:59.999Z]',
        // '[2021-02-01T02:00:00.000Z+TO+2021-03-01T01:59:59.999Z]',
        // '[2021-01-01T02:00:00.000Z+TO+2021-02-01T01:59:59.999Z]',
        // '[2020-12-01T02:00:00.000Z+TO+2021-01-01T01:59:59.999Z]',
        // '[2020-11-01T02:00:00.000Z+TO+2020-12-01T01:59:59.999Z]',
        // '[2020-10-01T02:00:00.000Z+TO+2020-11-01T01:59:59.999Z]',
        '[2020-09-01T02:00:00.000Z+TO+2020-10-01T01:59:59.999Z]',
        '[2020-08-01T02:00:00.000Z+TO+2020-09-01T01:59:59.999Z]',
        '[2020-07-01T02:00:00.000Z+TO+2020-08-01T01:59:59.999Z]',
        '[2020-06-01T02:00:00.000Z+TO+2020-07-01T01:59:59.999Z]',
        '[2020-05-01T02:00:00.000Z+TO+2020-06-01T01:59:59.999Z]',
        '[2020-04-01T02:00:00.000Z+TO+2020-05-01T01:59:59.999Z]',
        '[2020-03-01T02:00:00.000Z+TO+2020-04-01T01:59:59.999Z]',
        '[2020-02-01T02:00:00.000Z+TO+2020-03-01T01:59:59.999Z]',
        '[2020-01-01T02:00:00.000Z+TO+2020-02-01T01:59:59.999Z]',
        // '[2020-01-01T02:00:00.000Z+TO+2021-04-01T01:59:59.999Z]',
    );

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
        $this->load->model('model_providers');
        $this->load->model('model_stores');
        $this->load->model('model_payment');
        $this->load->model('model_clients');
        $this->load->model('model_products');
        $this->load->model('model_settings');
        $this->load->library('ordersMarketplace');
        
        $this->product_util = new Product();
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

    function run($id=null,$int_to=null,$seller = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__, $int_to . " " . $seller)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$int_to." ".$seller),"I");

        /* faz o que o job precisa fazer */
        if (!is_null($int_to)) {
            if (!is_null($seller)) {
                $retorno = $this->initIntegration($int_to, $seller);
            }
            else {
                echo "Informe o seller do marketplace para puxar o catálogo e seus produtos\n";
            }
		}
		else {
			echo "Informe o int_to do marketplace para puxar o catálogo e seus produtos\n";
		}

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

    private function initIntegration($int_to, $sellerId) {
		$this->integrationData = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
		$separateIntegrationData = json_decode($this->integrationData['auth_data']);

		$sellers = $this->listSellerToIntegrate();

        $page = 1;
        $per_page = self::REGISTERS;

		foreach ($sellers as $seller) {
            if (($sellerId == $seller['import_seller_id']) || ($sellerId == "ALL")) {
                echo "Inicio integração: ". $seller['import_seller_id'];
                foreach ($this->dates_diff as $date_diff) {
                    $this->syncSellerOrders($separateIntegrationData, $int_to, $seller, $date_diff, $page, $per_page);
                }
                echo "Fim integração: ". $seller['import_seller_id'];
            }
		}
	}

    private function listSellerToIntegrate() {
		return $this->model_stores->getStoreToIntegrateVtex(2);
	}

    function syncSellerOrders($separateIntegrationData, $int_to, $seller, $date_diff, $page, $per_page) {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
        $per_page = self::REGISTERS;

        $sellerName = $seller['name'];

		$integrationData         = $this->model_integrations->getIntegrationbyStoreIdAndInto(0,$int_to);
		$separateIntegrationData = json_decode($integrationData['auth_data']);
        // echo 'Page: '.$page.' Quantidade por pagina: '.$per_page;
        $endPoint = 'api/oms/pvt/orders?f_sellerNames='. urlencode($sellerName) . '&f_creationDate=creationDate:'.$date_diff .'&page='.$page.'&per_page='.$per_page;
        $this->processNew($separateIntegrationData, $endPoint);
        if ($this->responseCode == 429) {
            sleep(60);
            $this->processNew($separateIntegrationData, $endPoint);
        }
        if ($this->responseCode !== 200) {
            $erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
            echo $erro."\n";
            $this->log_data('batch',$log_name, $erro ,"E");
            return;
        }
        $response = json_decode($this->result, true);
        $orders = $response['list'];
        $paging = $response['paging'];

        $this->total_orders = $this->total_orders + intval($paging['total']);
        
        $this->amount = $this->amount +1;

        $record = $this->model_orders->getAmountOrders($seller["store_id"]);
        echo $this->amount . " - ";
        // if ($record['amount'] != $paging['total']) {
            echo $this->total_orders ." - ". $sellerName . " - Interval: ". $date_diff . " - Pages: ". $paging['pages'] ." Total: ". $paging['total'] . " - Importados: ". $record['amount'];
        // }
        echo PHP_EOL;

        // return ;

        $count = 0;
        foreach ($orders as $order) {
            echo ++$count . " - " . $sellerName . " - " . $order['orderId'] ;
            $order_data = $this->model_orders->getOrdersDatabyNumeroMarketplace($order['orderId']);

            if (is_null($order_data)) {
                $this->importOrder($separateIntegrationData, $int_to, $order);
            }
            else {
                echo " - Já importada.";
            }
            echo PHP_EOL;
        }

        if ($page < $paging['pages']) {
            $next = $page+1;

            echo PHP_EOL . 'Pagina: '.$next . ' - '. $paging['pages'] . PHP_EOL;
            
            $this->syncSellerOrders($separateIntegrationData, $int_to, $seller, $date_diff, $next, $per_page);
        }
    }

    function importOrder($separateIntegrationData, $int_to, $order) {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $endPoint = '/api/oms/pvt/orders/'. $order['orderId'];
        $this->processNew($separateIntegrationData, $endPoint);
        if ($this->responseCode == 429) {
            sleep(60);
            $this->processNew($separateIntegrationData, $endPoint);
        }
        if ($this->responseCode !== 200) {
            $erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
            echo $erro."\n";
            $this->log_data('batch',$log_name, $erro ,"E");
            return;
        }

        $detail = json_decode($this->result, true);

        $result = $this->createOrder($separateIntegrationData, $int_to, $order, $detail);

        if ($result[0] === true) {
            $order_id = $result[1];
            if ($order['status'] == 'canceled') {
                $this->model_orders->updatePaidStatus($order_id, 96);
                return  ;
            }

            if (!is_null($order['authorizedDate'])) {
                $this->model_orders->updatePaidStatus($order_id, 5);
                $this->model_orders->updateDataPagoWithCrossDocking($order_id, $order['authorizedDate'], $order['authorizedDate']);
            }

            if ($order['status'] == 'invoiced') {
                $this->model_orders->updatePaidStatus($order_id, 5);
                
                if (array_key_exists('packageAttachment', $detail)) {
                    $packageAttachment = $detail['packageAttachment'];
                    if (count($packageAttachment['packages']) > 0) {
                        $package = $packageAttachment['packages'][0];
                        if (array_key_exists('issuanceDate', $package)) {
                            $date_issuance = $package['issuanceDate'];
                            $this->model_orders->updateDataEnvio($order_id, $date_issuance);
                        }

                        if (array_key_exists('courierStatus', $package)) {
                            if (!is_null($package['courierStatus'])) {
                                if (array_key_exists('deliveredDate', $package['courierStatus'])) {
                                    if (!is_null($package['courierStatus']['finished'])) {
                                        if ($package['courierStatus']['finished'] === true) {
                                            $this->model_orders->updatePaidStatus($order_id, 6);
                                            $date_delivered = $package['courierStatus']['deliveredDate'];
                                            $this->model_orders->updateDataEntrega($order_id, $date_delivered);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
            }
        }
    }

    private function createOrder($separateIntegrationData, $int_to, $order, $detail)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        
        $order_data = $this->model_orders->getOrdersDatabyNumeroMarketplace($detail['orderId']);
        if ($order_data)
            return [true, $order_data['id']];

        // Verifico se todos os skus estão certos e são das mesmas empresas
        $company_id ='';
        $store_id = '';
        $erro = false;
        $cross_docking_default = 0;
        $cross_docking = $cross_docking_default;
        $cancelar_pedido = false;
        $totalOrder = 0;

        if (array_key_exists('sellers', $detail)) {
            if (count($detail['sellers']) == 1) {
                $key = $detail['sellers'][0]['id'];
                $store_record = $this->model_stores->getStoreBySellerId($key);
                $store_id = $store_record['id'];
                $company_id = $store_record['company_id'];
            }
        }

        foreach($detail['items'] as $item) {

            $sku        = $item['id'];
            $seller_id   = $item['seller'];
            echo $sku .  ' - ' .  $seller_id ;
            $sql = 
            "select p.* from company c ".
            "    join stores s on s.company_id = c.id ".
            "    join products p on p.store_id = s.id and p.company_id = c.id ".
            "    left join prd_variants pv on pv.prd_id = p.id ".
            "where  ".
                "(p.sku = ? or pv.sku = ?) and    ".
                "c.import_seller_id  = ? ";
            $query = $this->db->query($sql, array($sku, $sku, $seller_id));
            $prf = $query->row_array();
            if (empty($prf)) {
                echo "Produto ( {$sku} ) não encontrado!" . PHP_EOL;

                $stockKeepingUnit = $this->getProductVtex($separateIntegrationData, $sku);

                if ($stockKeepingUnit !== false) {
                    $stores = $this->model_stores->getStoreBySellerId($seller_id);
                    $this->product_util->createWithStockKeepingUnit($separateIntegrationData, $int_to, $stockKeepingUnit, $stores);
                }

                return [false, "Produto ( {$sku} ) não encontrado!"];
            }

            echo "Produto ( {$sku} ) encontrado!" . PHP_EOL;

            
            if ($store_id != '') {
                $company_id = $prf['company_id'];
                $store_id = $prf['store_id'];
            }
            
            $crossdoking = $prf['prazo_operacional_extra'];
            if (is_null($crossdoking))
                $crossdoking = 0;

            // Tempo de crossdocking
            if ($crossdoking)  // pega o pior tempo de crossdocking dos produtos
                if ((int)$crossdoking + $cross_docking_default > $cross_docking)
                    $cross_docking = $cross_docking_default + (int)$crossdoking;

            $totalOrder += (float)substr_replace($item['price'], '.', -2, 0) * $item['quantity'];
			
        }

        // Leio a Loja para pegar o service_charge_value
        $store = $this->model_stores->getStoresData($store_id);

        // pedido
        $orders = Array();

        //$orders['freight_seller'] = $store['freight_seller'];

        $paid_status = 1; // sempre chegará como não pago

        // gravo o novo pedido
        $clientProfileData  = $detail['clientProfileData'];
        $shippingAddress    = $detail['shippingData']['selectedAddresses'][0];
        $shippingLogistic   = $detail['shippingData']['logisticsInfo'][0];
        $paymentData        = $detail['paymentData'];

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

        $ship_company_preview = '';
        if (array_key_exists('packageAttachment', $detail)) {
            $packageAttachment = $detail['packageAttachment'];
            foreach($packageAttachment['packages'] as $package) {
                if (array_key_exists('courier', $package)) {
                    $ship_company_preview = $package['courier'];
                }
            }
        }

        $orders['customer_name']            = $shippingAddress['receiverName'];
        $orders['customer_address']         = $clients['customer_address'];
        $orders['customer_address_num']     = $clients['addr_num'];
        $orders['customer_address_compl']   = $clients['addr_compl'];
        $orders['customer_address_neigh']   = $clients['addr_neigh'];
        $orders['customer_address_city']    = $clients['addr_city'];
        $orders['customer_address_uf']      = $clients['addr_uf'];
        $orders['customer_address_zip']     = $clients['zipcode'];
        $orders['customer_reference']       = $shippingAddress['reference'] ?? '';

        $order_mkt                      = $detail['orderId'];
        $orders['bill_no']              = $order_mkt;
        $orders['numero_marketplace']   = $order_mkt;
        $orders['date_time']            = date('Y-m-d H:i:s', strtotime($order['creationDate']));
        $orders['customer_id']          = $client_id;
        $orders['customer_phone']       = $clients['phone_1'];
        $orders['sales_channel']        = $order['salesChannel'];
        $orders['total_order']          = $totalOrder;
        $orders['total_ship']           = substr_replace($shippingLogistic['price'], '.', -2, 0);
        $orders['ship_company_preview'] = $ship_company_preview;
        $orders['ship_service_preview'] = $shippingLogistic['selectedSla'];
        $orders['gross_amount']         = substr_replace($detail['value'], '.', -2, 0);
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
        $orders['imported']     = true;

        $orders['data_limite_cross_docking'] = null;

        $order_id = $this->model_orders->insertOrder($orders);
        if (!$order_id)
            return [false, "Não foi possível gravar o pedido ( {$order_mkt} )!"];

        // Itens
        $itensIds = array();

        foreach($detail['items'] as $item) {

            $sku        = $item['id'];
            $seller_id   = $item['seller'];

            $sql = 
            "select p.* from company c ".
            "    join stores s on s.company_id = c.id ".
            "    join products p on p.store_id = s.id and p.company_id = c.id ".
            "    left join prd_variants pv on pv.prd_id = p.id ".
            "where  ".
                "(p.sku = ? or pv.sku = ?) and    ".
                "c.import_seller_id  = ? ";
            $query = $this->db->query($sql, array($sku, $sku, $seller_id));

            $prf    = $query->row_array();

            $company_id = $prf['company_id'];
            $prd = $prf;

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
            $items['rate']          = substr_replace($item['price'], '.', -2, 0);
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
            if (!$item_id) {
                return [false, "Ocorreu um problema para gravar o item ( {$sku} )!"];
            }
            else {
                array_push($itensIds, $item_id);
            }

            // $this->model_products->reduzEstoque($prd['id'], $items['qty'], $variant, $order_id);
            // $this->model_vtex_ult_envio->reduzEstoque($prf['int_to'], $prd['id'], $items['qty']);
            // $this->model_promotions->updatePromotionByStock($prd['id'], $items['qty'], substr_replace($item['price'], '.', -2, 0));
        }

        if (array_key_exists('paymentData', $detail)) {
            $paymentData = $detail['paymentData'];
            if (array_key_exists('transactions', $paymentData)) {
                $transactions = $paymentData['transactions'];
                foreach($transactions as $transaction) {
                    foreach($transaction['payments'] as $payment) {
                        $date_due = $payment['dueDate'];
                        if (is_null($payment['dueDate'])) {
                            $date_due = date('Y-m-d H:i:s', strtotime($order['authorizedDate']));
                        }
                        $orders_payment = array(
                            'order_id' => $order_id,
                            'parcela' => $payment['installments'],
                            'bill_no' => $order_mkt,
                            'data_vencto' => $date_due,
                            'valor' => $payment['value']/100,
                            'forma_id' => $payment['group'],
                            'forma_desc' => $payment['paymentSystemName'],
                            'payment_method_id' => $payment['paymentSystem'],
                            'forma_cf' => ''
                        );
                    }
                }
                //@todo possivel bug
                $this->model_orders->insertParcels($orders_payment);
             }   
        }

        if (array_key_exists('packageAttachment', $detail)) {
            $packageAttachment = $detail['packageAttachment'];
            foreach($packageAttachment['packages'] as $package) {
                $date_delivered = '';
                if (array_key_exists('courierStatus', $package)) {
                    if (!is_null($package['courierStatus'])) {
                        if (array_key_exists('deliveredDate', $package['courierStatus'])) {
                            if (!is_null($package['courierStatus']['deliveredDate'])) {
                                $date_delivered = date('Y-m-d', strtotime($package['courierStatus']['deliveredDate']));
                            }
                        }
                    }
                }
                foreach($itensIds as $item_id) {
                    $freights_data = array(
                        'order_id' => $order_id,
                        'item_id' => $item_id,
                        'company_id' => $company_id,
                        'ship_company' => $package['courier'],
                        'date_delivered' => $date_delivered,
                        'ship_value' => substr_replace($shippingLogistic['price'], '.', -2, 0),
                        'idServico' => 0, 
                        'method' => $shippingLogistic['selectedSla'],
                        'codigo_rastreio' => $package['trackingNumber'],
                        'url_tracking' => $package['trackingUrl']
                    );
                    $this->model_orders->insertFreight($freights_data);
                } 
            }
        }

        // verificação do frete -
        /*if ($store['freight_seller'] == 1) {
            $this->model_orders->setShipCompanyPreview($order_id,'Logística Própria','Logística Própria',7);
        }
        else {*/
            $transportadora = in_array($shippingLogistic['selectedSla'], array('PAC', 'SEDEX', 'MINI')) ? 'CORREIOS' : 'Transportadora';
            $servico        = $shippingLogistic['selectedSla'];
            $prazo          = filter_var($shippingLogistic['shippingEstimate'], FILTER_SANITIZE_NUMBER_INT);
            $this->model_orders->setShipCompanyPreview($order_id, $transportadora, $servico,$prazo);
        //}

        return [true, $order_id];
    }

	private function getProductVtex($separateIntegrationData, $sku)  
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		try {
			$endPoint = 'api/catalog_system/pvt/sku/stockkeepingunitbyid/'.$sku;
			$this->processNew($separateIntegrationData, $endPoint);
			
			if ($this->responseCode == 429) {
				sleep(60);
				$this->processNew($separateIntegrationData, $endPoint);
			}
			if ($this->responseCode !== 200) {
				$erro = 'Erro httpcode: '.$this->responseCode.' ao chamar '.$endPoint;
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"E");
				return false;
			}

			$productData = json_decode($this->result, true);

			return $productData;
		} catch (Exception $e) {
			return false;
		}
	}

    function syncCanceledIntTo($int_to) {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $endPoint = '/api/oms/pvt/orders?f_status=canceled';
        $this->process($int_to, $endPoint);

        $orders = json_decode($this->result, true);

        foreach($orders['list'] as $order) {
            $this->markCancelled($int_to, $order);
        }
    }

    private function markCancelled($int_to, $order)
	{
		echo PHP_EOL . '[CANCELLED]['.$this->getInt_to().'] Order: '. $order['id'] . "... \n";
		if ($order_exist = $this->model_orders->getOrdersDatabyBill($int_to, $order['orderId'])) {
			echo "Ordem Já existe :".$order_exist['id']."  paid_status=".$order_exist['paid_status']."... \n";

			if (in_array($order_exist['paid_status'], [95, 96, 97, 98, 99])) {
				//pedido já cancelado.
				echo "Já está cancelado... \n";
			}
			else {
                $this->ordersmarketplace->cancelOrder($order_exist['id'], false);
				echo "Marcado para cancelamento... \n";
			}
		}
		else {
			echo "Order not found... \n";
		}
    }
    
    private function markApproved($int_to, $order) {
        echo PHP_EOL . '[APPROVED]['.$this->getInt_to().'] Order: '. $order['id'] . "... ";
        $cross_docking_default = 0;
        $cross_docking = $cross_docking_default; 
        
		if ($order_exist = $this->model_orders->getOrdersDatabyBill($int_to, $order['orderId'])) {
            $order_exist['paid_status'] = 3;
            $items = $this->model_orders->getOrdersItemData($order_exist['id']);

            foreach ($order['items'] as $item) {
                $sku_item =  explode('-', $item['sellerSku'])[0];

                $sql = "SELECT * FROM vtex_ult_envio WHERE skubling = ? AND int_to = ?";
                $query = $this->db->query($sql, array($sku_item, $int_to));
                $prf = $query->row_array();

                // Tempo de crossdocking 
                if (isset($prf['crossdocking'])) {  // pega o pior tempo de crossdocking dos produtos
                    if (((int) $prf['crossdocking'] + $cross_docking_default) > $cross_docking) {
                        $cross_docking = $cross_docking_default + (int) $prf['crossdocking']; 
                    };
                }
            }
            
            $date = (new DateTime($order["lastChange"]))->format("Y-m-d");
            $data_pago = (new DateTime($order["lastChange"]))->format("Y-m-d h:m:s.z");
            $data_limite_cross_docking = $this->somar_dias_uteis($date, $cross_docking, ''); 

            $this->model_orders->updateByOrigin($order_exist['id'], $order_exist);
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
            $skuExist = $this->process($product['int_to'], $endPoint, 'POST', $bodyParams);

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

    private function syncPayment()
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        $orders = $this->model_orders->getOrdersPaidWithoutPayment();

        foreach ($orders as $order) {

            $endPoint = '/api/oms/pvt/orders/' . $order['numero_marketplace'].'/payment-transaction';
            $this->process($order['origin'], $endPoint);

            $orderVtex = json_decode($this->result, true);

            if ($this->responseCode != 200) {
                $this->log_data('batch', $log_name, 'Status='.$this->responseCode.', para localizar o pagamento do pedido: '.$order['id'],"W");
                continue;
            }

            foreach ($orderVtex['payments'] as $parcel => $payment) {
                $arrPayment = array(
                    'order_id'          => $order['id'],
                    'parcela'           => $payment['installments'],
                    'bill_no'           => $order['numero_marketplace'],
                    'data_vencto'       => $payment['dueDate'] ?? date('Y-m-d'),
                    'valor'             => substr_replace($payment['value'], '.', -2, 0),
                    'forma_id'          => $payment['group'],
                    'forma_desc'        => $payment['paymentSystemName'],
                    'payment_method_id' => $payment['paymentSystem']
                );

                $this->model_orders->insertParcels($arrPayment);

                $this->log_data('batch', $log_name, 'Pagamento do pedido: '.$order['id'].' criado - payload='.json_encode($payment),"I");

                echo json_encode($payment)."\n";
            }
        }

    }
}