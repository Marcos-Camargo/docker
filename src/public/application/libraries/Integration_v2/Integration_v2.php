<?php

namespace Integration;

require_once 'system/libraries/Vendor/autoload.php';

require_once APPPATH . 'libraries/Helpers/StringHandler.php';

use DateTime;
use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Utils;
use Integration\Integration_v2\anymarket\AnyMarketConfiguration;
use Integration_v2\hub2b\Resources\Configuration;
use Integration_v2\tray\Resources\Auth;
use Integration_v2\hub2b\Resources\Auth as AuthHub2b;
use InvalidArgumentException;
use Model_catalogs;
use Psr\Http\Message\ResponseInterface;
use Model_stores;
use Model_products;
use Model_users;
use Model_api_integrations;
use Model_settings;
use Model_orders;
use Model_job_integration;
use Model_log_integration;
use Model_log_integration_unique;
use Model_atributos_categorias_marketplaces;
use Model_brands;
use Model_category;
use Model_attributes;
use Model_products_marketplace;
use Model_orders_to_integration;
use Model_frete_ocorrencias;
use Model_freights;
use Model_orders_integration_history;
use Model_fields_orders_add;
use Model_fields_orders_mandatory;
use Model_orders_payment;
use stdClass;
use UploadProducts;
use CalculoFrete;
use libraries\Helpers\StringHandler;

/**
 * @property \CI_DB_query_builder $db
 * @property \CI_Loader $load
 * @property \CI_Lang $lang
 * @property Client $client
 * @property Model_stores $model_stores
 * @property Model_products $model_products
 * @property Model_users $model_users
 * @property Model_api_integrations $model_api_integrations
 * @property Model_settings $model_settings
 * @property Model_orders $model_orders
 * @property Model_job_integration $model_job_integration
 * @property Model_log_integration $model_log_integration
 * @property Model_log_integration_unique $model_log_integration_unique
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_brands $model_brands
 * @property Model_category $model_category
 * @property Model_attributes $model_attributes
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_orders_to_integration $model_orders_to_integration
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_freights $model_freights
 * @property Model_integrations $model_integrations
 * @property \Model_job_schedule $model_job_schedule
 * @property Model_orders_integration_history $model_orders_integration_history
 * @property Model_catalogs $model_catalogs
 * @property Model_fields_orders_add $model_fields_orders_add
 * @property Model_fields_orders_mandatory $model_fields_orders_mandatory
 * @property Model_orders_payment $model_orders_payment
 * @property Model_order_to_delivered $model_order_to_delivered
 *
 * @property UploadProducts $uploadproducts
 * @property CalculoFrete $calculofrete
 */

class Integration_v2
{
    const LOG_TYPE_SUCCESS = 'S';
    const LOG_TYPE_ERROR = 'E';
    const LOG_TYPE_WARNING = 'W';

    /**
     * @var int Código da loja
     */
    public $store;

    /**
     * @var int Código da empresa
     */
    public $company;

    /**
     * @var Client Cliente of GuzzleHttp
     */
    public $client;
    public $client_cnl;

    /**
     * @var array Dados da integração (api_integrations)
     */
    public $integrationData;

    /**
     * @var string Nome da integração (api_integrations.integration)
     */
    public $integration;

    /**
     * @var int Quantidade de tentativas em casos de bloqueio por limite de requisição
     */
    public $countAttempt = 0;

    /**
     * @var bool Indica se a API em bloqueio por limite de requisições
     */
    public $restBlocked = false;

    /**
     * @var string Formato de comunicação na requisição
     */
    public $formatRequest = 'json';

    /**
     * @var object|null Credenciais da integradora
     */
    public $credentials = null;

    /**
     * @var object Configurações da integração
     */
    public $configurations;

    /**
     * @var string Rotina que está em execução
     */
    public $job;

    /**
     * @var string Código único para controle. Geralmente usada para guardar código do pedido e/ou produto para geração de log
     */
    public $unique_id;

    /**
     * @var string Data/hora em que a rotina foi executada
     */
    public $dateStartJob;

    /**
     * @var string Endpoint de api do seller center, para requisições em nós mesmo, nas nossas APIs
     */
    public $process_url;

    /**
     * @var string Código do seller center, é um parâmetro na tabela settings `sellercenter`
     */
    public $sellerCenter;

    /**
     * @var string Nome do seller center, é um parâmetro na tabela settings `sellercenter_name`
     */
    public $nameSellerCenter;

    /**
     * @var array Dados da loja. Tabela stores.
     */
    public $dataStore;

    /**
     * @var array Dados de logística da loja. calculofrete->getLogisticStore()
     */
    public $logisticStore;

    /**
     * @var string|null Data/hora em que a rotina foi executada por último
     */
    public $dateLastJob = null;

    /**
     * @var bool $store_uses_catalog Loja usa catálogo.
     */
    public $store_uses_catalog = false;

    /**
     * @var array $user_permission Permissão do usuário.
     */
    public $user_permission = [];

    protected $ignoreIntegrationLogTypes = [];

    /**
     * @var bool $debug Debug nas requisições com guzzle.
     */
    private $debug = false;

