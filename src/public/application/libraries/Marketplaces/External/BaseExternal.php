<?php

namespace Marketplaces\External;

use CI_DB_query_builder;
use CI_Lang;
use CI_Loader;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Model_api_integrations;
use Model_banks;
use Model_billet;
use Model_cities;
use Model_clients;
use Model_commissioning_orders_items;
use Model_company;
use Model_conciliacao_sellercenter;
use Model_conciliation;
use Model_external_integration_history;
use Model_integrations;
use Model_legal_panel;
use Model_order_items_cancel;
use Model_orders_to_process_commission;
use Model_nfes;
use Model_orders;
use Model_orders_payment;
use Model_payment;
use Model_product_return;
use Model_repasse;
use Model_settings;
use Model_stores;
use Psr\Http\Message\ResponseInterface;

require_once 'system/libraries/Vendor/autoload.php';

/**
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property Client $client
 * @property Model_settings $model_settings
 * @property Model_integrations $model_integrations
 * @property Model_stores $model_stores
 * @property Model_banks $model_banks
 * @property Model_company $model_company
 * @property Model_orders $model_orders
 * @property Model_payment $model_payment
 * @property Model_cities $model_cities
 * @property Model_external_integration_history $model_external_integration_history
 * @property Model_nfes $model_nfes
 * @property Model_clients $model_clients
 * @property Model_conciliation $model_conciliation
 * @property Model_conciliacao_sellercenter $model_conciliacao_sellercenter
 * @property Model_product_return $model_product_return
 * @property Model_repasse $model_repasse
 * @property Model_orders_to_process_commission $model_orders_to_process_commission
 * @property Model_orders_payment $model_orders_payment
 * @property Model_commissioning_orders_items $model_commissioning_orders_items
 * @property Model_billet $model_billet
 * @property Model_legal_panel $model_legal_panel
 * @property Model_api_integrations $model_api_integrations
 * @property Model_order_items_cancel $model_order_items_cancel
 */

abstract class BaseExternal
{
    /**
     * @var Client Cliente of GuzzleHttp
     */
    public $client;

    /**
     * @var string Endpoint de api do seller center, para requisições em nós mesmo, nas nossas APIs.
     */
    private $base_uri;

    /**
     * @var array Dados para autenticação da requisição.
     */
    private $auth_request;

    /**
     * @var object|null Credenciais da integradora.
     */
    private $credentials = null;

    /**
     * @var array Mapa dos campos nas credencials no banco com os dados para autenticar.
     */
    private $map_auth_request = array();

    /**
     * @var string|null Fornecedor requerente. automatic_job | daily_job
     */
    public $provider = '';

    /**
     * Instantiate a new BaseMarketplace instance.
     */
    public function __construct(?string $provider = 'automatic_job')
    {
        $this->load->model(
            array(
                'model_settings',
                'model_integrations',
                'model_stores',
                'model_company',
                'model_orders',
                'model_payment',
                'model_cities',
                'model_external_integration_history',
                'model_nfes',
                'model_clients',
                'model_conciliation',
                'model_conciliacao_sellercenter',
                'model_product_return',
                'model_repasse',
                'model_job_schedule',
                'model_orders_to_process_commission',
                'model_orders_payment',
                'model_commissioning_orders_items',
                'model_billet',
                'model_legal_panel',
                'model_api_integrations',
                'model_order_items_cancel'
            )
        );

        $this->setClientGuzzle();
    }

