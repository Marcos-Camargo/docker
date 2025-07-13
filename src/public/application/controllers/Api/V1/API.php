<?php

use Firebase\JWT\JWT;
use Integration_v2\Applications\Controllers\ApiIntegrationController;

require APPPATH . "/libraries/REST_Controller.php";

/**
 * Class API
 * @property Model_integration_erps $model_integration_erps
 * @property Model_log_integration $model_log_integration
 * @property Model_log_integration_unique $model_log_integration_unique
 * @property Model_api_integrations $model_api_integrations
 * @property Model_stores $model_stores
 * @property Model_orders $model_orders
 * @property Model_freights $model_freights
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_users $model_users
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 * @property Model_integration_logistic $model_integration_logistic
 * @property Model_order_to_delivered $model_order_to_delivered
 *
 * @property ApiIntegrationController $apiIntegrationController
 * @property JWT $jwt
 * @property CalculoFrete $calculofrete
 *
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_DB_query_builder $db
 * @property CI_Session $session
 * @property CI_Form_validation $form_validation
 * @property CI_Config $config
 * @property CI_Router $router
 * @property CI_Input $input
 *
 */
class API extends REST_Controller
{
    public $company_id = 0;
    public $company_cnpj = null;
    public $store_id = 0;
    public $user_email = '';
    public $user_id = 0;
    public $app_authorized=true;
    public $httpMethod = null;
    public $apiResource = null;
    public $endPointFunction = null;
    public $stores_by_provider = null;
    public $is_provider = false;
    public $user_permissions = [];
    public $starApiMetrics = null;
    public $starApiMetricsMl = null;

    public $logistic = null;

    const LOG_ROUTES = [
        'POST' => [
            ['job' => 'ApiProduct', 'route' => 'Api/V1/Variations/(:sku)', 'action' => 'create', 'entity' => 'product'],
            ['job' => 'ApiProduct', 'route' => 'Api/V1/Products', 'action' => 'create', 'entity' => 'product'],
            ['job' => 'ApiOrderInvoiced', 'route' => 'Api/V1/Orders/nfe', 'action' => 'create_invoiced', 'entity' => 'order'],
        ],
        'PUT' => [
            ['job' => 'ApiProduct', 'route' => 'Api/V1/Products/(:sku)', 'action' => 'update', 'entity' => 'product'],
            ['job' => 'ApiProduct', 'route' => 'Api/V1/Variations/sku/(:sku)/(:sku)', 'action' => 'update', 'entity' => 'product'],
            ['job' => 'ApiProduct', 'route' => 'Api/V1/Variations/sku/(:sku)', 'action' => 'update', 'entity' => 'product'],
            ['job' => 'ApiProduct', 'route' => 'Api/V1/Variations/(:sku)', 'action' => 'update', 'entity' => 'product'],
            ['job' => 'ApiOrderDelivered', 'route' => 'Api/V1/Orders/(:id)/delivered', 'action' => 'update_delivered', 'entity' => 'order'],
            ['job' => 'ApiOrderCanceled', 'route' => 'Api/V1/Orders/(:id)/canceled', 'action' => 'update_canceled', 'entity' => 'order'],
            ['job' => 'ApiOrderShipped', 'route' => 'Api/V1/Orders/(:id)/shipped', 'action' => 'update_shipped', 'entity' => 'order'],
        ]
    ];

    public $middlewareHeaderValidation = [
        'x-store-seller-key' => [
            'get' => [
                'orders' => [
                    'index_get' => [
                        'required' => false
                    ],
                    'list_get' => [
                        'required' => false
                    ]
                ],
                'products' => [
                    'list_get' => [
                        'required' => false
                    ],
                    'images_get' => [
                        'required' => false
                    ]
                ],
                'ordershistoric' => [
                    'index_get' => [
                        'required' => false
                    ]
                ]
            ],
            'delete' => [
                'orders' => [
                    'index_delete' => [
                        'required' => false
                    ]
                ]
            ]
        ]
    ];

    /**
     * @var bool
     */
    protected $tokenMaster = false;

    private const COMPANY_THAT_MANAGES_THE_SYSTEM = 1;

    protected $applicationData = [];

    /**
     * @var array $params Parâmetros GET.
     */
    public $params_filter = array();

    public function __construct()
    {
        parent::__construct();
        // Mostrar os erros somente em ambiente local, futuramente evoluir para ambiente dev
        if (ENVIRONMENT !== 'local') {
            ini_set('display_errors', 0);
        }
        $this->db->db_debug = false;

        $this->httpMethod = strtolower($this->_detect_method());
        $this->apiResource = strtolower(get_class($this));
        $this->load->library('JWT');
        $this->load->library('calculoFrete');
        $this->setPermissionAPI();
        $this->setFilters();
        $this->load->model('model_settings');
        $this->load->model('model_log_integration');
        $this->load->model('model_log_integration_unique');
        $this->load->model('model_integration_erps');
        $this->load->model('model_api_integrations');
        $this->load->model('model_stores');
        $this->load->model('model_users');
        $this->load->model('model_queue_products_marketplace');
        $this->load->model('model_integration_logistic');
        $this->load->model('model_order_to_delivered');

        $headers = getallheaders();
        foreach ($headers as $header => $value)
            $headers[strtolower($header)] = $value;
        if(isset($headers['x-language'])){
            switch ($headers['x-language']) {
                case 'en': $language = 'english'; break;
                case 'pt_br': $language = 'portuguese_br'; break;
                default: $language = 'portuguese_br'; break;
            }
        } else {
            $language = 'portuguese_br';
        }
        $this->lang->load('api', $language);

        $this->starApiMetrics = date("Y-m-d H:i:s");
        $this->starApiMetricsMl = DateTime::createFromFormat('U.u', microtime(true));

    }

    private function setFilters()
    {
        $this->params_filter = $this->get(null, true);
    }

