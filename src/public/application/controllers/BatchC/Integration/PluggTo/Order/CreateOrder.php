<?php

/**
 * Class CreateOrder
 *
 * php index.php BatchC/Integration/PluggTo/Order/CreateOrder run
 *
 */

require APPPATH . "controllers/BatchC/Integration/PluggTo/Main.php";

class CreateOrder extends Main
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

        $this->setJob('CreateOrder');

        $this->load->model('model_products');
        $this->load->model('model_orders');

        $this->_this = $this;
    }

    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if ($this->shutAppStatus) {
            echo $this->shutAppDesc . "\n";
            //$this->log_data('batch', $log_name, $this->shutAppDesc, "W");
            $this->log_integration($this->shutAppTitle, $this->shutAppDesc, "E");
            return false;
        }

        if (!$id || !$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        $this->store = $store;

        /* inicia o job */
        $this->setIdJob($id);
        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id='.$id.' store_id='.$store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        /* faz o que o job precisa fazer */
        echo "Pegando pedidos para enviar... \n";

        // Define a loja, para recuperar os dados para integração
        $this->setDataIntegration($store);

        try {
            // Recupera os pedidos
            $this->sendOrders();
        } catch (Throwable $e) {
            $this->log_integration('Erro ao processar pedidos', $e->getMessage(), "E");
        }

        // Grava a última execução
        $this->saveLastRun();

        // encerra o job
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }


    /**
     * Envia os pedidos para serem integrados
     */
    public function sendOrders()
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        $orders = $this->getOrdersIntegrations();
        $access_token = $this->getToken();

        $dataIntegration =  $this->model_settings->getSettingDatabyName('credencial_pluggto');
        $credentials = json_decode($dataIntegration['value']);

        $credentials_sale_intermediary =  $this->model_settings->getSettingDatabyName('sale_intermediary_pluggto');
        $sale_intermediary = isset($credentials_sale_intermediary['value']) ? $credentials_sale_intermediary['value']: ""; 

        $credentials_payment_intermediary =  $this->model_settings->getSettingDatabyName('payment_intermediary_pluggto');
        $payment_intermediary = isset($credentials_payment_intermediary['value']) ? $credentials_payment_intermediary['value']: "";

        $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        if(!$sellercenter_name){
            $sellercenter_name="Conecta Lá";
        }
        $sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
        if(!$sellercenter){
            $sellercenter="conectala";
        }

        foreach ($orders as $orderIntegration) {
            $orderId    = $orderIntegration['order_id'];
            $paidStatus = $orderIntegration['paid_status'];

            $companyId  = $orderIntegration['company_id'];
            $this->setCompany($companyId); 

            $this->setUniqueId($orderId); // define novo unique_id

            // verifica cancelado, para não integrar
            if ($this->getOrderCancel($orderId)) {
                $this->removeAllOrderIntegration($orderId);
                $msgError = "PEDIDO={$orderId} cancelado, não será integrado - ORDER_INTEGRATION=".json_encode($orderIntegration);
                echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Pedido {$orderId} cancelado", "<h4>Pedido {$orderId} não será integrado</h4> <ul><li>Pedido cancelado antes de ser realizado o pagamento.</li></ul>", "S");
                continue;
            }           

            // Ignoro o status pois ainda não foi pago e não será enviado para o erp
            if ($paidStatus != OrderStatusConst::WAITING_INVOICE) {
                // Pedido chegou como não pago, mas já mudou de status
                $this->getOrderOtherThanUnpaid($orderId);

                echo "Chegou não pago, vou ignorar\n";
                continue;
            }

            $order = $this->getDataOrdersIntegrations($orderId);

            if (!$order) {
                
                $msgError = "Não foi encontrado o PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId} <br> <ul><li>Não foi possível encontrar os dados do pedido.</li></ul>", "E");
                continue;
            }

            $orderMain = $order[0];

            // não encontrou o pedido
            if (!$orderMain['order_id']) {
                $msgError = "Não foi encontrado o PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId} <br> <ul><li>Não foi possível encontrar os dados do pedido.</li></ul>", "E");
                continue;
            }
       
            // não encontrou o cliente
            if (!$orderMain['name_client']) {
                $msgError = "Não foi encontrado o cliente para o PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi possível encontrar os dados do cliente para faturar o pedido.</li></ul>", "E");
                continue;
            }

            // não encontrou o produto(s)
            if (!$orderMain['id_iten_product']) {
                $msgError = "Não foi encontrado o(s) produto(s) para o PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi possível encontrar os dados do(s) produto(s) para faturar o pedido.</li></ul>", "E");
                continue;
            }

            $arrRetornoSituacao = $this->getStatusIntegration($paidStatus);
            $situacaoPluggto = $arrRetornoSituacao['historico'];
            $situacao = $arrRetornoSituacao['status'];
            
            $newOrder = array();            
            $fullName = explode(" ", $orderMain['name_client']);
            $first_name  = $fullName[0] ?? '';
            $last_name   = '';
            for ($i=1; $i < count($fullName); $i++) { 
                $last_name .=  $fullName[$i].' ';
            }
            
            $newOrder['receiver_name']                  = $first_name;
            $newOrder['receiver_lastname']              = $last_name;
            $newOrder['receiver_address_number']        = $orderMain['num_order'];
            $newOrder['receiver_address']               = $orderMain['address_client'];      
            $newOrder['receiver_address_reference']     = $orderMain['complement_order'] ?? "";
            $newOrder['receiver_address_complement']    = $orderMain['compl_client'] ?? "";
            $newOrder['receiver_city']                  = $orderMain['city_client'];
            $newOrder['receiver_state']                 = $orderMain['uf_client'];
            $newOrder['receiver_country']               = $orderMain['country_client'] ?? "BR";
            $newOrder['receiver_email']                 = $orderMain['email_client'];
            $newOrder['receiver_zipcode']               = $orderMain['cep_client'];
            $newOrder['receiver_phone_area']            = substr($orderMain['phone_client'], 0, 2);
            $newOrder['receiver_phone']                 = substr($orderMain['phone_client'], 2);
            $newOrder['receiver_phone2_area']           = "";
            $newOrder['receiver_phone2']                = "";
            $newOrder['receiver_neighborhood']          = $orderMain['neigh_client'];
                     

            $newOrder['payer_name']                     = $first_name;
            $newOrder['payer_lastname']                 = $last_name;
            $newOrder['payer_address']                  = $orderMain['address_client'];
            $newOrder['payer_address_number']           = $orderMain['num_order'];
            $newOrder['payer_additional_info']          = $orderMain['complement_order'] ?? "";
            $newOrder['payer_address_complement']       = $orderMain['compl_client'] ?? "";
            $newOrder['payer_neighborhood']             = $orderMain['neigh_client'];
            $newOrder['payer_city']                     = $orderMain['city_client'];
            $newOrder['payer_state']                    = $orderMain['uf_client'];
            $newOrder['payer_zipcode']                  = $orderMain['cep_client'];
            $newOrder['payer_phone_area']               = substr($orderMain['phone_client'], 0, 2);
            $newOrder['payer_phone']                    = substr($orderMain['phone_client'], 2);    
            $newOrder['payer_phone2_area']              = "";
            $newOrder['payer_phone2']                   = "";                     
            $newOrder['payer_email']                    = $orderMain['email_client'];

            $tpPessoa = strlen(preg_replace('/\D/', '', $orderMain['cpf_cnpj_client'])) == 14 ? "J" : "F";
            if($tpPessoa == "F"){
                $newOrder['payer_cpf']                  = $orderMain['cpf_cnpj_client'];
            }else{
                $newOrder['payer_cnpj']                 = $orderMain['cpf_cnpj_client'];
            }                      
            
            $newOrder['payer_tax_id']                   = "";            
            $newOrder['payer_document']                 = "";
            $newOrder['payer_razao_social']             = "";
            $newOrder['payer_company_name']             = "";
            $newOrder['sale_intermediary']              = $sale_intermediary;
            $newOrder['payment_intermediary']           = $payment_intermediary;
            $newOrder['intermediary_seller_id']         = $this->store;
            $newOrder['payer_country']                  = $orderMain['country_client'] ?? "BR";
            $newOrder['email_nfe']                      = $orderMain['email_client'];
            $newOrder['payer_gender']                   = "n/a";

            
           
            $newOrder['total_paid']                     = $sellercenter === 'somaplace' ? $orderMain['net_amount'] : $orderMain['gross_amount'];
            $newOrder['shipping']                       = $orderMain['total_ship'];            
            $newOrder['subtotal']                       = "";
            $newOrder['total']                          = $sellercenter === 'somaplace' ? $orderMain['net_amount'] : $orderMain['gross_amount'];
            $newOrder['discount']                       = $orderMain['discount_order'] ?? 0.0;
            $newOrder['payment_installments']           = 0;

            $newOrder['user_client_id']                 = $credentials->client_id_pluggto;        
            $newOrder['status']                         = "pending";//$this->getPluggToStatus($situacaoPluggto);
            
            $newOrder['original_id']                    = "".$orderId."";
           
            $newOrder['expected_send_date']             = "";
            $newOrder['expected_delivery_date']         = "";
            $newOrder['receiver_schedule_date']         = "";
            $newOrder['receiver_schedule_period']       = "afternoon";
            //$newOrder['stock_code']                     = "warehouse_1";
            //$newOrder['price_code']                     = "price_reseller";
            $newOrder['auto_reserve']                   = true;
            $newOrder['external']                       = "".$orderId."";
            $newOrder['channel']                        = "".$sellercenter_name."";
            

            // Transporte                           
            $freights = $this->getDataPrevisaoEntregaOrderIdConectala($orderId, $orderMain['company_id']);
            
            if($freights){
                $freights = $freights[0];   
            }
           
            $newOrder['payments'] = array();
            $valuePayment = $orderMain['valor_payment'] ?? ($sellercenter === 'somaplace' ? $orderMain['net_amount'] : $orderMain['gross_amount']);
            $payments = array(
                "payment_type"              => $this->getPaymentPluggTo($orderMain['forma_payment']),
                "payment_method"            => $orderMain['forma_payment'] ?? "Pagamento com cartão",
                "payment_installments"      => $orderMain['parcela_payment'] ?? 1,
                "payment_total"             => $valuePayment,
                "payment_quota"             => 0,
                "payment_interest"          => 0,                
            );           
            
            array_push($newOrder['payments'], $payments);

            $newOrder['shipments'] = array();

            $status_shipment = "invoiced";
            if(!empty($freights['data_envio'])){
                $status_shipment = "shipping_informed";
            }else if(!empty($freights['date_delivered'])){
                $status_shipment = "shipping_informed";
            }

            if(isset($orderMain['data_pago']))
            {
                $date_estimate = $this->somar_dias_uteis($orderMain['data_pago'] , $orderMain['ship_time_preview']);   
            }
            
            $shipments = array(
                "shipping_company"          => (isset($freights['ship_company']) ? $freights['ship_company'] : $orderMain['ship_company_preview']),
                "shipping_method"           => (isset($freights['method']) ? $freights['method'] : $orderMain['ship_service_preview']),
                "track_code"                => $freights['codigo_rastreio'] ?? "",
                "track_url"                 => $freights['url_tracking'] ?? "",
                "status"                    => $status_shipment, //shipped|delivered|issue
                "estimate_delivery_date"    => (isset($freights['prazoprevisto']) ? $freights['prazoprevisto'] : $date_estimate),
                "date_shipped"              => "", // $freights['data_envio']
                "date_delivered"            => "",
                "date_cancelled"            => "",
                "nfe_key"                   => "",
                "nfe_link"                  => "",
                "nfe_number"                => "",
                "nfe_serie"                 => "",
                "nfe_date"                  => "",
                "cfops"                     => "",
                "documents"                 => array(array(
                    "url"           => "",
                    "external"      => "",
                    "type"          => "",//"label|packing_list|invoice"
                )),
                "shipping_items"            => array(array()),
                "issues"                    => array(array(
                    "description"   => "",
                    "date"          => "",
                )),
            );            
            array_push($newOrder['shipments'], $shipments);            
            $newOrder['items'] = array();
            
            $itensPrdShip = array();

            $idProductAnterior = null;            
            foreach ($order as $iten) {             
                if($iten['id_product'] != $idProductAnterior) {
                    $sku = $this->getSkuProductVariationOrder($iten);

                    // não encontrou o SKU
                    if (!$sku) {
                        $msgError = "Não foi encontrado o SKU do produto/variação para integrar! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);                        
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>Não foi encontrado o SKU do produto/variação para integrar! SKU={".$iten['sku_product']."} ID Produto ConectaLá={".$iten['id_product']."}.</li></ul>", "E");
                        continue 2;
                    }
                    // Estoque zerado
                    $stockProduct = $this->getStockProduct($iten);
                    if ($stockProduct <= 0) {
                        $msgError = "Produto sem estoque para integrar! SKU={$iten['sku_product']}. VARIANT={$iten['variant_product']}. PEDIDO={$orderId} para ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order);                        
                        $this->log_data('batch', $log_name, $msgError, "W");
                        $this->log_integration("Erro para integrar o pedido {$orderId}", "Não foi possível integrar o pedido {$orderId}. <br> <ul><li>O produto não tem estoque para integrar! SKU={$iten['sku_product']} ID Produto ConectaLá={$iten['id_product']}.</li></ul>", "E");
                        continue 2;
                    }
                    
                    //'un'            => $iten['un_product'],
                    
                    $itemPrd = array(
                        'quantity'      => intval($iten['qty_product']),
                        'sku'           => trim($sku),
                        'price'         => number_format((float)$iten['rate_product'], 2, '.', ''),
                        'total'         => $iten['qty_product'] * number_format((float)$iten['rate_product'], 2, '.', ''),
                        'name'          => trim($iten['name_product']),
                        //'stock_code'    => "6394",
                        //'supplier_id'   => "6394",
                    );

                    $itensPrdShipping = array(
                        'sku'           => trim($sku),
                        'quantity'      => intval($iten['qty_product']),
                    );          
                                        
                    array_push($newOrder['items'], $itemPrd);     
                    array_push($itensPrdShip, $itensPrdShipping); 
                }
                $idProductAnterior = $iten['id_product'];
            }

            $newOrder['shipments'][0]['shipping_items'] = $itensPrdShipping;

            $orderEncode = json_encode($newOrder);

            $url    = "https://api.plugg.to/orders?access_token={$access_token}";

            $dataOrder = $this->sendREST($url, $orderEncode, 'POST');
            if ($dataOrder['httpcode'] >= 200 && $dataOrder['httpcode'] <= 299) {   
                
                $getRetorno = json_decode($dataOrder['content']);
                $idPluggTo = $getRetorno->Order->id;               
                
                if (!isset($idPluggTo)) {
                    $msgError = "Não foi possível obter o código do pedido integrado. PEDIDO={$orderMain['order_id']}! ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order) . " RETORNO=".json_encode($dataOrder);
                    echo "{$msgError}\n";
                    $this->log_data('batch', $log_name, $msgError, "W");
                    $this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>Não foi possível recuperar o código do pedido integrado para gravar. Contate o suporte!</li></ul>", "E");
                    continue;
                }
                
                ///muda o status do pedido para aprovado = approved
                $urlUpdate    = "https://api.plugg.to/orders/$idPluggTo?access_token={$access_token}";
                $updateOrder  = array();
                $updateOrder['status']  = "approved";
                $dataUpdate   = json_encode($updateOrder);
                $dataStatus   = json_decode(json_encode($this->sendREST($urlUpdate, $dataUpdate, 'PUT')));
                ////fim da mudança de status
                
                $this->saveOrderIdIntegration($orderMain['order_id'], $idPluggTo);

                if ($paidStatus != 3) {
                    $this->removeOrderIntegration($orderMain['order_id']);
                }
                
                $this->controlRegisterIntegration($orderIntegration);
                //$valorTotalPedido = $orderMain['gross_amount'];
                $this->log_integration("Pedido {$orderId} integrado", "<h4>Novo pedido integrado com sucesso</h4> <ul><li>O pedido {$orderMain['order_id']}, foi criado na PluggTo com o ID {$idPluggTo}</li></ul>", "S");            
                $this->log_data('batch', $log_name, "Pedido {$orderMain['order_id']} integrado com sucesso! enviado=" . json_encode($newOrder) . ' retorno_pluggto='.json_encode($dataOrder), "I");
                echo "Pedido {$orderId} integrado com sucesso com o código {$idPluggTo}!!!\n";
                continue;
            } else {
                $msgError = "Não foi possível integrar o pedido {$orderMain['order_id']}! ORDER_INTEGRATION=".json_encode($orderIntegration).", ORDER=" . json_encode($order) . " RETORNO=".json_encode($dataOrder);
                echo "{$msgError}\n";
                $this->log_data('batch', $log_name, $msgError, "W");

                // formatar mensagens de erro para log integration
                
                $arrErrors = array();
                $getRetorno = json_decode($dataOrder['content']);

                if($dataOrder['httpcode'] == 500) {
                    $arrErrors = 'Problema ao integrar!';
                } else {
                    $errors = $getRetorno->details->errmsg;                    
                }

                //$this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>" . implode('</li><li>', $arrErrors) . "</li></ul>", "E");
                $this->log_integration("Erro para integrar o pedido {$orderId}", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li>{$dataOrder['content']}</li></ul>", "E");
                continue;
            }            
        }
    }

    /**
     * Recupera os pedidos para integração
     *
     * @return array Retorno os pedidos na fila para integrar
     */
    public function getOrdersIntegrations()
    {
        return $this->db
            ->from('orders_to_integration')
            ->where(array(
                'store_id'  => $this->store,
                'new_order' => 1
            ))
            ->where_in('paid_status', [
                OrderStatusConst::WAITING_PAYMENT,
                OrderStatusConst::PROCESSING_PAYMENT,
                OrderStatusConst::WAITING_INVOICE
            ])
            ->get()
            ->result_array();
    }

    /**
     * Recupera a quantidade em estoque do produto
     *
     * @param   array   $iten   Array com dados do produto
     * @return  int             Retorna a quantidade em estoque do produto/variação
     */
    public function getStockProduct($iten)
    {
        if ($iten['variant_product'] == "") return $iten['qty_product'];

        $var = $this->db
            ->get_where('prd_variants',
            
                array(
                    'prd_id'    => $iten['id_product'],
                    'variant'   => $iten['variant_product']
                )
            )->row_array();

        return $var['qty'];
    }

     /**
     * Recupera se o pedido precisa ser cancelado
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se existe cancelamento
     */
    private function getOrderCancel($orderId)
    {
        $orderCancel = $this->db
            ->get_where('orders_to_integration',
                array(
                    'order_id'      => $orderId,
                    'store_id'      => $this->store,
                    'paid_status'   => OrderStatusConst::CANCELED_BEFORE_PAYMENT
                )
            )->row_array();

        if (!$orderCancel) return false;

        return true;
    }

    /**
     * Recupera dados de um pedido
     *
     * @param   int     $order_id   Código do pedido
     * @return  array               Retorna dados do pedido
     */
    public function getDataOrdersIntegrations($order_id)
    {
        return $this->db
            ->select(
                array(
                    'orders.id as order_id',
                    'orders.numero_marketplace as numero_marketplace',
                    'orders.customer_name as name_order',
                    'orders.customer_address as address_order',
                    'orders.customer_phone as phone_order',
                    'orders.date_time as date_created',
                    'orders.gross_amount as gross_amount',
                    'orders.net_amount',
                    'orders.discount as discount_order',
                    'orders.customer_address_num as num_order',
                    'orders.customer_address_compl as compl_order',
                    'orders.customer_address_neigh as neigh_order',
                    'orders.customer_address_city as city_order',
                    'orders.company_id as company_id',
                    'orders.customer_address_uf as uf_order',
                    'orders.customer_address_zip as cep_order',
                    'orders.customer_reference as reference_order',
                    'orders.customer_id as id_client',
                    'orders.total_ship as total_ship',
                    'orders.ship_company_preview as ship_company_preview',
                    'orders.ship_service_preview as ship_service_preview',
                    'orders.data_pago as data_pago',
                    'orders.ship_time_preview as ship_time_preview',
                    'clients.customer_name as name_client',
                    'clients.customer_address as address_client',
                    'clients.addr_num as num_client',
                    'clients.addr_compl as compl_client',
                    'clients.addr_neigh as neigh_client',
                    'clients.addr_city as city_client',
                    'clients.addr_uf as uf_client',
                    'clients.zipcode as cep_client',
                    'clients.phone_1 as phone_client',
                    'clients.email as email_client',
                    'clients.origin as origin_client',
                    'clients.cpf_cnpj as cpf_cnpj_client',
                    'clients.ie as ie_client',
                    'clients.rg as rg_client',
                    'orders_item.id as id_iten_product',
                    'orders_item.product_id as id_product',
                    'orders_item.sku as sku_product',
                    'orders_item.name as name_product',
                    'orders_item.un as un_product',
                    'orders_item.qty as qty_product',
                    'orders_item.rate as rate_product',
                    'orders_item.variant as variant_product',
                    'orders_payment.id as id_payment',
                    'orders_payment.parcela as parcela_payment',
                    'orders_payment.data_vencto as vencto_payment',
                    'orders_payment.valor as valor_payment',
                    'orders_payment.forma_desc as forma_payment'

                )
            )
            ->from('orders')
            ->join('orders_item', 'orders.id = orders_item.order_id')
            ->join('orders_payment', 'orders.id = orders_payment.order_id', 'left')
            ->join('clients', 'orders.customer_id = clients.id', 'left')
            ->where(
                array(
                    'orders.store_id'   => $this->store,
                    'orders.id'         => $order_id
                )
            )
            ->get()
            ->result_array();
            
    }

    /**
     * Recupera o SKU do produto/variação vendido
     *
     * @param   array       $iten   Array com dados do produto vendido
     * @return  false|string        Retorna o sku do produto ou variação, em caso de erro retorna false
     */
    public function getSkuProductVariationOrder($iten)
    {
        
        if ($iten['variant_product'] == "") return $iten['sku_product'];

        $var = $this->db
            ->get_where('prd_variants',
                array(
                    'prd_id'    => $iten['id_product'],
                    'variant'   => $iten['variant_product']
                )
            )->row_array();

        if (!$var) return false;

        return $var['sku'];
    }

    
    /**
     * Verifica se existe um status como pago, aguardando faturamento ou cancelado para criar o pedido
     *
     * @param   int $orderId    Código do pedido
     * @return  bool            Retorna o status para criação
     */
    public function getOrderOtherThanUnpaid($orderId)
    {
        $query = $this->db
            ->from('orders_to_integration')
            ->where(array(
                'store_id' => $this->store,
                'order_id' => $orderId
            ))
            ->where_in('paid_status', [
                OrderStatusConst::WAITING_INVOICE
            ])
            ->get();

        if($query->num_rows() == 0) return false;

        // Remover da fila do não pago
        $this->db->delete(
            'orders_to_integration',
            array(
                'store_id'   => $this->store,
                'order_id'   => $orderId,
                'paid_status'=> OrderStatusConst::WAITING_PAYMENT
            ), 1
        );

        // coloca o próximo status como new_order = 1
        $orderUpdated = $this->db
            ->from('orders_to_integration')
            ->where(
                array(
                    'store_id'  => $this->store,
                    'order_id'  => $orderId,
                    'new_order' => 0
                )
            )
            ->where_in('paid_status', [
                OrderStatusConst::WAITING_INVOICE
            ])
            ->order_by('id', 'asc')
            ->get()
            ->row_array();

        return $this->db->where('id', $orderUpdated['id'])->update('orders_to_integration', array('new_order' => 1)) ? true : false;
    }


    /**
     * Cria um novo registro de integração caso o status for 3, aguardar para faturar
     *
     * @param   array   $data   Dados da integração
     * @return  bool            Retorna o status da criação
     */
    public function controlRegisterIntegration($data)
    {
        if ($data['paid_status'] == OrderStatusConst::WAITING_INVOICE) {
            $idIntegration = $data['id'];

            $arrUpdate = array(
                'new_order' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            );

            $update = $this->db->where(
                array(
                    'id'        => $idIntegration,
                    'store_id'  => $this->store,
                )
            )->update('orders_to_integration', $arrUpdate);

            return $update ? true : false;
        }

        return false;
    }


    /**
     * Lançar estoque do pedido inserido
     *
     * @param $codProduto
     * @param $orderId
     * @param $dataEstoque
     * @throws Exception
     */
    private function PostOrderStock($skuProduto, $orderId, $dataEstoque)
    {
        $access_token = $this->getToken();
        $url = "https://api.plugg.to/skus/{$skuProduto}/stock?access_token={$access_token}";
        
        //"action":"increase/decrease/update"
        $data = '{"action": "update","quantity":'.$dataEstoque.',"sku":"'.$skuProduto.'"}';

        $dataOrder  = $this->sendREST($url, $data, 'PUT');
        $log_name   = $this->router->fetch_class() . '/' . __FUNCTION__;

        $getRetorno = json_decode($dataOrder['content']);
       
        if ($dataOrder['httpcode'] >= 400) {
            $msgError = "Não foi possível lançar estoque do pedido {$orderId}! SkuProduto={$skuProduto}, RETORNO=".json_encode($dataOrder);
            echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "W");

            // formatar mensagens de erro para log integration            
            $getRetorno = json_decode($dataOrder['content']);
            $errors = $getRetorno->details->errmsg;     
            $this->log_integration("Erro para lançar estoque do pedido {$orderId}", "<h4>Não foi possível lançar o estoque do pedido {$orderId} na PluggTo</h4> <ul><li>$errors</li></ul>", "E");
            return false;
        } else {
            return true;
        }
    }

    /**
     * Salvar ID do pedido gerado pelo integrador
     *
     * @param int $order_id Código do pedido na Conecta Lá
     * @param int $id_pluggto  Código do pedido na PluggTo
     */
    public function saveOrderIdIntegration($order_id, $id_pluggto)
    {
        $this->db->trans_begin();
        
        $return = (bool)$this->db->where(
            array(
                'id'        => $order_id,
                'store_id'  => $this->store,
            )
        )->update('orders', array('order_id_integration' => $id_pluggto));
        
        if($return){
            $this->db->trans_commit();
        }else{
            $this->db->trans_rollback();
        }
    }

    /**
     * Remove o pedido da fila de integração
     *
     * @param   int     $order_id   Código do pedido
     * @return  bool                Retornar o status da exclusão
     */
    public function removeOrderIntegration($order_id)
    {
        return $this->db->delete(
            'orders_to_integration',
            array(
                'store_id'  => $this->store,
                'order_id'  => $order_id,
                'new_order' => 1
            ), 1
        ) ? true : false;
    }

    /**
     * Remove todos os pedidos da fila de integração
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retornar o status da exclusão
     */
    public function removeAllOrderIntegration($orderId)
    {
        return $this->db->delete(
            'orders_to_integration',
            array(
                'store_id'  => $this->store,
                'order_id'  => $orderId
            )
        ) ? true : false;
    }
    
    /**
     * Recupera a transportadora que fará o envio do produto
     *
     * @param   string  $company    Empresa para transporte
     * @param   string  $service    Tipo de serviço do transporte
     * @return  array               Retorno com status da transportadora e dados de transporte
     */
    public function getProviderOrder($company, $service, $peso)
    {
        $cnpjTransportadora = null;

        if ($company == "CORREIOS")  // correios
            $cnpjTransportadora = "34028316000103";

        elseif ($company == "Transportadora" && $service == "Conecta Lá") { // romoaldo
            $dataStore = $this->model_stores->getStoresData($this->store);

            switch ($dataStore['addr_uf']) {
                case "SC":
                    $cnpjTransportadora = "05813363000160";
                    break;
                case "SP":
                    $cnpjTransportadora = "21341720000190";
                    break;
                case "MG":
                    $cnpjTransportadora = "86479268000254";
                    break;
                case "RJ":
                case "ES":
                    $cnpjTransportadora = "24566736000351";
                    break;
                default:
                    return null;
            }

        } elseif ($company == "Conecta Lá" && $service == "Jamef") // jamef
            $cnpjTransportadora = "20147617000656";
        elseif ($company == "Bradex" && $service == "Transportadora") // Bradex
            $cnpjTransportadora = "24566736000351";

        $dataProvider = $this->model_providers->getProviderDataForCnpj($cnpjTransportadora);

        if($cnpjTransportadora == null || $dataProvider == null) return null;

        $data['transportadora'] = $dataProvider['razao_social'];
        $data['tipo_frete'] = 'D';
        if ($company == "CORREIOS")
            $data['servico_correios'] = $service;

        $data['peso_bruto'] = number_format($peso, 3, '.', '');
        $data['cnpj'] = $cnpjTransportadora;

        return $data;
    }      
    
    /**
     * Recupera data previsao da entrega do pedido na Conectala
     *
     * @param   int         $orderId    Código do pedido
     * @param   int         $companyId  Código da empresa
     * @return  int|bool                Retorna data de previsao da entrega
     */
    private function getDataPrevisaoEntregaOrderIdConectala($orderId, $companyId)
    {
        $freights = $this->db
            ->select( array('prazoprevisto',
                            'ship_company',
                            'method',
                            'date_delivered',
                            'url_tracking',
                            'ship_value',
                            'codigo_rastreio'))
            ->from('freights')
            ->where(array(
                    'company_id'  => $companyId,
                    'order_id'    => $orderId,
            ))
            ->get()
            ->result_array();

        if (!$freights) return false;

        return $freights;
    }
}