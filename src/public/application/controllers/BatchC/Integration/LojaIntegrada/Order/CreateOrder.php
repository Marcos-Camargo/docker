<?php
require_once APPPATH . "controllers/BatchC/Integration/Integration.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/ParserData.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/LojaIntegradaOrderFields.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/LojaIntegradaOrderItensFields.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/AdjustDocumentNumber.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/ConvertStatusLojaIntegrada.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/LoadApiKeys.trait.php";
require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Traits/LoadIntegrationUser.php";
require_once "application/libraries/CalculoFrete.php";

require_once APPPATH . "controllers/BatchC/Integration/LojaIntegrada/Utils/RestRequestLI.php";

class CreateOrder extends Integration
{
    use LojaIntegradaOrderFields;
    use ParserData;
    use AdjustDocumentNumber;
    use LojaIntegradaOrderItensFields;
    use ConvertStatusLojaIntegrada;
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
            'userstore' => 0,
            'logged_in' => true,
        );
        $this->session->set_userdata($logged_in_sess);

        //        echo"\nL_27";
        $this->setTypeIntegration("LojaIntegrada");
        $this->setJob('CreateOrder');
        $this->load->model('model_products');
        $this->load->model('model_orders');
        $this->load->model('model_orders_item');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_clients');
        $this->load->model('model_api_integrations');

        $this->rest_request = new RestRequestLI();
        $this->_this = $this;
        $this->frete = new CalculoFrete();
		
		 $this->setJob('CreateOrder');
    }
    // php index.php BatchC/Integration/LojaIntegrada/Order/CreateOrder run null 63
    public function run($id = null, $store = null)
    {
        //   echo"\nL_37";
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$id || !$store) {
            echo "\nL_41_id: " . $id . " store: " . $store . "\n";
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
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
            // Define a loja, para recuperar os dados para integração
        } catch (Throwable $e) {
            $this->log_integration("Erro executar job de criação de pedidos", $e->getMessage(), 'E');
        }

        // Grava a última execução
        $this->saveLastRun();
        
		// deleta o cookie
		unlink($this->rest_request->cookiejar);
		
        // encerra o job
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }
    public function execution($id = null, $store = null)
    {
        $this->store = $store;
        $this->store_object = $this->model_stores->getStoresData($store);
        $this->company = $this->store_object['company_id'];
        try {
            $this->loadApiKey($this->store);
        } catch (Exception $e) {
            $this->log_integration("Erro ao carregar configurações de integração", $e->getMessage(), 'E');
            return;
        }
        echo "Pegando pedidos para enviar... \n";
        $orders_to_integration = $this->getAllOrderToIntegrationByStore($store);
        if (!$orders_to_integration) {
            return;
        }
        foreach ($orders_to_integration as $order_to_integration) {
            $this->setUniqueId($order_to_integration['order_code']);
            /*$logistic = $this->frete->getLogisticStore(array(
                'freight_seller'       => $this->store_object['freight_seller'],
                'freight_seller_type'  => $this->store_object['freight_seller_type'],
                'store_id'             => $this->store_object['id']
            ));
            if ($logistic['seller']) {
                $msg = "Não é possivel realizar integração de pedido pois o seller faz uso de logistica propria.\n";
                echo $msg;
                $this->log_integration(
                    "Erro ao processar pedido",
                    sprintf("%s</br>Order: %s</br>Logistic:%s", $msg, json_encode($order_to_integration), json_encode($logistic)),
                    'E'
                );
                $response = $this->cleanQeuen($order_to_integration);
                echo (json_encode($response) . "\n");
                continue;
            }*/
            try {
                $success = $this->sendOrder($order_to_integration);
                if ($success) {
                    $response = $this->cleanQeuen($order_to_integration);
                    echo(json_encode($response) . "\n");
                }
            } catch (Throwable $e) {
                $this->log_integration("Erro ao enviar pedido {$order_to_integration['order_code']}", $e->getMessage(), 'E');
            }
            // }
        }
    }
    private function cleanQeuen($order_to_integration)
    {
        $this->rest_request->setHeaders($this->header_opt);
        $this->rest_request->setUp($this->url_conectala . '/Api/V1/Orders/' . $order_to_integration["order_code"], 'DELETE', null);
        $this->rest_request->send();

        $response = json_decode($this->rest_request->response, true);
        echo (json_encode($response) . "\n");
        if (!$response["success"]) {
            return false;
        }
        echo ($response["message"] . "\n");
        return $response["message"];
    }
    private function getAllOrderToIntegrationByStore($store)
    {
        // $this->store_object = $this->model_stores->getStoresData($store);
        $this->integration = $this->model_api_integrations->getDataByStore($this->store_object['id']);
        $this->integration = $this->integration[0];
        $this->user = $this->loadApiIntegrationUserByStore($this->store_object);
        echo "Dados Usuário:\n";
        print_r($this->user);
        $this->header_opt = [
            'x-user-email: ' . $this->user['email'],
            'x-api-key: ' . $this->store_object["token_api"],
            'x-store-key: ' . $this->store_object['id'],
        ];
        $this->rest_request->setHeaders($this->header_opt);

        $this->rest_request->setUp("{$this->url_conectala}/Api/V1/Orders?only_new_paid=true", 'GET', null);
        $this->rest_request->send();
        $response = json_decode($this->rest_request->response, true);
        echo (json_encode($response) . "\n");
        if (!$response["success"]) {
            if ($response['message'] !== 'No results were found') {
                $this->log_integration(
                    "Erro ao obter pedidos",
                    sprintf("%s", $this->rest_request->response),
                    'E'
                );
            }
            return false;
        }
        return $response["result"];
    }
    private function getOrderToIntegrationById($order_id)
    {
        $this->rest_request->setHeaders($this->header_opt);
        $response = $this->rest_request->setUp($this->url_conectala . '/Api/V1/Orders/' . $order_id, 'GET', null);
        $this->rest_request->send();
        $response = json_decode($this->rest_request->response, true);
        echo (json_encode($response) . "\n");
        if (!$response["success"]) {
            return false;
        }
        if (isset($response["result"])) {
            return $response["result"];
        }
        return false;
    }
    public function sendOrder($order_to_integration)
    {
        $order = $this->getOrderToIntegrationById($order_to_integration["order_code"]);
        if (!$order) {
            return false;
        }
        $orderDB = $this->model_orders->getOrderByIdAndStore(
            $order_to_integration["order_code"],
            $this->store_object['id']
        );
        if (!empty($orderDB['order_id_integration'])) {
            $this->log_integration(
                "Pedido {$order_to_integration["order_code"]} já integrado",
                sprintf(
                    "Pedido %s já integrado com o id %s na loja integrada.",
                    $order_to_integration["order_code"],
                    $orderDB['order_id_integration']
                ),
                'W'
            );
            echo "Pedido {$order_to_integration["order_code"]} já integrado {$orderDB['order_id_integration']}.";
            return true;
        }
        $order = $order['order'];
        if (!in_array((int)$order["status"]["code"], CreateOrder::statusToSendOrder())) {
            echo "Pedido {$order_to_integration["order_code"]} com status {$order["status"]["code"]} não integrado.";
            return true;
        }
        if ($order["customer"]["cpf"]) {
            $order["customer"]["cpf_cnpj"] = $order["customer"]["cpf"];
            $order['customer']['type'] = 'CPF';
        } else {
            $order["customer"]["cpf_cnpj"] = $order["customer"]["cnpj"];
            $order['customer']['type'] = 'CNPJ';
        }

        $order['customer']['phones'][0] = preg_replace('/[^0-9]/', '', $order['customer']['phones'][0]);
        $order['customer']['phones'][1] = preg_replace('/[^0-9]/', '', $order['customer']['phones'][1]);
        $order['billing_address']['phone'] = preg_replace('/[^0-9]/', '', $order['billing_address']['phone']);

        $order['ship_option'] = $order["shipping"]["shipping_carrier"] . '-' . $order["shipping"]["service_method"];
        $order['reference'] = 'CONECTALA/' . $order["system_marketplace_code"];
        $order['paid_status'] = $this->convertStatusToLojaIntegrada($order["status"]["code"]);
        $orderLojaIntegrada = $this->converteData($this->getOrderFields(), $order);
        $orderLojaIntegrada['items'] = [];
        foreach ($order["items"] as $item) {
            $product = $this->model_products->getProductData(0, $item["product_id"]);
            if ($product === null) {
                echo ("Produto dentro do pedido não conhecido pelo sistema.\n");
                return false;
            }
            $item['product_id_erp'] = $product['product_id_erp'];
            $item['amount'] = $item["original_price"] * $item["qty"];
            $item2 = $this->converteData($this->getOrderItensFields(), $item);
            if (isset($item["variant_order"])) {
                $variant = $this->model_products->getVariantsByProd_idAndVariant($item["product_id"], $item["variant_order"]);
                $item2["product_id"] = $variant["variant_id_erp"];
            }
            $orderLojaIntegrada['items'][] = $item2;
        }
        $orderLojaIntegrada['integration_data'] = [
            'integrator' => 'Conecta Lá',
            'marketplace' => $order["system_marketplace_code"],
            'external_id' => $order_to_integration["order_code"]
        ];
        $this->rest_request->setHeaders([
            "Authorization: chave_api {$this->chave_api} aplicacao {$this->chave_aplicacao}",
        ]);
        // echo("")
        echo ($this->url . "/v1/integration/sales" . "\n");
        echo ("Dados enviados" . json_encode($orderLojaIntegrada, JSON_UNESCAPED_UNICODE) . "\n");
        echo ("chave_api {$this->chave_api} aplicacao {$this->chave_aplicacao}\n");
        $this->rest_request->setUp($this->url . "/v1/integration/sales", 'POST', json_encode($orderLojaIntegrada, JSON_UNESCAPED_UNICODE));
        $response = $this->rest_request->send();
        if (!$response || $this->rest_request->code != 201) {
            echo ("Falha no retorno vindo da API \nCode: {$this->rest_request->code}\nResponse:{$this->rest_request->response}\n");
            $this->log_integration(
                "Falha no envio do pedido {$order_to_integration["order_code"]}",
                sprintf(
                    "Código: %s</br>Retorno: %s",
                    $order_to_integration["order_code"],
                    $this->rest_request->response
                ),
                'E'
            );
            return false;
        }
        echo ("Response: " . json_encode($this->rest_request->response, JSON_UNESCAPED_UNICODE) . "\n");
        $response = json_decode($this->rest_request->response, true);
        echo ("Code: " . $this->rest_request->code . "\n");
        $this->log_integration(
            "Sucesso no envio do pedido {$order_to_integration["order_code"]}",
            sprintf(
                "Pedido %s integrado com o id %s na loja integrada.",
                $order_to_integration["order_code"],
                $response['id']
            ),
            'S'
        );
        $this->model_orders->updateOrderById($order_to_integration["order_code"], ['order_id_integration' => $response['id']]);
        return true;
    }

    public static function statusToSendOrder()
    {
        return [
            OrderStatusConst::WAITING_INVOICE,
            OrderStatusConst::WAITING_TRACKING,
            OrderStatusConst::WAITING_SHIPPING,
            OrderStatusConst::SHIPPED_IN_TRANSPORT,
            OrderStatusConst::WITH_TRACKING_WAITING_SHIPPING,
            OrderStatusConst::SHIPPED_IN_TRANSPORT_45,
            OrderStatusConst::INVOICED_WAITING_TRACKING,
            OrderStatusConst::PROCESSING_INVOICE,
            OrderStatusConst::WAITING_SHIPPING_TO_TRACKING,
        ];
    }
}