    /**
     * Método mágico para utilização do CI_Controller
     *
     * @param   string  $var    Propriedade para consulta
     * @return  mixed           Objeto da propriedade
     */
    public function __get(string $var)
    {
        return get_instance()->$var;
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    abstract public function setAuthToken();

    /**
     * @param int $store_id
     * @return mixed
     * @throws Exception
     */
    abstract public function notifyStore(int $store_id);

    /**
     * @note Caso não use um dos tipos de ações, configure para não usar, pois sempre será chamada.
     *
     * @param int $order_id
     * @param string $action paid | cancel | refund
     * @return mixed
     * @throws Exception
     */
    abstract public function notifyOrder(int $order_id, string $action);

    /**
     * @param int $order_id
     * @return void
     * @throws Exception
     */
    abstract public function notifyNfeValidation(int $order_id);

    /**
     * @param object $data
     * @return void
     * @throws Exception
     */
    abstract public function receiveStore(object $data);

    /**
     * @param object $data
     * @return void
     * @throws Exception
     */
    abstract public function receiveOrder(object $data);

    /**
     * @param object $data
     * @return void
     * @throws Exception
     */
    abstract public function receiveNfe(object $data);

    /**
     * Define a instância Client de GuzzleHttp
     */
    private function setClientGuzzle()
    {
        $this->client = new Client([
            'verify' => false, // no verify ssl
            'timeout' => 10000,
            'connect_timeout' => 10000,
            'allow_redirects' => true
        ]);
    }

    /**
     * @param string $provider
     * @return void
     */
    public function setProvider(string $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param object $credentials
     * @return void
     */
    public function setExternalCredentials(object $credentials)
    {
        // Define a credencial.
        $this->credentials = $credentials;

        // Define os campos para ser utilizado nas requisições com o marketplace.
        $this->setAuthRequest();
    }

    /**
     * @param   string|null $field
     * @return  string|object|null
     */
    public function getCredentials(string $field = null)
    {
        if (!is_null($field)) {
            // Credenciais inválidas ou campo não existente.
            if (!is_object($this->credentials) || !property_exists($this->credentials, $field)) {
                return null;
            }
            return $this->credentials->$field;
        }

        return $this->credentials;
    }

    /**
     * @param string $base_uri
     */
    public function setBaseUri(string $base_uri)
    {
        $this->base_uri = $base_uri;
    }

    /**
     * @param string $uri
     * @return string
     */
    public function getBaseUri(string $uri = ''): string
    {
        // se não vir o endpoint completo, monto aqui
        if (!preg_match('/http/', $uri)) {
            return $this->base_uri.$uri;
        }

        return $uri;
    }

    /**
     *
     * get body ===> getBody()->getContents()
     * get status code ===> getStatusCode()
     *
     * https://docs.guzzlephp.org/en/stable/overview.html
     *
     * @param   string $method
     * @param   string $uri
     * @param   array $options  Request options to apply. See \GuzzleHttp\RequestOptions.
     * @return  ResponseInterface
     * @throws  Exception
     */
    public function request(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        try {
            $this->setAuthToken();
            $options = $this->getDataAuthToRequest($options);

            // se não vir o endpoint completo, monto aqui
            if (!preg_match('/http/', $uri)) {
                $uri = $this->base_uri.$uri;
            }

            $request = $this->client->request($method, $uri, $options);
        } catch (GuzzleException | BadResponseException $exception) {
            throw new Exception(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage(), $exception->getCode());
        }

        return $request;
    }

    /**
     * @param   array $options
     * @return  array
     */
    private function getDataAuthToRequest(array $options = []): array
    {
        // Não existe Content-Type.
        if (
            !array_key_exists('headers', $options) ||
            !array_key_exists('Content-Type', $options['headers'])
        ) {
            $options['headers']['Content-Type'] = "application/json";
        }

        // Não existe Accept.
        if (
            !array_key_exists('headers', $options) ||
            !array_key_exists('Accept', $options['headers'])
        ) {
            $options['headers']['Accept'] = "application/json";
        }

        // Define os dados para serem utilizado na requisição definidos no cabeçalho.
        if (array_key_exists('headers', $this->auth_request ?? [])) {
            if (!array_key_exists('headers', $options)) {
                $options['headers'] = array();
            }
            $options['headers'] = array_merge_recursive($options['headers'], $this->auth_request['headers']);
        }

        // Define os dados para serem utilizado na requisição definidos no corpo da requisição.
        if (array_key_exists('json', $this->auth_request ?? [])) {
            if (!array_key_exists('json', $options)) {
                $options['json'] = array();
            }
            $options['json'] = array_merge_recursive($options['json'], $this->auth_request['json']);
        }

        // Define os dados para serem utilizado na requisição definidos na URL.
        if (array_key_exists('query', $this->auth_request ?? [])) {
            if (!array_key_exists('query', $options)) {
                $options['query'] = array();
            }
            $options['query'] = array_merge_recursive($options['query'], $this->auth_request['query']);
        }

        return $options;
    }

    /**
     * @return void
     */
    private function setAuthRequest()
    {
        $this->auth_request = array();

        // Credential não encontrada.
        if (empty($this->getCredentials())) {
            return;
        }

        // Lê os valores definidos no contrutor do marketplace para definir os campos e valores a serem utilizados na requisição com o marketplace.
        foreach ($this->map_auth_request as $map_auth_request_key => $map_auth_request_value) {
            $field = $this->getCredentials($map_auth_request_key);
            // Encontrou o valor para associar.
            if ($field) {
                $this->auth_request[$map_auth_request_value['type']][$map_auth_request_value['field']] = $field;
            }
        }
    }

    /**
     * @param  array $map_auth_request [ 'CAMPO_DEFINIDO_NO_JSON_DA_TABELA_INTEGRATIONS' => [ 'field' => 'CAMPO_DA_REQUISIÇÃO_DO_MARKETPLACE', 'type' => 'headers|query|json' ] ]
     * @return void
     */
    public function setMapAuthRequest(array $map_auth_request)
    {
        $this->map_auth_request = $map_auth_request;
    }

    public function saveErrorBeforToSend(string $register_id, string $type, string $method, string $response)
    {
        $this->model_external_integration_history->create(array(
            'register_id'       => $register_id,
            'external_id'       => '',
            'type'              => $type,
            'method'            => $method,
            'uri'               => '',
            'request'           => '',
            'response_webhook'  => '{}',
            'status_webhook'    => 0,
            'response'          => $response,
        ));
    }
}