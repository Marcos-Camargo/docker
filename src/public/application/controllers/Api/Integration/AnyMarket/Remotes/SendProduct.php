<?php

use Firebase\JWT\JWT;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/UpdatePrazoOperacionalExtra.trait.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/UpdateProductTrait.trait.php";

require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ValidationProduct.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";
require_once APPPATH . "controllers/BatchC/Integration/Integration.php";

class SendProduct extends MainController
{
    use UpdateProductTrait;
    const PASTA_DE_IMAGEM = 'assets/images/product_image';
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_api_integrations');
        $this->load->model('model_anymarket_temp_product');
        $this->load->model('model_anymarket_log');
        $this->load->library('rest_request');
        $this->validator = new ValidationProduct($this);
        $this->CI = $this;
        $this->CI->load->library('uploadproducts');
        $this->app_id_anymarket = $this->model_settings->getValueIfAtiveByName('app_id_anymarket');
        if (!$this->app_id_anymarket) {
            die("appID não definida para este usuario, por favor solicite a devida configuração no site da anymarket.");
        }
        $this->load->model('model_settings');
        $this->url_anymerket = $this->model_settings->getValueIfAtiveByName('url_anymarket');
        if (!$this->url_anymerket) {
            throw new Exception("\'url_anymerket\' não está definido no sistema");
        }
        $this->validator = new ValidationProduct($this);
    }
    /*
     *sendProduct retorna dado para anymarket
     *
     */
     
    public function __destruct() {
		parent::__destruct();
        // deleta o cookie
		unlink($this->rest_request->cookiejar);
    }
	 
    public function index_post()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $body = file_get_contents('php://input');
        $body = json_decode($body, true);

        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $log_data = [
            'endpoint' => 'SendProduct',
            'body_received' => json_encode($body),
            'store_id' => $integration['store_id'],
        ];
        $this->model_anymarket_log->create($log_data);
        $credentiais = json_decode($integration["credentials"], true);
        if (!isset($credentiais['token_anymarket'])) {
            return "token_anymarket não definida para este usuario, por favor solicite a devida configuração no site da anymarket.";
        }
        $this->token = $credentiais['token_anymarket'];
        $this->app_id_anymarket = $this->appId = $this->model_settings->getValueIfAtiveByName('app_id_anymarket');
        if (!$this->appId) {
            throw new Exception("\'app_id_anymarket\' não está definido no sistema");
        }
        $this->url_anymerket = $this->model_settings->getValueIfAtiveByName('url_anymarket');
        if (!$this->url_anymerket) {
            throw new Exception("\'url_anymerket\' não está definido no sistema");
        }
        $credentiais = json_decode($integration['credentials'], true);
        $response = [
            "publicationStatus" => "UNPUBLISHED",
            "marketplaceStatus" => "O Marketplace está processando o produto.",
            "transmissionStatus" => "OK", //
        ];
        $anymarket_queue = $this->db->select()
            ->from('anymarket_queue')
            ->where([
                'idSku' => (string)$body['idSku'],
                'integration_id' => $integration['id'],
                'checked' => 0,
            ])->get()->row_array();
        if (!$anymarket_queue) {
            $data = [
                'received_body' => json_encode($body),
                'integration_id' => $integration['id'],
                'idSku' => $body['idSku'],
                'idProduct' => $body['idProduct'],
                'idSkuMarketplace' => $body['idSkuMarketplace'],
            ];
            $this->db->insert('anymarket_queue', $data);
        }
        $this->response($response, 200);
        // $this->updateProductTrait($body, $integration);
    }
}
