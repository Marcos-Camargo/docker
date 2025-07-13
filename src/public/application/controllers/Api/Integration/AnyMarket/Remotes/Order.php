<?php
use Firebase\JWT\JWT;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ParserOrderAnymarket.php";

require_once APPPATH . "libraries/Integration_v2/Order_v2.php";

use GuzzleHttp\Utils;
use Integration\Integration_v2\Order_v2;

class Order extends MainController
{
    /**
     * @var Order_v2
     */
    private $order_v2;

    public function __construct()
    {
        parent::__construct();
        $this->order_v2 = new Order_v2();

        $this->load->model('model_api_integrations');
        $this->load->model('model_orders');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_anymarket_temp_product');
        $this->load->model('model_orders_payment');
        $this->load->model('model_orders_item');
        $this->load->model('model_clients');
        $this->load->model('model_anymarket_log');
        $this->load->model('model_anymarket_order_to_update');
        $this->load->model('model_settings');

        $this->order_v2->setJob(__CLASS__);
    }
    public function index_get($idInMarketplace)
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0] ?? [];
        if(empty($integration)) {
            $this->response('unauthorized', 401);
            return;
        }
        $order = $this->model_orders->getOrdersData(0, $idInMarketplace);
        $payment = $this->model_orders_payment->getByOrderId($order['id']);
        $order_itens = $this->model_orders_item->getItensByOrderId($order['id']);
        $clients = $this->model_clients->getClientsData($order['customer_id']);
        $sellercenter   = $this->model_settings->getValueIfAtiveByName('sellercenter');
        $parser = new ParserOrderAnymerket($this);
        $parserOrder = $parser->parserToAnymarketFormat($order, $order_itens, $payment, $clients, $integration, $sellercenter);
        // dd($parserOrder);
        $this->response($parserOrder, REST_Controller::HTTP_OK);
    }
    public function updateOrderStatusInMarketPlace_put()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $order = $this->model_orders->getOrdersData(0, $body['idInMarketplace']);
        $log_data = [
            'endpoint' => 'Order_updateOrderStatusInMarketPlace',
            'body_received' => json_encode($body),
            'store_id' => "0",
        ];
        $this->model_anymarket_log->create($log_data);
        $data = [
            'company_id' => $this->data['usercomp'],
            'store_id' => $this->data['userstore'],
            'order_anymarket_id' => $body['orderId'],
            'order_id' => $body['idInMarketplace'],
            'old_status' => $body['oldStatus'],
            'new_status' => $body['currentStatus'],
        ];

        $responseData = true;
        $id = $this->model_anymarket_order_to_update->create($data);
        try {
            $this->order_v2->startRun($this->data['userstore']);
            $this->order_v2->setToolsOrder();
            $this->order_v2->setUniqueId($data["order_id"]);
            if($this->order_v2->toolsOrder->updateOrderFromIntegration($data)) {
                $this->model_anymarket_order_to_update->setIntegrated($id);
            }
        } catch (Throwable $e) {
            $responseData = false;
            $this->order_v2->log_integration(
                "Erro ao atualizar pedido {$data['order_id']}",
                "<h4>Não foi possível atualizar o pedido {$data['order_id']}:</h4><p>{$e->getMessage()}</p>",
                "E"
            );
        }
        $this->response($responseData, REST_Controller::HTTP_OK);
    }
    public function afterSaveOrUpdateOrderInAnymarket_put()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $log_data = [
            'endpoint' => 'Order_updateOrderStatusInMarketPlace',
            'body_received' => json_encode($body),
            'store_id' => "0",
        ];
        $this->model_anymarket_log->create($log_data);
        $this->response(true, REST_Controller::HTTP_OK);
    }
    public function initialImportDate_get()
    {
        $dataformat = "Y-m-d\Th:i:sP";

        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $credentials = json_decode($integration['credentials'], true);
        $createAt = new DateTime($credentials['inicial_date_order'], new DateTimeZone("America/Sao_Paulo"));
        $createAt_formated = $createAt->format($dataformat);
        $inicaldate = ['Date' => isset($credentials['inicial_date_order']) ? $createAt_formated : null];
        $this->response($inicaldate, REST_Controller::HTTP_OK);
    }
}
