<?php

use Firebase\JWT\JWT;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";
class Account extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_api_integrations');
        $this->load->model('model_anymarket_log');

    }
    public function index_get()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];

        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);

        $data = [
            'id' => $integration[0]['user_id'],
            'name' => strtoupper($integration[0]['store_name'])
        ];
        $this->response($data, REST_Controller::HTTP_OK);
    }
    public function active_get()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $response = [];
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integrations = $this->model_api_integrations->getUserByOI($payload->oi);
        foreach ($integrations as $integration) {
            array_push($response, [
                'id' => $integration['user_id'],
                'name' => strtoupper($integration['store_name'])
            ]);
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }
    public function default_get()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];

        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);

        $data = ['id' => $integration[0]['user_id'], 'name' => strtoupper($integration[0]['store_name'])];
        $this->response($data, REST_Controller::HTTP_OK);
    }
}