    /**
     * Verifica se foram enviados todos os headers
     *
     * @param $header_request
     * @return array|bool
     */
    public function verifyHeader($header_request, $validateStoreProvider = true)
    {
        $headers = array();

        // Headers obrigatórios para requisição
        if (isset($header_request['x-provider-key'])) {
            $this->is_provider = true;
            $headers_valid = array(
                "x-email",
                "x-api-key",
                "x-provider-key",
                "accept",
                "content-type"
            );

            $validation = $this->getMiddlewareHeaderValidation('x-store-seller-key');
            if ($validateStoreProvider && (empty($validation) || $validation['required'] === true)) {
                $headers_valid[] = 'x-store-seller-key';
            }
        } else {
            $headers_valid = array(
                "x-user-email",
                "x-api-key",
                "x-store-key",
                "accept",
                "content-type",
            );
        }

        // headers recuperado na solicitação
        foreach ($header_request as $header => $value) {
            $headers[strtolower($header)] = $value;
        }

        // Verifica se não foram enviados todos os headers
        foreach ($headers_valid as $header_valid) {
            if (!array_key_exists($header_valid, $headers)) {
                return array(false, $header_valid);
            }
        }

        $this->user_email = isset($header_request['x-provider-key']) ? $headers['x-email'] : $headers['x-user-email'];

        $headers['x-application-id'] = $header_request['x-application-id'] ?? null;

        return array(true, $headers);
    }

    /**
     * Verifica KeyAPI com o x-store-key enviado no headewr
     *
     * @param $decodeKeyAPI
     * @param $cod_store
     * @return bool
     */
    public function verifyKeyAPIStore($decodeKeyAPI, $cod_store)
    {
        if (!isset($decodeKeyAPI->cod_store)) return false;

        $sql    = "SELECT * FROM stores WHERE id = ?";
        $query  = $this->db->query($sql, array($decodeKeyAPI->cod_store));

        // rick  $sqlUser    = "SELECT * FROM users WHERE email = ?";
        // rick  $queryUser  = $this->db->query($sqlUser, array($this->user_email));
        // rick   $fetchUser  = $queryUser->first_row();

        if($query->num_rows() === 0) return false; // Se não encontrar nenhum resultado

        $fetch = $query->first_row();

        if($fetch->id != $cod_store) return false; // Se o código da loja informado na requisição(x-store-key) não for o mesmo da consulta

        // Define valor para store e company
        $this->store_id = $fetch->id;
        $this->company_id = $fetch->company_id;
        // rick  $this->user_id = $fetchUser->id;

        return true;
    }

    /**
     * Decodifica a KeyAPI
     *
     * @param   string  $KeyAPI
     * @return  array|object
     */
    public function decodeKeyAPI($KeyAPI)
    {
        $key = get_instance()->config->config['encryption_key']; // Key para decodificação
        $decodeJWT = $this->jwt->decode($KeyAPI, $key, array('HS256'));
        // Verifica se ocorreu algum problema para decodificar a key
        if(is_string($decodeJWT))
            return array(
                'success' => false,
                'message' => $decodeJWT
            );

        return $decodeJWT;
    }

    /**
     * Verificação inicial, verifica headers, decope KeyAPI e store
     *
     * @param bool $validateStoreProvider   Valida o header com base no fornecedor.
     * @param bool $validateCnpjCompany     Quando verdade, irá validar se o cnpj do fornecedor é um cnpj de alguma empresa.
     */
    public function verifyInit(bool $validateStoreProvider = true, bool $validateCnpjCompany = true)
    {
        // Verifica se foram enviados todos os headers
        $headers = $this->verifyHeader(array_change_key_case(getallheaders()), $validateStoreProvider);

        // Não foram enviado todos os headers
        if(!$headers[0]){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,$this->lang->line('api_not_headers') . $headers[1],"W");
            return array(false, array('success' => false, 'message' => $this->lang->line('api_not_headers') . $headers[1]));
        }

        $headers = $headers[1];

        if (!$this->validateApplicationId($headers['x-application-id'])) {
            return [false, ['success' => false, 'message' => sprintf("x-application-id: %s inválido", $headers['x-application-id'])]];
        }

        // Decodifica token
        $decodeKeyAPI = $this->decodeKeyAPI($headers['x-api-key']);

