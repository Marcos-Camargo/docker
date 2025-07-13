<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;

require APPPATH . "libraries/REST_Controller.php";

/**
 * @property Model_orders $model_orders
 * @property Model_products $model_products
 * @property Model_api_integrations $model_api_integrations
 * @property Model_settings $model_settings
 */

class Notification extends REST_Controller
{
    /**
     * Instantiate a new ControlProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_orders');
        $this->load->model('model_products');
        $this->load->model('model_api_integrations');
        $this->load->model('model_settings');
        header('Integration: v2');
    }
   
    public function index_post()
    {
        ob_start();
        // Recupera dados enviado pelo body
        $webhook = json_decode(file_get_contents('php://input'));
        $this->log_data('api', 'Api/ControlProduct/PluggTo/' . ($webhook->type ?? '') . '/' . ($webhook->action ?? ''), json_encode($webhook));

        $type   = $webhook->type;
        $user   = $webhook->user;
        $action = $webhook->action;


        // Não esperado chegar um tipo nulo.
        if ($type === null) {
            ob_clean();
            return $this->response('notificação não esperada', REST_Controller::HTTP_BAD_REQUEST);
        }

        try {
            $store = $this->getStoreByUserIntegration($webhook->id, $webhook->type, $webhook->action);
        } catch (InvalidArgumentException $exception) {
            ob_clean();
            return $this->response($exception->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
        }
        
        if ($type === 'products') {
            require APPPATH . "controllers/Api/Integration_v2/pluggto/Product.php";
            if ($action == 'updated') {
                try {
                    Product::update($webhook, $store);
                } catch (Exception $exception) {
                    ob_clean();
                    return $this->response($exception->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
                }
            } elseif($action == 'created') {
                try {
                    Product::create($webhook, $store);
                } catch (Exception $exception) {
                    ob_clean();
                    return $this->response($exception->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
                }
            }
        }
        elseif ($type === 'orders') {
            require APPPATH . "controllers/Api/Integration_v2/pluggto/Order.php";
            try {
                Order::update($webhook, $store);
            } catch (Exception $exception) {
                ob_clean();
                return $this->response($exception->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        ob_clean();
        return $this->response(null, REST_Controller::HTTP_OK);
    }

    /**
     * Consulta a loja pelo código na integradora.
     *
     * @param   string  $id     Usuário PluggTo.
     * @param   string  $type   Tipo de requisição (products|orders).
     * @param   string  $action Tipo de ação (created|updated)
     * @return  int
     */
    public function getStoreByUserIntegration(string $id, string $type, string $action): int
    {
        if ($type === 'products') {
            $result = $this->model_products->getByProductIdErp($id);
        } elseif ($type === 'orders') {
            $result = $this->model_orders->getOrderByOrderIdIntegration($id);
        } else {
            throw new InvalidArgumentException("Tipo de solicitação ($type), não esperada.");
        }

        if (!$result) {
            try {
                if ($type === 'products' && $action === 'created') {
                    return $this->getStoreByUserIntegrationCreateProduct($id);
                }
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException("$type ($id), {$exception->getMessage()}.");
            }
            throw new InvalidArgumentException("$type ($id), não localizado.");
        }

        return $result['store_id'];

    }

    /**
     * Recupera a loja se for uma criação do novo produto.
     * @warning Precisamos fazer uma requisição na PluggTo para saber quem é o dono do produto.
     *
     * @param   string  $id ID do produto na PluggTo.
     * @return  int
     */
    public function getStoreByUserIntegrationCreateProduct(string $id): int
    {
        // precisamos pegar o token que dura 1 hora
        $credentialsParam = $this->model_settings->getValueIfAtiveByName('credencial_pluggto');

        if (!$credentialsParam) {
            throw new InvalidArgumentException('Não foi localizado o parâmetro de credenciais do canal da PluggTo');
        }

        $credentialsParam = Utils::jsonDecode($credentialsParam);

        $urlAccessToken     = 'https://api.plugg.to/oauth/token';
        $queryAccessToken   = array(
            'form_params' => array(
                'grant_type'    => 'password',
                'client_id'     => $credentialsParam->client_id_pluggto,
                'client_secret' => $credentialsParam->client_secret_pluggto,
                'username'      => $credentialsParam->username_pluggto,
                'password'      => $credentialsParam->password_pluggto
            ),
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            )
        );

        // consulta o access token
        try {
            $client = new Client([
                'verify' => false // no verify ssl
            ]);
            $request = $client->request('POST', $urlAccessToken, $queryAccessToken);
            $bodyAccessToken = Utils::jsonDecode($request->getBody()->getContents());

            $queryGetStore = array('query' => array('access_token' => $bodyAccessToken->access_token));

            $request = $client->request('GET', "https://api.plugg.to/products/$id", $queryGetStore);
            $bodyGetStore = Utils::jsonDecode($request->getBody()->getContents());

            $supplierId = $bodyGetStore->Product->supplier_id ?? $bodyGetStore->Order->supplier_id ?? null;

            if (!$supplierId) {
                throw new InvalidArgumentException("Não foi possível localizar o supplier_id da requisição, para criação de produto.\n" . Utils::jsonEncode($bodyGetStore));
            }

            // recupera a loja pelo supplier_id pluggto
            $store = $this->model_api_integrations->getStoreByDataCredentials("\"user_id\":\"$supplierId\"");

            if (!$store) {
                throw new InvalidArgumentException("supplier_id ($supplierId) PluggTo, não encontrado como uma loja.");
            }

            return $store;

        } catch (InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException("Não foi possível recuperar as credenciais\n{$exception->getMessage()}");
        }
    }
}