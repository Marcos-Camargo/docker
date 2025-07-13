<?php

require APPPATH . "controllers/BatchC/Integration/Integration.php";
require APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ParserOrderAnymarket.php";
class UpdateStatus extends Integration
{
    private $dataStore;
    private $url_anymarket;

    public function __construct()
    {
        parent::__construct();
        $this->setTypeIntegration('AnyMarket');

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => true,
        );
        $this->session->set_userdata($logged_in_sess);

        $this->setJob('UpdateStatus');
        $this->load->model('model_api_integrations');
        $this->load->model('model_orders');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_anymarket_order_to_update');
        $this->load->model('model_nfes');
        $this->load->model('model_freights');
        $this->load->model('model_settings');
        $this->load->library('calculoFrete');
        $this->url_anymarket = $this->model_settings->getValueIfAtiveByName('url_anymarket');
        if (!$this->url_anymarket) {
            throw new Exception("\'url_anymarket\' não está definido no sistema");
        }
    }

    /*
     * php index.php BatchC/Integration/AnyMarket/Order/UpdateStatus run null null
     * order(post-any)->initialImportDate(get-conecta)->order status(PUT-any)
     *
     * updateOrderStatusInMarketPlace(put-conectala)->order by id(get-any)
     */
    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }
		$this->store = $store;

        /* inicia o job */
        $this->setIdJob($id);
        $store_array     = $this->model_stores->getStoresById($store);

        if (!$store_array) {
            echo "Loja {$store} não encontrada\n";
            $this->log_data('batch', $log_name, 'finish', "I");
            return;
        }

        $this->dataStore = $store_array;
        $this->company   = $store_array['company_id'];
        $modulePath      = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();

        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            echo ("'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store");
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        // Define a loja, para recuperar os dados para integração
        $this->startUpdateOrders($store);

        // Recupera os pedidos

        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    private function getIntegration(int $store)
    {
        echo "Pegando dados para integração\n";
        $integration = $this->model_api_integrations->getUserByAnyByStore($store);
        $credentiais = json_decode($integration["credentials"], true);
        if (!isset($credentiais['token_anymarket'])) {
            return "token_anymarket não definida para este usuario, por favor solicite a devida configuração no site da anymarket.\nLoja: {$store}";
        }
        $this->setToken($credentiais['token_anymarket']);
        $app_id_anymarket = $this->model_settings->getValueIfAtiveByName('app_id_anymarket');
        if (!$app_id_anymarket) {
            return "appID não definida para este usuario, por favor solicite a devida configuração no site da anymarket.\nLoja: {$store}";
        }
        $this->setAppKey($app_id_anymarket);
        if (!$integration) {
            echo "Mais de uma integração Anymarket para esta loja.\nLoja: {$store}";
            return false;
        }
        echo "Inciando procedimento para a integração na loja {$store} com OI AnyMarket {$integration['id_anymarket_oi']}\n";
        return true;
    }

    private function startUpdateOrders(int $store)
    {
        $response = $this->getIntegration($store);
        if ($response !== true) {
            $this->log_integration($response, "<h4>Parametros de configuração ausentes.</h4><p>{$response}</p>", "E");
            echo ($response . "\n");
            return;
        }

        $this->store = $store;
        $store_array = $this->model_stores->getStoresById($store);
        $this->company = $store_array['company_id'];
        $orders_to_update = $this->model_anymarket_order_to_update->getNewOrders($this->company, $store);

        // ler status de pedido que vieram por api da anymarket
        foreach ($orders_to_update as $order) {
            echo "Pedido {$order['order_id']}\n";

            if ($this->updateOrderTableAnymarket($order)) {
                $this->model_anymarket_order_to_update->setIntegrated($order['id']);
            }
        }

        // ler status de pedido atualizados dentro do seller center
        $ordersToIntegration = $this->model_orders_to_integration->getOrdersByStoreToSend($store);
        foreach ($ordersToIntegration as $order) {
            echo "Pedido {$order['order_id']}\n";

            if ($this->updateOrderTableToIntegration($order)) {
                $this->model_orders_to_integration->delete($order['id']);
            }
        }
    }

    private function updateOrderTableToIntegration(array $order): bool
    {
        $this->setUniqueId($order["order_id"]);

        $order_in_db        = $this->model_orders->getOrdersData(0, $order["order_id"]);

        $url                = "{$this->url_anymarket}orders/{$order["order_id"]}";
        $response           = $this->sendREST($url);

        if ($response['httpcode'] != 200) {
            echo "Não foi possível recuperar dados do pedido {$order["order_id"]} na integradora.\nurl_request={$url}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
            return false;
        }

        $body       = json_decode($response["content"], true);
        $logistic   = $this->calculofrete->getLogisticStore(array(
            'freight_seller' 		=> $this->dataStore['freight_seller'],
            'freight_seller_type' 	=> $this->dataStore['freight_seller_type'],
            'store_id'				=> $this->dataStore['id']
        ));

        $withAnymarket      = $logistic['seller'] && $logistic['type'] === 'anymarket';
        $withoutAnymarket   = !$logistic['seller'] || $logistic['type'] !== 'anymarket';

        if (!$order_in_db['order_id_integration']) {
            echo "Pedido {$order["order_id"]} não tem relacionamento com nenhum pedido. orders.order_id_integration está vazio.\n";
            return true;
        }

        if ($body['status'] === 'CANCELED' && !in_array($order_in_db['paid_status'], array(95,96,97,98,99))) {
            $this->log_integration("Não é possível cancelar um pedido via Anymarket",
                "O pedido {$order["order_id"]} não pode ser cancelado via ANYMARKET, é necessário que entre em contato com o marketplace",
                "E"
            );
            $url_confirm = "{$this->url_anymarket}orders/{$order["order_id"]}/transmissionStatus";
            $confirm_data = [
                "marketPlaceStatus" => $order['old_status'],
                "success" => "false",
                "errorMessage" => "O pedido não pode ser cancelado via ANYMARKET, é necessário que entre em contato com o marketplace",
            ];
            $this->sendREST($url_confirm, json_encode($confirm_data), 'PUT');
            return true;
        }

        // Pedido para cancelar
        $orderCancel = $this->model_orders_to_integration->getOrderCancel($order["order_id"], $this->store);
        if ($orderCancel || in_array($order_in_db['paid_status'], array(95,96,97))) {
            //cancelar na integradora
            $msgError = "Pedido deve ser cancelado. PEDIDO={$order["order_id"]}. ORDER_INTEGRATION=".json_encode($order);
            echo "{$msgError}\n";

            $data = [
                'date' => date("Y-m-d\TH:i:sP", strtotime($order_in_db["date_cancel"] ?? date('Y-m-d H:i:s'))),
                "code" => 'BUYER_CANCELED',
            ];

            $url        = "{$this->url_anymarket}orders/{$order["order_id"]}/markAsCanceled";
            $response   = $this->sendREST($url, json_encode($data), 'PUT');

            // não foi possível atualizar pedido
            if ($response['httpcode'] != 200) {
                $payloadBody = json_encode($data);
                echo "[ERROR] Não foi possível cancelar pedido {$order["order_id"]} na integradora.\nurl_request={$url}\npayload_body={$payloadBody}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
                return false;
            }

            echo json_encode($response) . "\n";

            $this->log_integration("Pedido {$order["order_id"]} atualizado para Cancelado.", "<h4>Pedido {$order["order_id"]} cancelado com sucesso.</h4><p>" . json_encode($response) . "</p>", "S");
            $this->model_orders_to_integration->removeAllOrderIntegration($order["order_id"], $this->store);

            return true;
        }

        if (!in_array($order["paid_status"], array(3,5,6,40,43,45,53))) {
            echo "status {$order["paid_status"]} não mapeado para realizar alguma ação. Será ignorado\n";

            $this->model_orders_to_integration->delete($order['id']);
            return true;
        }

        // pedido com NFe
        if ($order["paid_status"] == 3) {

            $nfe_to_order = $this->model_nfes->getNfesDataByOrderId($order['order_id'], true);

            if (count($nfe_to_order) != 0) {
                $this->model_orders->updateStatusForOrder($order['order_id'], $order['store_id'], 52, 3);
                echo "Pedido {$order['order_id']} está com nota fiscal\n";
                return true;
            }

            // pedido ainda sem nota fiscal
            if (!isset($body["invoice"])) {
                echo "Pedido {$order["order_id"]} ainda sem nota fiscal.\n";
                return false;
            }

            // grava nfe
            $responseNfe = $this->setNfeOrder($body["invoice"], $order_in_db);

            // já existia uma nfe no pedido
            if ($responseNfe !== false) {
                echo "Pedido {$order["order_id"]} atualizado para Faturado.\n";
                $this->log_integration("Pedido {$order["order_id"]} atualizado para Faturado.", $responseNfe, "S");
            }

            return true;
        }

        // pedido com rastreio ou enviar rastreio
        if (in_array($order["paid_status"], array(40,53))) {

            // logística não é da anymarket, então não precisamos trazer dado de rastreio de lá, somente enviar.
            if ($order["paid_status"] == 53 && ($withoutAnymarket)) {

                $freight = $this->model_freights->getFreightsDataByOrderId($order['order_id']);

                // pedido sem frete, não deveria acontecer
                if (count($freight) === 0) {
                    echo "Pedido {$order["order_id"]} asem rastreio não deveria acontecer. ERROR_CRITICAL\n";
                    $this->log_integration("Pedido {$order["order_id"]} não atualizado", "<p>Não foi encontrado rastreio para atualizar o pedido.</p>", "E");
                    return false;
                }

                $freight = $freight[0];

                $urlSendTracking = "{$this->url_anymarket}orders/{$order["order_id"]}";
                $bodySendTracking = [
                    'tracking' => [
                        "url"           => $freight["url_tracking"],
                        "number"        => $freight["codigo_rastreio"],
                        "carrier"       => $freight["ship_company"],
                        "estimateDate"  => date('Y-m-d\TH:i:sP', strtotime($freight['prazoprevisto'])),
                    ]
                ];
                $response = $this->sendREST($urlSendTracking, json_encode($bodySendTracking), 'PUT');

                // não foi possível atualizar pedido
                if ($response['httpcode'] != 200) {
                    $payloadBody = json_encode($bodySendTracking);
                    echo "[ERROR] Não foi possível atualizar rastreio do pedido {$order["order_id"]} na integradora.\nurl_request={$urlSendTracking}\npayload_body={$payloadBody}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
                    return false;
                }

                $this->log_integration("Pedido {$order["order_id"]} atualizado para Com Rastreio.", "<h4>Foi enviado dados de rastreio para o pedido {$order["order_id"]}</h4> 
                          <ul>
                            <li><strong>Código de rastreio:</strong> {$freight["codigo_rastreio"]}</li>
                            <li><strong>Transportadora:</strong> {$freight["ship_company"]}</li>
                            <li><strong>Previsão de entrega:</strong> ".date('d/m/Y', strtotime($freight['prazoprevisto']))."</li>
                          </ul>", "S");
                echo "Pedido {$order["order_id"]} enviou dados de rastreio.\n";
            }
            // logística do seller com anymarket, então iremos buscar os dados de rastreio
            elseif ($order["paid_status"] == 40 && $withAnymarket) {

                // pedido ainda sem dado de rastreio
                if (!isset($body["tracking"])) {
                    echo "Pedido {$order["order_id"]} ainda sem rastreio.\n";
                    return false;
                }

                //valida dados obrigatorios
                if (empty($body["tracking"]['url']) ||
                    empty($body["tracking"]['number']) ||
                    (empty($body["tracking"]['carrier']) && empty($order_in_db['ship_company_preview'])) ||
                    empty($body["tracking"]['estimateDate'])
                ) {
                    $message = array();
                    if (empty($body["tracking"]['url'])) {
                        array_push($message, 'URL de rastreamento precisa ser preenchida.');
                    }
                    if (empty($body["tracking"]['number'])) {
                        array_push($message, 'Código de rastreamento precisa ser preenchido');
                    }
                    if (empty($body["tracking"]['carrier']) && empty($order_in_db['ship_company_preview'])) {
                        array_push($message, 'Transportadora de entrega precisa ser preenchida');
                    }
                    if (empty($body["tracking"]['estimateDate'])) {
                        array_push($message, 'Data estiada de entrega precisa ser preenchida');
                    }
                    echo "Pedido {$order["order_id"]} não foi possível obter dadso de rastreio: ".implode(', ', $message)."\n";
                    $this->log_integration("Pedido {$order["order_id"]} não foi atualizado", "<h4>Não foi possível obter os dados de rastreio do pedido {$order['order_id']}</h4>".implode('</br>', $message), "W");

                    return false;
                }

                // adicionar rastreio no pedido
                $responseTracking = $this->setTrackingOrder($body, $order_in_db);

                // já existia um rastreio no pedido
                if ($responseTracking !== false) {
                    $this->log_integration("Pedido {$order["order_id"]} atualizado para Com Rastreio.", $responseTracking, "S");
                    echo "Pedido {$order["order_id"]} recuperou dados de rastreio.\n";
                } else {
                    echo "Pedido {$order["order_id"]} já exister dados de rastreio.\n";
                }
            }

            return true;
        }

        // pedido com data de despacho
        if (in_array($order["paid_status"], array(43,5))) {
            // logística não é da anymarket, então não precisamos trazer a data de depacho de lá, somente enviar.
            if ($order["paid_status"] == 5 && ($withoutAnymarket)) {

                $urlSendShipped = "{$this->url_anymarket}orders/{$order["order_id"]}/markAsShipped";
                $bodySendShipped = [
                    "Date" => date('Y-m-d\TH:i:sP', strtotime($order_in_db['data_envio'] ?? date('Y-m-d H:i:s')))
                ];
                $response = $this->sendREST($urlSendShipped, json_encode($bodySendShipped), 'PUT');

                // não foi possível atualizar pedido
                if ($response['httpcode'] != 200) {
                    $payloadBody = json_encode($bodySendShipped);
                    echo "[ERROR] Não foi possível atualizar pedido {$order["order_id"]} para enviado na integradora.\nurl_request={$urlSendShipped}\npayload_body={$payloadBody}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
                    return false;
                }

                $this->log_integration("Pedido {$order["order_id"]} atualizado para Enviado.", "<h4>Atualizado status do pedido {$order_in_db['id']} para <b>Enviado</b> em ".date('d/m/Y H:i', strtotime($order_in_db['data_envio']))."</h4", "S");

                echo "Pedido {$order["order_id"]} enviou data de despacho/coleta.\n";
            } elseif ($order["paid_status"] == 43 && $withAnymarket) {

                // pedido ainda sem data de despacho/coleta
                if (!isset($body["tracking"]["shippedDate"])) {
                    echo "Pedido {$order["order_id"]} ainda sem data de despacho/coleta.\n";
                    return false;
                }

                $this->setShippedOrder($body, $order_in_db);
                $this->log_integration("Pedido {$order["order_id"]} atualizado para Enviado.", "<h4>Atualizado status do pedido {$order_in_db['id']} para <b>Enviado</b> em ".date('d/m/Y H:i', strtotime($body["tracking"]["shippedDate"]))."</h4", "S");
                echo "Pedido {$order["order_id"]} recuperou data de despacho/coleta.\n";
            }
            return true;
        }

        // pedido com data de entrega
        if (in_array($order["paid_status"], array(45,6))) {

            // logística não é da anymarket, então não precisamos trazer a data de depacho de lá, somente enviar.
            if ($order["paid_status"] == 6 && ($withoutAnymarket)) {
                $urlDelivered = "{$this->url_anymarket}orders/" . $order["order_id"] . "/markAsDelivered";
                $bodySendDevlivered = [
                    "deliveredDate"   => date('Y-m-d\TH:i:sP', strtotime($order_in_db['data_entrega'] ?? date('Y-m-d H:i:s')))
                ];
                $response = $this->sendREST($urlDelivered, json_encode($bodySendDevlivered), 'PUT');

                // não foi possível atualizar pedido
                if ($response['httpcode'] != 200) {
                    $payloadBody = json_encode($bodySendDevlivered);
                    echo "[ERROR] Não foi possível atualizar pedido {$order["order_id"]} para enviado na integradora.\nurl_request={$urlDelivered}\npayload_body={$payloadBody}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
                    return false;
                }

                $this->log_integration("Pedido {$order["order_id"]} atualizado para Entregue.", "<h4>Atualizado status do pedido {$order_in_db['id']} para <b>Entregue</b> em ".date('d/m/Y H:i', strtotime($order_in_db['data_entrega']))."</h4", "S");

                echo "Pedido {$order["order_id"]} enviou data de entrega.\n";
            } elseif ($order["paid_status"] == 45 && $withAnymarket) {

                // pedido ainda sem data de entrega
                if (!isset($body["tracking"]["deliveredDate"])) {
                    echo "Pedido {$order["order_id"]} ainda sem data de entrega.\n";
                    return false;
                }

                $this->setDeliveredOrder($body, $order_in_db);
                $order_in_db = $this->model_orders->getOrdersData(0, $order["order_id"]);

                $this->log_integration("Pedido {$order["order_id"]} atualizado para Entregue.", "<h4>Atualizado status do pedido {$order_in_db['id']} para <b>Entregue</b> em ".date('d/m/Y H:i', strtotime($body["tracking"]["deliveredDate"]))."</h4", "S");

                echo "Pedido {$order["order_id"]} recuperou data de entrega.\n";
            }
        }

        return true;
    }

    private function updateOrderTableAnymarket(array $order): bool
    {
        $this->setUniqueId($order["order_id"]);

        $orderId            = $order["order_id"];
        $realized_operation = false;
        $statusToRemove     = null;
        $order_in_db        = $this->model_orders->getOrdersData(0, $order["order_id"]);
        $url                = "{$this->url_anymarket}orders/{$orderId}";
        $response           = $this->sendREST($url);

        if ($response['httpcode'] != 200) {
            echo "Não foi possível recuperar dados do pedido {$orderId} na integradora.\nurl_request={$url}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
            return false;
        }

        $body       = json_decode($response["content"], true);
        $logistic   = $this->calculofrete->getLogisticStore(array(
            'freight_seller' 		=> $this->dataStore['freight_seller'],
            'freight_seller_type' 	=> $this->dataStore['freight_seller_type'],
            'store_id'				=> $this->dataStore['id']
        ));

        $withoutAnymarket = !$logistic['seller'] || $logistic['type'] !== 'anymarket';

        if (!empty($order['order_id_integration'])) {
            echo "Pedido {$orderId} não tem relacionamento com nenhum pedido. orders.order_id_integration está vazio.\n";
            return true;
        }

        if ($order['new_status'] == 'CANCELED') {
            $this->log_integration("Não é possível cancelar um pedido via Anymarket",
                "O pedido {$order["order_id"]} não pode ser cancelado via ANYMARKET, é necessário que entre em contato com o marketplace",
                "E"
            );
            $url_confirm = "{$this->url_anymarket}orders/{$order["order_id"]}/transmissionStatus";
            $confirm_data = [
                "marketPlaceStatus" => $order['old_status'],
                "success" => "false",
                "errorMessage" => "O pedido não pode ser cancelado via ANYMARKET, é necessário que entre em contato com o marketplace",
            ];

            $resp = $this->sendREST($url_confirm, json_encode($confirm_data), 'PUT');
            return $resp['httpcode'] == 200;
        }

        echo ("\n\nRespose orders: " . $response["content"] . "\n\nisset:" . (isset($body["invoice"]) ? 'true' : 'false') . "\n");

        // pedido cancelado, deverá cancelar o pedido na integradora
        $orderCancel = $this->model_orders_to_integration->getOrderCancel($order_in_db['id'], $order_in_db['store_id']);
        if ($orderCancel || in_array($order_in_db['paid_status'], array(95,96,97))) {
            echo "Pedido {$orderId} cancelado, iniciando rotina de comunicação do cancelamento.\n";
            $data = [
                'date' => date("Y-m-d\TH:i:sP", strtotime($order_in_db["date_cancel"] ?? date('Y-m-d H:i:s'))),
                "code" => 'BUYER_CANCELED',
            ];

            $url = "{$this->url_anymarket}orders/{$order_in_db['id']}/markAsCanceled";
            $response = $this->sendREST($url, json_encode($data), 'PUT');

            // não foi possível atualizar pedido
            if ($response['httpcode'] != 200) {
                $payloadBody = json_encode($data);
                echo "[ERROR] Não foi possível atualizar dados do pedido {$orderId} na integradora.\nurl_request={$url}\npayload_body={$payloadBody}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
                return false;
            }

            $this->log_integration("Pedido {$orderId} atualizado para Cancelado.", "<h4>Pedido {$orderId} cancelado com sucesso.</h4><p>" . json_encode($response) . "</p>", "S");

            $this->model_orders_to_integration->removeAllOrderIntegration($order_in_db['id'], $order_in_db['store_id']);

            // pedido cancelado não ler mais nada para baixo
            return true;
        }

        // pedido com NFe
        if (isset($body["invoice"]) && $this->canInsertNfe($order_in_db)) {
            // grava nfe
            $responseNfe = $this->setNfeOrder($body["invoice"], $order_in_db);

            // já existia uma nfe no pedido
            if ($responseNfe !== false) {
                echo "Pedido {$order["order_id"]} atualizado para Faturado.\n";
                $this->log_integration("Pedido {$order["order_id"]} atualizado para Faturado.", $responseNfe, "S");
            }
            $order_in_db = $this->model_orders->getOrdersData(0, $order["order_id"]);
            $url = "{$this->url_anymarket}orders/{$orderId}/transmissionStatus";
            $confirm_data = [
                "marketPlaceStatus" => "INVOICED",
                "success" => "true",
                "errorMessage" => "",
            ];
            $response = $this->sendREST($url, json_encode($confirm_data), 'PUT');

            // não foi possível atualizar pedido
            if ($response['httpcode'] != 200) {
                $payloadBody = json_encode($confirm_data);
                echo "[ERROR] Não foi possível confirmar transmissão de status do pedido {$orderId} na integradora.\nurl_request={$url}\npayload_body={$payloadBody}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
            }

            $realized_operation = true;
            $statusToRemove = [3];
        }

        // pedido com rastreio
        if (isset($body["tracking"])) {

            try{
                // logística não é da anymarket, então não precisamos trazer dado de rastreio de lá, somente enviar.
                if ($withoutAnymarket) {
                    throw new Exception("Este Pedido ".$order['order_id']."  possui a regra de logistica no Seller Center, portanto, a atualização será enviada do Marketplace para o ANYMARKET quando houver a mudança de status.");
                    echo "Este Pedido ".$order['order_id']."  possui a regra de logistica no Seller Center, portanto, a atualização será enviada do Marketplace para o ANYMARKET quando houver a mudança de status.\n";
                    //return true;
                }
                
                if (isset($body["tracking"]) && $this->canInsertTracker($order_in_db)) {
                    // adicionar rastreio no pedido
                    $responseTracking = $this->setTrackingOrder($body, $order_in_db);

                    // já existia um rastreio no pedido
                    if ($responseTracking !== false) {
                        $this->log_integration("Pedido {$order["order_id"]} atualizado para Com Rastreio.", $responseTracking, "S");
                        echo "Pedido {$order["order_id"]} recuperou dados de rastreio.\n";
                    } else {
                        echo "Pedido {$order["order_id"]} já exister dados de rastreio.\n";
                    }

                    $order_in_db = $this->model_orders->getOrdersData(0, $order["order_id"]);
                    $confirm_data = [
                        "marketPlaceStatus" => "ENVIADO",
                        "success"           => "true",
                        "errorMessage"      => '',
                    ];
                }   
            }catch (Throwable $e)  { 
                $confirm_data = [
                    "marketPlaceStatus" => "AGUARDANDO COLETA/ENVIO",
                    "success"           => "false",
                    "errorMessage"      => $e->getMessage(),
                ];
            }

            $url = "{$this->url_anymarket}orders/{$orderId}/transmissionStatus";
            $response = $this->sendREST($url, json_encode($confirm_data), 'PUT');

            // não foi possível atualizar pedido
            if ($response['httpcode'] != 200) {
                $payloadBody = json_encode($confirm_data);
                echo "[ERROR] Não foi possível confirmar transmissão de status do pedido {$orderId} na integradora.\nurl_request={$url}\npayload_body={$payloadBody}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
            }

            if (isset($body["tracking"]) && $this->canInsertTracker($order_in_db)) {
                $realized_operation = true;
                $statusToRemove = [40];
            }
        }

        // pedido com data de despacho
        if (isset($body["tracking"]["shippedDate"])) {

            try{
                // logística não é da anymarket, então não precisamos trazer dado de rastreio de lá, somente enviar.
                if ($withoutAnymarket) {
                    throw new Exception("Este Pedido ".$order["order_id"]."  possui a regra de logistica no Seller Center, portanto, a atualização será enviada do Marketplace para o ANYMARKET quando houver a mudança de status.");
                    echo "Este Pedido ".$order["order_id"]."  possui a regra de logistica no Seller Center, portanto, a atualização será enviada do Marketplace para o ANYMARKET quando houver a mudança de status.\n";
                    //return true;
                }

                if (isset($body["tracking"]["shippedDate"]) && $this->canSetShippedOrder($order_in_db)) {
                    $this->setShippedOrder($body, $order_in_db);
                    $confirm_data = [
                        "marketPlaceStatus" => "ENVIADO",
                        "success" => "true",
                        "errorMessage" => "",
                    ];
                }
            }catch (Throwable $e)  { 
                $confirm_data = [
                    "marketPlaceStatus" => "AGUARDANDO COLETA/ENVIO",
                    "success"           => "false",
                    "errorMessage"      => $e->getMessage(),
                ];
            }

            $url = "{$this->url_anymarket}orders/{$orderId}/transmissionStatus";
            $response = $this->sendREST($url, json_encode($confirm_data), 'PUT');
            // não foi possível atualizar pedido
            if ($response['httpcode'] != 200) {
                $payloadBody = json_encode($confirm_data);
                echo "[ERROR] Não foi possível confirmar transmissão de status do pedido {$orderId} na integradora.\nurl_request={$url}\npayload_body={$payloadBody}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
            }
            $this->log_integration("Pedido {$orderId} atualizado para Enviado.", "<h4>Atualizado status do pedido {$order_in_db['id']} para <b>Enviado</b></h4", "S");
            echo "Enviado e movendo o status do pedido para - paid_id={$order_in_db["paid_status"]}\n";

            if (isset($body["tracking"]["shippedDate"]) && $this->canSetShippedOrder($order_in_db)) {
                $this->log_integration("Pedido {$orderId} atualizado para Enviado.", "<h4>Atualizado status do pedido {$order_in_db['id']} para <b>Enviado</b></h4", "S");
                echo "Enviado e movendo o status do pedido para - paid_id={$order_in_db["paid_status"]}\n";
                $realized_operation = true;
                $statusToRemove = [45];
            }
        }

        // pedido com data de entrega
        if (isset($body["tracking"]["deliveredDate"])) {

            try{
                // logística não é da anymarket, então não precisamos trazer dado de rastreio de lá, somente enviar.
                if ($withoutAnymarket) {
                    $realized_operation = true;
                    throw new Exception("Este Pedido ".$order["order_id"]."  possui a regra de logistica no Seller Center, portanto, a atualização será enviada do Marketplace para o ANYMARKET quando houver a mudança de status.");
                    echo "Este Pedido ".$order["order_id"]."  possui a regra de logistica no Seller Center, portanto, a atualização será enviada do Marketplace para o ANYMARKET quando houver a mudança de status.\n";
                    //return true;
                }
    
                if (isset($body["tracking"]["deliveredDate"]) && $this->canSetConcludedTracker($order_in_db)) {
                    $this->setDeliveredOrder($body, $order_in_db);
                    $order_in_db = $this->model_orders->getOrdersData(0, $order["order_id"]);
                    $confirm_data = [
                        "marketPlaceStatus" => "ENTREGUE",
                        "success" => "true",
                        "errorMessage" => "",
                    ];
                    $realized_operation = true;
                }
            }catch (Throwable $e)  { 
                $confirm_data = [
                    "marketPlaceStatus" => "EM TRANSPORTE",
                    "success"           => "false",
                    "errorMessage"      => $e->getMessage(),
                ];
            }
            
            $url = "{$this->url_anymarket}orders/{$orderId}/transmissionStatus";
            $response = $this->sendREST($url, json_encode($confirm_data), 'PUT');

            // não foi possível atualizar pedido
            if ($response['httpcode'] != 200) {
                $payloadBody = json_encode($confirm_data);
                echo "[ERROR] Não foi possível confirmar transmissão de status do pedido {$orderId} na integradora.\nurl_request={$url}\npayload_body={$payloadBody}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
            }

            if (isset($body["tracking"]["deliveredDate"]) && $this->canSetConcludedTracker($order_in_db)) {
                $this->setDeliveredOrder($body, $order_in_db);
                $this->log_integration("Pedido {$orderId} atualizado para Entregue.", "<h4>Atualização para entregue do pedido {$order_in_db['id']}</h4><p>Entregue e movendo o status do pedido para - paid_id={$order_in_db["paid_status"]}</p>", "S");
                echo ("Entregue e movendo o status do pedido para - paid_id={$order_in_db["paid_status"]}\n");
                $realized_operation = true;
                $statusToRemove = [45];
            }
        }

        // remove status do pedido da fila
        if ($statusToRemove !== null) {
            $this->model_orders_to_integration->removeOrderToIntegrationByStatus($order_in_db['id'], $order_in_db['store_id'], $statusToRemove);
        }

        // pedido está fechado(entregue)
        if ($this->isClosed($order_in_db)) {
            $message = "O pedido {$orderId} está fechado, isto é, o mesmo já se encontra com o status entregue... Caso deseje alterar ou consultar dados do mesmo, consulte a equipe conectala.";

            echo $message . "\n";

            $url = "{$this->url_anymarket}orders/{$orderId}/transmissionStatus";
            $confirm_data = [
                "marketPlaceStatus" => "ENTREGUE",
                "success"           => "true",
                "errorMessage"      => $message,
            ];
            $response = $this->sendREST($url, json_encode($confirm_data), 'PUT');

            // não foi possível atualizar pedido
            if ($response['httpcode'] != 200) {
                $payloadBody = json_encode($confirm_data);
                echo "[ERROR] Não foi possível confirmar transmissão de status do pedido {$orderId} na integradora.\nurl_request={$url}\npayload_body={$payloadBody}\nhttp_code={$response['httpcode']}\nresponse={$response["content"]}\n\n";
            }

            $realized_operation = true;
        }

        return $realized_operation;
    }

    private function isClosed($order)
    {
        return $order['paid_status'] == 60 || $order['paid_status'] == 6;
    }

    private function canInsertNfe($order)
    {
        return $order['paid_status'] == 3;
    }

    private function canInsertTracker($order)
    {
        return $order['paid_status'] == 40;
    }

    private function canSetShippedOrder($order)
    {
        return $order['paid_status'] == 43;
    }

    private function canSetConcludedTracker($order)
    {
        return $order['paid_status'] == 45 || $order['paid_status'] == 5;
    }

    private function setNfeOrder($invoice, $order)
    {
        $nfe_to_order = $this->model_nfes->getNfesDataByOrderId($order['id'], true);

        $this->model_orders->updateStatusForOrder($order['id'], $order['store_id'], 52, 3);

        if (count($nfe_to_order) == 0) {
            $data = [
                'order_id'      => $order['id'],
                'company_id'    => $order['company_id'],
                'date_emission' => date('d/m/Y H:i:s', strtotime($invoice["date"])),
                'nfe_serie'     => $invoice["series"],
                'nfe_num'       => $invoice["number"],
                'chave'         => $invoice["accessKey"],
                'store_id'      => $order['store_id'],
                'nfe_value'     => $order['total_order'] + $order['total_ship'] - $order['discount'],
            ];
            $this->model_nfes->create($data);
            //$order['paid_status'] = 52;

            return "<h4>Foi atualizado dados de faturamento do pedido {$data['order_id']}</h4> 
                          <ul>
                            <li><strong>Chave:</strong> {$data['chave']}</li>
                            <li><strong>Número:</strong> {$data['nfe_num']}</li>
                            <li><strong>Série:</strong> {$data['nfe_serie']}</li>
                            <li><strong>Data de Emissão:</strong> {$data['date_emission']}</li>
                            <li><strong>Valor:</strong> R$" . number_format($data['nfe_value'], 2, ',', '.') . "</li>
                          </ul>";
        }

        //$this->model_orders->updateStatusForOrder($order['id'], $order['store_id'], 52);
        echo "Pedido já havia sido faturado, a id da fatura anterior é: {$nfe_to_order[0]['id']}\n";
        return false;
    }

    private function setTrackingOrder($body, $order)
    {
        $orders_item = $this->model_orders->getOrdersItemData($order["id"]);
        $tracking    = $body["tracking"];
        $freight     = $this->model_freights->getFreightsDataByOrderId($order['id']);
        $carrier     = empty($tracking["carrier"]) ? $order['ship_company_preview'] : $tracking["carrier"];

        if (count($freight) == 0) {

            $isCorreios = preg_match("%correios%", '/' . str_replace('%', '.*?', strtolower($carrier)) . '/') > 0;

            $updateOrder = [
                'ship_company_preview'  => $isCorreios ? 'CORREIOS' : 'TRANSPORTADORA'
            ];
            $this->model_orders->updateByOrigin($order['id'], $updateOrder);
            $this->model_orders->updateStatusForOrder($order['id'], $order['store_id'], 51, 40);

            foreach ($orders_item as $order_item) {
                $data = array(
                    'order_id' => $order["id"],
                    "item_id" => $order_item['id'],
                    'company_id' => $order["company_id"],
                    'ship_company' => $carrier,
                    'date_delivered' => '',
                    'method' => $order["ship_service_preview"],
                    "CNPJ" => '',
                    "status_ship" => 0,
                    //"ship_value" 			=> $quote['cost'],
                    'ship_value' => floatval($order["total_ship"]), // mostro o que foi pago e não o valor real da contratação.
                    'prazoprevisto' => date('Y-m-d', strtotime($tracking["estimateDate"])),
                    //"idservico"			=> "",
                    'codigo_rastreio' => $tracking["number"],
                    //"link_etiqueta_a4"  	=> $gerarEtiquetas['A4'],
                    //"link_etiqueta_termica" => $gerarEtiquetas['Termica'],
                    //"link_plp"			=> "",
                    //"link_etiquetas_zpl"	=> "", //Frete rápido não tem integração com etiqueta zpl
                    "data_etiqueta" => date("Y-m-d H:i:s"),
                    'url_tracking' => $tracking["url"],
                    'sgp' => 0, // 0 = não rastrear internamente
                    //"shipping_order_id"		=> "",
                    'in_resend_active' => $order['in_resend_active'],
                );
                $this->model_freights->create($data);
            }

            return "<h4>Foi recuperado dados de rastreio para o pedido {$data['order_id']}</h4> 
                          <ul>
                            <li><strong>Código de rastreio:</strong> {$tracking["number"]}</li>
                            <li><strong>Transportadora:</strong> $carrier</li>
                            <li><strong>Previsão de entrega:</strong> ".date('Y-m-d', strtotime($tracking["estimateDate"]))."</li>
                          </ul>";
        } else {
            echo "Dados de frete já existe, atualizando para os novos.\n";
            return false;
        }
    }

    private function setShippedOrder($body, $order)
    {
        $this->model_orders->updateStatusForOrder($order['id'], $order['store_id'], 55, 43);
        $this->model_orders->updateByOrigin($order['id'], array('data_envio' => date('Y-m-d H:i:s', strtotime($body["tracking"]["shippedDate"]))));
    }

    private function setDeliveredOrder($body, $order)
    {
        $freights = $this->model_freights->getFreightsDataByOrderId($order['id']);

        // atualiza data nos rastreios
        foreach ($freights as $freight) {
            $data_to_freight = [
                'date_delivered' => date('Y-m-d H:i:s', strtotime($body["tracking"]["deliveredDate"]))
            ];
            $this->model_freights->update($data_to_freight, $freight['id']);
        }

        // atualiza data no pedido
        $data_to_order = [
            'data_entrega'  => date('Y-m-d H:i:s', strtotime($body["tracking"]["deliveredDate"]))
        ];
        $this->model_orders->updateByOrigin($order['id'], $data_to_order);

        // atualiza pedido pro status 60. Se for logistica propria($ownLogistic) valida se esta no status 45 se não for valida o status 5
        $orderStatus = in_array((int)$order['paid_status'], [5, 45]) ? $order['paid_status'] : 45;
        $this->model_orders->updateStatusForOrder($order['id'], $order['store_id'], 60, $orderStatus);
    }
}