<?php
require_once APPPATH . "controllers/BatchC/Integration/Integration.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Utils/RestRequestLI.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/LoadApiKeys.trait.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/LoadIntegrationUser.php";

class UpdateStatus extends Integration
{
    use LoadApiKey, LoadIntegrationUser;
    private $url = "https://api.awsli.com.br";

    /**
     * @var RestRequestLI
     */
    protected $rest_request;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => true,
        );
        $this->session->set_userdata($logged_in_sess);

        $this->setTypeIntegration("LojaIntegrada");
        $this->setJob('UpdateStatus');
        $this->load->model('model_api_integrations');
        $this->load->model('model_orders');
        $this->load->model('model_freights');
        $this->load->model('model_nfes');

        $this->rest_request = new RestRequestLI();

        $this->_this = $this;
		
		$this->setJob('UpdateStatus');
    }
    // php index.php BatchC/Integration/LojaIntegrada/Order/UpdateStatus run null 63
    public function run($id = null, $store = null)
    {
        //   echo"\nL_37";
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            echo "\nL_41_id: " . $id . " store: " . $store . "\n";
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        try {
            $this->loadApiKey($store);
        } catch (Exception $e) {
            $this->log_integration("Erro ao carregar configurações de integração", $e->getMessage(), 'E');
            return;
        }
        /* inicia o job */
        $this->setIdJob($id);
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        try {
            /* faz o que o job precisa fazer */
            $this->execution($id, $store);
        } catch (\Throwable $e) {
            echo "Erro: " . $e->getMessage();
        }
		
        // deleta o cookie
        if (
            isset($this->rest_request->cookiejar) && 
            file_exists($this->rest_request->cookiejar)
        ) {
            unlink($this->rest_request->cookiejar);
        }
        
        // Grava a última execução
        $this->saveLastRun();
        
        // encerra o job
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }
    public function execution($id = null, $store = null)
    {
        echo ("Iniciando Execução.\n");
        $this->store = $store;
        $this->store_object = $this->model_stores->getStoresData($store);
        $this->company = $this->store_object['company_id'];
        $this->user = $this->loadApiIntegrationUserByStore($this->store_object);
        echo "Dados Usuário:\n";
        print_r($this->user);
        $this->header_opt = [
            'x-user-email: ' . $this->user['email'],
            'x-api-key: ' . $this->store_object["token_api"],
            'x-store-key: ' . $this->store_object['id'],
        ];
        // echo("Função inativada por conta da parceira não dispor informações referente a nota fiscal, url de frete, apesar de informar o codigo de rastreio, tão pouco dados referente a entrega.\n");
        // return;
        $dataformat = "Y-m-d\Th:i:s";
        $date = new DateTime('now', new DateTimeZone("America/Sao_Paulo"));
        $date->modify("-1 Hour");
        $date_string = $date->format($dataformat);
        $url = $this->url . "/v1/pedido/search/?{$this->params}&since_atualizado={$date_string}";
        while (true) {
	        echo ($url . "\n");
			// apago o cookie
			unlink($this->rest_request->cookiejar);
			$this->rest_request = null;
			$this->load->library('rest_request');
	        $this->rest_request->setUp($url, "GET");
	        $sucess = $this->rest_request->send();
	        $orders_loja_integrada = json_decode($this->rest_request->response, true);
	        if (!$sucess) {
	            echo ("Processo interrompido por fala na chamada ao servidor Loja integrada.");
				return;
	        }
          	var_dump($orders_loja_integrada);
          	
            if (is_array($orders_loja_integrada["objects"]))
                $this->processQueue($orders_loja_integrada["objects"]);
            if (!isset($orders_loja_integrada["meta"]["next"])) {
                break;
            }
            
            // $url = $this->url . $orders_loja_integrada["meta"]["next"] . "?{$this->params}&since_atualizado={$date_string}";
            $url = $this->url . $orders_loja_integrada["meta"]["next"];
        }
    }
    private function processQueue($orders_loja_integrada)
    {
        foreach ($orders_loja_integrada as $key => $order_loja_integrada) {
            echo ("==============================================================================\n");
            $this->processOrder($order_loja_integrada);
            echo ("==============================================================================\n");
        }
    }
    private function processOrder($order_loja_integrada)
    {
        $this->has_error = false;
        
        echo ("Numero do pedido na loja integrada: {$order_loja_integrada["numero"]}\n");
        if (!$order_loja_integrada['id_anymarket']) {
            echo ("Falha, pedido sem id externa.\n");
            return;
        }
        if (!$order_loja_integrada["id_anymarket"]) {
            echo ("Falha, pedido vindo sem id interna da loja integrada.\n");
            return;
        }
        $order = $this->getOrderToIntegrationById($order_loja_integrada["id_anymarket"]);
        if (!$order) {
            echo ("Falha, pedido não encontrado.\n");
            return;
        }
        $order = $order['order'];
        echo ("Status do pedido na Conecta Lá: {$order['status']['code']}\n");
        if ($order["status"]["code"] == $this->model_orders->PAID_STATUS['delivered_reported_buyer']) {
            echo ("Pedido já entregue\n");
            return;
        }
		// apago o cookie
		unlink($this->rest_request->cookiejar);
		$this->rest_request = null;
		$this->load->library('rest_request');
        $url = $this->url . $order_loja_integrada["resource_uri"] . "?{$this->params}";
        $this->rest_request->setUp($url, "GET");
        $sucess = $this->rest_request->send();
        if (!$sucess) {
            echo ("Falha na requisição a Loja Integrada, indo para o proximo pedido.\n");
            return;
        }
        $orders_complete_in_loja_integrada = json_decode($this->rest_request->response, true);
        $sucess = $this->updateToBilled($order, $orders_complete_in_loja_integrada);

        if (!$this->store_object['freight_seller']) {
            echo ("Cliente não usa frete proprio.\n");
            return;
        }
        $this->setToTrakingOrder($order, $orders_complete_in_loja_integrada);

        $sucess = $this->updateToSent($order, $orders_complete_in_loja_integrada);
        if ($sucess) {
            echo ("Atualização de status realizada com sucesso.\n");
        }
        $sucess = $this->updateToConcluded($order, $orders_complete_in_loja_integrada);
        if ($sucess) {
            echo ("Produto atualizado para concluido.\n");
        }
    }
    private function canSetTrakingData($order, $orders_complete_in_loja_integrada)
    {
        if (!in_array($order["status"]["code"], [$this->model_orders->PAID_STATUS['invoice_status'], $this->model_orders->PAID_STATUS['nfe_sent_to_marketplace'], $this->model_orders->PAID_STATUS['wait_tracking']])) {
            return false;
        }
        if ($order["status"]["code"] != $this->model_orders->PAID_STATUS['wait_tracking']) {
            if (
                !$this->has_error
            ) {
                echo ("Este pedido não pode ter o codigo de rastreio inserido. Status do pedido:{$order['status']['code']}\nStatus esperado: {$this->model_orders->PAID_STATUS['wait_tracking']}\n");
                $this->has_error = true;
            }
            return false;
        }
        if ($orders_complete_in_loja_integrada["envios"][0]["objeto"]) {
            return true;
        }
        echo ("Loja integrada ainda não disponibilizou o codigo de rastreio para este pedido.\n");
        $this->has_error = true;
        return false;
    }
    private function setToTrakingOrder($order, $orders_complete_in_loja_integrada)
    {
        if (!$this->canSetTrakingData($order, $orders_complete_in_loja_integrada)) {
            return;
        }
        $traking = $orders_complete_in_loja_integrada["envios"][0];
        $this->rest_request->setHeaders($this->header_opt);
        $url = $this->url_conectala . '/Api/V1/Tracking/' . $order['code'];
        $response = $this->rest_request->setUp($url, 'GET', null);
        $response = $this->rest_request->send();
        $response = json_decode($this->rest_request->response, true);
        if ($response["success"] && !isset($response["result"]["error"])) {
            echo ("Pedido já contem codigo de rastreio.\n");
            return true;
        }
        // http://teste.conectala.com.br/app/Api/V1/Tracking/{{Order}}
        $traking_data = [
            'tracking' => [
                'date_tracking' => $traking["data_criacao"],
                "items" => [],
                'track' => [
                    "carrier" => "CORREIOS",
                    "carrier_cnpj" => "",
                    "url" => "https://www2.correios.com.br/sistemas/rastreamento/default.cfm"
                ]
            ]
        ];
        foreach ($order["items"] as $item) {
            $item_data = [
                'sku' => $item["sku"],
                'qty' => $item["qty"],
                'code' => $traking["objeto"],
                'method' => "PAC",
                'service_id' => $traking['id'],
                'value' => $traking["valor"],
                'delivery_date' => "",
                'url_label_a4' => "",
                'url_label_thermic' => "",
                'url_label_zpl' => "",
                'url_plp' => "",
            ];
            $traking_data['tracking']['items'][] = $item_data;
        }
        echo ("traking_data:" . json_encode($traking_data) . "\n");
        $this->rest_request->setHeaders($this->header_opt);
        $url = $this->url_conectala . '/Api/V1/Tracking/' . $order['code'];
        echo ($url . "\n");
        $response = $this->rest_request->setUp($url, 'POST', json_encode($traking_data));
        $response = $this->rest_request->send();
        $response = json_decode($this->rest_request->response, true);
        echo (__FUNCTION__ . "\n status: " . $this->rest_request->code . "\n response: " . json_encode($response) . "\n");
        if ($this->rest_request->code != 201) {
            return false;
        }
        $this->log_integration("Sucesso na sincronização do pedido.", "Inserido codigo de rastreio({$traking["objeto"]}) no pedido({$order['code']}) na Conecta Lá", 'S');
        return true;
    }
    private function getOrderToIntegrationById($order_id)
    {
        $this->rest_request->setHeaders($this->header_opt);
        $response = $this->rest_request->setUp($this->url_conectala . '/Api/V1/Orders/' . $order_id, 'GET', null);
        $this->rest_request->send();
        $response = json_decode($this->rest_request->response, true);
        if (!$response["success"]) {
            echo (__FUNCTION__ . "\n status: " . $this->rest_request->code . "\n response: " . json_encode($response) . "\n");
            return false;
        }
        return $response["result"];
    }
    /**
     * 
     */
    private function updateToBilled(&$order, $orders_complete_in_loja_integrada)
    {
        if (isset($order["invoice"])) {
            echo ("Pedido já contem nota fiscal, indo para proxima checagem\n");
            return false;
        }
        $url = $this->url . "/v1/pedido_nf/" . $orders_complete_in_loja_integrada["numero"] . "?{$this->params}";
        $this->rest_request->setUp($url, "GET");
        $sucess = $this->rest_request->send();
        if (!$sucess) {
            echo ("Falha na requisição da nota fiscal à Loja Integrada, indo para o proximo pedido.\n");
            $this->log_integration("Falha para sincronização do pedido.", "Pedido({$order['code']}) - Falha na requisição da nota fiscal à Loja Integrada", 'E');
            return false;
        }
        $order_nf = json_decode($this->rest_request->response, true);
        // $order_nf = json_decode($this->rest_request->response, true);
        if (empty($order_nf)) {
            echo ("Nota fiscal ainda não disponivel.\nPedido: {$orders_complete_in_loja_integrada['id_anymarket']}\n");
            $this->log_integration("Falha para sincronização do pedido.", "Pedido({$order['code']}) - Nota fiscal ainda não disponivel.", 'W');
            $this->has_error = true;
            return false;
        }
        $nfe = [
            'nfe' => [
                [
                    "order_number" => $order["code"],
                    "invoce_number" =>  $order_nf["numero"],
                    "price" => $order["payments"]["gross_amount"],
                    "serie" => $order_nf["serie"],
                    "access_key" => $order_nf["access_key"],
                    "emission_datetime" => (new DateTime($order_nf["data"]))->format('d/m/Y H:i:s'),
                ]
            ]
        ];
        echo (__FUNCTION__ . " " . json_encode($nfe) . "\n");
        $this->rest_request->setHeaders($this->header_opt);
        echo ($this->url_conectala . '/Api/V1/Orders/nfe' . "\n");
        echo (json_encode($nfe) . "\n");
        $response = $this->rest_request->setUp($this->url_conectala . '/Api/V1/Orders/nfe', 'POST', json_encode($nfe));
        $response = $this->rest_request->send();
        $response = json_decode($this->rest_request->response, true);
        echo (__FUNCTION__ . "\n status: " . $this->rest_request->code . "\n response: " . json_encode($response) . "\n");
        if ($this->rest_request->code != 201) {
            return false;
        }
        $this->log_integration("Sucesso na sincronização do pedido.", "Pedido({$order['code']}) marcado como faturado na Conecta Lá", 'S');
        if ($sucess) {
            $order = $this->getOrderToIntegrationById($orders_complete_in_loja_integrada["id_anymarket"]);
            if (!$order) {
                echo ("Falha, pedido não encontrado.\n");
                return;
            }
            $order = $order['order'];
        }
        return true;
    }
    public function updateToSent(&$order, $orders_complete_in_loja_integrada)
    {
        $order_shipping = $orders_complete_in_loja_integrada["envios"][0];
        if (!$this->canUpdateToSent($orders_complete_in_loja_integrada, $order)) {
            return false;
        }
        $shipp_data = [
            'shipment' =>
            [
                "shipped_date" => (new DateTime($order_shipping["data_modificacao"]))->format('d/m/Y H:i:s'),
            ]
        ];
        echo (__FUNCTION__ . " " . json_encode($shipp_data) . "\n");
        $this->rest_request->setHeaders($this->header_opt);
        $url = $this->url_conectala . '/Api/V1/Orders/' . $order['code'] . "/shipped";
        echo ($url . "\n");
        echo (json_encode($shipp_data) . "\n");
        $response = $this->rest_request->setUp($url, 'PUT', json_encode($shipp_data));
        $response = $this->rest_request->send();
        $response = json_decode($this->rest_request->response, true);
        echo (__FUNCTION__ . "\n status: " . $this->rest_request->code . "\n response: " . json_encode($response) . "\n");
        if ($this->rest_request->code != 200) {
            return false;
        }
        $this->log_integration("Sucesso na sincronização do pedido.", "Pedido({$order['code']}) marcado como enviado na Conecta Lá", 'S');
        return true;
    }
    public function updateToConcluded(&$order, $orders_complete_in_loja_integrada)
    {
        if (!$this->canUpdateToConcluded($orders_complete_in_loja_integrada, $order)) {
            return false;
        }
        $shipp_data = [
            'shipment' =>
            [
                "delivered_date" => (new DateTime($orders_complete_in_loja_integrada["data_modificacao"]))->format('d/m/Y H:i:s'),
            ]
        ];
        echo (__FUNCTION__ . " " . json_encode($shipp_data) . "\n");
        $this->rest_request->setHeaders($this->header_opt);
        $url = $this->url_conectala . '/Api/V1/Orders/' . $order['code'] . "/delivered";
        echo ($url . "\n");
        echo (json_encode($shipp_data) . "\n");
        $response = $this->rest_request->setUp($url, 'PUT', json_encode($shipp_data));
        $response = $this->rest_request->send();
        $response = json_decode($this->rest_request->response, true);
        echo (__FUNCTION__ . "\n status: " . $this->rest_request->code . "\n response: " . json_encode($response) . "\n");
        if ($this->rest_request->code != 201) {
            return false;
        }
        $this->log_integration("Sucesso na sincronização do pedido.", "Pedido marcado como concluido na Conecta Lá", 'S');
        return true;
    }
    public function canUpdateToSent($orders_complete_in_loja_integrada, $order)
    {
        if (in_array($order["status"]["code"], [$this->model_orders->PAID_STATUS['wait_to_send_to_marketplace_sent'], $this->model_orders->PAID_STATUS['wait_to_set_conclude']])) {
            return false;
        }
        if (!in_array($orders_complete_in_loja_integrada["situacao"]["codigo"], ["pedido_enviado", "pedido_entregue"])) {
            if (!$this->has_error) {
                echo ("Este pedido não pode ser atualizado para enviado. Status loja integrada {$orders_complete_in_loja_integrada["situacao"]["codigo"]}\n");
                $this->has_error = true;
            }
            return false;
        }
        if ($order["status"]["code"] != $this->model_orders->PAID_STATUS['billed_on_marketplace']) {
            if (!$this->has_error) {
                echo ("Este pedido não pode ser atualizado para Enviado. Status do pedido:{$order['status']['code']}\nStatus esperado: {$this->model_orders->PAID_STATUS['billed_on_marketplace']}\n");
                $this->has_error = true;
            }
            return false;
        }
        return true;
    }
    public function canUpdateToConcluded($orders_complete_in_loja_integrada, $order)
    {
        if (!in_array($orders_complete_in_loja_integrada["situacao"]["codigo"], ["pedido_entregue"])) {
            if (!$this->has_error) {
                echo ("Este pedido não pode ser atualizado para concluido. Status loja integrada {$orders_complete_in_loja_integrada["situacao"]["codigo"]}\n");
                $this->has_error = true;
            }
            return false;
        }
        if ($order["status"]["code"] != $this->model_orders->PAID_STATUS['sent_on_marketplace']) {
            if (!$this->has_error) {
                echo ("Este pedido não pode ser atualizado para concluido. Status do pedido:{$order['status']['code']}\nStatus esperado: {$this->model_orders->PAID_STATUS['sent_on_marketplace']}\n");
                $this->has_error = true;
            }
            return false;
        }
        return true;
    }
}
