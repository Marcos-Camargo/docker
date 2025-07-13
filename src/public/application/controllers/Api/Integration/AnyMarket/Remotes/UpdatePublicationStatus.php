<?php

use Firebase\JWT\JWT;

require_once APPPATH . "controllers/Api/Integration/AnyMarket/MainController.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/CheckIfActiveOnProduct.trait.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/RequestRestAnymarket.trait.php";
class UpdatePublicationStatus extends MainController
{
    use CheckIfActiveOnProduct;
    use RequestRestAnymarket;
    public function __construct()
    {
        parent::__construct();
        // $this->load->model('model_api_integrations');
        $this->load->model('model_products');
        $this->load->model('model_anymarket_temp_product');
        $this->load->model('model_settings');
        $this->load->model('model_anymarket_log');
        $this->load->library('rest_request');
        $this->app_id_anymarket = $this->model_settings->getValueIfAtiveByName('app_id_anymarket');
        if (!$this->app_id_anymarket) {
            die("appID não definida para este usuario, por favor solicite a devida configuração no site da anymarket.");
        }
        $this->url_anymerket = $this->model_settings->getValueIfAtiveByName('url_anymarket');
        if (!$this->url_anymerket) {
            throw new Exception("\'url_anymerket\' não está definido no sistema");
        }
    }
    
	public function __destruct() {
		parent::__destruct();
        // deleta o cookie
		unlink($this->rest_request->cookiejar);
    }
	
