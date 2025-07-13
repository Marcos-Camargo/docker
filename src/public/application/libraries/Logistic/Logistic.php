<?php

require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingIntegrator.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingCarrier.php";

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7;
use Microservices\v1\Logistic\ShippingIntegrator;
use Microservices\v1\Logistic\ShippingCarrier;

/**
 * @property CI_Loader $load
 * @property CI_Router $router
 * @property CI_DB_driver $db
 *
 * @property RedisCodeigniter $redis
 *
 * @property Model_freights $model_freights
 * @property Model_orders $model_orders
 * @property Model_settings $model_settings
 * @property Model_quotes_ship $model_quotes_ship
 * @property Model_products_catalog $model_products_catalog
 * @property Model_products $model_products
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_nfes $model_nfes
 * @property Model_shipping_tracking_occurrence $model_shipping_tracking_occurrence
 * @property Model_clients $model_clients
 * @property Model_shipping_price_rules $model_shipping_price_rules
 * @property Model_stores $model_stores
 * @property Model_integration_logistic_api_parameters $model_integration_logistic_api_parameters
 * @property Model_orders_with_problem $model_orders_with_problem
 * @property Model_order_items_cancel $model_order_items_cancel
 * @property Model_pickup_point $model_pickup_point
 * @property Model_withdrawal_time $model_withdrawal_time
 *
 * @property ShippingIntegrator $ms_shipping_integrator
 * @property ShippingCarrier $ms_shipping_carrier
 */

abstract class Logistic
{
    /**
     * @var Client Cliente of GuzzleHttp
     */
    public $client;

    /**
     * @var mixed
     */
    public $dbReadonly;

    /**
     * @var string nome da logística.
     */
    public $logistic;

    /**
     * @var int Código da loja.
     */
    public $store;

    /**
     * @var array Credenciais da integração.
     */
    public $credentials;

    /**
     * @var ?int Código da integração.
     */
    public $integration_logistic_id = null;

    /**
     * @var array Dados para autenticação da requisição.
     */
    public $authRequest;

    /**
     * @var string
     */
    public $endpoint;

    /**
     * @var array
     */
    public $dataQuote;

    /**
     * @var string código do seller center.
     */
    public $sellerCenter;

    /**
     * @var bool Logística da loja.
     */
    public $freightSeller;

    /**
     * @var RedisCodeigniter
     */
    public $redis;

    /**
     * @var int Quantidade de request feitas e geraram 429.
     */
    private $requested_attempts = 0;

    /**
     * @var bool $debug Debug nas requisições com guzzle.
     */
    private $debug = false;

    /**
     * @var bool $has_multiseller Tem multiseller na cotação de frete
     */
    public $has_multiseller = false;

    /**
     * @todo Enviar essa regra para o banco de dados.
     *
     * @var array[] Mapeamento de situação de cada integradora.
     */
    public $statusValidOccurrence = array(
        'shipped' => array( // status que o objeto foi postado/recolhido
            'sequoia' => array(4,5,15),
            'sgpweb' => array(
                'Objeto postado',
                'Objeto postado após o horário limite da agência',
                'Objeto postado após o horário limite da unidade'
            ),
            'intelipost' => array(
                'IN_TRANSIT',
                'SHIPPED'
            ),
            'freterapido' => array(2,15,16,17),
            'panex' => array(100,101,102,103),
            'correios' => array(
                'Objeto postado',
                'Objeto postado após o horário limite da agência',
                'Objeto postado após o horário limite da unidade'
            ),
        ),
        'delivery' => array( // status que o objeto foi entregue ao cliente
            'sequoia' => array(10),
            'sgpweb' => array(
                'Entrega realizada em endereço vizinho, conforme autorizado pelo remetente',
                'Entrega especial',
                'Objeto entregue ao destinatário'
            ),
            'intelipost' => array('DELIVERED'),
            'freterapido' => array(3),
            'panex' => array(1,2),
            'correios' => array(
                'Entrega realizada em endereço vizinho, conforme autorizado pelo remetente',
                'Entrega especial',
                'Objeto entregue ao destinatário'
            ),
        ),
        'theft_devolution' => array( // status que o objeto foi extraviado/devolvido ao remetente
            'sequoia' => array(7,11,14),
            'sgpweb' => array(
                'Objeto e/ou conteúdo avariado por acidente com veículo', // extravio e/ou roubo
                'Objeto roubado', // extravio e/ou roubo
                'Objeto não localizado no fluxo postal', // extravio e/ou roubo
                'Objeto e/ou conteúdo avariado', // indício de extravio
                'Objeto entregue ao remetente' // devolução
            ),
            'intelipost' => array(),
            'freterapido' => array(7,8,18),
            'panex' => array(23,26,27,36,37,38,60,78,79,80,81,137),
            'correios' => array(
                'Objeto e/ou conteúdo avariado por acidente com veículo', // extravio e/ou roubo
                'Objeto roubado', // extravio e/ou roubo
                'Objeto não localizado no fluxo postal', // extravio e/ou roubo
                'Objeto e/ou conteúdo avariado', // indício de extravio
                'Objeto entregue ao remetente' // devolução
            ),
        ),
        'available_withdrawal' => array( // status que o objeto precisa ser retirado por um local pelo cliente.
            'sequoia' => array(),
            'sgpweb' => array(
                'Disponível para retirada',
                'Objeto aguardando retirada no endereço indicado',
                'Objeto disponível para retirada em Caixa Postal',
                'Objeto encaminhado para retirada no endereço indicado'
            ),
            'intelipost' => array(),
            'freterapido' => array(19),
            'panex' => array(29),
            'correios' => array(
                'Disponível para retirada',
                'Objeto aguardando retirada no endereço indicado',
                'Objeto disponível para retirada em Caixa Postal',
                'Objeto encaminhado para retirada no endereço indicado'
            ),
        )
    );

    public $type_logistic_to_hire = array(
        'manual_hiring' => 0,
        'sgpweb'        => 1,
        'freterapido'   => 2,
        //'manual_hiring' => 3, // igual ao 1.
        'intelipost'    => 4,
        'sequoia'       => 5,
        'panex'         => 6,
        'correios'      => 7,
    );

