<?php

namespace Microservices\v1;

require_once 'system/libraries/Vendor/autoload.php';

use CI_DB_query_builder;
use CI_Lang;
use CI_Loader;
use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Model_stores;
use Model_settings;
use Model_vtex_ult_envio;

/**
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property Client $client
 * @property Model_settings $model_settings
 * @property Model_stores $model_stores
 * @property Model_vtex_ult_envio $model_vtex_ult_envio
 */

class Microservices
{
    /**
     * @var int Código da loja
     */
    public $store = 'null';

    /**
     * @var int|null Código da empresa
     */
    public $company;

    /**
     * @var Client Cliente of GuzzleHttp
     */
    public $client;

    /**
     * @var string Formato de comunicação na requisição
     */
    public $formatRequest = 'json';

    /**
     * @var string Endpoint de api do seller center, para requisições em nós mesmo, nas nossas APIs
     */
    public $process_url;

    /**
     * @var string Pasta do endpoint so microsserviço.
     */
    public $path_url;

    /**
     * @var string Código do seller center, é um parâmetro na tabela settings `sellercenter`
     */
    public $sellerCenter;

    /**
     * @var string Nome do seller center, é um parâmetro na tabela settings `sellercenter_name`
     */
    public $nameSellerCenter;

    /**
     * @var array Opções para requisição. Veja \GuzzleHttp\RequestOptions.
     */
    public $optionsRequest = array();

    /**
     * Instantiate a new Integration_v2 instance.
     * @throws Exception
     */
    public function __construct()
    {
        $this->load->model(
            array(
                'model_stores',
                'model_settings',
                'model_vtex_ult_envio'
            )
        );

        try {
            $this->setClientGuzzle();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

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
            'timeout' => 3
        ]);
    }

    /**
     * Define a url de processamento de API
     * @throws Exception
     */
    public function setProcessUrl()
    {
        $url = $this->model_settings->getValueIfAtiveByName('microservice_api_url');

        if (!$url) {
            throw new Exception('Parâmetro microservice_api_url não configurado.');
        }

        if (substr($url, -1) === '/') {
            $url = substr($url, 0, -1);
        }

        $this->process_url = $url;
    }

    /**
     * Define a pasta do microsserviço.
     *
     * @param string $path Pasta do microsserviço.
     * @example '/shipping_integrator/conectala/api'
     */
    public function setPathUrl(string $path)
    {
        $this->path_url = $path;
    }

    /**
     * Define a loja
     *
     * @param int|null $store Código da loja (stores.id)
     */
    public function setStore(?int $store)
    {
        if (is_null($store)) {
            $store = 'null';
        }

        $this->store = $store;

        return $this;
    }

    /**
     * Define a empresa
     *
     * @param int $company Código da empresa (company.id)
     */
    public function setCompany(int $company)
    {
        $this->company = $company;
    }

    /**
     * Define o código do seller center
     */
    public function setSellerCenter()
    {
        $this->sellerCenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
    }

    /**
     * Define o nome do seller center
     */
    public function setNameSellerCenter()
    {
        $this->nameSellerCenter = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
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
     * @param   array $options Request options to apply. See \GuzzleHttp\RequestOptions.
     * @return  ResponseInterface
     * @throws  Exception
     */
    public function request(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        $key = "{$this->sellerCenter}:microservices:jwt";
        $access_token = null; //CacheManager::get($key);
        if (!isset($access_token)) {
	        $authorization = $this->authenticatorKeycloak();
            $access_token = $authorization->token_type .' '. $authorization->access_token;
            // $expires_token       = $authorization->expires_in - 20;
            //if (!isset($access_token)) {
            //    CacheManager::setex($key, $access_token, $expires_token);
            //}
        }
        if (isset($access_token)) {
            $options['headers']['Authorization'] = $access_token;
        }

        if (!isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = "application/$this->formatRequest";
        }

        if (array_key_exists('headers', $this->optionsRequest)) {
            $options['headers'] = array_merge_recursive($options['headers'], $this->optionsRequest['headers']);
        }

        if (array_key_exists('json', $this->optionsRequest)) {
            if (!array_key_exists('json', $options)) {
                $options['json'] = array();
            }
            $options['json'] = array_merge_recursive($options['json'], $this->optionsRequest['json']);
        }

        if (array_key_exists('query', $this->optionsRequest)) {
            if (!array_key_exists('query', $options)) {
                $options['query'] = array();
            }
            $options['query'] = array_merge_recursive($options['query'], $this->optionsRequest['query']);
        }

        try {
            $request = $this->client->request($method, $this->process_url.$this->path_url.$uri, $options);
        } catch (Exception | GuzzleException | BadResponseException $exception) {
            throw new Exception(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage(), $exception->getCode());
        }

        return $request;
    }

    /**
     * @param   array       $data
     * @throws  Exception
     */
    public function createSetting(array $data)
    {
        try {
            $this->request('POST', "/setting/create", array('json' => array('setting' => $data)));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param   array       $data
     * @param   string      $setting
     * @throws  Exception
     */
    public function updateSetting(array $data, string $setting)
    {
        try {
            $this->request('PUT', "/setting/$setting", array('json' => $data));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @throws  Exception
     */
    public function authenticatorKeycloak()
    {
        $ms_authenticator_client_id = $this->model_settings->getValueIfAtiveByName('ms_authenticator_client_id');
        $ms_authenticator_realm = $this->model_settings->getValueIfAtiveByName('ms_authenticator_realm');
        $ms_authenticator_secret = $this->model_settings->getValueIfAtiveByName('ms_authenticator_secret');
        $ms_authenticator_url = $this->model_settings->getValueIfAtiveByName('ms_authenticator_url');

        if (
            !isset($ms_authenticator_client_id) || empty($ms_authenticator_client_id) ||
            !isset($ms_authenticator_realm) || empty($ms_authenticator_realm) ||
            !isset($ms_authenticator_secret) || empty($ms_authenticator_secret) ||
            !isset($ms_authenticator_url) || empty($ms_authenticator_url)
        ) {
            throw new Exception('Parâmetros do Keycloak não configurados.');
        }

        $options = array(
            'form_params' => array(
                'client_id' => $ms_authenticator_client_id,
                'client_secret' => $ms_authenticator_secret,
                'grant_type' => 'client_credentials'
            ),
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            )
        );

        $method = "POST";
        $uri = "$ms_authenticator_url/realms/$ms_authenticator_realm/protocol/openid-connect/token";

        try {
            $request = $this->client->request($method, $uri, $options);
        } catch (Exception|GuzzleException|BadResponseException $exception) {
            throw new Exception(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage(), $exception->getCode());
        }

        return json_decode($request->getBody());
    }

    public function getErrorFormatted(string $errors): array
    {
        $errorsFormatted = [];
        $errors = json_decode($errors, true);

        if (!is_array($errors)) {
            if (is_string($errors)) {
                return [$errors];
            }
            return (array)json_encode($errors, JSON_UNESCAPED_UNICODE);
        }
        foreach ($errors as $errors_field) {
            foreach ($errors_field as $error) {
                $errorsFormatted[] = $error;
            }
        }
        return $errorsFormatted;
    }
}