    public function index_put()
    {
        if (!$this->checkToken()) {
            $this->response('unauthorized', 401);
            return;
        }
        $bodyString = file_get_contents('php://input');
        $body = json_decode($bodyString, true);
        $anymarket_token = $_SERVER['HTTP_X_ANYMARKET_TOKEN'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $credentiais = json_decode($integration["credentials"], true);
        
        $log_data = [
            'endpoint' => 'UpdatePublicationStatus(PUT)',
            'body_received' => json_encode($body),
            'store_id' => $integration['store_id'],
        ];
        $this->model_anymarket_log->create($log_data);
        $where = [
            'anymarketId' => (string)$body['idSku'],
            'integration_id' => $integration['id'],
        ];
        if (!isset($credentiais['token_anymarket'])) {
            return "token_anymarket não definida para este usuario, por favor solicite a devida configuração no site da anymarket.";
        }
        $this->token = $credentiais['token_anymarket'];
        $this->app_id_anymarket = $this->appId = $this->model_settings->getValueIfAtiveByName('app_id_anymarket');;
        if (!$this->appId) {
            throw new Exception("\'app_id_anymarket\' não está definido no sistema");
        }
        $this->url_anymerket = $this->model_settings->getValueIfAtiveByName('url_anymarket');
        if (!$this->url_anymerket) {
            throw new Exception("\'url_anymerket\' não está definido no sistema");
        }
        $idSkuMarketplace = $body["idSkuMarketplace"];
        $url = $this->url_anymerket . "skumarketplace/" . $idSkuMarketplace;
        $result = $this->sendREST($url);
        if ($result['httpcode'] != 200) {
            $response = [
                "publicationStatus" => "ERROR",
                "marketplaceStatus" => "Tentativa de cadastrar um produto com anuncio inexistente.",
                "transmissionStatus" => "UMPUBLISH", //
                "_response" => $result['content']
            ];
            $this->response($response, 200);
            return;
        }
        $body_request = json_decode($result['content'], true);
        $temp_product = $this->model_anymarket_temp_product->getData($where);
        $temp_product_att = [];
        $product_temp_data = json_decode($temp_product['data'], true);
        $temp_product_att['need_update'] = 2;
        $temp_product_att['json_received'] = json_encode($body);
        $this->model_anymarket_temp_product->update($temp_product['id'], $temp_product_att);
        try {
            $product_att = [];
            $product = $this->model_products->getByProductIdErp($body_request['sku']['product']['id']);
            if ($product) {
                $variants_by_product = $this->model_products->getVariantsByProd_id($product['id']);
                if (count($variants_by_product) == 0) {
                    $product_att['qty'] = $body['availableAmount'];
                    $product_att['status'] = $body['status'] == 'ACTIVE' ? 1 : 2;
                    $this->model_products->update($product_att, $product['id']);
                    $this->sendConfirmation($body, $credentiais, $product['id'], $product, $product_temp_data['sku']);
                    $product_new_status=$product_att['status']==1?'ATIVO':'INATIVO';
                    $response = [
                        "publicationStatus" => $body['status'],
                        "marketplaceStatus" => "Processando a atualização. Movendo para status: {$product_new_status}",
                        "transmissionStatus" => "OK",
                    ];
                    $this->response($response, 200);
                } else {
                    // $variant = $this->model_products->getVariantsByVariant_id_erp($body['idSku']);
                    $this->updateOnlyVariant($body['idSku'], $body, $credentiais, $product_temp_data);
                }
            } else {
                $this->updateOnlyVariant($body['idSku'], $body, $credentiais, $product_temp_data);
            }

        } catch (Exception $e) {
            $response = [
                "code" => "PublicationValidationException",
                "httpStatus" => 422,
                "message" => "Erro no processo de atualização de status",
                "details" => $e->getMessage(),
            ];
            $this->response($response, 422);
        }
    }
    private function updateOnlyVariant($variant_id_erp, $body, $credentials, $product_temp_data)
    {
        $variant = $this->model_products->getVariantsByVariant_id_erp($variant_id_erp);
        if ($variant) {
            $var_att = [];
            $var_att['status'] = $body['status'] == 'ACTIVE' ? 1 : 2;
            $this->model_products->updateVar($var_att, $variant['prd_id'], $variant['variant']);
            $product = $this->model_products->getProductData(0, $variant['prd_id']);
            $this->sendConfirmation(
                $body,
                $credentials,
                $product['id'],
                $product,
                $product_temp_data['sku']
            );
            $this->checkIfActiveOnProduct($product);
            // $variants_by_product = $this->model_products->getVariantsByProd_id($product['id']);
            // $has_active = false;
            // foreach ($variants_by_product as $variant) {
            //     if ($variant['status'] == 1) {
            //         $has_active = true;
            //     }
            // }
            // $finalStatus = $body['status'] == 'ACTIVE' ? 1 : 2;
            // if (!$has_active && $finalStatus == 2) {
            //     $product_att = [];
            //     $product_att['status'] = $body['status'] == 'ACTIVE' ? 1 : 2;
            //     $this->model_products->update($product_att, $product['id']);
            // } else if ($has_active && $finalStatus == 1) {
            //     $product_att = [];
            //     $product_att['status'] = $body['status'] == 'ACTIVE' ? 1 : 2;
            //     $this->model_products->update($product_att, $product['id']);
            //     // } else {
            //     //     $product_att = [];
            //     //     $product_att['status'] = $body['status'] == 'ACTIVE' ? 1 : 2;
            //     //     $this->model_products->update($product_att, $product['id']);
            // }
            $response = [
                "publicationStatus" => $body['status'],
                "marketplaceStatus" => "Processando a atualização. Movendo para status: {$product['status']} com variação",
                "transmissionStatus" => "OK",
            ];
            $this->response($response, 200);
        } else {
            $response = [
                "publicationStatus" => $body['status'],
                "marketplaceStatus" => "Produto não encontrado.",
                "transmissionStatus" => "ERR",
            ];
            $this->response($response, 422);
        }
    }
    private function sendConfirmation(
        $body,
        $credentials,
        $variant_id,
        $product,
        $skuInMarketplace,
        $add = ''
    )
    {

        if (in_array($body['status'], ['ACTIVE', 'PAUSED'])) {
            $status = $body['status'] == 'ACTIVE' ? 'ATIVO' : 'PAUSADO';
        } else {
            $status = 'INATIVO';
        }
        $url_confirm = $this->url_anymerket . "skumarketplace/" . $body['idSkuMarketplace'] . "";
        $data_to_send = [
            'idInMarketplace' => $variant_id,
            'idInSite' => $product['id'],
            'skuInMarketplace' => $skuInMarketplace,
            'marketplaceStatus' => $status,
            'idSku' => $body['idSku'],
            'transmissionStatus' => "OK",
            'errorMsg' => '',
            'status' => $body['status'],
        ];
        $headers = array(
            "Content-Type: application/json",
            "appId: {$this->app_id_anymarket}",
            "token: {$credentials['token_anymarket']}",
        );
        $res = $this->rest_request->sendREST($url_confirm, $data_to_send, 'PUT', $headers);
    }
}