    /**
     * @var int $user_id_to_debug Email para ser usado quando a loja não tem um cadastrado, somente para validação específicas.
     */
    public $user_id_to_debug = null;

    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct()
    {
        $this->load->model(
            array(
                'model_users',
                'model_orders',
                'model_stores',
                'model_brands',
                'model_settings',
                'model_products',
                'model_category',
                'model_freights',
                'model_attributes',
                'model_job_integration',
                'model_log_integration',
                'model_log_integration_unique',
                'model_api_integrations',
                'model_frete_ocorrencias',
                'model_products_marketplace',
                'model_orders_to_integration',
                'model_atributos_categorias_marketplaces',
                'model_integrations',
                'model_orders_integration_history',
                'model_job_schedule',
                'model_catalogs',
                'model_fields_orders_mandatory',
                'model_fields_orders_add',
                'model_orders_payment',
                'model_order_to_delivered'

            )
        );

        $this->load->library(
            array(
                'CalculoFrete',
                'UploadProducts'
            )
        );

        $this->lang->load('api', 'portuguese_br');
        $this->lang->load('application', 'portuguese_br');
        $this->lang->load('messages', 'portuguese_br');

        $this->setClientGuzzle();
        $this->setProcessUrl();
        $this->setSellerCenter();
        $this->setNameSellerCenter();
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
    private function setClientGuzzle(int $timout = 900)
    {
        $this->client = new Client([
            'verify' => false, // no verify ssl
            'timeout' => $timout,
            'connect_timeout' => $timout,
            'allow_redirects' => true
        ]);
        $this->client_cnl = new Client([
            'verify' => false, // no verify ssl
            'timeout' => 120,
            'connect_timeout' => 120,
            'allow_redirects' => true
        ]);
    }

    /**
     * Define os dados para integração
     *
     * @param int $store
     */
    public function setAuth(int $store)
    {
        $this->setStore($store);
        $this->setDataStore();
        $this->setCompany($this->dataStore['company_id']);
        $dataIntegration = $this->model_api_integrations->getIntegrationByStore($store);

        if (!$this->dataStore) {
            throw new InvalidArgumentException("Loja ($store) não localizado");
        }

        // 'import_internal_by_csv', serve para não validar na tabela de integrações, pois nem todas as lojas terão.
        if (!$dataIntegration) {
            if (is_null($this->credentials) || !array_key_exists('import_internal_by_csv', $this->credentials->api_internal)) {
                throw new InvalidArgumentException("Integração para a loja ($store) não localizada.");
            }
        }

        $this->setLogisticStore();

        try {
            $this->setCompany($this->dataStore['company_id']);
            if (is_null($this->credentials) || !array_key_exists('import_internal_by_csv', $this->credentials->api_internal)) {
                $this->integrationData = $dataIntegration;
                $this->setIntegration($dataIntegration['integration']);
                $this->setConfigurations($dataIntegration);
                $this->setCredentials($dataIntegration);
                if ($dataIntegration['integration'] == 'anymarket') {
                    $this->setClientGuzzle(10);
                } else {
                    $this->setClientGuzzle(10);
                }
            }
            $this->setCredentialsApiInternal();
            $this->setResourceConfiguration();
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (!$this->model_settings->getValueIfAtiveByName('ignore_integration_inactive_store')) {
            if (!Model_api_integrations::isActiveIntegration($dataIntegration) && !array_key_exists('import_internal_by_csv', $this->credentials->api_internal)) {
                throw new InvalidArgumentException("Loja '{$dataIntegration['store_name']}' ou integração '{$dataIntegration['integration']}' inativas. Não é possível concluir o processamento.");
            }
        }
    }

    protected function setResourceConfiguration()
    {

    }

    /**
     * Define a url de processamento de API
     */
    public function setProcessUrl()
    {
        $url = $this->model_settings->getValueIfAtiveByName('internal_api_url');
        if (!$url) {
            $url = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
            if (!$url) {
                $url = base_url();
            }
        }

        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        $this->process_url = $url;
    }

    /**
     * Define se haverá debug
     *
     * @param bool $debug Debug
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * Retorna de haverá debug
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Define a loja
     *
     * @param int $store Código da loja (stores.id)
     */
    public function setStore(int $store)
    {
        $this->store = $store;
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
     * Define a integração
     *
     * @param string $integration Código da loja (stores.id)
     */
    public function setIntegration(string $integration)
    {
        if (in_array(
            $integration,
            array(
                'viavarejo_b2b_casasbahia',
                'viavarejo_b2b_pontofrio',
                'viavarejo_b2b_extra'
            ))
        ) {
            $integration = 'viavarejo_b2b';
        }

        // [OEP-1789] Atualmente, a integração com a mevo, terá o mesmo formato da vtex.
        if ($integration == 'mevo') {
            $integration = 'vtex';
        }

        $this->integration = strtolower($integration);
    }

    /**
     * Define as credenciais da integração da loja
     *
     * @param array $dataCredentials Código da loja (api_integrations.credentials)
     */
    public function setCredentials(array $dataCredentials)
    {
        $credentials = $dataCredentials['credentials'];

        $this->credentials = Utils::jsonDecode($credentials);
        $this->credentials->integration = $dataCredentials['integration'];

        try {
            $this->setCredentialsAux();
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }
    }

    public function setConfigurations(array $data)
    {
        $configurations = $data['configuration'] ?? null;
        $this->configurations = Utils::jsonDecode($configurations ?? '{}');
    }

    /**
     * Define credenciais de api interna para chamar as nossas próprias api para criação de produtos
     */
    public function setCredentialsApiInternal()
    {
        // Recuperar e-mail de qualquer usuário que pode gerenciar a loja
        $emailUser = $this->getEmailCredentialsApiInternal();

        if (is_null($this->credentials) || !property_exists($this->credentials, 'api_internal')) {
            if (is_null($this->credentials)) {
                $this->credentials = new StdClass;
            }
            $this->credentials->api_internal = array();
        }

        $this->credentials->api_internal = array_merge(
            $this->credentials->api_internal,
            array(
                'Content-Type'  => 'application/json',
                'accept'        => 'application/json;charset=UTF-8',
                'x-user-email'  => $emailUser,
                'x-api-key'     => $this->dataStore['token_api'],
                'x-store-key'   => $this->store
            )
        );
    }

    public function getEmailCredentialsApiInternal()
    {
        $user = $this->model_users->fetchStoreManagerUser($this->store, $this->company);
        $emailUser = $user['email'] ?? null;

        if ($emailUser === null) {
            if (empty($this->user_id_to_debug)) {
                throw new InvalidArgumentException('Loja não contém nenhum usuário, é necessário a loja ter pelo menos um usuário para usar a integração.');
            }
            $user = $this->model_users->getUserById($this->user_id_to_debug);
            $emailUser = $user['email'] ?? null;

            // Mesmo com o email não foi encontrado usuário.
            if ($emailUser === null) {
                throw new InvalidArgumentException('Loja não contém nenhum usuário, é necessário a loja ter pelo menos um usuário para usar a integração.');
            }
        }

        $user_id = $user['id'] ?? 0;
        $group   = $this->model_users->getUserGroup($user_id);
        if ($group) {
            $this->user_permission = unserialize($group['permission']);
        }

        return $emailUser;
    }

    /**
     * Define o job
     *
     * @param string $job Nome do job que será executado (job_integration.job)
     */
    public function setJob(string $job)
    {
        $this->job = $job;
    }

    /**
     * Define o unique id
     *
     * @param string $uniqueId Código único para controle
     */
    public function setUniqueId(string $uniqueId)
    {
        $uniqueId = explode(':', $uniqueId);
        $this->unique_id = $uniqueId[0] ?? $uniqueId;
    }

    /**
     * Define o código do seller center
     */
    public function setSellerCenter()
    {
        $sellerCenter       = $this->model_settings->getSettingDatabyName('sellercenter');
        $this->sellerCenter = $sellerCenter['value'];
    }

    /**
     * Define o nome do seller center
     */
    public function setNameSellerCenter()
    {
        $nameSellerCenter       = $this->model_settings->getSettingDatabyName('sellercenter_name');
        $this->nameSellerCenter = $nameSellerCenter['value'];
    }

    /**
     * Define os dados da loja
     */
    public function setDataStore()
    {
        $this->dataStore = $this->model_stores->getStoresData($this->store);

        if (!$this->dataStore) {
            throw new InvalidArgumentException("Loja $this->store não encontrada");
        }

        $this->store_uses_catalog = count($this->model_catalogs->getCatalogsStoresDataByStoreId($this->store)) > 0;
    }

    /**
     * Define a logística que a loja utiliza
     */
    public function setLogisticStore()
    {
        $this->logisticStore = $this->calculofrete->getLogisticStore(array(
            'freight_seller'        => $this->dataStore['freight_seller'],
            'freight_seller_type'   => $this->dataStore['freight_seller_type'],
            'store_id'              => $this->dataStore['id']
        ), true);
        if (strpos($this->integration, 'viavarejo_b2b') !== false) {
            $this->logisticStore['type'] = $this->integration;
        }

        // [OEP-1789] Atualmente, a integração com a mevo, terá o mesmo formato da vtex.
        if ($this->logisticStore['type'] == 'mevo') {
            $this->logisticStore['type'] = 'vtex';
        }
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
     * @throws  InvalidArgumentException
     */
    public function request(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        if (!$this->restBlocked) {
            $this->countAttempt = 0;
        }

        if (!isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = "application/$this->formatRequest";
        }

        try {
            switch ($this->integration) {
                case 'lojaintegrada':
                    $options['query']['format'] = 'json';
                    $appKey = $this->model_settings->getValueIfAtiveByName('chave_aplicacao_loja_integrada');
                    $options['headers']['Authorization'] = "chave_api {$this->credentials->chave_api} aplicacao {$appKey}";
                    $lojaIntegradaApi = $this->model_settings->getValueIfAtiveByName('loja_integrada_api_url');
                    if (!preg_match('/http/', $uri)) {
                        $uri = str_replace("//v1/", "/v1/", str_replace("/v1//", "/v1/", "/v1/{$uri}"));
                        $uri = str_replace("/v1/v1/", "/v1/", "{$lojaIntegradaApi}{$uri}");
                        $uri = str_replace("//v1/", "/v1/", str_replace("/v1//", "/v1/", $uri));
                    }
                    break;
                case 'anymarket':
                    $accessToken = $this->credentials->token_anymarket;
                    $parameters = (array)$this->credentials;
                    require_once APPPATH . 'libraries/Integration_v2/anymarket/AnyMarketConfiguration.php';
                    $anyConfig = new AnyMarketConfiguration(
                        $accessToken,
                        $parameters
                    );
                    if (!preg_match('/http/', $uri)) {
                        $apiEndpoint = $anyConfig->getHost();
                        $uri = "{$apiEndpoint}{$uri}";
                    }
                    $options['headers']['appId'] = $anyConfig->getAppId();
                    $options['headers']['token'] = $anyConfig->getAccessToken();
                    break;
                case 'jn2':
                    $options['headers']['Authorization'] = "Bearer {$this->credentials->token_jn2}";
                    break;
                case 'vtex':
                    $base_url = "{$this->credentials->environment_vtex}.com.br";
                    if (!empty($this->credentials->base_url_external)) {
                        $base_url = $this->credentials->base_url_external;
                    }

                    // se não vir o endpoint completo, monto aqui
                    if (!preg_match('/http/', $uri)) {
                        $uri = "https://{$this->credentials->account_name_vtex}.$base_url/$uri";
                    }

                    $options['headers']['accept'] = "application/vnd.vtex.ds.v10+json";
                    $options['headers']['X-VTEX-API-AppToken'] = $this->credentials->token_vtex;
                    $options['headers']['X-VTEX-API-AppKey'] = $this->credentials->appkey_vtex;
                    break;
                case 'eccosys':
                    $options['headers']['Authorization'] = $this->credentials->token_eccosys;
                    break;
                case 'bseller':
                    $options['headers']['X-Auth-Token'] = $this->credentials->token_bseller;
                    break;
                case 'bling_v3':
                    if (!empty($this->credentials->revoke)) {
                        throw new InvalidArgumentException("O acesso ao aplicativo foi revogado.", 401);
                    }
                    // se não vir o endpoint completo, monto aqui
                    if (!preg_match('/http/', $uri)) {
                        $uri = "https://www.bling.com.br/Api/v3/$uri";
                    }
                    require_once APPPATH . 'libraries/Integration_v2/bling_v3/Configuration.php';
                    $blingConfig = new bling_v3\Configuration($this->credentials, $this->store);
                    $options['headers']['Authorization'] = $blingConfig->getAccessToken();
                    $this->credentials = $blingConfig->getNewCredentials();
                    break;
                case 'pluggto':
                    // se não vir o endpoint completo, monto aqui
                    if (!preg_match('/http/', $uri)) {
                        $uri = "https://api.plugg.to/$uri";
                    }

                    if (isset($this->credentials->access_token)) {
                        $options['query']['access_token'] = $this->credentials->access_token;
                    }

                    break;
                case 'tiny':

                    // se não vir o endpoint completo, monto aqui
                    if (!preg_match('/http/', $uri)) {
                        $uri = "https://api.tiny.com.br/api2/$uri";
                    }

                    $options['query']['token'] = $this->credentials->token_tiny;
                    $options['query']['formato'] = $this->formatRequest;

                    break;
                case 'bling':

                    // se não vir o endpoint completo, monto aqui
                    if (!preg_match('/http/', $uri)) {
                        $uri = "https://bling.com.br/Api/v2/$uri/$this->formatRequest/";
                    }

                    if (
                        array_key_exists('json', $options) &&
                        array_key_exists('xml', $options['json']) &&
                        (
                            $method == "POST" ||
                            $method == "PUT"
                        )
                    ) {
                        $options['json']['xml'] = rawurlencode($options['json']['xml']);
                    }

                    if (preg_match('/produto/', $uri)) {
                        $options['query']['loja'] = $this->credentials->loja_bling;
                    }

                    $options['query']['apikey'] = $this->credentials->apikey_bling;
                    break;
                case 'viavarejo_b2b':
                    // se não vir o endpoint completo, monto aqui
                    if (!preg_match('/http/', $uri)) {
                        if (ENVIRONMENT === 'development') {
                            $endpoint = "https://b2b-integracao.{$this->credentials->flag}.viavarejo-hlg.com.br";
                        } else {
                            $endpoint = "https://api-integracao-b2b.{$this->credentials->flag}.com.br";
                        }

                        $uri = "$endpoint/$uri";
                    }

                    $options['headers']['Authorization'] = $this->credentials->token_b2b_via;
                    $options['headers']['Accept'] = 'text/plain';

                    if (ENVIRONMENT !== 'development') {
                        // Consultar informações para chamar o nosso servidor com IP fixo.
                        $tokenEndpoint  = $this->model_settings->getValueIfAtiveByName('fixed_ip_token_url');
                        $endpoint       = $this->model_settings->getValueIfAtiveByName('fixed_ip_api_url');

                        if ($method == 'GET' && empty($options['query']['cnpj'])) {
                            $options['query']['cnpj'] = cnpj(onlyNumbers($this->credentials->cnpj));
                        }

                        $options = array(
                            'json' => array(
                                'uri'       => $uri,
                                'method'    => $method,
                                'options'   => $options
                            ),
                            'headers' => array(
                                'api-key' => $tokenEndpoint
                            )
                        );
                        $method  = "POST";
                        $uri = "$endpoint/request";
                    }
                    break;
                case 'tray':
                    require_once APPPATH . 'libraries/Integration_v2/tray/Resources/Auth.php';
                    $options['query']['access_token'] = Auth::getInstance()->fetchAccessToken($this->store);
                    if (!preg_match('/http/', $uri)) {
                        $endpoint = Auth::getInstance()->fetchApiAddress($this->store);
                        $uri = "$endpoint/$uri";
                    }
                    break;
                case 'hub2b':
                    require_once APPPATH . 'libraries/Integration_v2/hub2b/Resources/Auth.php';
                    require_once APPPATH . 'libraries/Integration_v2/hub2b/Resources/Configuration.php';
                    if (strpos($uri, 'listskus') !== false) {
                        $options['headers']['Auth'] = $this->credentials->authToken ?? '';
                        $endpoint = Configuration::getApiV1URL();
                    } else {
                        $options['query']['access_token'] = AuthHub2b::getInstance()->fetchAccessToken($this->store);
                        $endpoint = Configuration::getApiV2URL();
                    }
                    if (!preg_match('/http/', $uri)) {
                        $configHub2b = new Configuration($this->model_settings);
                        $uri = "{$endpoint}{$uri}";
                        $uri = str_replace('ID_MARKETPLACE', $configHub2b->getMarketPlaceId(), $uri);
                        $uri = str_replace('ID_TENANT', $this->credentials->idTenant ?? '', $uri);
                        $uri = str_replace('ID_SALES_CHANNEL', $configHub2b->getSalesChannelId(), $uri);
                    }
                    break;
                case 'ideris':
                    require_once APPPATH . 'libraries/Integration_v2/ideris/Resources/Auth.php';
                    $accessToken = \Integration_v2\ideris\Resources\Auth::getInstance()->fetchAccessToken($this->store);
                    $options['headers']['Authorization'] = "Bearer {$accessToken}";
                    if (!preg_match('/http/', $uri)) {
                        $endpoint = \Integration_v2\ideris\Resources\Auth::getInstance()->fetchApiAddress($this->store);
                        $uri = "{$endpoint}{$uri}";
                    }
                    break;
                case 'magalu':
                require_once APPPATH . 'libraries/Integration_v2/magalu/Resources/Auth.php';
                $accessToken = \Integration_v2\magalu\Resources\Auth::getInstance()->fetchAccessToken($this->store);
                $options['headers']['Authorization'] = "Bearer {$accessToken}";
                if (!preg_match('/http/', $uri)) {
                    $endpoint = \Integration_v2\magalu\Resources\Auth::getInstance()->fetchApiAddress($this->store);
                    $uri = "{$endpoint}{$uri}";
                }
                break;// NÃO REMOVER!
                case 'NEW_INTEGRATION':
                    $options['headers']['API_KEY'] = $this->credentials->API_KEY;
                    break;
            }

            if ($this->getDebug()) {
                $options['debug'] = $this->getDebug();
            }

            $request = $this->client->request($method, $uri, $options);
        } catch (InvalidArgumentException | GuzzleException | BadResponseException $exception) {
            if (in_array($this->integration, array('bling', 'tiny', 'bling_v3'))) {
                // Se for bling, irá ver se é um bloqueio de requisição para tentar novamente.
                try {
                    $this->checkToRevokeAccess($exception);
                    $request = $this->apiBlockSleepBling($exception, $method, $uri, $options);
                } catch (InvalidArgumentException | Exception $exception) {
                    throw new InvalidArgumentException($exception->getMessage(), $exception->getCode());
                }
            } else if (in_array($this->integration, ['lojaintegrada','hub2b'])) {
                try {
                    $request = $this->apiBlockSleepLojaIntegrada($exception, $method, $uri, $options);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException($exception->getMessage(), $exception->getCode() );
                }
            }  else if (in_array($this->integration, ['ideris'])) {
                // Se for bling, irá ver se é um bloqueio de requisição para tentar novamente.
                try {
                    $request = $this->apiBlockSleepIderis($exception, $method, $uri, $options);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException($exception->getMessage(), $exception->getCode() );
                }

            }  else if (in_array($this->integration, ['magalu'])) {
                // Se for magalu, irá ver se é um bloqueio de requisição para tentar novamente.
                try {
                    return $this->apiBlockSleepMagalu($exception, $method, $uri, $options);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException($exception->getMessage(), $exception->getCode());
                }
            } else {
                $message = $exception->getMessage();
                if (method_exists($exception, 'getResponse')) {
                    if (method_exists($exception->getResponse(), 'getBody')) {
                        if (method_exists($exception->getResponse()->getBody(), 'getContents')) {
                            $message = $exception->getResponse()->getBody()->getContents();
                            $exception->getResponse()->getBody()->seek(0);
                        }
                    }
                }
                throw new InvalidArgumentException($message, $exception->getCode());
            }
        }

        try {
            // Se for tiny, irá ver se é um bloqueio de requisição para tentar novamente.
            $request = $this->apiBlockSleepTiny($request, $method, $uri, $options);
            // Valida se deu status 200 e a integradora trata erros no status 200
            $this->checkErrorWithStatus200($request);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode());
        }

        return $request;
    }

    /**
     * Verifica se a API foi bloqueada por limite de requisição
     * Atualmente funcional para Bling
     *
     * @param   Exception       $exception  Requisição
     * @param   string          $method     Método da requisição
     * @param   string          $uri        URL requisição
     * @param   array           $options    Request options to apply. See \GuzzleHttp\RequestOptions.
     * @return  Response                    Retorno da requisição
     * @throws  InvalidArgumentException
     */
    public function apiBlockSleepBling(Exception $exception, string $method, string $uri = '', array $options = [])
    {
        if (!in_array($this->integration, array('bling', 'bling_v3'))) {
            $message = $exception->getMessage();
            if (method_exists($exception, 'getResponse')) {
                $message = $exception->getResponse()->getBody()->getContents();
                $exception->getResponse()->getBody()->seek(0);
            }
            throw new InvalidArgumentException($message, $exception->getCode());
        }

        $attempts = 15;

        // enquanto a api estiver bloqueada ficará a tentar até encontrar o resultado
        $httpCode = method_exists($exception, 'getCode') ? $exception->getCode() : $exception->getStatusCode();

        if ($httpCode == 401 && $this->integration === 'bling') {
            foreach (array(
                 'Integration_v2/Product/bling/CreateProduct',
                 'Integration_v2/Product/bling/UpdateProduct',
                 'Integration_v2/Product/bling/UpdatePriceStock',
                 'Integration_v2/Order/CreateOrder',
                 'Integration_v2/Order/UpdateStatus'
            ) as $module) {
                $this->model_job_schedule->deleteByModuleAndParam($module, $this->store);
            }
        }

        while ($httpCode == 429) {
            $this->restBlocked = true;
            if ($this->countAttempt > $attempts) {
                throw new InvalidArgumentException('API request blocked. Try again later!', $exception->getCode());
            }

            // API Bloqueada, vou esperar 5 segundos e tentar novamente 15x
            sleep(5); // espera 5 segundos
            $this->countAttempt++;
            $response = $this->request($method, $uri, $options); // enviar uma nova requisição para ver se já liberou
            $exception = $response;
            $httpCode = method_exists($exception, 'getCode') ? $exception->getCode() : $exception->getStatusCode();
        }

        $this->restBlocked = false;

        if (!isset($response)) {
            $message = $exception->getMessage();
            if (method_exists($exception, 'getResponse')) {
                $message = $exception->getResponse()->getBody()->getContents();
                $exception->getResponse()->getBody()->seek(0);
            }
            throw new InvalidArgumentException($message, $exception->getCode());
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    public function checkToRevokeAccess(Exception $exception)
    {
        if ($this->integration !== 'bling_v3') {
            return;
        }

        // {"error":{"type":"invalid_token","message":"invalid_token","description":"The access token provided is invalid"}}
        $code = $exception->getCode();
        $message = $exception->getMessage();
        if (method_exists($exception, 'getResponse')) {
            $message = $exception->getResponse()->getBody()->getContents();
            $exception->getResponse()->getBody()->seek(0);
        }
        $message_decode = json_decode($message);

        if (in_array($code, array(401, 400))) {
            $has_revoke = false;
            if (
                !empty($message_decode->error->type) &&
                (
                    $message_decode->error->type == 'invalid_token' ||
                    $message_decode->error->type == 'invalid_grant'
                )
            ) {
                $has_revoke = true;
                $this->setUniqueId('error_validation_revoke');
                $this->log_integration(
                    "O acesso ao aplicado Bling foi revogado",
                    "<p>Acesse a página em <u>Integração → Solicitar Integração</u>, selecione a integração com Bling v3 e renove o acesso ao aplicado.</p>",
                    "E");
            } else if (!empty($message_decode->error->message) && $message_decode->error->message == 'Invalid authorization code') {
                $has_revoke = true;
                $this->setUniqueId('error_validation_revoke');
                $this->log_integration(
                    "O acesso ao aplicado Bling foi revogado",
                    "<p>Por motivos de segurança, o código de autorização da foi revogado. Acesse a página em <u>Integração → Solicitar Integração</u>, selecione a integração com Bling v3 e renove o acesso ao aplicado.</p>",
                    "E");
            } else if (!empty($message_decode->error->message) && $message_decode->error->message == 'Empresa inativa') {
                $has_revoke = true;
                $this->setUniqueId('error_validation_revoke');
                $this->log_integration(
                    "Empresa inativa no Bling",
                    "<p>{$message_decode->error->description}. Acesse a conta Bling para regularizar o acesso.</p>",
                    "E");
            }

            if (empty($this->credentials->revoke) && $has_revoke) {
                $this->setUniqueId('error_validation_revoke');
                $blingConfig = new bling_v3\Configuration($this->credentials, $this->store);

                if (strtotime(addHoursToDate($this->credentials->expire_at, 5)) < strtotime(dateNow()->format(DATETIME_INTERNATIONAL))) {
                    $blingConfig->updateCredentials(array('revoke' => true));
                }
            }

            if ($this->unique_id === 'error_validation_revoke') {
                throw new Exception(!empty($message_decode->error->message) ? $message_decode->error->message : $message, $code);
            }
        }
    }

    /**
     * Verifica se a API foi bloqueada por limite de requisição
     * Atualmente funcional para Bling
     *
     * @param Exception $exception Requisição
     * @param string $method Método da requisição
     * @param string $uri URL requisição
     * @param array $options Request options to apply. See \GuzzleHttp\RequestOptions.
     * @return  Response                    Retorno da requisição
     * @throws  InvalidArgumentException
     */
    public function apiBlockSleepLojaIntegrada(Exception $exception, string $method, string $uri = '', array $options = [])
    {
        $attempts = 15;

        // enquanto a api estiver bloqueada ficará a tentar até encontrar o resultado
        $httpCode = method_exists($exception, 'getCode') ? $exception->getCode() : $exception->getStatusCode();

        while ($httpCode == 429) {
            $this->restBlocked = true;
            if ($this->countAttempt > $attempts) {
                throw new InvalidArgumentException('API request blocked. Try again later!', $exception->getCode());
            }
            // API Bloqueada, vou esperar 10 segundos e tentar novamente 15x
            sleep(10); // espera 10 segundos
            $this->countAttempt++;
            $response = $this->request($method, $uri, $options); // enviar uma nova requisição para ver se já liberou
            $exception = $response;
            $httpCode = method_exists($exception, 'getCode') ? $exception->getCode() : $exception->getStatusCode();
        }

        $this->restBlocked = false;

        if (!isset($response)) {
            $message = $exception->getMessage();
            if (method_exists($exception, 'getResponse')) {
                $message = $exception->getResponse()->getBody()->getContents();
                $exception->getResponse()->getBody()->seek(0);
            }
            throw new InvalidArgumentException($message, $exception->getCode());
        }

        return $response;
    }

    public function apiBlockSleepIderis(Exception $exception, string $method, string $uri = '', array $options = [])
    {
        $httpCode = method_exists($exception, 'getCode') ? $exception->getCode() : $exception->getStatusCode();
        if ($this->countAttempt == 0 && in_array((int)$httpCode, [401])) {
            $this->countAttempt++;
            \Integration_v2\ideris\Resources\Auth::getInstance()->hackRefreshToken();
            return $this->request($method, $uri, $options);
        }
        $message = $exception->getMessage();
        if (method_exists($exception, 'getResponse')) {
            $message = $exception->getResponse()->getBody()->getContents();
            $exception->getResponse()->getBody()->seek(0);
        }
        throw new InvalidArgumentException($message, $exception->getCode());
    }

    public function apiBlockSleepMagalu(Exception $exception, string $method, string $uri = '', array $options = [])
    {
        $httpCode = method_exists($exception, 'getCode') ? $exception->getCode() : $exception->getStatusCode();
        if ($this->countAttempt == 0 && in_array((int)$httpCode, [401])) {
            $this->countAttempt++;
            \Integration_v2\magalu\Resources\Auth::getInstance()->hackRefreshToken();
            return $this->request($method, $uri, $options);
        }
        $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
        throw new InvalidArgumentException($message, $httpCode);
    }

    /**
     * Verifica se a API foi bloqueada por limite de requisição.
     * Atualmente funcional para Tiny.
     *
     * @param   Response        $response   Requisição
     * @param   string          $method     Método da requisição
     * @param   string          $uri        URL requisição
     * @param   array           $options    Request options to apply. See \GuzzleHttp\RequestOptions.
     * @return  Response                    Retorno da requisição
     * @throws  InvalidArgumentException
     */
    public function apiBlockSleepTiny(Response $response, string $method, string $uri = '', array $options = [])
    {
        if ($this->integration !== 'tiny') {
            return $response;
        }

        $attempts = 15;

        $bodyRequest = $response->getBody()->getContents();
        $response->getBody()->seek(0);
        if ($this->formatRequest === 'json') {
            $bodyRequest = Utils::jsonDecode($bodyRequest);
        }
        // Converte caso chegue em xml
        else {
            $bodyRequest = $this->convertXmlToObject($bodyRequest);
        }

        $bodyRequest = $bodyRequest->retorno ?? $bodyRequest;

        // enquanto a api estiver bloqueada ficará a tentar até encontrar o resultado
        while ($bodyRequest->status === "Erro" && isset($bodyRequest->codigo_erro) && (int)$bodyRequest->codigo_erro === 6) {
            $this->restBlocked = true;

            if ($this->countAttempt > $attempts) {
                throw new InvalidArgumentException('API request blocked. Try again later!', 429);
            }

            // API Bloqueada, vou esperar 15s e tentar novamente.
            sleep(15); // espera 15 segundos
            $this->countAttempt++;
            $response = $this->request($method, $uri, $options); // enviar uma nova requisição para ver se já liberou
            $bodyRequest = $response->getBody()->getContents();
            $response->getBody()->seek(0);

            if ($this->formatRequest === 'json') {
                $bodyRequest = Utils::jsonDecode($bodyRequest);
            }
            // Converte caso chegue em xml
            else {
                $bodyRequest = $this->convertXmlToObject($bodyRequest);
            }

            $bodyRequest = $bodyRequest->retorno ?? $bodyRequest;
        }
        $this->restBlocked = false;

        return $response;
    }

    /**
     * Converter um XML em objeto
     *
     * @param   string $xml Dado de uma xml
     * @return  Utils       Retorna um objeto convertido do xml
     */
    public function convertXmlToObject(string $xml): object
    {
        $responseXml = simplexml_load_string($xml);
        $jsonEncode = Utils::jsonEncode($responseXml);

        return Utils::jsonDecode($jsonEncode);
    }

    /**
     * Define o valor que o job foi iniciado para filtros de consultas
     */
    public function setDateStartJob()
    {
        $this->dateStartJob = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
    }

    /**
     * Grava o horário da última execução
     */
    public function saveLastRun($saveLastRun = true)
    {
        $date = dateNow(TIMEZONE_DEFAULT);
        if ($this->dateStartJob) {
            foreach (
                array(
                    'd/m/Y%20H:i:s',
                    'd/m/YTH:i:s',
                    'd/m/Y H:i:s',
                    'Y-m-dTH:i:s',
                    'Y-m-d%20H:i:s',
                    'Y-m-d H:i:s'
                ) as $format
            ) {
                if ($date = (DateTime::createFromFormat($format, $this->dateStartJob) !== false)) {
                    $date = DateTime::createFromFormat($format, $this->dateStartJob);
                    break;
                }
            }

            if ($date === false) {
                $date = dateNow(TIMEZONE_DEFAULT);
            }
        }

        $this->dateStartJob = $date->format(DATETIME_INTERNATIONAL);

        if ($saveLastRun) {
            // A integração 'viavarejo_b2b', contém um sufixo da marca, então não iremos conseguimr atualizar.
            // Deveria vir viavarejo_b2b_extra, por exemplo.
            // Não tem problema passar null, pois cada loja só pode ter uma única integração.
            $integration = $this->integration === "viavarejo_b2b" ? null : $this->integration;
            $this->model_job_integration->setLastRun($integration, $this->store, $this->job, $this->dateStartJob);
        }
    }

    public function setIgnoreIntegrationLogTypes(array $logTypes = [])
    {
        $this->ignoreIntegrationLogTypes = $logTypes;
    }

    /**
     * Cria um log da integração para ser mostrada na tela
     *
     * @param   string      $title          Título do log
     * @param   string      $description    Descrição do log
     * @param   string      $type           Tipo de log (S = sucesso, E = erro, W = alerta)
     * @return  bool                        Retornar o estado da criação do log
     */
    public function log_integration(string $title, string $description, string $type): bool
    {
        if ($type === 'I') {
            $type = 'S';
        }

        if ($type === 'W') {
            $type = 'E';
        }

        if (in_array($type, $this->ignoreIntegrationLogTypes)) {
            return false;
        }

        $unique_id = empty($this->unique_id) ? uniqid() : trim($this->unique_id);

        $data = array(
            'store_id'      => $this->store,
            'company_id'    => $this->company,
            'title'         => trim($title),
            'description'   => trim($description),
            'type'          => trim($type),
            'job'           => trim($this->job),
            'unique_id'     => $unique_id,
            'status'        => 1,
        );

        //TRATA OS DADOS DA TABELA RESUMIDA
        $logExist_unique = $this->model_log_integration_unique->getLogByData(
            array(
                'store_id'      => $this->store,
                'company_id'    => $this->company,
                'unique_id'     => trim($unique_id)
            )
        );

        if ($logExist_unique) {
            $this->model_log_integration_unique->remove(
                $logExist_unique['id']
            );
        }

        $this->model_log_integration_unique->create($data);

        // verifica se o log já existe, para não ser duplicado
        if (in_array($type, array('E', 'W'))) {
            $logExist = $this->model_log_integration->getLogByData(
                array(
                    'store_id'      => $this->store,
                    'company_id'    => $this->company,
                    'description'   => trim($description),
                    'title'         => trim($title),
                    'unique_id'     => trim($unique_id)
                )
            );
            if ($logExist) {
                $update = array('date_updated' => (new DateTime())->format(DATETIME_INTERNATIONAL));
                if (empty($this->unique_id)) {
                    $update['unique_id'] = $unique_id;
                }
                return $this->model_log_integration->update($update, $logExist['id']);
            }
        }

        return $this->model_log_integration->create($data);
    }

    /**
     * Recupera mensagem de retorno na requisição das APIs internas
     *
     * @param   string  $message    Mensagem recebida no response da requisição
     * @return  string              Mensagem formatada caso consiga descodificar, caso contrario retornar o que recebeu no parâmetro $message
     */
    public function getMessageRequestApiInternal(string $message): string
    {
        $message = trim($message);

        try {
            if ($contentError = Utils::jsonDecode($message)) {
                $message = $contentError->message ?? '';
                $message = is_string($message) ? $message : json_encode($message);
            }
        } catch (\Throwable $e) {

        }

        return $message;
    }

    /**
     * Validação inicial para iniciar o Batch
     *
     * @param   string|int  $params Parâmetro opcional para o batch (job_schedule.params). Nesse caso é o código da loja
     * @return  bool
     */
    public function startRun($params): bool
    {
        // Define a loja, para recuperar os dados para integração
        try {
            $this->setAuth($params);
        } catch (InvalidArgumentException | Exception $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return true;
    }

    /**
     * Define a data em que o job foi executado pela última vez
     */
    public function setLastRun()
    {
        if (empty($this->job)) return;
        $this->dateLastJob = $this->model_job_integration->getLastRunJob($this->job, $this->store);
    }

    /**
     * Recupera o código da loja pelo apiKey
     *
     * @param   string  $apiKey ApiKey de callback (stores.token_callback)
     * @return int|null         Retorna o código da loja, ou nulo caso não encontre
     */
    public function getStoreForApiKey(string $apiKey): ?int
    {
        $dataStore = $this->model_stores->getStoreTokenCallback($apiKey);
        return $dataStore['id'] ?? null;
    }

    /**
     * @param Response $request Requisição
     */
    private function checkErrorWithStatus200(Response $request)
    {
        if ($this->integration === 'tiny') {
            $decodeRequest = Utils::jsonDecode($request->getBody()->getContents());
            $request->getBody()->seek(0);

            if (
                $decodeRequest->retorno->status !== 'OK' &&
                isset($decodeRequest->retorno->erros) &&
                isset($decodeRequest->retorno->status)
            ) {
                if (is_array($decodeRequest->retorno->erros)) {
                    $error = array_map(function ($error){
                        return $error->erro;
                    }, $decodeRequest->retorno->erros);
                    $error = implode(' | ', $error);
                } elseif (is_string($decodeRequest->retorno->erros)) {
                    $error = $decodeRequest->retorno->erros;
                } else {
                    $error = Utils::jsonEncode($decodeRequest->retorno->erros);
                }

                throw new InvalidArgumentException($error, 400);
            }
        }
    }

    /**
     * Define credenciais particulares de cada integradora
     */
    public function setCredentialsAux()
    {
        if ($this->integration === 'pluggto') {
            // precisamos pegar o token que dura 1 hora
            $credentials = $this->model_settings->getValueIfAtiveByName('credencial_pluggto');

            if (!$credentials) {
                throw new InvalidArgumentException('Não foi localizado o parâmetro de credenciais do canal da PluggTo');
            }

            $credentials = Utils::jsonDecode($credentials);

            $this->credentials->client_id       =  $credentials->client_id_pluggto;
            $this->credentials->client_secret   =  $credentials->client_secret_pluggto;
            $this->credentials->username        =  $credentials->username_pluggto;
            $this->credentials->password        =  $credentials->password_pluggto;

            $urlAccessToken     = 'oauth/token';
            $queryAccessToken   = array(
                'form_params' => array(
                    'grant_type'    => 'password',
                    'client_id'     => $credentials->client_id_pluggto,
                    'client_secret' => $credentials->client_secret_pluggto,
                    'username'      => $credentials->username_pluggto,
                    'password'      => $credentials->password_pluggto
                ),
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded'
                )
            );

            // consulta o access token
            try {
                $request = $this->request('POST', $urlAccessToken, $queryAccessToken);
                $bodyAccessToken = Utils::jsonDecode($request->getBody()->getContents());
                $this->credentials->access_token = $bodyAccessToken->access_token;
            } catch (InvalidArgumentException | GuzzleException $exception) {
                throw new InvalidArgumentException("Não foi possível recuperar as credenciais\n{$exception->getMessage()}");
            }
        } elseif ($this->integration === 'tiny') {
            $this->credentials->id_lista_tiny = null;
            // Usa lista de preço
            if (!empty($this->credentials->lista_tiny)) {

                // código da lista de preço ainda não foi recuperada
                if (!$this->credentials->id_lista_tiny) {

                    $urlListPrice   = "listas.precos.pesquisa.php";
                    $queryListPrice = array(
                        'query' => array(
                            'pesquisa' => $this->credentials->lista_tiny
                        )
                    );

                    try {
                        $request = $this->request('GET', $urlListPrice, $queryListPrice);
                    } catch (InvalidArgumentException|GuzzleException $exception) {
                        throw new InvalidArgumentException($exception->getMessage());
                    }

                    $registers = Utils::jsonDecode($request->getBody()->getContents());
                    $registers = $registers->retorno->registros ?? array();

                    $idList = null;
                    foreach ($registers as $register) {
                        if ($register->registro->descricao == $this->credentials->lista_tiny) {
                            $idList = $register->registro->id;
                            break;
                        }
                    }

                    // Existe uma lista configurada, mas não encontrou na tiny
                    if ($idList === null) {
                        throw new InvalidArgumentException("Existe uma lista de preço configurada, mas não encontrou na tiny. ({$this->credentials->lista_tiny})");
                    }

                    $this->credentials->id_lista_tiny = $idList;

                    $dataIntegrationStore = $this->model_api_integrations->getIntegrationByStore($this->store);
                    $credentials = Utils::jsonDecode($dataIntegrationStore['credentials']);
                    $credentials->id_lista_tiny = $idList;

                    $this->model_api_integrations->updateByStore($this->store, array('credentials' => Utils::jsonEncode($credentials)));
                }
            }
        }
    }

    private function getBodyRequestInternal($method, $uri, $options): array
    {
        return array(
            'uri'       => $uri,
            'method'    => $method,
            'options'   => $options
        );
    }

    public function createButtonLogRequestIntegration($payload): string
    {
        return "<div class='col-md-12 d-flex justify-content-center mb-3'><button type='button' class='btn btn-primary text-center btnCollapseLogRequestProduct'>{$this->lang->line('api_view_sent_call')}</button></div><div class='collapseLogRequestProduct d-none'><pre>" . json_encode($payload, JSON_UNESCAPED_UNICODE) . "</pre></div>";
    }

    public function putCredentialsApiInternal(int $user_id)
    {
        $group = $this->model_users->getUserGroup($user_id);
        if ($group) {
            $this->user_permission = unserialize($group['permission']);
        }
        
        $user = $this->model_users->getUserData($user_id);
        
        if (!$user) {
            return;
        }

        $this->credentials->api_internal["x-user-email"] = $user['email'];
    }
}