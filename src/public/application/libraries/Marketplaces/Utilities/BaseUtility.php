<?php

namespace Marketplaces\Utilities;

use CI_DB_query_builder;
use CI_Lang;
use CI_Loader;
use Error;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Model_banks;
use Model_company;
use Model_integrations;
use Model_legal_panel;
use Model_order_value_refund_on_gateways;
use Model_orders;
use Model_product_return;
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
 * @property Model_legal_panel $model_legal_panel
 * @property Model_product_return $model_product_return
 * @property Model_order_value_refund_on_gateways $model_order_value_refund_on_gateways
 */

class BaseUtility
{
    /**
     * @var Client Cliente of GuzzleHttp
     */
    public $client;

    /**
     * @var object Instância da integração
     */
    public $external_integration;

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
     * @var string|null Nome da integradora externa
     */
    private $external_integration_name = null;

    /**
     * Instantiate a new BaseMarketplace instance.
     */
    public function __construct()
    {
        $this->load->model(
            array(
                'model_settings',
                'model_integrations',
                'model_stores',
                'model_company',
                'model_orders',
                'model_legal_panel',
                'model_product_return',
                'model_order_value_refund_on_gateways'
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
     * @throws Exception
     */
    public function setBaseUri(string $base_uri)
    {
        $this->base_uri = $base_uri;
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
        $options  = $this->getDataAuthToRequest($options);

        try {
            $request = $this->client->request($method, $this->base_uri.$uri, $options);
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

    /**
     * @param string $external_integration_name
     * @return void
     * @throws Exception
     */
    public function setExternalIntegration(string $external_integration_name)
    {
        if ($external_integration_name != $this->external_integration_name) {
            // Por padrão seguir o nome da biblioteca com apenas a primeira letra em maiúsculo, pode usar underline em separação.
            $external_integration_name = ucfirst($external_integration_name);


            // Biblioteca não encontrada.
            if (!file_exists(APPPATH . "libraries/Marketplaces/External/$external_integration_name.php")) {
                throw new Exception("Biblioteca para o marketplaces $external_integration_name não configurado");
            }

            // Gambiarra para saber se já requeriu a biblioteca.
            $arrValidate = array_map(function ($item) use ($external_integration_name) {
                return likeText(
                    "%application==libraries==Marketplaces==External==$external_integration_name.php%",
                    str_replace('/', '==', str_replace('\\', '==', $item))
                );
            }, get_included_files());

            // Biblioteca já foi requerida, só limpa a propriedade, se não, requere.
            if (!in_array(true, $arrValidate)) {
                require APPPATH . "libraries/Marketplaces/External/$external_integration_name.php";
            } else {
                unset($this->external_integration);
            }

            try {
                $instance = "Marketplaces\\External\\$external_integration_name";
                $this->external_integration = new $instance($this);
            } catch (Exception|Error $exception) {
                throw new Exception($exception->getMessage());
            }
        }
    }
}