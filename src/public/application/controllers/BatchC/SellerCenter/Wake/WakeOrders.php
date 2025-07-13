<?php

require APPPATH . "controllers/BatchC/SellerCenter/Wake/Main.php";

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
 * @property Model_sellercenter_ult_envio $model_sellercenter_last_post
 * @property Model_promotions $model_promotions
 * @property Model_order_payment_transactions $model_order_payment_transactions
 * @property Model_orders_payment $model_orders_payment
 *
 * @property OrdersMarketplace $ordersmarketplace
 *
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property CI_Router $router
 */

 
class WakeOrders extends Main {
    var $int_to='';
    var $apikey='';
    var $site='';
    var $appToken='';
    var $accountName='';
    var $environment='';
    var $baseurl = '';

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
        $this->load->model('model_sellercenter_last_post');
        $this->load->model('model_promotions');
        $this->load->model('model_order_payment_transactions');
        $this->load->model('model_orders_payment');

        $this->load->library('ordersMarketplace');
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

    private function setApiUrl($baseurl) {
        $this->baseurl = $baseurl;
    }
    private function getApiUrl() {
        return $this->baseurl;
    }

    // php index.php BatchC/SellerCenter/Wake/WakeOrders run null int_to
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
            $api_keys = json_decode($integration['auth_data']);
            $this->setApikey($api_keys ?? null);

			if (!$integration) {
				echo " Marketplace ".$params." não encontrado!";
				die;
			}
            
