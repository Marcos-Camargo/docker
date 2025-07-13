<?php

require APPPATH . "controllers/BatchC/Integration/Integration.php";
require APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ParserOrderAnymarket.php";
class CreateOrder extends Integration
{
    private $integration = null;
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

        $this->setJob('CreateOrder');
        $this->load->model('model_api_integrations');
        $this->load->model('model_orders');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_orders_payment');
        $this->load->model('model_orders_item');
        $this->load->model('model_clients');
        $this->load->model('model_settings');
        $this->url_anymarket = $this->model_settings->getValueIfAtiveByName('url_anymarket');
        if (!$this->url_anymarket) {
            throw new Exception("\'url_anymarket\' não está definido no sistema");
        }
    }

    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        if (!$store) {
            echo ("Parametros informados incorretamente. ID={$id} - STORE={$store}");
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            return;
        }

        $this->store    = $store;
        $store_array    = $this->model_stores->getStoresById($store);
        $this->company  = $store_array['company_id'];

        /* inicia o job */
        $this->setIdJob($id);
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            echo ('Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store);
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store, "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        // Define a loja, para recuperar os dados para integração
        $this->sendAllOrders();

        // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    private function getIntegration($store)
    {
        echo ("Pegando dados para integração\n");
        $this->integration = $this->model_api_integrations->getUserByAnyByStore($store);
        $credentiais = json_decode($this->integration["credentials"], true);
        if (!isset($credentiais['token_anymarket'])) {
            return "token_anymarket não definida para este usuario, por favor solicite a devida configuração no site da anymarket.\nLoja: {$store}";
        }
        $this->setToken($credentiais['token_anymarket']);
        $app_id_anymarket = $this->model_settings->getValueIfAtiveByName('app_id_anymarket');
        if (!$app_id_anymarket) {
            return "appID não definida para este usuario, por favor solicite a devida configuração no site da anymarket.\nLoja: {$store}";
        }
        $this->setAppKey($app_id_anymarket);
        if (!$this->integration) {
            echo ("Mais de uma integração Anymarket para esta loja.\nLoja: {$store}");
            return false;
        }
        echo ("Inciando procedimento para a integração {$this->integration['id']} na loja {$store} com OI AnyMarket {$this->integration['id_anymarket_oi']}\n");
        return true;
    }

    private function sendAllOrders()
    {
        $response = $this->getIntegration($this->store);
        if ($response !== true) {
            $this->log_integration($response, "<h4>{$response}</h4>", "E");
            //echo ($response . "\n");
            return;
        }

        $orders = $this->model_orders_to_integration->getOrdersToSend($this->store);

        if (count($orders) > 0) {
            foreach ($orders as $order) {
                $this->sendOrder($order);
            }
        }
    }

    private function sendOrder($order_to_integration): bool
    {
        $this->setUniqueId($order_to_integration['order_id']);
        $url            = "{$this->url_anymarket}orders";
        $order          = $this->model_orders->getOrdersData(0, $order_to_integration['order_id']);
        $sellercenter   = $this->model_settings->getValueIfAtiveByName('sellercenter');

        // verifica cancelado, para não integrar
        if ($this->model_orders_to_integration->isCanceled($order['id'], $this->store)) {
            echo "Pedido {$order['id']} cancelado, não será integrado e removido os status da fila.\n";
            $this->log_integration("Pedido {$order['id']} cancelado", "<h4>Pedido {$order['id']} não será integrado</h4> <ul><li>Pedido cancelado antes de ser realizado o pagamento.</li></ul>", "S");
            $this->model_orders_to_integration->removeAllOrderIntegration($order["id"], $this->store);
            return true;
        }

        // Igonoro o status pois ainda não foi pago e não será enviado pro erp
        if ($order_to_integration['paid_status'] != 3) {
            // Pedido chegou como não pago, mas já mudou de status
            $this->model_orders_to_integration->getOrderOtherThanUnpaid($order["id"], $this->store);

            echo "Pedido {$order["id"]} chegou não pago, vou ignorar\n";
            return true;
        }

        echo "Iniciando o envio para AnyMarket do pedido {$order_to_integration['order_id']}-{$order_to_integration['id']}\n";
        $payment = $this->model_orders_payment->getByOrderId($order['id']);
        $order_itens = $this->model_orders_item->getItensByOrderId($order['id']);
        $clients = $this->model_clients->getClientsData($order['customer_id']);

        $parser = new ParserOrderAnymerket($this);

        $order['origin'] = $this->model_settings->getValueIfAtiveByName('sellercenter_name') ?? $order['origin'];
        $parserOrder = $parser->parserToAnymarketFormat($order, $order_itens, $payment, $clients, $this->integration, $sellercenter);
        echo "JSON order {$order['id']}=\n".json_encode($parserOrder, JSON_UNESCAPED_UNICODE) . "\n\n";
        $parserOrder = json_encode($parserOrder);

        $response = $this->sendREST($url, $parserOrder, 'POST');
        if ($response["httpcode"] == 200) {
            $response = json_decode($response["content"], true);
            echo ("Criação do pedido {$order['id']} realizada com sucesso, ID na anymarket: {$response["id"]}\n");
            $this->log_integration("Criação do pedido {$order['id']} realizada com sucesso", "<h4>Criação do pedido {$order['id']} realizada com sucesso</h4><p>ID na anymarket: {$response["id"]}</p>", "S");
            $order = $this->model_orders->getOrdersData(0, $order_to_integration['order_id']);
            $update_data = [
                'order_id_integration' => $response["id"],
            ];
            $this->model_orders->updateByOrigin($order['id'], $update_data);

            // controlador da lista para pedidos que chegaram como aguardando faturamento ou cancelado
            $this->model_orders_to_integration->controlRegisterIntegration($order_to_integration);

            // remove da fila de integração
            if ($order_to_integration['paid_status'] != 3) {
                $this->model_orders_to_integration->removeOrderIntegration($order['id'], $this->store);
            }

            return true;
        } else {
            echo "Erro na requisição: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
            $this->log_integration(
                "Erro na criação do pedido {$order['id']}",
                "<h4>Erro na criação do pedido {$order['id']}</h4><p>" . json_encode($response, JSON_UNESCAPED_UNICODE) . "</p>",
                "E"
            );
            $url_monitoring = "{$this->url_anymarket}monitorings";
            $content = json_decode($response['content'], true);
            $now = new DateTime('now', new DateTimeZone("America/Sao_Paulo"));
            $dataformat = "Y-m-d\TH:i:sP";
            echo (json_encode($order, JSON_UNESCAPED_UNICODE) . "\n");
            $credentials = json_decode($this->integration['credentials'], true);
            // dd($order_to_integration, $credentials,$order_to_integration['id'] );
            $orderOrigin = $this->model_settings->getValueIfAtiveByName('sellercenter_name') ?? "CONECTALA";
            $data_monitorings = [
                "message" => $content['message'],
                'details' => "PEDIDO NÂO IMPORTADO",
                'createdAt' => $now->format($dataformat),
                "origin" => strtoupper($orderOrigin),
                'id' => $order['id'],
                "partnerId" => $order['id'],
                "type" => "CRITICAL_ERROR",
                "retryCallbackURL" => base_url("Api/Integration/AnyMarket/Remotes/orderRequest/" . $order['id'] . "?token=" . $credentials['token2']),
                "status" => "PENDING",
            ];
            echo (base_url("Api/Integration/AnyMarket/Remotes/orderRequest/" . $order_to_integration['id'] . "?token=" . $credentials['token2']) . "\n");
            echo (json_encode($data_monitorings, JSON_UNESCAPED_UNICODE) . "\n");
            $response = $this->sendREST($url_monitoring, json_encode($data_monitorings, JSON_UNESCAPED_UNICODE), 'POST');
            echo ("Retorno do monitorings: " . $response['content'] . "\n\n\n");
            return false;
        }
    }
}
