<?php

namespace Marketplaces;

use CI_DB_query_builder;
use CI_Lang;
use CI_Loader;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Model_banks;
use Model_company;
use Model_integrations;
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
 */

class BaseMarketplace
{
    /**
     * @var Client Cliente of GuzzleHttp
     */
    private $client;

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
     * @var string Código do marketplace.
     */
    private $int_to;

    /**
     * @var array Mapa dos campos nas credencials no banco com os dados para autenticar.
     */
    private $map_auth_request = array();

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
                'model_integrations'
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
     * @param string $int_to
     * @throws Exception
     */
    public function setCredentials(string $int_to)
    {
        // As credenciais do marketplace já estão definidas.
        if ($this->getIntTo() === $int_to) {
            return;
        }

        // Define o novo int_to.
        $this->setIntTo($int_to);

        // Consulta os dadso da integração.
        $integration = $this->model_integrations->getIntegrationByIntTo($int_to, 0);

        // Integração não encontrada.
        if (!$integration) {
            $this->credentials = null;
            throw new Exception("Integration $int_to not found.");
        }

        // Credencial inválida.
        if (empty($integration['auth_data']) || in_array($integration['auth_data'], array('{}', '[]'))) {
            $this->credentials = null;
            throw new Exception("Credential to $int_to invalid.");
        }

        // Define a credencial.
        $this->credentials = json_decode($integration['auth_data']);

        // Define os campos para ser utilizado nas requisições com o marketplace.
        $this->setAuthRequest();
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
     * @param string $int_to
     * @return void
     */
    private function setIntTo(string $int_to)
    {
        $this->int_to = $int_to;
    }

    /**
     * @return string|null
     */
    private function getIntTo(): ?string
    {
        return $this->int_to;
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
            $this->auth_request = array();
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
}