			echo " Buscando pedidos da ".$params."\n";
        }
		else {
			$params = null; 
		}
                                    //status interno wake
        $this->syncOrderCreate($params); // integra os pedidos
        $this->syncProgressPayment($params); // pedidos pagos 1
        $this->syncListCanceled($params); //3,4,5,6,7,8,22
    
        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    private function syncOrderCreate($int_to = null)
    {
        echo dateNow()->format(DATETIME_INTERNATIONAL) . ' ' . __FUNCTION__ . "\n";
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        $limit = 200;
        $offset = 0;


        $baseUrlInternal = $this->model_settings->getValueIfAtiveByName('internal_api_url');

        $dataRetroativa = intval($this->model_settings->getValueIfAtiveByName('retroativo_pedidos_wake'));

        if(empty($dataRetroativa)){
            $dataRetroativa = 2;
        }
       
        $dataAtual = new DateTime();

        // Calculando a data inicial subtraindo os dias retroativos
        $dataInicial = new DateTime();
        $dataInicial->modify("-$dataRetroativa days");

        // Convertendo as datas para strings no formato 'Y-m-d H:i:s'
        $dataAtualStr = $dataAtual->format('Y-m-d H:i:s');
        $dataInicialStr = $dataInicial->format('Y-m-d H:i:s');


        $pagina = 1;
        while (true) {
            // Definindo os parâmetros
            $params = [
                'dataInicial' => $dataInicialStr,
                'enumTipoFiltroData' => 'DataPedido',
                'pagina' => $pagina,
                'quantidadeRegistros' => 50,
                'direcaoOrdenacao' => 'ASC',
                'dataFinal' =>  $dataAtualStr
            ];

            $queryString = http_build_query($params);
    
            $end_point =  'pedidos?' . $queryString;
            
            $auth_data = $this->getApikey();
        
            
            $this->processNew($auth_data,$end_point,'GET');

            echo "Pagina: " . $pagina . "\n\n";
            if (empty($this->result)) {
                echo "Nenhum pedido encontrado nas datas especificadas data Inicio $dataInicialStr e data Fim $dataAtualStr \n";
                return;
            }

            
            $resultJson = $this->result;
            $orders = json_decode($resultJson);

            echo count($orders) . " pedidos a serem criados \n";

            if(count($orders) == 0){
                return;
            }
        
            foreach ($orders as $order) {

                $items = $this->model_orders->getOrdersDatabyNumeroMarketplace($order->pedidoId);
    
                $pedidoInternoWakeEndPoint =  $baseUrlInternal . 'Api/SellerCenter/Wake/Orders/'.$int_to;

                $dataIntegration = json_encode([$order],JSON_UNESCAPED_UNICODE);
            
                // se não encontrar, vamos cadastrar o item no seller
                if(empty($items)){
                    $this->processInternalNew($pedidoInternoWakeEndPoint,'POST', $dataIntegration);
            
                    $resultJson = $this->result;     
                    $response = json_decode($resultJson, true);

                    if (isset($response['error'])) {
                        // Tratando o erro
                        $errorCode = $response['error']['code'];
                        $errorMessage = $response['error']['message'];
                        echo "Erro: [$errorCode] $errorMessage\n";
                        continue;
                    }else {   
                        if (is_array($response) && isset($response[0]['ordeId'])) {
                            echo "Pedido processado com sucesso. Pedido n°: " . $response[0]['ordeId'] . "\n";
                        } else {
                            echo "Erro ao processar o pedido. Resposta inválida ou ordem não encontrada.\n";
                        }
                    }                     
            
                }else{
                    echo "Pedido já existe no sistema. Pedido n°: " . $items['id'] . "\n";
                }
                
            }

            $pagina++;
        }
        echo "Encerrando a criação de pedidos \n";
    }

    private function syncProgressPayment($int_to=null)
    {

       
        echo dateNow()->format(DATETIME_INTERNATIONAL) . ' ' . __FUNCTION__ . "\n";
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        
        $dataRetroativa = intval($this->model_settings->getValueIfAtiveByName('retroativo_pedidos_wake'));

        if(empty($dataRetroativa)){
            $dataRetroativa = 2;
        }
       
        $dataAtual = new DateTime();

        // Calculando a data inicial subtraindo os dias retroativos
        $dataInicial = new DateTime();
        $dataInicial->modify("-$dataRetroativa days");

        // Convertendo as datas para strings no formato 'Y-m-d H:i:s'
        $dataAtualStr = $dataAtual->format('Y-m-d H:i:s');
        $dataInicialStr = $dataInicial->format('Y-m-d H:i:s');

        $pagina = 1;
        while (true) {
            // Definindo os parâmetros
            $params = [
                'dataInicial' => $dataInicialStr,
                'enumTipoFiltroData' => 'DataPedido',
                'situacoesPedido' => 1,
                'pagina' => $pagina,
                'quantidadeRegistros' => 50,
                'direcaoOrdenacao' => 'ASC',
                'dataFinal' =>  $dataAtualStr
            ];

            $queryString = http_build_query($params);
    
            $end_point =  'pedidos?' . $queryString;
            
            $auth_data = $this->getApikey();
        
            
            $this->processNew($auth_data,$end_point,'GET');

            echo "Pagina: " . $pagina . "\n";

            if (empty($this->result)) {
                echo "Nenhum pedido encontrado nas datas especificadas data Inicio $dataInicialStr e data Fim $dataAtualStr \n";
                return;
            }

                
            $resultJson = $this->result;
            $orders = json_decode($resultJson);

            echo count($orders) . " pedidos pagos\n";

            if(count($orders) == 0){
                return;
            }

            foreach ($orders as $order) {

                $items = $this->model_orders->getOrdersDatabyNumeroMarketplace($order->pedidoId);

                if (in_array($items['paid_status'], [1, 2])){
                    $cross_docking = 0;
                    $data_pago = date("Y-m-d H:i:s");

                    $updateOrder = array(
                        'data_pago' => $data_pago,
                        'data_limite_cross_docking' => $this->somar_dias_uteis(date("Y-m-d"), $cross_docking),
                        'paid_status' => 3,
                        'ship_companyName_preview' => $order->frete->freteContrato,
                        'shipping_estimate_date' => $this->somar_dias_uteis(date("Y-m-d H:i:s"),$order->frete->prazoEnvio)
                    );

                    $this->model_orders->updateByOrigin($items['id'], $updateOrder);

                    echo "Pedido n°: {$order->pedidoId} pagamento aprovado .\n";

                    $this->log_data('batch', $log_name, "Pedido {$items['id']} pago.\nUpdateOrder=".json_encode($updateOrder)."\nPedido={$this->result}");

                }else{
                    echo "Pedido n°: {$order->pedidoId} está em um status diferente de 1 ou 2.\n";
                    continue;
                }

            }
          $pagina++;
        }
        echo "Encerrando os pedidos pagos \n";
    }

    function syncListCanceled($int_to = null) {
        echo dateNow()->format(DATETIME_INTERNATIONAL) . ' ' . __FUNCTION__ . "\n";
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        
        $dataRetroativa = intval($this->model_settings->getValueIfAtiveByName('retroativo_pedidos_wake'));

        if(empty($dataRetroativa)){
            $dataRetroativa = 2;
        }
       
        $dataAtual = new DateTime();

        // Calculando a data inicial subtraindo os dias retroativos
        $dataInicial = new DateTime();
        $dataInicial->modify("-$dataRetroativa days");

        // Convertendo as datas para strings no formato 'Y-m-d H:i:s'
        $dataAtualStr = $dataAtual->format('Y-m-d H:i:s');
        $dataInicialStr = $dataInicial->format('Y-m-d H:i:s');

        $pagina = 1;
        $situacoesPedido = '3,4,5,6,7,8,22';
        while (true) {
            // Definindo os parâmetros
            $params = [
                'dataInicial' => $dataInicialStr,
                'enumTipoFiltroData' => 'DataPedido',
                'situacoesPedido' => $situacoesPedido,
                'pagina' => $pagina,
                'quantidadeRegistros' => 50,
                'direcaoOrdenacao' => 'ASC',
                'dataFinal' =>  $dataAtualStr
            ];

            $queryString = http_build_query($params);
    
            $end_point =  'pedidos?' . $queryString;
            
            $auth_data = $this->getApikey();
        
            $this->processNew($auth_data,$end_point,'GET');

            echo "Pagina: " . $pagina . "\n";

            if (empty($this->result)) {
                echo "Nenhum pedido encontrado nas datas especificadas data Inicio $dataInicialStr e data Fim $dataAtualStr \n";
                return;
            }

                
            $resultJson = $this->result;
            $orders = json_decode($resultJson);

            echo count($orders) . " pedidos cancelados \n";

            if(count($orders) == 0){
                return;
            }

            foreach ($orders as $order) {

                $this->markCancelled($int_to, $order);

                $this->log_data('batch', $log_name, "Pedido n°: {$order->pedidoId} cancelado.\n Pedido={$this->result}");


            }
          $pagina++;
        }
        echo "Encerrando os pedidos cancelados \n";
    }

    private function markCancelled($int_to, $order)
	{
		echo '[CANCELLED]['.$this->getInt_to().'] Order: '. $order->pedidoId . "... \n";

		if ($order_exist = $this->model_orders->getOrdersDatabyBill($int_to, $order->pedidoId)) {
            echo "Order Já existe : " . $order_exist['id'] . "  paid_status = ". $order_exist['paid_status']."...  \n";
            
            if($order_exist['paid_status'] == 1){
                if (!in_array($order_exist['paid_status'], [95, 96, 97, 98])) {
                    $this->ordersmarketplace->cancelOrder($order_exist['id'], false, false);
                }
            }else{
                $this->ordersmarketplace->cancelOrder($order_exist['id'], false);
            }
			
		}
		else {
			echo 'Order n°' . $order_exist['id'] . ' não encontrado... \n';
		}

		echo PHP_EOL;

    }
    
    //chamada de curl para o metodo syncOrderCreate
    private function processInternalNew($endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to=null, $function= null )
    {
    		
	    $url = $endPoint;

        $this->header = [
            'content-type: application/json',
            'accept:application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
		// curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->result       = curl_exec($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $err        = curl_errno($ch);
	    $errmsg     = curl_error($ch);
       
		curl_close($ch);

		if ($err) {
			echo "Houve Erro no curl internal: ". $errmsg."\n";
		}
		
		if ($this->responseCode == 429) {
		    echo "Muitas requisições internal já enviadas httpcode=429. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($endPoint, $method, $data);
		}
		if ($this->responseCode == 504) {
		    echo "Deu Timeout internal httpcode=504. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($endPoint, $method, $data);
		}
        if ($this->responseCode == 503) {
		    echo "Wake internal com problemas httpcode=503. Nova tentativa em 60 segundos.\n";
            sleep(60);
			$this->processNew($endPoint, $method, $data);
		}
		if (!is_null($prd_id)) {
			$data_log = array( 
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'url' => $url,
				'function' => $function,
				'method' => $method,
				'sent' => $data,
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}

        return;
    }
}