        if (isset($headers['x-provider-key'])) {
            if (is_array($decodeKeyAPI)) {
                return array(false, $decodeKeyAPI);
            } else {
                if(!$this->verifyKeyAPIProvider($decodeKeyAPI, $headers['x-provider-key'], $headers['x-email'])) {
                    return array(false, array('success' => false, 'message' => $this->lang->line('api_x-provider-key_api')));
                }
            }
            $stores_by_provider = $this->model_stores->getStoresByProvider($headers['x-provider-key']);

            // Já verificou se o fornecedor existe, não irá validar se o cnpj do fornecedor corresponde a uma empresa.
            if (!$validateCnpjCompany && !count($stores_by_provider)) {
                return array(true, []);
            }

            $providerCnpj = $this->db->select('cnpj')->where('id', $decodeKeyAPI->provider_id)->get('providers')->row();
            $this->setTokenMaster($providerCnpj->cnpj);

            $cnpjOfTheCompanyThatTheStoreBelongsTo = $this->db->select('company.cnpj')
                ->get_where('company',
                    $this->db->compile_binds(
                        "(REPLACE(REPLACE(REPLACE(company.CNPJ, '.', ''), '-', ''), '/', '')) = (REPLACE(REPLACE(REPLACE(?, '.', ''), '-', ''), '/', ''))", [$this->company_cnpj]
                    )
                )->row();


            if (!$cnpjOfTheCompanyThatTheStoreBelongsTo && !count($stores_by_provider)) {
                return array(false, array('success' => false, 'message' => $this->lang->line('api_cnpj_do') . $this->company_cnpj . $this->lang->line('api_cnpj_do_end')));
            }
            $this->setTokenMaster($cnpjOfTheCompanyThatTheStoreBelongsTo->cnpj ?? null);

            // Fornecedor gerencia lojas.
            // Não é admin, se for, deve remover a seleção de lojas, pois irá gerenciar todas.
            if (!$this->tokenMaster && count($stores_by_provider)) {
                $this->stores_by_provider = array_map(function($store){
                    return $store['id'];
                }, $stores_by_provider);
                return array(true, []);
            }

            if ($this->tokenMaster) {
                $storeTokenMaster = $this->setStoreTokenMaster($headers, $validateStoreProvider);
                return $storeTokenMaster;
            } else {
                return array(false, array('success' => false, 'message' => $this->lang->line('api_cnpj_do_main') . $this->company_cnpj . $this->lang->line('api_cnpj_do_main_end')));
            }

        } else {
            // Não possível decodificar a key_api
            if(is_array($decodeKeyAPI)) {
                return array(false, $decodeKeyAPI);
            } else {
                // Código da loja informada no x-store-ke não corresponde com o código do token
                if (!$this->verifyKeyAPIStore($decodeKeyAPI, $headers['x-store-key'])) {
                    $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_store_key_api'), "W");
                    return array(false, array('success' => false, 'message' => $this->lang->line('api_store_key_api')));
                }
            }

            // Verificar e-mail do usuário
            $queryUser = $this->db->query("select u.id, u.email  from users u left join user_group ug on ug.user_id = u.id left join `groups` g on g.id = ug.group_id where u.email = '{$headers['x-user-email']}' and g.only_admin = 1");
            if ($queryUser->num_rows() > 0) {
                $fetchUser=$queryUser->first_row();
                $this->user_id = $fetchUser->id;
                $this->setPermissionByUserId($this->user_id);
            }else {
                $queryUser = $this->db->query("SELECT * FROM users WHERE email = '{$headers['x-user-email']}' AND (company_id = {$this->company_id} OR store_id = {$this->store_id})");
                if($queryUser->num_rows() === 0){
                    $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,$this->lang->line('api_mail_key_api'),"W");
                    return array(false, array('success' => false, 'message' => $this->lang->line('api_mail_key_api')));
                }
                $fetchUser=$queryUser->first_row();
                $this->user_id = $fetchUser->id;
                $this->setPermissionByUserId($this->user_id);
            }
        }

        if (($this->store_id ?? 0) > 0 && ($this->applicationData['id'] ?? 0) > 0) {
            try {
                require_once APPPATH . "libraries/Integration_v2/Applications/Controllers/ApiIntegrationController.php";
                $this->apiIntegrationController = new ApiIntegrationController($this->model_api_integrations, $this->lang);
                $this->apiIntegrationController->validateStoreIntegrationApplication([
                    'store_id' => $this->store_id ?? null,
                    'company_id' => $this->company_id ?? null,
                    'user_id' => $this->user_id ?? null,
                    'status' => Model_api_integrations::ACTIVE_STATUS
                ],
                    $this->applicationData
                );
            } catch (Throwable $e) {
                return [false, ['success' => false, 'message' => $e->getMessage()]];
            }
        }

        return array(true, []);

    }

    protected function validateApplicationId($applicationId)
    {
        if (empty($applicationId)) return true;
        $application = $this->model_integration_erps->getByApplicationId($applicationId);
        if (empty($application['id'] ?? '')) return false;
        $this->applicationData = $application;
        return true;
    }

    protected function getMiddlewareHeaderValidation($headerName)
    {
        if (isset($this->middlewareHeaderValidation[$headerName])) {
            $methods = $this->middlewareHeaderValidation[$headerName];
            $resources = $methods[$this->httpMethod] ?? [];
            $functions = $resources[$this->apiResource] ?? [];
            $function = $this->endPointFunction ? ($functions[$this->endPointFunction] ?? ['required' => true]) : ($functions['*'] ?? ['required' => true]);
            return $functions['*'] ?? $function;
        }
        return [];
    }

    private function setStoreTokenMaster($headers, $validateStoreProvider = true)
    {
        if (!$validateStoreProvider) {
            $this->company_id = null;
            $this->store_id = null;

            return array(true, []);
        }

        if (!isset($headers['x-store-seller-key'])) {
            $validation = $this->getMiddlewareHeaderValidation('x-store-seller-key');
            if (isset($validation['required']) && !$validation['required']) {
                $this->company_id = self::COMPANY_THAT_MANAGES_THE_SYSTEM;
                $this->store_id = 0;
                return [true, []];
            }
            return array(false, array('success' => false, 'message' => $this->lang->line('api_x-store-seller-key_not')));
        }

        $dataStore = $this->getDataStore($headers['x-store-seller-key']);
        if (!$dataStore)
            return array(false, array('success' => false, 'message' => $this->lang->line('api_store_not_found')));

        $this->company_id = (int)$dataStore->company_id;
        $this->store_id = (int)$dataStore->id;

        return array(true, []);

    }

    /**
     * @param mixed $cnpj
     * @return void
     */
    private function setTokenMaster($cnpj)
    {
        if ($cnpj === null) return false;
        $mainCompanyCnpj   = $this->db->select('cnpj')->where('id', self::COMPANY_THAT_MANAGES_THE_SYSTEM)->get('company')->row();
        $this->tokenMaster = preg_replace('/[^0-9]/', '', $cnpj) == preg_replace('/[^0-9]/', '', $mainCompanyCnpj->cnpj);
    }

    public function changeType($value, $type = "string")
    {
        if ($value === null) {
            return null;
        }

        switch ($type){
            case "string":
                return (string)filter_var($value, FILTER_SANITIZE_STRING);
            case "int":
                return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            case "number":
                return (float)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            case "float":
                $positionDot = strpos("{$value}", '.');
                $positionComma = strpos("{$value}", ',');
                if ($positionDot > $positionComma) {
                    $value = str_replace(',', '', $value);
                } else {
                    $value = str_replace(',', '.', str_replace('.', '', $value));
                }
                if ($value != '') {
                    $value = number_format($value, 2, '.', '');
                }
                return filter_var($value, FILTER_VALIDATE_FLOAT);
            case "array":
                return (array)$value;
            case "object":
                return (object)$value;
            case "boolean":
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            default:
                return $value;
        }
    }

    public function returnError($msg = "No results were found")
    {
        return array('success' => false, 'message' => $msg);
    }

    public function returnErrorUpdateProduct($data)
    {
        $columnError = array();

        switch ($data['error']){
            case 1:
                $msg = $this->lang->line('api_not_product_key');
                break;
            case 2:
                $msg = $this->lang->line('api_no_data_update');
                break;
            case 3:
                $msg = $this->lang->line('api_failure_communicate_database');
                break;
            case 4:
                $msg = $this->lang->line('api_parameter_not_match_field_update');
                $columnError = $data['data'];
                break;
            case 9:
                if(isset($data['data'])) $msg = $data['data'];
                else $msg = $this->lang->line('api_unknown_error');
                break;
        }

        if(count($columnError) > 0) $msg .= ". Field(s): " . implode(', ', $columnError);

        return array('success' => false, 'message' => $msg);
    }

    public function getCodeInfo($table, $column, $value, $where = "", $columnCode = 'id')
    {
        $value = htmlspecialchars_decode($value);
        if ($value == "") {
            return false;
        }

        $columnCodeBkp = $columnCode;
        if ($columnCode === 'id' && in_array($table, array('brands', 'categories'))) {
            $columnCode = 'id,active';
        }

        //$a = "SELECT {$columnCode} FROM {$table} WHERE {$column} = ".'"'.$value.'"'." {$where}";
        //$query = $this->db->query($a);
        $a = "SELECT {$columnCode} FROM {$table} WHERE {$column} = ? {$where}";
        $query = $this->db->query($a, array($value));

        if($query->num_rows() === 0 && $table == "brands") {
            $sqlBrand = $this->db->insert_string('brands', array('name' => $value, 'active' => 1));
            $this->db->query($sqlBrand);
            $query = $this->db->query("SELECT {$columnCode} FROM {$table} WHERE {$column} = '{$value}' {$where}");
        }

        $columnCode = $columnCodeBkp;

        if($query->num_rows() === 0 && $table != "brands") {
            return false;
        }

        $result = $query->first_row();

        if (isset($result->active) && $result->active == 2) {
            return false;
        }

        if (count(explode('.', $columnCode)) == 2) {
            $columnCode =  explode('.', $columnCode)[1];
        }

        return $result->$columnCode;
    }

    function ValidDateAndTime($dat)
    {
        $rsTime = true;
        $expDateTime = explode(" ", $dat);

        $_data = $expDateTime[0];
        $_time = count($expDateTime) == 2 ? $expDateTime[1] : false;

        $expDate = explode("/", $_data);
        if(count($expDate) != 3) return array(false, 'date');
        $d = $expDate[0];
        $m = $expDate[1];
        $y = $expDate[2];

        // Verifica data - 1=true, 0=false!
        $verifyDate = checkdate($m,$d,$y);

        if ($verifyDate == 1) $rsDate = true;
        else return array(false, 'date');

        // Verifica time
        if($_time !== false){
            $expTime = explode(":", $_time);
            if(count($expTime) !== 3) return array(false, 'time');

            if($expTime[0] < 0 || $expTime[0] > 23) return array(false, 'time');
            if($expTime[1] < 0 || $expTime[1] > 59) return array(false, 'time');
            if($expTime[2] < 0 || $expTime[2] > 59) return array(false, 'time');

            $rsTime = true;
        }

        if(!$rsTime || !$rsDate) return array(false);

        return array(true);
    }

    function log_data($mod,$action,$value,$tipo = 'I')
    {

        if (!$this->model_settings->getValueIfAtiveByName('enable_log_api')) {
            return true;
        }

        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }elseif(!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = "NONE";
        }

        $store_id   = $this->store_id ?? '...';
        $company_id = $this->company_id ?? '...';
        $user_id    = $this->user_id ?? '...';
        $user_email = $this->user_email ?? '...';

        if($value) {
            $datalog = array(
                'user_id' => 1,
                'company_id' => $this->company_id ?? '0',
                'store_id' => $this->store_id ?? '0',
                'module' => $mod,
                'action' => $action,
                'ip' => $ip,
                'value' => "[URL={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}]\n[STORE_ID={$store_id}]\n[COMPANY_ID={$company_id}]\n[USER_ID={$user_id}]\n[USER_MAIL={$user_email}]\n\n".$value,
                'tipo' => $tipo
            );
            $insert = $this->db->insert('log_history_api', $datalog);
            return ($insert == true) ? true : false;
        }

        return false;
    }

    public function getDataStoreIntegration()
    {
        $query = $this->db->query("SELECT * FROM api_integrations WHERE store_id = {$this->store_id}");
        return $query->first_row();
    }

    public function checkAuth($header)
    {
        $headers = $this->verifyHeaderProvider($header);

        if(!$headers[0]){
            return array(false, array('success' => false, 'message' => $this->lang->line('api_not_headers') . $headers[1]));
        }

        $headers = $headers[1];

        $decodeKeyAPI = $this->decodeKeyAPI($headers['x-api-key']);

        if (is_array($decodeKeyAPI)) {
            return array(false, $decodeKeyAPI);
        } else {

            if(!$this->verifyKeyAPIProvider($decodeKeyAPI, $headers['x-provider-key'], $headers['x-email'])) {
                return array(false, array('success' => false, 'message' => $this->lang->line('provider')));
            }
        }

        $providerCnpj = $this->db->select('cnpj')->where('id', $decodeKeyAPI->provider_id)->get('providers')->row();
        $this->setTokenMaster($providerCnpj->cnpj);

        return array(true);
    }

    public function verifyHeaderProvider($header_request)
    {
        $headers = array();

        $headers_valid = [
            "x-email",
            "x-api-key",
            "x-provider-key",
            "accept",
            "content-type",
        ];

        foreach ($header_request as $header => $value) {
            $headers[strtolower($header)] = $value;
        }

        foreach ($headers_valid as $header_valid) {
            if(!array_key_exists($header_valid , $headers)) {
                return array(false, $header_valid);
            }
        }

        return array(true, $headers);
    }

    public function verifyKeyAPIProvider($decodeKeyAPI, $cod_provider, $email)
    {
        if (!isset($decodeKeyAPI->provider_id)) {
            return false;
        }
        $sql    = "SELECT * FROM providers WHERE id = {$decodeKeyAPI->provider_id} AND responsible_email = '{$decodeKeyAPI->email}' AND active_token_api = 1";
        $query  = $this->db->query($sql);

        if ($query->num_rows() === 0) {
            return false;
        }

        $fetch = $query->first_row();

        if ($fetch->id != $cod_provider) {
            return false;
        }

        if ($fetch->responsible_email != $email) {
            return false;
        }

        // Define cnpj do fornecedor
        $this->company_cnpj = $fetch->cnpj;

        return true;
    }

    public function setLogistic()
    {
        if (empty($this->logistic)) {
            $store = $this->db->get_where('stores', array('id' => $this->store_id))->row_array();
            $this->logistic =  $this->calculofrete->getLogisticStore(array(
                'freight_seller' 		=> $store['freight_seller'],
                'freight_seller_type' 	=> $store['freight_seller_type'],
                'store_id'				=> $store['id']
            ));
        }
    }

    public function getStoreUseFreight(): bool
    {
        $this->setLogistic();
        return $this->logistic['seller'] == 1;
    }

    public function getStoreUseFreightByProvider(): bool
    {
        if (!$this->is_provider) {
            return false;
        }
        $this->setLogistic();
        if (!empty($this->logistic['type'])) {
            $integration_logistic_id = $this->logistic['shipping_id'];
            if ($integration_logistic_id) {
                $integration_logistic = $this->model_integration_logistic->getIntegrationsById($integration_logistic_id);
                $external_integration_id = $integration_logistic['external_integration_id'];
                if ($external_integration_id) {
                    return true;
                }
            }
        }

        return false;
    }

    private function setPermissionAPI()
    {
        $query = $this->db->query('SELECT * FROM settings WHERE `name` = ?', array('sellercenter'));
        $settingSellerCenter = $query->row_array();

        if (!$settingSellerCenter ||
            (
                $settingSellerCenter['value'] != 'conectala' &&
                $settingSellerCenter['value'] != 'novomundo' &&
                $settingSellerCenter['value'] != 'ortobom'	 &&
                $settingSellerCenter['value'] != 'vertem'
            )
        )
            $this->app_authorized = false;
        else $this->app_authorized = true;
    }

    /**
     * Recupera se o produto já está integrado com algum maeketplace
     *
     * @param   int         $prd_id Código do produto
     * @return  array|null          Retorna um array se existir se nõa retorna nulo
     */
    public function getPrdIntegration($prd_id)
    {
        return $this->db
            ->from('prd_to_integration')
            ->where('prd_id', $prd_id)
            ->get()
            ->result_array();
    }

    /**
     * Remover todos os acentos
     *
     * @param   string  $string Texto para remover os acentos
     * @return  string
     */
    public function removeAccents($string){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
    }

    public function somar_dias_uteis( $str_data, $int_qtd_dias_somar, $feriados = '')
    {
        // Caso seja informado uma data do MySQL do tipo DATETIME - aaaa-mm-dd 00:00:00
        // Transforma para DATE - aaaa-mm-dd
        // limite máximo de 365 dias 
        $int_qtd_dias_somar = $int_qtd_dias_somar > 365 ? 365 : $int_qtd_dias_somar;

        $str_data = substr( $str_data, 0, 10 );
        // Se a data estiver no formato brasileiro: dd/mm/aaaa
        // Converte-a para o padrão americano: aaaa-mm-dd
        if ( preg_match( "@/@", $str_data ) == 1 ) {
            $str_data = implode( "-", array_reverse( explode( "/", $str_data ) ) );
        }
        // chama a funcao que calcula a pascoa
        $pascoa_dt = $this->dataPascoa( date( 'Y' ) );
        $aux_p = explode( "/", $pascoa_dt );
        $aux_dia_pas = $aux_p[0];
        $aux_mes_pas = $aux_p[1];
        $pascoa = "$aux_mes_pas" . "-" . "$aux_dia_pas"; // crio uma data somente como mes e dia
        // chama a funcao que calcula o carnaval
        $carnaval_dt = $this->dataCarnaval( date( 'Y' ) );
        $aux_carna = explode( "/", $carnaval_dt );
        $aux_dia_carna = $aux_carna[0];
        $aux_mes_carna = $aux_carna[1];
        $carnaval = "$aux_mes_carna" . "-" . "$aux_dia_carna";
        // chama a funcao que calcula corpus christi
        $CorpusChristi_dt = $this->dataCorpusChristi( date( 'Y' ) );
        $aux_cc = explode( "/", $CorpusChristi_dt );
        $aux_cc_dia = $aux_cc[0];
        $aux_cc_mes = $aux_cc[1];
        $Corpus_Christi = "$aux_cc_mes" . "-" . "$aux_cc_dia";
        // chama a funcao que calcula a sexta feira santa
        $sexta_santa_dt = $this->dataSextaSanta( date( 'Y' ) );
        $aux = explode( "/", $sexta_santa_dt );
        $aux_dia = $aux[0];
        $aux_mes = $aux[1];
        $sexta_santa = "$aux_mes" . "-" . "$aux_dia";
        $feriados = array(
            "01-01", //Ano Novo
            $carnaval,
            $sexta_santa,
            $pascoa,
            $Corpus_Christi,
            "04-21", //Tiradentes
            "05-01", //Dia Mundial do Trabalho
            "07-09", //Independência do Brasil
            "10-12", //Nossa Senhora Aparecida
            "11-02", //Finados
            "11-15", //Proclamação da República
            "12-25", //Natal
        );

        $array_data = explode( '-', $str_data );
        $count_days = 0;
        $int_qtd_dias_uteis = 0;
        while ( $int_qtd_dias_uteis < $int_qtd_dias_somar ) {
            $count_days++;
            $day = date( 'm-d', strtotime( '+' . $count_days . 'day', strtotime( $str_data ) ) );
            if ( ($dias_da_semana = gmdate( 'w', strtotime( '+' . $count_days . ' day', gmmktime( 0, 0, 0, $array_data[1], $array_data[2], $array_data[0] ) ) ) ) != '0' && $dias_da_semana != '6' && !in_array( $day, $feriados ) ) {
                $int_qtd_dias_uteis++;
            }
        }
        return gmdate( 'Y-m-d', strtotime( '+' . $count_days . ' day', strtotime( $str_data ) ) );
    }

    private function dataPascoa( $ano = false, $form = "d/m/Y" ) {
        $ano = $ano ? $ano : date( "Y" );
        if ( $ano < 1583 ) {
            $A = ($ano % 4);
            $B = ($ano % 7);
            $C = ($ano % 19);
            $D = ((19 * $C + 15) % 30);
            $E = ((2 * $A + 4 * $B - $D + 34) % 7);
            $F = ( int ) (($D + $E + 114) / 31);
            $G = (($D + $E + 114) % 31) + 1;
            return date( $form, mktime( 0, 0, 0, $F, $G, $ano ) );
        } else {
            $A = ($ano % 19);
            $B = ( int ) ($ano / 100);
            $C = ($ano % 100);
            $D = ( int ) ($B / 4);
            $E = ($B % 4);
            $F = ( int ) (($B + 8) / 25);
            $G = ( int ) (($B - $F + 1) / 3);
            $H = ((19 * $A + $B - $D - $G + 15) % 30);
            $I = ( int ) ($C / 4);
            $K = ($C % 4);
            $L = ((32 + 2 * $E + 2 * $I - $H - $K) % 7);
            $M = ( int ) (($A + 11 * $H + 22 * $L) / 451);
            $P = ( int ) (($H + $L - 7 * $M + 114) / 31);
            $Q = (($H + $L - 7 * $M + 114) % 31) + 1;
            return date( $form, mktime( 0, 0, 0, $P, $Q, $ano ) );
        }
    }

    // dataCarnaval(ano, formato);
    // Autor: Yuri Vecchi
    //
    // Funcao para o calculo do Carnaval
    // Retorna o dia do Carnaval no formato desejado ou false.
    //
    // ######################ATENCAO###########################
    // Esta funcao sofre das limitacoes de data de mktime()!!!
    // ########################################################
    //
    // Possui dois parametros, ambos opcionais
    // ano = ano com quatro digitos
    //	 Padrao: ano atual
    // formato = formatacao da funcao date() http://br.php.net/date
    //	 Padrao: d/m/Y

    private function dataCarnaval( $ano = false, $form = "d/m/Y" ) {
        $ano = $ano ? $ano : date( "Y" );
        $a = explode( "/", $this->dataPascoa( $ano ) );
        return date( $form, mktime( 0, 0, 0, $a[1], $a[0] - 47, $a[2] ) );
    }

    // dataCorpusChristi(ano, formato);
    // Autor: Yuri Vecchi
    //
    // Funcao para o calculo do Corpus Christi
    // Retorna o dia do Corpus Christi no formato desejado ou false.
    //
    // ######################ATENCAO###########################
    // Esta funcao sofre das limitacoes de data de mktime()!!!
    // ########################################################
    //
    // Possui dois parametros, ambos opcionais
    // ano = ano com quatro digitos
    //	 Padrao: ano atual
    // formato = formatacao da funcao date() http://br.php.net/date
    //	 Padrao: d/m/Y

    private function dataCorpusChristi( $ano = false, $form = "d/m/Y" ) {
        $ano = $ano ? $ano : date( "Y" );
        $a = explode( "/", $this->dataPascoa( $ano ) );
        return date( $form, mktime( 0, 0, 0, $a[1], $a[0] + 60, $a[2] ) );
    }

    // dataSextaSanta(ano, formato);
    // Autor: Yuri Vecchi
    //
    // Funcao para o calculo da Sexta-feira santa ou da Paixao.
    // Retorna o dia da Sexta-feira santa ou da Paixao no formato desejado ou false.
    //
    // ######################ATENCAO###########################
    // Esta funcao sofre das limitacoes de data de mktime()!!!
    // ########################################################
    //
    // Possui dois parametros, ambos opcionais
    // ano = ano com quatro digitos
    // Padrao: ano atual
    // formato = formatacao da funcao date() http://br.php.net/date
    // Padrao: d/m/Y

    private function dataSextaSanta( $ano = false, $form = "d/m/Y" ) {
        $ano = $ano ? $ano : date( "Y" );
        $a = explode( "/", $this->dataPascoa( $ano ) );
        return date( $form, mktime( 0, 0, 0, $a[1], $a[0] - 2, $a[2] ) );
    }

    public function sendEmailMarketing($to, $subject, $body, $from = null, $attach = null)
    {
        if (is_null($from)) {
            $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
            if (!$from) {
                $from = 'marketing@conectala.com.br';
            }
        }
        //SAO PAULO
        /*
        $config['protocol'] = 'smtp';
        $config['smtp_host'] = "email-smtp.sa-east-1.amazonaws.com";
        $config['smtp_port'] = "587";
        $config['smtp_crypto'] = 'tls';
        $config['smtp_user'] = "AKIAX56SPJIDZVGV6HRO";
         $config['smtp_pass'] = "BA+Zofvv4t9thv30itfKbDTwlrK95u+b3TrXgPUtfcxa";

        //North Vriginia
        $config['smtp_host'] = "email-smtp.us-east-1.amazonaws.com";
        $config['smtp_port'] = "587";
        $config['smtp_crypto'] = 'tls';
        $config['smtp_user'] = "AKIAX56SPJIDSGXGYSF4";
         $config['smtp_pass'] = "BE6cuR2nDuvf+1t5FCX2w1VIGNudriXtpv1/crLEJvaK";
        */
        $config = array();
        $config['smtp_host']= $this->model_settings->getValueIfAtiveByName('smtp_host');
        if ($config['smtp_host'] === false) {
            $config['smtp_host'] = "email-smtp.us-east-1.amazonaws.com";
        }
        $config['smtp_port']= $this->model_settings->getValueIfAtiveByName('smtp_port');
        if ($config['smtp_port'] === false) {
            $config['smtp_port'] = "587";
        }
        $config['smtp_crypto']= $this->model_settings->getValueIfAtiveByName('smtp_crypto');
        if ($config['smtp_crypto'] === false) {
            $config['smtp_crypto'] = 'tls';
        }
        $config['smtp_user']= $this->model_settings->getValueIfAtiveByName('smtp_user');
        if ($config['smtp_user'] === false) {
            $config['smtp_user'] = "AKIAX56SPJIDSGXGYSF4";
        }
        $config['smtp_pass']= $this->model_settings->getValueIfAtiveByName('smtp_pass');
        if ($config['smtp_pass'] === false) {
            $config['smtp_pass'] = "BE6cuR2nDuvf+1t5FCX2w1VIGNudriXtpv1/crLEJvaK";
        }
        $config['protocol']= $this->model_settings->getValueIfAtiveByName('smtp_protocol');
        if ($config['protocol'] === false) {
            $config['protocol'] = "smtp";
        }

        $config['smtp_timeout']='10';
        $config['charset'] = "utf-8";
        $config['mailtype'] = "html";
        $config['newline'] = "\r\n";
        $config['crlf']     = "\r\n";
        $config['wordwrap'] = TRUE;
        $this->load->library('email',$config);
        $this->email->initialize($config);

        if (!is_array($to)) {
            $sendto[] = $to;
        }else {
            $sendto = $to;
        }
        $this->email->from($from,$from);
        $this->email->to($sendto);
        $this->email->subject($subject);
        $this->email->message($body);
        // Anexo
        if($attach)
            $this->email->attach($attach);

        if (!$this->email->send()) {
            $this->log_data("email","send", $this->email->print_debugger(),"E");
            return (array('ok'=>false,'msg'=> $this->lang->line('api_error_mail') . $this->email->print_debugger()));
        } else {
            return (array('ok'=>true,'msg'=> $this->lang->line('api_success_mail')));
        }

    }

    public function getDataStore($store = null)
    {
        $sql = "SELECT * FROM stores WHERE id = ?";
        $query = $this->db->query($sql, array($store ?? $this->store_id));
        return $query->first_row();
    }

    public function getDataCatalogByStore($store)
    {
        $this->load->model('Model_catalogs');

        $catalog = $this->Model_catalogs->getCatalogsStoresDataByStoreId($store);

        return $catalog ? $catalog : false;
    }

    public function setPermissionByUserId($user_id)
    {
        $permission = [];

        $user_group = $this->model_users->getUserGroup($user_id);

        if ($user_group) {
            $permission = unserialize($user_group['permission']);
        }

        $this->user_permissions = $permission ?? [];
    }

    public function response($data = NULL, $http_code = NULL, string $additional_description = NULL)
    {
        parent::response($data, $http_code);

        $method = strtoupper($this->_detect_method());
        if (!empty($this->store_id) && !empty(self::LOG_ROUTES[$method] ?? '')) {
            $entityId = null;
            $requestRoute = uri_string();
            $exp_route = explode('/', $requestRoute);
            foreach ($exp_route as $key_route => $value_route) {
                if ($key_route >= ($exp_route[0] === 'app' ? 4 : 3)) {
                    break;
                }

                if ($value_route === 'app') {
                    continue;
                }

                $exp_route[$key_route] = ucfirst($value_route);
            }
            $requestRoute = implode('/', $exp_route);
            $enabled = array_filter(self::LOG_ROUTES[$method] ?? [], function ($route) use ($requestRoute, &$entityId) {
                $route['route'] = str_replace('(:sku)', '([A-Za-z0-9_-]*)', $route['route']);
                $route['route'] = str_replace('(:id)', '([0-9]*)', $route['route']);
                $route['route'] = str_replace('/', '\/', $route['route']);
                preg_match(sprintf("/%s/", $route['route']), $requestRoute, $matches);
                if (strcasecmp($matches[1] ?? '', 'attributes') === 0 || strcasecmp($matches[1] ?? '', 'sku') === 0) return false;
                if (!empty($matches))
                    $entityId = $matches[1] ?? null;
                return !empty($matches);
            });
            if (empty($enabled)) return;
            $requestBody = json_decode(json_encode((object)($this->_post_args ?? [])), true);
            $entityId = $entityId
                ?? array_column($requestBody, 'sku')[0]
                ?? array_column($requestBody['product'] ?? [], 'sku')[0]
                ?? array_column($requestBody['nfe'] ?? [], 'order_number')[0]
                ?? array_column($requestBody['order'] ?? [], 'code')[0]
                ?? array_column($requestBody['order'] ?? [], 'marketplace_number')[0]
                ?? array_column($requestBody, 'order_number')[0]
                ?? array_column($requestBody, 'code')[0]
                ?? array_column($requestBody, 'marketplace_number')[0]
                ?? null;
            $enabled = current($enabled);
            $error = !$data['success'] ? '_error' : '';
            $title = sprintf($this->lang->line("api_log_title_{$enabled['action']}_{$enabled['entity']}{$error}"), ($entityId ?? 'não identificado'));
            $message = $data['message']['data'] ?? $data['message'] ?? $data;
            if (!is_string($message)) {
                $message = json_encode($message);
            }
            // Descrição adicional. Criada com o intuíto de mostrar o que foi enviado na requisição no histórico de integração.
            if (!is_null($additional_description)) {
                $message .= $additional_description;
            }
            $this->model_log_integration->log([
                'store_id' => $this->store_id,
                'company_id' => $this->company_id,
                'title' => $title,
                'description' => $message,
                'type' => $data['success'] ? 'S' : 'E',
                'status' => 1,
                'job' => $enabled['job'],
                'unique_id' => $entityId ?? date('Ymd'),
            ]);
            $this->model_log_integration_unique->log([
                'store_id' => $this->store_id,
                'company_id' => $this->company_id,
                'title' => $title,
                'description' => $message,
                'type' => $data['success'] ? 'S' : 'E',
                'status' => 1,
                'job' => $enabled['job'],
                'unique_id' => $entityId ?? date('Ymd'),
            ]);
        }

        if ($this->model_settings->getValueIfAtiveByName('enable_log_api_metrics')) {
            $ApiMetrics = array();
            $ApiMetrics['response_code'] =  $http_code;
            $this->apiMetrics($ApiMetrics);
        }


    }

    public function baseUrl($url) {
        return baseUrlPublic($url);
    }

    public function createButtonLogRequestIntegration($payload): string
    {
        return "<div class='col-md-12 d-flex justify-content-center mb-3'><button type='button' class='btn btn-primary text-center btnCollapseLogRequestProduct'>{$this->lang->line('api_view_sent_call')}</button></div><div class='collapseLogRequestProduct d-none'><pre>" . json_encode($payload, JSON_UNESCAPED_UNICODE) . "</pre></div>";
    }

    public function checkStoreByProvider(int $store_id, array $data = array()): bool
    {
        // Se for admin não deve validar a loja.
        if ($this->tokenMaster) {
            return true;
        }

        if (is_null($this->stores_by_provider)) {
            return $store_id == $this->store_id;
        }

        if (array_key_exists('store_id', $data)) {
            $this->store_id = $data['store_id'];
        }

        if (array_key_exists('company_id', $data)) {
            $this->company_id = $data['company_id'];
        }

        return in_array($store_id, $this->stores_by_provider);
    }

    public function getStoreToLists()
    {
        return is_null($this->stores_by_provider) ? $this->store_id : $this->stores_by_provider;
    }

    public function inputClean($raw_input = false, $remove_html_tags = true)
    {
        if ($raw_input) {
            return json_decode(json_encode(cleanArray(json_decode($this->input->raw_input_stream,true), false, $remove_html_tags)));
        }
        else {
            return json_decode(json_encode(cleanArray(json_decode(file_get_contents('php://input'),true), false, $remove_html_tags)));
        }
    }

    public function cleanGet($get= null , $remove_html_tags = true )
    {
        if (is_null($get)) {
            $get = $this->input->get();
        }
        if (is_array($get)) {
            return cleanArray($get, false, $remove_html_tags);
        } elseif (is_object($get)) {
            $get = cleanArray(json_decode(json_encode($get), true), false, $remove_html_tags);
            return json_decode(json_encode($get), FALSE);
        } else {
            return xssClean($get, false, $remove_html_tags);
        }
    }

    /* Instruções de Uso da função:
     * response_code = código de retorno da API (200,201,500,etc)
    */

    public function apiMetrics($arrayInputs = array()){

        $endDate =  date("Y-m-d H:i:s");
        $endDateMl = DateTime::createFromFormat('U.u', microtime(true));

        $startDateFormat = $this->starApiMetricsMl;
        $EndDateFormat = $endDateMl;
        // the difference through one million to get micro seconds
        $uDiff = ($startDateFormat->format('u') - $EndDateFormat->format('u')) / (1000 * 1000);
        $diff = $startDateFormat->diff($EndDateFormat);
        $s = (int) $diff->format('%s') - $uDiff;
        $i = (int) ($diff->format('%i')) * 60; // convert minutes into seconds
        $h = (int) ($diff->format('%h')) * 60 * 60; // convert hours into seconds

        $execution_time = sprintf('%.6f', abs($h + $i + $s));

        $raw_input_stream = $this->input->raw_input_stream;
        $request_body = "";

        if (!empty($this->uri->keyval['rsegment'])) {
            $request_body = str_replace("{$this->router->class}/{$this->router->method}/", '', implode('/',$this->uri->rsegment_array()));
        }elseif(!empty($raw_input_stream)){
            $request_body = json_encode(json_decode($raw_input_stream), JSON_UNESCAPED_UNICODE);
        }elseif(!empty($this->input->get())){
            $request_body = $this->input->get();
            $request_body = json_encode($request_body);
        }

        $integration = "";
        if(!empty($this->applicationData)){
            $integration = $this->applicationData['name'];
        }


        $arrayInputs += array(
            'url' => $this->router->class,
            'method_type' => $this->input->method(),
            'method' =>  $this->router->method,
            'start_date' => $this->starApiMetrics,
            'end_date' => $endDate,
            'integration' =>  $integration,
            'execution_time' => $execution_time*1000,
            'request_body' => $request_body
        );

        $insert = $this->db->insert('log_execution_api', $arrayInputs);
        return $insert == true;


    }

    /**
     * Recuperar a url de interna de API
     */
    public function getInternalUrl()
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

        return $url;
    }

    public function sendProductToQueue(int $prd_id)
    {
        $this->model_queue_products_marketplace->create(array(
            'status' => 0,
            'prd_id' => $prd_id
        ));
    }
    
    public function getDataAtualizacaoForcada($store_id, $order_id, $origin) {
        //verifica se a loja tiver configurada
        $hasField = $this->model_order_to_delivered->getHasOrderToDeliveredField($store_id);

        //se não tiver, não entra na validação
        if (!$hasField || !$origin ) {
            return null;
        }

        $tracking = $this->model_order_to_delivered->getTrackingByStoreAndMarketplace($store_id, $origin); //origin é compativel com int_to
        if (!$tracking) {
            return null;
        }

        $orderConfig = $this->model_order_to_delivered->getConfigById($tracking['order_to_delivered_config_id']);
        if (!$orderConfig || empty($orderConfig['dias_para_atualizar'])) {
            return null;
        }

        $orderDays = $orderConfig['dias_para_atualizar'];

        //busca a data de criação do pedido
        $orderCreatedAt = $this->model_order_to_delivered->getOrderDateCreate($order_id);

        //se não tiver dara de criacao ou num de dias
        if (!$orderCreatedAt || !$orderDays) {
            return null;
        }

        //coneverte a criacao do pedido p timestamp
        $createdAtTimestamp = strtotime($orderCreatedAt);

        //soma a quantidade de dias colocada com a data de criacao
        $forcedUpdateTimestamp = strtotime("+{$orderDays} days", $createdAtTimestamp);

        //se a data e hora atuais ultrapassaram/atingiram a data limite calculada.
        if (time() >= $forcedUpdateTimestamp) {
            return $forcedUpdateTimestamp;
        }
        
        //ainda não atingiu o limite, retorna null
        return null;
    }

}
