<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";

class Brands extends MainController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_brands');
        $this->load->model('model_brand_anymaket_from_to');
    }

    public function index_get()
    {
        $brands = $this->model_brands->getActiveBrands();
        $response = [];
        foreach ($brands as $brand) {
            if (!empty($brand['name'])) {
                $response[] = ['codeInMarketplace' => $brand['id'], 'name' => $brand['name']];
            }
        }
        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function bind_get($idAnyMarketBrand)
    {
        $whereData = ['api_integration_id' => $this->accountIntegration['id'], 'idBrandAnymarket' => $idAnyMarketBrand];
        $brand = $this->model_brand_anymaket_from_to->getData($whereData);
        if ($brand != null) {
            $this->response(['idBrandAnymarket' => $brand['idBrandAnymarket'], 'idBrandMarketplace' => $brand['brand_id']], REST_Controller::HTTP_OK);
            return;
        }
        $this->response(['status' => 'err', 'msg' => "Marca não conhecida na {$this->sellercenterName}"], REST_Controller::HTTP_OK);
    }

    public function bind_post()
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $integration = $this->accountIntegration;

        $brand = $this->model_brands->getBrandData($body['idBrandMarketplace']);
        if ($brand == null) {
            $this->response(['status' => 'err', 'msg' => "Marca não conhecida na {$this->sellercenterName}"], REST_Controller::HTTP_OK);
            return;
        }
        $data = ['idBrandAnymarket' => $body['idBrandAnymarket'], 'api_integration_id' => $integration['id']];
        $brand_anymarket = $this->model_brand_anymaket_from_to->getData($data);
        if ($brand_anymarket) {
            $data = ['idBrandAnymarket' => $body['idBrandAnymarket'], 'brand_id' => $brand['id'], 'api_integration_id' => $integration['id']];
            if ($this->model_brand_anymaket_from_to->update($brand_anymarket['id'], $data)) {
                $this->response(true, REST_Controller::HTTP_OK);
                return;
            }
            $this->response(false, REST_Controller::HTTP_OK);
            return;
        }
        $data = ['idBrandAnymarket' => $body['idBrandAnymarket'], 'brand_id' => $brand['id'], 'api_integration_id' => $integration['id']];
        if ($this->model_brand_anymaket_from_to->create($data)) {
            $this->response(true, REST_Controller::HTTP_OK);
            return;
        }
        $this->response(false, REST_Controller::HTTP_OK);
    }

    public function bind_delete($idAnymarketBrand)
    {
        $whereData = ['api_integration_id' => $this->accountIntegration['id'], 'idBrandAnymarket' => $idAnymarketBrand];
        $brand = $this->model_brand_anymaket_from_to->getData($whereData);
        if ($brand == null) {
            $this->response(true, REST_Controller::HTTP_OK);
            return;
        }
        $response = $this->model_brand_anymaket_from_to->delete($brand['id']);
        $this->response($response, REST_Controller::HTTP_OK);
    }
}
