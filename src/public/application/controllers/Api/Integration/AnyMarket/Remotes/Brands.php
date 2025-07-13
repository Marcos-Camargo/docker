<?php

use Firebase\JWT\JWT;

// require_once APPPATH . "libraries/REST_Controller.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";
class Brands extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_brands');
        $this->load->model('model_api_integrations');
        $this->load->model('model_brand_anymaket_from_to');
        $this->load->model('model_anymarket_log');

    }
    public function index_get()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $response = [];
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];

        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $brands = $this->model_brands->getActiveBrands();
        foreach ($brands as $brand) {
            if (!empty($brand['name'])) {
                $response[] = ['codeInMarketplace' => $brand['id'], 'name' => $brand['name']];
            }
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }
    public function bind_get($idAnymarketBrand)
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $response = [];
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];

        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $whereData = ['api_integration_id' => $integration['id'], 'idBrandAnymarket' => $idAnymarketBrand];
        $brand = $this->model_brand_anymaket_from_to->getData($whereData);
        if ($brand != null) {
            $this->response(['idBrandAnymarket' => $brand['idBrandAnymarket'], 'idBrandMarketplace' => $brand['brand_id']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => 'err', 'msg' => "Marca não conhecida na {$this->sellercenterName}"], REST_Controller::HTTP_OK);
        }
    }
    public function bind_post()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];

        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $log_data = [
            'endpoint' => 'Brands_post',
            'body_received' => json_encode($body),
            'store_id' => $integration['store_id'],
        ];
        $this->model_anymarket_log->create($log_data);

        $brand = $this->model_brands->getBrandData($body['idBrandMarketplace']);
        // dd($body, $payload, $integration, $brand,$brand==null);
        if ($brand == null) {
            $this->response(['status' => 'err', 'msg' => "Marca não conhecida na {$this->sellercenterName}"], REST_Controller::HTTP_OK);
            // $this->response("Marca não conhecida na {$this->sellercenterName}", REST_Controller::HTTP_OK);
            // die;
        } else {
            $data = ['idBrandAnymarket' => $body['idBrandAnymarket'], 'api_integration_id' => $integration['id']];
            $brand_anymarket = $this->model_brand_anymaket_from_to->getData($data);
            if ($brand_anymarket) {
                $data = ['idBrandAnymarket' => $body['idBrandAnymarket'], 'brand_id' => $brand['id'], 'api_integration_id' => $integration['id']];
                if ($this->model_brand_anymaket_from_to->update($brand_anymarket['id'], $data)) {
                    $this->response(true, REST_Controller::HTTP_OK);
                } else {
                    $this->response(false, REST_Controller::HTTP_OK);
                }
            } else {
                $data = ['idBrandAnymarket' => $body['idBrandAnymarket'], 'brand_id' => $brand['id'], 'api_integration_id' => $integration['id']];
                if ($this->model_brand_anymaket_from_to->create($data)) {
                    $this->response(true, REST_Controller::HTTP_OK);
                } else {
                    $this->response(false, REST_Controller::HTTP_OK);
                }
            }
        }
    }
    public function bind_delete($idAnymarketBrand)
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $response = [];
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];

        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $whereData = ['api_integration_id' => $integration['id'], 'idBrandAnymarket' => $idAnymarketBrand];
        $brand = $this->model_brand_anymaket_from_to->getData($whereData);
        if ($brand == null) {
            //$this->response(['status' => 'err', 'msg' => "Marca não vinculada no {$this->sellercenterName}"], REST_Controller::HTTP_OK);
            $this->response(true, REST_Controller::HTTP_OK);
        } else {
            $response = $this->model_brand_anymaket_from_to->delete($brand['id']);
            $this->response($response, REST_Controller::HTTP_OK);
        }
    }
}