    public function __construct(array $option)
    {
        $db                     = $option['readonlydb'];
        $logistic               = $option['integration'];
        $store                  = $option['store'];
        $dataQuote              = $option['dataQuote'];
        $freightSeller          = $option['freightSeller'];
        $sellerCenter           = $option['sellerCenter'] ?? null;
        $this->redis            = $option['redis'] ?? null;
        $validate_credentials   = $option['validate_credentials'] ?? true;

        $this->dbReadonly   = $db;
        $this->logistic     = $logistic;

        $this->load->library("Microservices\\v1\\Logistic\\ShippingIntegrator", array(), 'ms_shipping_integrator');
        $this->load->library("Microservices\\v1\\Logistic\\ShippingCarrier", array(), 'ms_shipping_carrier');
        $this->load->model('model_order_items_cancel');
        $this->load->model('model_pickup_point');
        $this->load->model('model_withdrawal_time');

        try {
            $this->setDataQuote($dataQuote);
            $this->setSellerCenter($sellerCenter);
            $this->setLogistic($logistic);
            $this->setClientGuzzle();
            $this->setStore($store);
            $this->setFreightSeller($freightSeller);
            if ($validate_credentials) {
                $this->setCredentialsRequest();
                $this->setAuthRequest();
            }
        } catch (InvalidArgumentException | Exception $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    abstract public function setAuthRequest();

    /**
     * Cotação.
     *
     * @param   array   $dataQuote      Dados para realizar a cotação.
     * @param   bool    $moduloFrete    Dados de envio do produto por módulo frete.
     * @return  array
     */
    abstract public function getQuote(array $dataQuote, bool $moduloFrete = false): array;

    /**
     * Efetua a criação/alteração do centro de distribuição na integradora, quando usar contrato seller center.
     */
    abstract public function setWarehouse();

    /**
     * Método mágico para utilização do CI_Controller.
     *
     * @param   string  $var    Propriedade para consulta.
     * @return  mixed           Objeto da propriedade.
     */
    public function __get(string $var)
    {
        return get_instance()->$var;
    }

    /**
     * Define a instância Client de GuzzleHttp
     */
    public function setClientGuzzle($timeout = null)
    {
        if ($timeout == null) {
            $timeout = 1.5;
            $timeout_redis = null;

            $key_redis = "$this->sellerCenter:settings:timeout_quote_ms";
            if ($this->redis && $this->redis->is_connected) {
                $data_redis = $this->redis->get($key_redis);
                if ($data_redis !== null) {
                    $timeout = $timeout_redis = $data_redis;
                }
            }

            if ($timeout_redis === null) {
                $this->load->model('model_settings');
                if ($value_timeout = $this->model_settings->getValueIfAtiveByName('timeout_quote_ms')) {
                    $timeout = $value_timeout / 1000;
                }
                if ($this->redis && $this->redis->is_connected && $value_timeout) {
                    $this->redis->setex($key_redis, 3600, $timeout);
                }
            }
        }

        $this->client = new Client([
            'verify' => false, // no verify ssl
            'timeout' => $timeout
        ]);
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
     * Realizar requisição na integradora.
     *
     * @param   string              $method     Método da requisição.
     * @param   string              $uri        URI da requisição.
     * @param   array               $options    Opções complementares para a requisição, como body, params e headers.
     * @return  ResponseInterface
     */
    public function request(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        $method_original    = $method;
        $uri_original       = $uri;
        $options_original   = $options;

        $options['headers']['Content-Type'] = "application/json";

        if (array_key_exists('headers', $this->authRequest ?? [])) {
            if (!array_key_exists('headers', $options)) {
                $options['headers'] = array();
            }
            $options['headers'] = array_merge_recursive($options['headers'], $this->authRequest['headers']);
        }

        if (array_key_exists('json', $this->authRequest ?? [])) {
            if (!array_key_exists('json', $options)) {
                $options['json'] = array();
            }
            $options['json'] = array_merge_recursive($options['json'], $this->authRequest['json']);
        }

        if (array_key_exists('query', $this->authRequest ?? [])) {
            if (!array_key_exists('query', $options)) {
                $options['query'] = array();
            }
            $options['query'] = array_merge_recursive($options['query'], $this->authRequest['query']);
        }

        try {
            if ($this->getDebug()) {
                $options['debug'] = $this->getDebug();
            }

            $request = $this->client->request($method, $this->endpoint.$uri, $options);
            $this->requested_attempts = 0;
        } catch (InvalidArgumentException | GuzzleException | BadResponseException $exception) {
            try {
                $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
                $second_time_retry = null;

                if ($this->requested_attempts <= 5 && $exception->getCode() == 429) {
                    $second_time_retry = 1;
                }

                if (
                    $this->logistic == 'correios' &&
                    $this->requested_attempts <= 10 &&
                    (
                        // O Rotúlo ainda não gerado pela API Rótulo ID recibo da pré-postagem: c07e410b-7d2a-41ce-be41-0b7d2af1cee1
                        // Emissão de rótulo: Cannot invoke "String._dt_getBytes(java.nio.charset.Charset)" because "src" is null
                        // idRecibo não encontrado, gentileza verificar se foi informado corretamente.
                        // Emissão de rótulo: Rótulo não localizado para os parâmetros informados.
                        // Erro ao acessar API Rotulo. Por favor, tente novamente.IdRecibo Recibo em sincronização.
                        (likeText('%O Rotúlo ainda%', $message) && likeText('%gerado pela API Rótulo ID%', $message)) ||
                        likeText('%O rótulo ainda não gerado pela API Rotulo%', $message) ||
                        likeText('%Emissão de rótulo%', $message) ||
                        likeText('%idRecibo não encontrado%', $message) ||
                        likeText('%IdRecibo Recibo em sincronização%', $message)
                    )
                ) {
                    $second_time_retry = 3;
                }

                if (!is_null($second_time_retry)) {
                    $this->requested_attempts++;
                    sleep($second_time_retry);
                    $request = $this->request($method_original, $uri_original, $options_original);
                } else {
                    $this->requested_attempts = 0;
                    throw new InvalidArgumentException($message, $exception->getCode());
                }
            } catch (Exception|Error $exception) {
                $this->requested_attempts = 0;
                throw new InvalidArgumentException($message ?? $exception->getMessage(), $exception->getCode());
            }
        }

        return $request;
    }

    /**
     * Realizar requisição na integradora assíncrono.
     *
     * @param   string          $method         Método da requisição.
     * @param   string|array    $uri            URI da requisição.
     * @param   array           $all_options    Opções complementares para a requisição, como body, params e headers.
     * @return  array
     */
    public function requestAsync(string $method, $uri = '', array $all_options = []): array
    {
        $promises = array();

        foreach ($all_options as $prd => $options) {
            $options['headers']['Content-Type'] = "application/json";

            if (array_key_exists('headers', $this->authRequest)) {
                $options['headers'] = array_merge_recursive($options['headers'], $this->authRequest['headers']);
            }

            if (array_key_exists('json', $this->authRequest)) {
                if (!array_key_exists('json', $options)) {
                    $options['json'] = array();
                }
                $options['json'] = array_merge_recursive($options['json'], $this->authRequest['json']);
            }

            if (array_key_exists('query', $this->authRequest)) {
                if (!array_key_exists('query', $options)) {
                    $options['query'] = array();
                }
                $options['query'] = array_merge_recursive($options['query'], $this->authRequest['query']);
            }

            $uri_request = '';
            if (is_array($uri)) {
                foreach ($uri as $uri_key => $uri_value) {
                    if (likeText("%$uri_key%", $prd)) {
                        $uri_request = $uri_value;
                        break;
                    }
                }
                // não encontrou o uri.
                if (empty($uri_request)) {
                    continue;
                }
            } else {
                $uri_request = $uri;
            }

            if ($this->getDebug()) {
                $options['debug'] = $this->getDebug();
            }

            $promises[$prd] = $this->client->requestAsync($method, $this->endpoint.$uri_request, $options);
        }

        // Throws a ConnectException if any of the requests fail.
        try {
            Promise\Utils::unwrap($promises);
        } catch (RequestException | ConnectException | Throwable $e) {}

        $requests   = [];
        $fulfilled  = [];

        foreach ($promises as $p_index => $p_value) {
            if ($p_value->getState() == 'fulfilled') {
                $requests[] = $p_index;
                $fulfilled[$p_index] = $p_value;
            }
        }

        $promises = $fulfilled;
        $responses = Promise\Utils::settle($promises)->wait();

        $services = array();

        foreach ($requests as $r) {
            if ($responses[$r]['state'] == 'fulfilled') {
                $services[$r] = json_decode($responses[$r]['value']->getBody()->getContents());
            }
        }

        return $services;
    }

    public function setSellerCenter(?string $sellerCenter)
    {
        $this->sellerCenter = $sellerCenter;

        if ($sellerCenter === null) {
            $sellerCenter = $this->dbReadonly->where('name', 'sellercenter')->get('settings')->row_object();
            $this->sellerCenter = $sellerCenter->value;
        }
    }

    public function setLogistic(string $logistic)
    {
        $this->logistic = $logistic;
    }

    public function setStore(int $store)
    {
        $this->store = $store;
    }

    public function setFreightSeller(bool $freightSeller)
    {
        $this->freightSeller = $freightSeller;
    }

    public function setEndpoint(string $endpoint)
    {
        if (substr($endpoint, -1) === '/') {
            $endpoint = substr($endpoint, 0, -1);
        }

        $this->endpoint = $endpoint;
    }

    public function setDataQuote(array $dataQuote)
    {
        $this->dataQuote = $dataQuote;
    }

    /**
     * Retorna as credenciais de acordo com a integradora configurada no seller.
     *
     * @throws Exception Exceção na solicitação.
     */
    public function setCredentialsRequest()
    {
        $key_redis = "$this->sellerCenter:credencials_integration:$this->store";
        if ($this->redis && $this->redis->is_connected) {
            $data_redis = $this->redis->get($key_redis);
            if ($data_redis !== null) {
                $this->credentials = json_decode($data_redis, true);
                return;
            }
        }

        // Excepcionalmente, a logística pela tabela interna, não precisa identificação das credênciais.
        if ($this->logistic === 'TableInternal') {
            return;
        }

        $responseCredentials = new StdClass();

        $erps = $this->getTypesLogisticERP();
        // Remove precode, pois os dados de cotação vai para a tabela logistic.
        $keyErp = array_search('precode', $erps);
        if ($keyErp !== false) {
            unset($erps[$keyErp]);
        }

        if (in_array($this->logistic, $erps)) {
            $credentials = $this->dbReadonly->get_where('api_integrations', array('store_id' => $this->store))->row_object();
            if (!$credentials) {
                throw new Exception("Falha para obter as credenciais da integração $this->logistic.");
            }

            $this->credentials = Utils::jsonDecode($credentials->credentials, true);
            if ($this->redis && $this->redis->is_connected) {
                $this->redis->setex($key_redis, 1800, json_encode($this->credentials, JSON_UNESCAPED_UNICODE));
            }
            return;
        }

        $integrations = [];
        if ($this->ms_shipping_carrier->use_ms_shipping) {
            try {
                if ($this->logistic == "precode") {
                    $this->ms_shipping_integrator->setStore($this->store);
                    $integrations_aux = $this->ms_shipping_integrator->getConfigure($this->logistic);
                } else {
                    $this->ms_shipping_carrier->setStore($this->store);
                    $integrations_aux = $this->ms_shipping_carrier->getConfigure($this->logistic);
                }

                if (
                    !is_null($integrations_aux) &&
                    isset($integrations_aux->id) &&
                    isset($integrations_aux->credentials)
                ) {
                    $integrations[] = array(
                        "id"            => $integrations_aux->id,
                        "credentials"   => json_encode($integrations_aux->credentials),
                        "store_id"      => $this->store
                    );
                    $integrations = json_decode(json_encode($integrations, false));
                }
            } catch (Exception $exception) {}
        }

        if (!$this->ms_shipping_carrier->use_ms_shipping) {
            $integrations = $this->dbReadonly
                ->select('id,credentials,store_id')
                ->group_start()
                    ->where('store_id', $this->store)
                    ->or_where('store_id', 0)
                ->group_end()
                ->where('active', 1)
                ->where('integration', $this->logistic)
                ->order_by('store_id', 'DESC')
                ->get('integration_logistic use index (ix_integration_logistic_store_id_active_integration)')
                ->result_object();
        }

        if ((count($integrations) !== 1 && count($integrations) !== 2) || !in_array($integrations[0]->store_id, array($this->store, 0))) {
            throw new Exception('Falha para obter as credenciais da loja.');
        }

        foreach ($integrations as $key => $integration) {
            $this->integration_logistic_id = $integration->id;

            $integration = json_decode($integration->credentials);

            if (in_array($this->logistic, array('sgpweb', 'correios')) && $integration !== null) {
                if (empty($integration)) {
                    throw new Exception('Falha para obter as credenciais da loja. Não configurada.');
                }

                if (!property_exists($integration, 'type_contract')) {
                    throw new Exception('Falha para obter as credenciais da loja. type_contract não encontrado.');
                }

                if ($integration->type_contract === 'old') {
                    $integration->available_services = (array)json_decode('{"MINI":"00000","PAC":"04669","SEDEX":"04162"}');
                } else {
                    $integration->available_services = (array)json_decode('{"MINI":"04227","PAC":"03298","SEDEX":"03220"}');
                }

                // Mantém somente os serviços configurados.
                if (!empty($integration->services)) {
                    foreach ($integration->available_services as $service_name => $service_code) {
                        if (!in_array(strtolower($service_name), $integration->services)) {
                            unset($integration->available_services[$service_name]);
                        }
                    }
                }
            }

            if ($key === 0 && $integration !== null) {
                $this->credentials = (array)$integration;
                if ($this->redis && $this->redis->is_connected) {
                    $this->redis->setex($key_redis, 3600, json_encode($this->credentials, JSON_UNESCAPED_UNICODE));
                }
                return;
            }

            if ($key === 0) {
                continue;
            }

            $this->credentials = (array)$integration;
            if ($this->redis && $this->redis->is_connected) {
                $this->redis->setex($key_redis, 3600, json_encode($this->credentials, JSON_UNESCAPED_UNICODE));
            }
            return;
        }

        throw new Exception('Falha para obter as credenciais da loja.');
    }

    public function zipCodeQuery(string $zipCode): object
    {
        $zipCode = str_pad($zipCode, 8, "0", STR_PAD_LEFT);

        $region = $this->dbReadonly->where('zipcode', $zipCode)->get('cep')->row_object();
        if ($region) {
            return $region;
        }

        try {
            $this->createZipCode($zipCode);
            return $this->dbReadonly->where('zipcode', $zipCode)->get('cep')->row_object();
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }
    }

    public function createZipCode(string $zipCode)
    {
        try {
            $timeout = 0.250;
            $client = new Client([
                'verify' => false,
                'timeout' => $timeout,
                'connect_timeout' => $timeout
            ]);
            $response = $client->get("https://viacep.com.br/ws/$zipCode/json/");
            $contentOrder = Utils::jsonDecode($response->getBody()->getContents());
        } catch (InvalidArgumentException | GuzzleException | BadResponseException $exception) {
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            throw new InvalidArgumentException($message);
        }

        if (property_exists($contentOrder, 'erro')) {
            throw new InvalidArgumentException('Foi encontrado um erro para consultar o CEP.');
        }

        if (!property_exists($contentOrder, 'cep')) {
            throw new InvalidArgumentException('Não foi encontrado o CEP para criar o registro.');
        }

        $capitals = array(
            'Rio Branco/AC',
            'Maceió/AL',
            'Macapá/AP',
            'Manaus/AM',
            'Salvador/BA',
            'Fortaleza/CE',
            'Brasília/DF',
            'Vitória/ES',
            'Goiânia/GO',
            'São Luís/MA',
            'Cuiabá/MT',
            'Campo Grande/MS',
            'Belo Horizonte/MG',
            'Belém/PA',
            'João Pessoa/PB',
            'Curitiba/PR',
            'Recife/PE',
            'Teresina/PI',
            'Rio de Janeiro/RJ',
            'Natal/RN',
            'Porto Alegre/RS',
            'Porto Velho/RO',
            'Boa Vista/RR',
            'Florianópolis/SC',
            'São Paulo/SP',
            'Aracaju/SE',
            'Palmas/TO',
        );

        $data = array(
            'zipcode'       => $zipCode,
            'city'          => $contentOrder->localidade,
            'state'         => $contentOrder->uf,
            'address'       => $contentOrder->logradouro,
            'neighborhood'  => $contentOrder->bairro,
            'capital'       => in_array($contentOrder->localidade, $capitals),
        );

        if (!$this->db->insert('cep', $data)) {
            throw new InvalidArgumentException('Não foi possível gravar o novo CEP.');
        }
    }

    public function getTypesLogisticERP(): array
    {
        $rowApiIntegration = $this->dbReadonly
            ->select('integration')
            ->where('credentials != ', '{}')
            ->group_by("integration")
            ->get('api_integrations')
            ->result_object();

        return array_map(function ($integration){
            if (in_array(
                $integration->integration,
                array(
                    'viavarejo_b2b_casasbahia',
                    'viavarejo_b2b_pontofrio',
                    'viavarejo_b2b_extra'
                ))
            ) {
                $integration->integration = 'viavarejo_b2b';
            }

            return $integration->integration;
        }, $rowApiIntegration);
    }

    /**
     * Recupera as promoções ativas dos produtos e aplica nos serviços encontrados.
     *
     * @param   array   $services   Serviços encontrados.
     * @param   string  $uf         Código UF.
     * @return  array
     */
    public function getPromotion(array $services, string $uf): array
    {
        $updatedTaxes = array();
        $productsPromotionTemp = array();
        foreach ($services as $service) {
            // Não encontrou o código do produto, então não aplicará a promoção.
            if (empty($service['prd_id'])) {
                $updatedTaxes[] = $service;
                continue;
            }
            if (!array_key_exists($service['prd_id'], $productsPromotionTemp)) {
                $productsPromotionTemp[$service['prd_id']] = $this->getPromotionProduct($service['prd_id'], $uf);
            }

            if (isset($productsPromotionTemp[$service['prd_id']]['criterion_type'])) {
                if ($productsPromotionTemp[$service['prd_id']]['criterion_type'] == '2') {
                    $service['value'] -= round((float) $productsPromotionTemp[$service['prd_id']]['price_type_value'], 2);

                    if ($service['value'] < 0) {
                        $service['value'] = 0;
                    } else {
                        $service['value'] = round($service['value'], 2);
                    }
                } else {
                    $discount = ((float) $productsPromotionTemp[$service['prd_id']]['price_type_value']);
                    $service['value'] -= round((((float)$service['value']) * ($discount / 100)), 2);
                    $service['value'] = round($service['value'], 2);
                }
            }
            $updatedTaxes[] = $service;
        }

        return $updatedTaxes;
    }

    /**
     * Recupera promoção ativa de um certo produto.
     *
     * @param   int     $product    Código do produto.
     * @param   string  $uf         Código UF.
     * @return  array
     */
    private function getPromotionProduct(int $product, string $uf): array
    {
        $dataPromotion = $this->dbReadonly->select('lp.*, lpp.product_id, lpp.active_status, lps.active_status, lpr.logistic_promotion_idregion')
            ->join('logistic_promotion_stores AS lps', 'lp.id = lps.logistic_promotion_id')
            ->join('logistic_promotion_product AS lpp', 'lp.id = lpp.promotion_id')
            ->join('logistic_promotion_region AS lpr', 'lpr.logistic_promotion_id = lp.id', 'left')
            ->where(
                array(
                    'lp.status'         => true,
                    'lp.deleted'        => false,
                    'lpp.product_id'    => $product,
                    'lpp.active_status' => true,
                    'lps.active_status' => true,
                    'lps.id_stores'     => $this->store
                )
            )
            ->get('logistic_promotion AS lp')
            ->result_array();

        $promotionsActive = array();
        foreach ($dataPromotion as $promotion) {
            if ($promotion['region'] == "0" || $promotion['logistic_promotion_idregion'] == $uf) {
                $promotionsActive = $promotion;
                break;
            }
        }

        return $promotionsActive;
    }

    /**
     * Retorna o valor do frete para um ou mais produtos com base na regra
     * de leilão configurada.
     * ---------------------------------------------------------
     * $rule                        = $rule->rules_seller_conditions_status_id = 2
     * $quoteResponse               = [
     *                                      "success" => "true",
     *                                      "origin" => "VTEX",
     *                                      "data" => [
     *                                          "services" => [
     *                                              "0" => ...
     *                                              "1" => ...
     *                                          ]
     *                                      ]
     *                                  ]
     * $settingStoreTestModuloFrete = [ "id" => 2, "name" => ... ]
     * $dataAd                      = [ "id" => 2, "int_to" => ... ]
     * $platform                    = "VTEX"
     *
     * ---------------------------------------------------------
     * Os itens contidos no vetor $quoteResponse são filtrados neste método
     * e retornados, no mesmo vetor, como resultado da operação.
     * ---------------------------------------------------------
     * @return   array   $quoteResponse  Serviços filtrados que atendem ao
     * critério da regra de leilão.
     */
    public function shippingAuctionRules(
        ?object $rule,
        array $quoteResponse,
        string $platform,
        bool $groupServices
    ): array
    {
        // A plataforma do marketplace é VTEX e a plataforma da loja é VTEX.
        $vtexToVtex     = isset($quoteResponse['origin']) && $quoteResponse['origin'] == 'VTEX' && $platform == 'VTEX';
        // A plataforma do marketplace não é VTEX e a plataforma da loja é VTEX.
        $dontVtexToVtex = isset($quoteResponse['origin']) && $quoteResponse['origin'] == 'VTEX' && $platform != 'VTEX';

        /**
         * if ($rule->rules_seller_conditions_status_id == 3) {
         *    0 => 'nenhuma'
         *    1 => 'todos os resultados'
         *    2 => 'menor preço'
         *    3 => 'menor prazo'
         *    4 => 'menor prazo e menor preço'
         */
        if (!is_null($rule) && isset($rule->rules_seller_conditions_status_id)) {
            if ($rule->rules_seller_conditions_status_id == 2) {
                if (!$groupServices || $vtexToVtex) {
                    $groupedShipping = array();
                    $index = 0;
                    foreach ($quoteResponse['data']['services'] as $filteredShipping) {
                        $groupedShipping[$filteredShipping['method']][$index] = $filteredShipping;
                        $index += 1;
                    }

                    $lowestPrice = -1;
                    foreach ($groupedShipping as $currentGroup) {
                        $groupPricesSum = 0;
                        foreach ($currentGroup as $groupPrices) {
                            $groupPricesSum += $groupPrices['value'];
                        }

                        if ($lowestPrice == -1 || $groupPricesSum < $lowestPrice) {
                            $lowestPrice = $groupPricesSum;
                            $quoteResponse['data']['services'] = $currentGroup;
                        }
                    }
                } else if ($dontVtexToVtex) {
                    $groupedShipping = array();
                    $index = 0;
                    foreach ($quoteResponse['data']['services'] as $filteredShipping) {
                        $groupedShipping[$filteredShipping['method']][$index] = $filteredShipping;
                        $index += 1;
                    }

                    $lowestPrice = -1;
                    $lowestPriceGroup = array();
                    foreach ($groupedShipping as $currentGroup) {
                        $groupPricesSum = 0;
                        foreach ($currentGroup as $groupPrices) {
                            $groupPricesSum += $groupPrices['value'];
                        }

                        if ($lowestPrice == -1 || $groupPricesSum < $lowestPrice) {
                            $lowestPrice = $groupPricesSum;
                            $lowestPriceGroup = $currentGroup;
                        }
                    }

                    $biggestDeadline = -1;
                    $lowestMethod = "";
                    $lowestProvider = "";
                    foreach ($lowestPriceGroup as $g) {
                        if ($biggestDeadline == -1 || $g['deadline'] > $biggestDeadline) {
                            $biggestDeadline = $g['deadline'];
                            $lowestMethod = $g['method'];
                            $lowestProvider = $g['provider'];
                        }
                    }

                    $quoteResponse['data']['services'] = array(array(
                        "prd_id"    => "prd_id",
                        "skumkt"    => "skumkt",
                        "quote_id"  => null,
                        "method_id" => null,
                        "value"     => $lowestPrice,
                        "deadline"  => $biggestDeadline,
                        "method"    => $lowestMethod,
                        "provider"  => $lowestProvider
                    ));
                } else {
                    $prices = array();
                    $current_price = null;

                    $services_array = $quoteResponse['data']['services'];
                    foreach ($services_array as $v_service) {
                        if ($current_price === null || $v_service['value'] < $current_price) {
                            $current_price = $v_service['value'];
                            $prices = $v_service;
                        }
                    }
                    $quoteResponse['data']['services'] = array($prices);
                }
            } else if ($rule->rules_seller_conditions_status_id == 3) {
                if (!$groupServices || $vtexToVtex) {
                    $groupedShipping = array();
                    $index = 0;
                    foreach ($quoteResponse['data']['services'] as $filteredShipping) {
                        $groupedShipping[$filteredShipping['method']][$index] = $filteredShipping;
                        $index += 1;
                    }

                    $smallestDeadline = -1;
                    foreach ($groupedShipping as $currentGroup) {
                        foreach ($currentGroup as $g) {
                            if ($smallestDeadline == -1 || $g['deadline'] < $smallestDeadline) {
                                $smallestDeadline = $g['deadline'];
                                $quoteResponse['data']['services'] = $currentGroup;
                            }
                        }
                    }
                } else if ($dontVtexToVtex) {
                    $groupedShipping = array();
                    $index = 0;
                    foreach ($quoteResponse['data']['services'] as $filteredShipping) {
                        $groupedShipping[$filteredShipping['method']][$index] = $filteredShipping;
                        $index += 1;
                    }

                    $smallestDeadline = -1;
                    $smallestDeadlineGroup = array();
                    foreach ($groupedShipping as $currentGroup) {
                        foreach ($currentGroup as $g) {
                            if ($smallestDeadline == -1 || $g['deadline'] < $smallestDeadline) {
                                $smallestDeadline = $g['deadline'];
                                $smallestDeadlineGroup = $currentGroup;
                            }
                        }
                    }

                    $priceGroup = 0;
                    $biggestDeadline = -1;
                    $smallestMethod = "";
                    $smallestProvider = "";
                    foreach ($smallestDeadlineGroup as $g) {
                        if (
                            ($biggestDeadline == -1) ||
                            ($g['deadline'] > $biggestDeadline)
                        ) {
                            $biggestDeadline = $g['deadline'];
                            $smallestMethod = $g['method'];
                            $smallestProvider = $g['provider'];
                        }
                        $priceGroup += $g['value'];
                    }

                    $quoteResponse['data']['services'] = array(array(
                        "prd_id"    => "prd_id",
                        "skumkt"    => "skumkt",
                        "quote_id"  => null,
                        "method_id" => null,
                        "value"     => $priceGroup,
                        "deadline"  => $biggestDeadline,
                        "method"    => $smallestMethod,
                        "provider"  => $smallestProvider
                    ));
                } else {
                    $deadlines = array();
                    $currentDeadline = null;

                    $services_array = $quoteResponse['data']['services'];
                    foreach ($services_array as $v_service) {
                        if (($currentDeadline === null) || ($v_service['deadline'] < $currentDeadline)) {
                            $currentDeadline = $v_service['deadline'];
                            $deadlines = $v_service;
                        }
                    }
                    $quoteResponse['data']['services'] = array($deadlines);
                }
            } else if ($rule->rules_seller_conditions_status_id == 4) {
                if (!$groupServices || $vtexToVtex) {
                    $groupedShipping = array();
                    $index = 0;
                    foreach ($quoteResponse['data']['services'] as $filteredShipping) {
                        $groupedShipping[$filteredShipping['method']][$index] = $filteredShipping;
                        $index += 1;
                    }

                    // Encontra e armazena as informações do serviço com menor preço.
                    $lowestPrice = -1;
                    $price = array();
                    foreach ($groupedShipping as $currentGroup) {
                        $groupPricesSum = 0;
                        foreach ($currentGroup as $groupPrices) {
                            $groupPricesSum += $groupPrices['value'];
                        }

                        if (
                            ($lowestPrice == -1) ||
                            ($groupPricesSum < $lowestPrice)
                        ) {
                            $lowestPrice = $groupPricesSum;
                            $price = $currentGroup;
                        }
                    }

                    $current_quotes = array();
                    foreach ($price as $p) {
                        $current_quotes[] = $p;
                    }

                    /*
                    Caso haja múltiplos serviços, encontra as informações dos dois
                    serviços com os menores prazos de entrega.
                    Caso haja apenas um serviço, armazena suas informações.

                    Em ambos os casos, armazena o(s) serviço(s) somente se ele(s) já não
                    está/estão armazenados no vetor de cotações, "$current_quotes".
                    Este filtro garante que, havendo múltiplos serviços, o cliente
                    receberá múltiplas cotações, sem que haja repetição de cotação.
                    */
                    $smallestDeadline = -1;
                    $smallestDeadline_g = array();
                    $smallerDeadline = -1;
                    $smallerDeadline_g = array();
                    foreach ($groupedShipping as $currentGroup) {
                        foreach ($currentGroup as $g) {
                            if (($smallerDeadline == -1) && ($smallestDeadline == -1)) {
                                $smallerDeadline = $g['deadline'];
                                $smallerDeadline_g = $currentGroup;
                                $smallestDeadline = $g['deadline'];
                                $smallestDeadline_g = $currentGroup;
                            } else if ($smallerDeadline == $smallestDeadline) {
                                if ($g['deadline'] < $smallestDeadline) {
                                    $smallestDeadline = $g['deadline'];
                                    $smallestDeadline_g = $currentGroup;
                                } else if ($g['deadline'] > $smallestDeadline) {
                                    $smallerDeadline = $g['deadline'];
                                    $smallerDeadline_g = $currentGroup;
                                }
                            } else {
                                if ($g['deadline'] < $smallestDeadline) {
                                    $smallestDeadline = $g['deadline'];
                                    $smallestDeadline_g = $currentGroup;
                                } else if ($g['deadline'] > $smallestDeadline && $g['deadline'] < $smallerDeadline) {
                                    $smallerDeadline = $g['deadline'];
                                    $smallerDeadline_g = $currentGroup;
                                }
                            }
                        }
                    }

                    foreach ($smallestDeadline_g as $d) {
                        $found = false;
                        foreach ($current_quotes as $c) {
                            if ($c == $d) {
                                $found = true;
                                break;
                            }
                        }

                        if ($found === false) {
                            $current_quotes[] = $d;
                        }
                    }

                    /*foreach ($smallerDeadline_g as $d) {
                        $found = false;
                        foreach ($current_quotes as $c) {
                            if ($c == $d) {
                                $found = true;
                                break;
                            }
                        }

                        if ($found === false) {
                            $current_quotes[] = $d;
                        }
                    }*/

                    $quoteResponse['data']['services'] = $current_quotes;
                } else if ($dontVtexToVtex) {
                    $groupedShipping = array();
                    $index = 0;
                    foreach ($quoteResponse['data']['services'] as $filteredShipping) {
                        $groupedShipping[$filteredShipping['method']][$index] = $filteredShipping;
                        $index += 1;
                    }

                    $lowestPrice = -1;
                    $lowestPriceGroup = array();
                    foreach ($groupedShipping as $currentGroup) {
                        $groupPricesSum = 0;
                        foreach ($currentGroup as $groupPrices) {
                            $groupPricesSum += $groupPrices['value'];
                        }

                        if ($lowestPrice == -1 || $groupPricesSum < $lowestPrice) {
                            $lowestPrice = $groupPricesSum;
                            $lowestPriceGroup = $currentGroup;
                        }
                    }

                    $biggestDeadline = -1;
                    $lowestMethod = "";
                    $lowestProvider = "";
                    foreach ($lowestPriceGroup as $g) {
                        if ($biggestDeadline == -1 || $g['deadline'] > $biggestDeadline) {
                            $biggestDeadline = $g['deadline'];
                            $lowestMethod = $g['method'];
                            $lowestProvider = $g['provider'];
                        }
                    }

                    $lowestPrice_response = array(
                        "prd_id"    => "prd_id",
                        "skumkt"    => "skumkt",
                        "quote_id"  => null,
                        "method_id" => null,
                        "value"     => $lowestPrice,
                        "deadline"  => $biggestDeadline,
                        "method"    => $lowestMethod,
                        "provider"  => $lowestProvider
                    );

                    $smallestDeadline = -1;
                    $smallestDeadlineGroup = array();
                    foreach ($groupedShipping as $currentGroup) {
                        foreach ($currentGroup as $g) {
                            if ($smallestDeadline == -1 || $g['deadline'] < $smallestDeadline) {
                                $smallestDeadline = $g['deadline'];
                                $smallestDeadlineGroup = $currentGroup;
                            }
                        }
                    }

                    $priceGroup = 0;
                    $biggestDeadline = -1;
                    $smallestMethod = "";
                    $smallestProvider = "";
                    foreach ($smallestDeadlineGroup as $g) {
                        if ($biggestDeadline == -1 || $g['deadline'] > $biggestDeadline) {
                            $biggestDeadline = $g['deadline'];
                            $smallestMethod = $g['method'];
                            $smallestProvider = $g['provider'];
                        }
                        $priceGroup += $g['value'];
                    }

                    $smallestDeadline_response = array(
                        "prd_id"    => "prd_id",
                        "skumkt"    => "skumkt",
                        "quote_id"  => null,
                        "method_id" => null,
                        "value"     => $priceGroup,
                        "deadline"  => $biggestDeadline,
                        "method"    => $smallestMethod,
                        "provider"  => $smallestProvider
                    );

                    $current_quotes = array($lowestPrice_response);
                    if (!in_array($smallestDeadline_response, $current_quotes)) {
                        $current_quotes[] = $smallestDeadline_response;
                    }
                    $quoteResponse['data']['services'] = $current_quotes;
                } else {
                    $prices = array();
                    $current_price = null;

                    $deadlines = array();
                    $currentDeadline = null;

                    $services_array = $quoteResponse['data']['services'];
                    if (count($services_array) === 1) {
                        $current_quotes = $services_array;
                    } else {
                        foreach ($services_array as $v_service) {
                            if ($current_price === null || $v_service['value'] < $current_price) {
                                $current_price = $v_service['value'];
                                $prices = $v_service;
                            }

                            if ($currentDeadline === null || $v_service['deadline'] < $currentDeadline) {
                                $currentDeadline = $v_service['deadline'];
                                $deadlines = $v_service;
                            }
                        }

                        $current_quotes = array($prices);
                        if (!in_array($deadlines, $current_quotes)) {
                            $current_quotes[] = $deadlines;
                        }
                    }
                    $quoteResponse['data']['services'] = $current_quotes;
                }
            }
        }

        return $quoteResponse;
    }

    /**
     * Deixar serviços de envio com frete grátis.
     *
     * Consultar parametro 'stores_free_shipping' e recuperar as lojas que
     * participaram dessa promoção.
     *
     * O valor desse parametro deve se ser uma string onde as lojas serão
     * inseridas separadas por vírgula.
     * $rowSettings['value'] = "1,6,35,255";
     * Deverá ser feito um explode para transformar em array e verificar se
     * o código da loja em questão está dentro do array.
     *
     * O recebimento no parametro '$services' sempre será todos os serviços
     * disponíveis.
     * O retorno deverá ser o mesmo array contendo apenas o valor zerado.
     *
     * @param   array   $services   Serviços cotado
     * @return  array               Retonar os serviços com valor zerado, caso não existe a promoção para a loja, retornar o que recebeu
     */
    public function getFreeShipping(array $services): array
    {
        $querySettings  = $this->dbReadonly->get_where('settings', array('name' => 'stores_free_shipping'));
        $rowSettings    = $querySettings->row_array();

        $arrStoresFreeShipping = array();
        if ($rowSettings && $rowSettings['status'] == 1) {
            $arrStoresFreeShipping = explode(',', $rowSettings['value']);
        }

        // loja não deve ter o valor de frete zerado.
        if (!in_array($this->store, $arrStoresFreeShipping)) {
            return $services;
        }

        // zerar valores de frete
        foreach ($services as $key => $service) {
            $services[$key]['value'] = 0;
        }

        return $services;
    }

    /**
     * Retornar o serviço mais barato
     *
     * Consultar parametro 'frete_mais_barato' e recuperar se está ativo para participaram desse evento.
     *
     * O recebimento no parametro '$services' sempre será todos os serviços disponíveis.
     * O retorno deverá ser um array contendo apenas serviço com o valor mais barato.
     *
     * @param   array $services             Serviços cotado
     * @param   bool  $alwaysLowerShipping  Sempre retornar o frete mais barato, o parâmetro será ignorado
     * @return  array                       Retonar os serviços com valor zerado, caso não existe a promoção para a loja, retornar o que recebeu
     */
    public function getLowerShipping(array $services, bool $alwaysLowerShipping = false): array
    {
        $querySettings  = $this->dbReadonly->get_where('settings', array('name' => 'frete_mais_barato'));
        $rowSettings    = $querySettings->row_array();

        if (!$alwaysLowerShipping && (!$rowSettings || $rowSettings['status'] == 2)) {
            return $services;
        }

        $newService = array();

        // ler os serviços e pegar o mais barato
        foreach ($services as $service) {
            if (empty($newService) || $newService['value'] > $service['value']) {
                $newService = $service;
            }
        }

        return array($newService);
    }

    /**
     * Retornar o serviço permitidos para os Correios
     *
     * Consultar parametro 'servicos_permitidos_simulation' e recuperar se está ativo e ler os serviços.
     *
     * O recebimento no parametro '$services' sempre será todos os serviços disponíveis.
     * O retorno deverá ser o mesmo array contendo apenas o valor zerado.
     *
     * @param   array $services Serviços cotado
     * @return  array           Retonar os serviços com valor zerado, caso não existe a promoção para a loja, retornar o que recebeu
     */
    public function getAllowedServiceCorreios(array $services): array
    {
        $querySettings  = $this->dbReadonly->get_where('settings', array('name' => 'servicos_permitidos_simulation'));
        $rowSettings    = $querySettings->row_array();

        if (!$rowSettings || $rowSettings['status'] == 2 || empty($rowSettings['value'])) {
            return $services;
        }

        $allowedService = explode(',',$rowSettings['value']);

        $newService = array();

        // ler os serviços e pegar o mais barato
        foreach ($services as $service) {
            if (!isset($service['method'])) {
                continue;
            }

            if (!in_array($service['method'], $allowedService)) {
                continue;
            }

            $newService[] = $service;
        }

        return $newService;
    }

    /**
     * Retorna a pasta informada dentro de assets/images/etiquetas.
     *
     * @return 	string  Caminho da pasta dentro de assets/images/etiquetas
     */
    public function getPathLabel(): string
    {
        $serverpath = $_SERVER['SCRIPT_FILENAME'];
        $pos = strpos($serverpath,'assets');
        $serverpath = substr($serverpath,0,$pos);
        $targetDir = $serverpath . 'assets/images/etiquetas';
        if (!file_exists($targetDir)) {
            // cria o diretorio para receber as etiquetas
            @mkdir($targetDir);
        }
        return $targetDir;
    }

    /**
     * Cria o registro da ocorrência do rastreio
     *
     * Descrição do vetor: dataOccurrence
     *
     * description      Descrição complementar da ocorrência
     * name             Nome da ocorrência
     * code             Código da ocorrência
     * code_name        Código da ocorrência (secundária, caso o seja melhor a lógica por um nome)
     * date             Data da ocorrência
     * statusOrder      Status do pedido (orders.paid_status)
     * freightId        Código do frete (freights.id)
     * orderId          Código do pedido
     * trackingCode     Código de rastreio
     * address_place    Nome do local da ocorrência
     * address_name     Nome do logradouro da ocorrência
     * address_number   Número do endereço da ocorrência
     * address_zipcode  CEP da ocorrência
     * address_neigh    Bairro da ocorrência
     * address_city     Cidade da ocorrência
     * address_state    Estado da ocorrência
     *
     * @param   array   $dataOccurrence Dados da ocorrência para validação e inclusão.
     * @return  bool                    Retorna o status da inclusão.
     */
    public function setNewRegisterOccurrence(array $dataOccurrence): bool
    {
        $this->load->model('model_orders');
        $this->load->model('model_freights');
        $this->load->model('model_frete_ocorrencias');

        if (
            $this->sellerCenter === 'somaplace' &&
            ($dataOccurrence['code_name'] ?? $dataOccurrence['code']) == 11 &&
            strtolower($this->logistic) === 'sequoia' &&
            in_array($dataOccurrence['statusOrder'], array(4,53))
        ) {
            echo "Pedido ({$dataOccurrence['orderId']}) de SOMAPLACE da transportadora SEQUOIA, no STATUS DE TRANSPORTE 11, com STATUS DO PEDIDO 4 ou 53\n";
            $orderUpdate = array(
                'paid_status'   => 55,
                'data_envio'    => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)
            );
            $this->model_orders->updateByOrigin($dataOccurrence['orderId'], $orderUpdate);
            return true;
        }

        $occurrence = $this->model_frete_ocorrencias->getOcorrenciasByFreightIdName($dataOccurrence['freightId'], $dataOccurrence['name']);
        if (!$occurrence) {
            $log_name   = __CLASS__.'/'.__FUNCTION__;
            $order      = array();
            $freight    = array();

            echo "Pedido {$dataOccurrence['orderId']} ( {$dataOccurrence['trackingCode']} ) Vou atualizar o status do frete. Status = {$dataOccurrence['name']}\n";

            // atualizo o status da Order
            if (in_array( // verifica se precisa ser enviado para atualizar o status
                    $dataOccurrence['statusOrder'], array(4,53)) &&
                in_array($dataOccurrence['code_name'] ?? $dataOccurrence['code'], $this->statusValidOccurrence['shipped'][strtolower($this->logistic)])
            ) {
                // Condição para quando o sellerCenter for 'vertem' e code_name for 'shipped'
                if ($this->sellerCenter === 'vertem' && strtolower($dataOccurrence['code_name']) === 'shipped') {
                    $order['paid_status']   = 53; 
                    $freight['status_ship'] = 53; 
                    $order['data_envio']    = $dataOccurrence['date'];
                } else {
                    // 55 = avisar o marketplace do envio
                    $order['paid_status']   = 55;
                    $freight['status_ship'] = 55;
                    $order['data_envio']    = $dataOccurrence['date'];
                }
            }
            // objeto está aguardando retirada do cliente
            elseif (
                $dataOccurrence['statusOrder'] == 5
                && in_array($dataOccurrence['code_name'] ?? $dataOccurrence['code'], $this->statusValidOccurrence['available_withdrawal'][strtolower($this->logistic)])
            ) {
                // 58 = Cliente deve retirar no local
                $freight['date_delivered']  = $dataOccurrence['date'];
                $order['paid_status']       = 58;
                $freight['status_ship']     = 58;
            }
            elseif ( // verifica se precisa ser entregue para atualizar o status
                in_array($dataOccurrence['statusOrder'], array(5, 58)) &&
                in_array($dataOccurrence['code_name'] ?? $dataOccurrence['code'], $this->statusValidOccurrence['delivery'][strtolower($this->logistic)])
            ) {
                // 60 = avisar o marketplace da entrega
                $freight['status_ship']     = 60;
                $order['paid_status']       = 60;
                $freight['date_delivered']  = $dataOccurrence['date'];
                $order['data_entrega']      = $dataOccurrence['date'];
            }

            // Verficar se não estiver nos status (4,53,5,55,6,60) não deve fazer nada
            if (in_array(
                    $dataOccurrence['code_name'] ?? $dataOccurrence['code'],
                    $this->statusValidOccurrence['shipped'][strtolower($this->logistic)]
                ) &&
                !in_array(
                    $dataOccurrence['statusOrder'],
                    array(
                        4,53,5,55,58,6,60
                    )
                )
            ) {
                echo 'Objeto com status de enviado, mas ainda não foi atualizado no marketplace para receber essa situação. Está no status=' . $dataOccurrence['statusOrder'] . '. Rastreio=' . $dataOccurrence['trackingCode'] . ' do pedido=' . $dataOccurrence['orderId'] . ' frete=' . $dataOccurrence['freightId'] . '. Retorno=' . json_encode($dataOccurrence, true) . "\n";
                return false;
            }

            // verifica se o pedido foi entregue, mas não está em um status que pode ser entregue
            if (in_array(
                    $dataOccurrence['code_name'] ?? $dataOccurrence['code'],
                    $this->statusValidOccurrence['delivery'][strtolower($this->logistic)]
                ) &&
                !in_array(
                    $dataOccurrence['statusOrder'], array(
                        5,58,6,60
                    )
                )
            ) {
                // está no status 53, posso marcar como coletado/despachado
                if ($dataOccurrence['statusOrder'] == 53) {
                    // 55 = avisar o marketplace do envio
                    $order['paid_status']   = 55;
                    $freight['status_ship'] = 55;
                    $order['data_envio']    = $dataOccurrence['date'];

                    $dataOccurrence['code'] = 0;
                    $dataOccurrence['name'] = 'Em Transporte (Não encontrou ocorrência)';
                    $dataOccurrence['description'] = 'Em Transporte (Não encontrou ocorrência)';
                    $dataOccurrence['address_place'] = '';
                    $dataOccurrence['address_name'] = '';
                    $dataOccurrence['address_number'] = '';
                    $dataOccurrence['address_zipcode'] = '';
                    $dataOccurrence['address_neigh'] = '';
                    $dataOccurrence['address_city'] = '';
                    $dataOccurrence['address_state'] = '';

                } else {
                    echo 'Objeto com status de entregue, mas ainda não foi atualizado no marketplace para receber essa situação. Está no status=' . $dataOccurrence['statusOrder'] . '. Rastreio=' . $dataOccurrence['trackingCode'] . ' do pedido=' . $dataOccurrence['orderId'] . ' frete=' . $dataOccurrence['freightId'] . '. Retorno=' . json_encode($dataOccurrence, true) . "\n";
                    get_instance()->log_data('batch', $log_name, 'Objeto com status de entregue, mas ainda não foi atualizado no marketplace para receber essa situação. Está no status=' . $dataOccurrence['statusOrder'] . '. Rastreio=' . $dataOccurrence['trackingCode'] . ' do pedido=' . $dataOccurrence['orderId'] . ' frete=' . $dataOccurrence['freightId'] . '. Retorno=' . json_encode($dataOccurrence, true), "W");
                    return false;
                }
            }

            // perdido / extraviado / devolvido
            if (in_array($dataOccurrence['code_name'] ?? $dataOccurrence['code'], $this->statusValidOccurrence['theft_devolution'][strtolower($this->logistic)])){
                if (!in_array($dataOccurrence['statusOrder'], array(5,58))) {
                    return false;
                }
                if (
                    ($dataOccurrence['code_name'] ?? $dataOccurrence['code']) == 11 &&
                    $this->sellerCenter === 'somaplace' &&
                    strtolower($this->logistic) === 'sequoia' &&
                    !count($this->model_frete_ocorrencias->getOcorrenciasByCodeAndFreight($dataOccurrence['freightId'], $this->statusValidOccurrence['shipped'][strtolower($this->logistic)]))
                ) {
                    echo "Pedido ({$dataOccurrence['orderId']}) de SOMAPLACE da transportadora SEQUOIA, no STATUS DE TRANSPORTE 11, com STATUS DO PEDIDO 5 ou 58\n";
                    $freight['status_ship']     = 60;
                    $order['paid_status']       = 60;
                    $freight['date_delivered']  = $dataOccurrence['date'];
                    $order['data_entrega']      = $dataOccurrence['date'];
                } else {
                    $order['paid_status']       = 59;
                    $order['in_resend_active']  = 0;
                    $freight['status_ship']     = 59;
                }
            }

            if (in_array($dataOccurrence['statusOrder'], array(58, 53, 4)) && !isset($order['paid_status'])) {
                get_instance()->log_data('batch', $log_name, 'Objeto com status de postado, mas ocorreu algum problema para atualizar o status. Está no status=' . $dataOccurrence['statusOrder'] . '. Rastreio=' . $dataOccurrence['trackingCode'] . ' do pedido=' . $dataOccurrence['orderId'] . ' frete=' . $dataOccurrence['freightId'] . '. Retorno=' . json_encode($dataOccurrence, true), "W");
                return false;
            }

            $order['last_occurrence'] = $dataOccurrence['name'];

            if (count($freight)) {
                $this->model_freights->updateFreights($dataOccurrence['orderId'],$dataOccurrence['trackingCode'],$freight);
            }

            $this->model_orders->updateByOrigin($dataOccurrence['orderId'], $order);

            // array para gravar a ocorrência
            $freightOccurrence = array(
                'freights_id'       => $dataOccurrence['freightId'],
                'codigo'            => $dataOccurrence['code'],
                'nome'              => $dataOccurrence['name'],
                'data_ocorrencia'   => $dataOccurrence['date'],
                'data_atualizacao'  => $dataOccurrence['date'],
                'mensagem'          => $dataOccurrence['description'],
                'addr_place'        => $dataOccurrence['address_place'],
                'addr_name'         => $dataOccurrence['address_name'],
                'addr_num'          => $dataOccurrence['address_number'],
                'addr_cep'          => $dataOccurrence['address_zipcode'],
                'addr_neigh'        => $dataOccurrence['address_neigh'],
                'addr_city'         => $dataOccurrence['address_city'],
                'addr_state'        => $dataOccurrence['address_state']
            );

            echo "Pedido {$dataOccurrence['orderId']} ( {$dataOccurrence['trackingCode']} ) - gravar ocorrência - " . json_encode($freightOccurrence) . "\n";
            $this->model_frete_ocorrencias->create($freightOccurrence);
            get_instance()->log_data('batch',$log_name, "Pedido {$dataOccurrence['orderId']} ( {$dataOccurrence['trackingCode']} ) gravou ocorrência\n" . json_encode($freightOccurrence));
        } else {
            echo "Pedido {$dataOccurrence['orderId']} ( {$dataOccurrence['trackingCode']} ) não ocorreu mudança no status.\n";
        }

        return true;
    }

    public function applyShippingPricingRules(array $shipping_quote, RedisCodeigniter $redis): array
    {
        $key_redis_shipping_price_rules_data = "$this->sellerCenter:{$shipping_quote['data']['marketplace']}:{$shipping_quote['data']['logistic']['type']}:".implode(':', array_map(function($item){
                return "{$item['skumkt']}:{$item['method']}:{$item['provider']}:{$item['value']}:{$item['deadline']}";
            }, $shipping_quote['data']['services']));
        $this->load->model('model_shipping_price_rules');

        if ($redis->is_connected) {
            $data_redis = $redis->get($key_redis_shipping_price_rules_data);
            if ($data_redis !== null) {
                $data_redis_decode = json_decode($data_redis, true);
                if ($data_redis_decode) {
                    return $data_redis_decode;
                }
            }
        }

        $shipping_data = $this->model_shipping_price_rules->getShippingData($shipping_quote);

        if ($shipping_data === false) {
            $logistic_integration = isset($shipping_quote['data']['logistic']['type']) ? $shipping_quote['data']['logistic']['type'] : null;
            $marketplace = isset($shipping_quote['data']['marketplace']) ? $shipping_quote['data']['marketplace'] : null;
            $table_name = isset($shipping_quote['table_name']) ? $shipping_quote['table_name'] : null;
            $skumkt = isset($shipping_quote['data']['services'][0]['skumkt']) ? $shipping_quote['data']['services'][0]['skumkt'] : null;
            $store_id = isset($shipping_quote['data']['skus'][$skumkt]['store_id']) ? $shipping_quote['data']['skus'][$skumkt]['store_id'] : null;

            $shipping_quote['shipping']['apply'] = array("failure" => "No shipping data found.",
                'information' => array(
                    'logistic_integration'  => $logistic_integration,
                    'marketplace'           => $marketplace,
                    'table_name'            => $table_name,
                    'skumkt'                => $skumkt,
                    'store_id'              => $store_id
                )
            );

            return $shipping_quote;
        } else if (isset($shipping_data['shipping']['data']['failure'])) {
            $shipping_quote['shipping']['data']['failure'] = $shipping_data['shipping']['data']['failure'];

            return $shipping_quote;
        }

        $shipping_rules = $this->model_shipping_price_rules->getActiveShippingPriceRules();

        if (empty($shipping_rules)) {
            $shipping_quote['shipping']['apply']['failure'] = "No shipping rules found.";

            return $shipping_quote;
        }

        foreach ($shipping_data as $current_shipping) {
            $shipping_counter = 0;
            if (($current_shipping['shipping_integration_id'] ?? null) !== null) {
                $shipping_counter += 1;
            }

            if (($current_shipping['shipping_company_id'] ?? null) !== null) {
                $shipping_counter += 1;
            }

            if (
                ($shipping_counter == 0) ||
                empty((string) $current_shipping['marketplace_id']) ||
                empty((string) $current_shipping['product_price'])
            ) {
                break;

                $shipping_quote['shipping']['apply']['failure'] = "'marketplace_id' and/or 'product_price' not provided.";

                return $shipping_quote;
            }

            $shipping_integration_id = $current_shipping['shipping_integration_id'] ?? null;
            $shipping_company_id = $current_shipping['shipping_company_id'] ?? null;
            $marketplace_id = $current_shipping['marketplace_id'];
            $product_price = $current_shipping['product_price'];

            foreach($shipping_rules as $pricing_rule) {
                $shipping_ids = explode(";", $pricing_rule['table_shipping_ids']);
                $integrations_ids = array();
                $companies_ids = array();

                foreach($shipping_ids as $si) {
                    if (strpos($si, "100000") !== false) {
                        $integrations_ids[] = substr($si, 6);
                    } else {
                        $companies_ids[] = $si;
                    }
                }

                $found = 0;
                if (in_array($shipping_company_id, $companies_ids)) {
                    $found += 1;
                }

                if (in_array($shipping_integration_id, $integrations_ids)) {
                    $found += 1;
                }

                $channels_ids = explode(";", $pricing_rule['mkt_channels_ids']);
                if (in_array($marketplace_id, $channels_ids)) {
                    $found += 1;
                }

                $pricing_rule_id = $pricing_rule['id'];
                $applied_price_range = "";
                $price_range = explode(";", $pricing_rule['price_range']);
                foreach($price_range as $pr) {
                    $current_range = explode(",", $pr);
                    if (($product_price >= $current_range[0]) && ($product_price <= $current_range[1])) {
                        $applied_price_range = $current_range;
                        $found += 1;
                        break;
                    }
                }

                if ($found >= 3) {

                    foreach($shipping_quote['data']['services'] as $current_index => $quote) {
                        $freight_price = (int) str_replace('.', '', number_format($quote['value'], 2, '.', ''));

                        // preço do frete / (1 - ((custo mkt + custo rma + margem de frete) / 100))
                        $new_shipping_price = ceil($freight_price / (1 - (($applied_price_range[2] + $applied_price_range[3] + $applied_price_range[4]) / 100)));
                        $number_length = strlen($new_shipping_price);
                        $new_shipping_price = substr($new_shipping_price, 0, $number_length - 2) . "." . substr($new_shipping_price, $number_length - 2);

                        $shipping_quote['data']['services'][$current_index]['value'] = floatval($new_shipping_price);

                        $shipping_quote['shipping']['apply'] = array("success" => "Shipping pricing rule applied.",
                            'rule' => array(
                                'rule_id'           => $pricing_rule_id,
                                'greater_than'      => $applied_price_range[0],
                                'less_than'         => $applied_price_range[1],
                                'mkt_cost'          => $applied_price_range[2],
                                'rma_cost'          => $applied_price_range[3],
                                'freight_margin'    => $applied_price_range[4]
                            )
                        );
                    }

                    break 2;
                }
            }
        }

        if ($redis->is_connected) {
            $redis->setex($key_redis_shipping_price_rules_data, 3600, json_encode($shipping_quote, JSON_UNESCAPED_UNICODE));
        }
        return $shipping_quote;
    }

    /**
     * Ignora serviços duplicados.
     *
     * @param   array   $services
     * @return  array
     */
    public function setServicesDuplicated(array $services): array
    {
        $newServices = array();
        $existingService = array();

        foreach ($services as $service) {
            if (in_array($service['method'].'-'.$service['skumkt'], $existingService)) {
                continue;
            }

            $existingService[] = $service['method'].'-'.$service['skumkt'];
            $newServices[] = $service;
        }

        return $newServices;
    }

    /**
     * Define as credenciais do seller center para a integradora informada.
     *
     * @throws Exception
     */
    public function setCredentialsSellerCenter()
    {
        $integration = [];
        if ($this->ms_shipping_carrier->use_ms_shipping) {
            try {
                $this->ms_shipping_carrier->setStore(0);
                $integrations_aux = $this->ms_shipping_carrier->getConfigure($this->logistic);

                if (
                    !is_null($integrations_aux) &&
                    isset($integrations_aux->id) &&
                    isset($integrations_aux->credentials)
                ) {
                    $integration = array(
                        "id"            => $integrations_aux->id,
                        "credentials"   => json_encode($integrations_aux->credentials),
                        "store_id"      => $this->store
                    );
                }
            } catch (Exception $exception) {}
        } else {
            $integration = $this->dbReadonly
                ->select('id,credentials,store_id')
                ->where('store_id', 0)
                ->where('integration', $this->logistic)
                ->get('integration_logistic')
                ->row_array();
        }

        if (empty($integration)) {
            throw new Exception('Falha para obter as credenciais do seller center.');
        }

        $this->integration_logistic_id = $integration['id'];

        $integration = json_decode($integration['credentials']);

        if (in_array($this->logistic, array('sgpweb', 'correios')) && $integration !== null) {
            if (!property_exists($integration, 'type_contract')) {
                throw new Exception('Falha para obter as credenciais do seller center. type_contract não encontrado.');
            }

            if ($integration->type_contract === 'new') {
                $integration->available_services = (array)json_decode('{"MINI":"04227","PAC":"03298","SEDEX":"03220"}');
            } else {
                $integration->available_services = (array)json_decode('{"MINI":"00000","PAC":"04669","SEDEX":"04162"}');
            }
        }

        $this->credentials = (array)$integration;
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function getSettingRedis(RedisCodeigniter $redis, string $setting_name)
    {
        if ($this->sellerCenter) {
            $key_redis = $this->sellerCenter.":settings:$setting_name";
            if ($redis->is_connected) {
                $data_redis = $redis->get($key_redis);
                if ($data_redis !== null) {
                    return json_decode($data_redis, true);
                }
            }
        }

        $time_exp_redis = 3600;

        $setting = $this->dbReadonly->get_where('settings', array('name' => $setting_name))->row_array();

        if ($redis->is_connected && $this->sellerCenter) {
            $redis->setex($key_redis, $time_exp_redis, json_encode($setting, JSON_UNESCAPED_UNICODE));
        }
        return $setting;
    }

    public function setAdditionalDeadline(RedisCodeigniter $redis, array $services): array
    {
        $use_crossdocking_on_freight = $this->getSettingRedis($redis, 'use_crossdocking_on_freight');

        if ($use_crossdocking_on_freight && $use_crossdocking_on_freight['status'] == 1 && is_numeric($use_crossdocking_on_freight['value'])) {
            $use_crossdocking_on_freight_value = (int)$use_crossdocking_on_freight['value'];

            if ($use_crossdocking_on_freight_value > 0) {
                foreach ($services as $key => $service) {
                    $services[$key]['deadline'] += $use_crossdocking_on_freight_value;
                }
            }
        }

        return $services;
    }

    public function formatShippingMethod(array $products, array $service): array
    {
        $count_products = count($products);
        return array_map(function($key, $sku) use ($service, $count_products) {
            $value = $service['value'];

            if ($count_products > 1) {
                $value = roundDecimal($service['value'] / $count_products);

                if ($key !== 0 && ($key + 1) == $count_products) {
                    $value = $service['value'] - ($value * $key);
                }
            }

            return array_merge($service, array(
                'value'     => $value,
                'prd_id'    => $sku['prd_id'] ?? null,
                'skumkt'    => $sku['skumkt'] ?? null,
            ));
        }, array_keys($products), $products);
    }

    /**
     * Consultar pontos de retirada ativos.
     *
     * @return  array
     */
    public function getPickupPoints(): array
    {
        $key_redis = "$this->sellerCenter:pickup_points:store:$this->store";
        if ($this->redis && $this->redis->is_connected) {
            $data_redis = $this->redis->get($key_redis);
            if ($data_redis !== null) {
                return json_decode($data_redis, true);
            }
        }

        $pickup_points = $this->model_pickup_point->getByStoreIdAndActive($this->store);
        foreach ($pickup_points as $key => $pickup_point) {
            $pickup_points[$key]['withdrawal_times'] = $this->model_withdrawal_time->getByPickupPointId($pickup_point['id']);
        }

        if ($this->redis && $this->redis->is_connected && !empty($pickup_points)) {
            $this->redis->setex($key_redis, 3600, json_encode($pickup_points, JSON_UNESCAPED_UNICODE));
        }

        return $pickup_points;
    }
       /**
     * Executa cotação assíncrona usando Guzzle promises
     * 
     * Versão assíncrona do método getQuote() para uso em execução paralela.
     * Mantém a mesma assinatura e comportamento do método original.
    * 
    * @param array $dataQuote Dados da cotação
    * @param bool $moduloFrete Flag do módulo de frete
    * @return mixed Promise ou array dependendo da implementação
    */
    public function getQuoteAsync(array $dataQuote, bool $moduloFrete = false)
    {
        // Implementação padrão que pode ser sobrescrita
        return \GuzzleHttp\Promise\Create::promiseFor(
            $this->getQuote($dataQuote, $moduloFrete)
        );
    }
}