<?php
use Firebase\JWT\JWT;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";

class OrderRequest extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_api_integrations');
        $this->load->model('model_anymarket_temp_product');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_orders');
        $this->load->model('model_stores');
    }
    public function index_post($order_id)
    {
        $dados =  $this->input->get();
        $this->input = $dados;
        if (!$this->checkToken()) {
            $this->load->view('any_market/unauthorized');
            return;
        }
        $anymarket_token = $dados['token'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $store=$this->model_stores->getStoreById($integration['store_id']);
        $order=$this->model_orders->getOrdersData(0,$order_id);
        $data=[
            'order_id'=>$order_id,
            'company_id'=>$store['company_id'],
            'store_id'=>$store['id'],
            'paid_status'=>$order['paid_status']

        ];
        $order_to_integration = $this->model_orders_to_integration->create($data);
        if (!$order_to_integration) {
            echo ('false');
            return;
        }
        echo ('true');
        return;
    }
    public function checkToken()
    {
        if (!isset($this->input['token'])) {
            return false;
        }
        $anymarket_token = $this->input['token'];
        if (empty($anymarket_token)) {
            return false;
        }
        $payload = explode('.', $anymarket_token);
        if (!isset($payload[1])) {
            return false;
        }
        $payload = $payload[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        if (!isset($payload->oi)) {
            return false;
        }
        return true;
    }
}
