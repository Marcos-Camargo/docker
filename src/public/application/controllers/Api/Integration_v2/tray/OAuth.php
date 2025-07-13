<?php

require_once 'system/libraries/Vendor/autoload.php';
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Helpers/URL.php';
require_once APPPATH . 'libraries/Integration_v2/tray/Resources/Configuration.php';
require_once APPPATH . 'libraries/Integration_v2/tray/Services/AuthService.php';
require_once APPPATH . 'libraries/Integration_v2/tray/Controllers/AuthApp.php';

use GuzzleHttp\Client;
use libraries\Helpers\URL;
use Integration_v2\tray\Resources\Configuration;
use Integration_v2\tray\Services\AuthService;
use Integration_v2\tray\Controllers\AuthApp;

/**
 * Class OAuth
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property Client $client
 * @property Model_company $model_company
 * @property Model_stores $model_stores
 * @property Model_api_integrations $model_api_integrations
 * @property Model_settings $model_settings
 * @property Model_users $model_users
 * @property Configuration $configuration
 * @property AuthService $authService
 */
class OAuth extends REST_Controller
{
    protected $loginUrl;

    protected $companyId;
    protected $storeId;
    protected $userId;
    protected $integration;
    protected $queryParams;

    protected $trayStoreId;

    protected $isOnIframe = false;

    public function __construct($config = 'rest')
    {
        parent::__construct($config);
        $this->load->library('session');
        $this->load->model('model_company');
        $this->load->model('model_stores');
        $this->load->model('model_settings');
        $this->load->model('model_api_integrations');
        $this->load->model('model_users');

        $this->client = new Client([
            'verify' => false,
            'timeout' => 900,
            'connect_timeout' => 900,
            'allow_redirects' => true
        ]);

        $this->configuration = new Configuration();
        $this->authService = new AuthService($this->model_api_integrations);

        $layout = $this->model_settings->getSettingDatabyName('layout_seller_center');
        if ($layout['status'] == 1) {
            $settingSellerCenter = $this->model_settings->getValueIfAtiveByName('sellercenter') ?? 'conectala';
            $layout = ['value' => $settingSellerCenter];
        }
        $this->session->set_userdata('layout', $layout ?? ['value' => 'conectala']);
        $company = $this->model_company->getCompanyData(1);
        $this->session->set_userdata('logo', $company['logo']);

        $this->queryParams = $this->_query_args;
        $this->trayStoreId = $this->queryParams['store'] ?? 0;
        try {
            $this->loginUrl = (new URL(base_url('auth/login')))->addQuery([
                'redirect_url' => URL::retrieveServerCurrentURL()
            ])->getURL();
        } catch (Throwable $e) {
            $this->loginUrl = base_url();
        }
        if (empty($this->session->userdata('logged_in'))) {
            $this->isOnIframe = ((string)($this->queryParams['_in_iframe'] ?? '')) == 'true';
            if (!$this->isOnIframe) redirect($this->loginUrl);
            $this->fetchUserDataByTrayStoreId();
        }

        $this->companyId = $this->session->userdata('usercomp') ?? $this->companyId ?? null;
        $this->companyId = $this->companyId !== null ? (int)$this->companyId : null;
        $this->storeId = $this->queryParams['integration_store_id'] ?? $this->session->userdata('tray_store_config') ?? $this->session->userdata('userstore') ?? $this->storeId ?? null;
        $this->userId = $this->session->userdata('id') ?? $this->userId ?? null;
    }

    public function index_get($view = 'index')
    {
        if (!$this->validation()) {
            return;
        }

        $stores = [];
        if ($this->storeId == 0) {
            $stores = $this->model_api_integrations->getStoresByCompanyIdWithoutIntegration($this->companyId);
        }

        $logoUrl = base_url() . "/assets/skins/" . $this->session->userdata('skin') . "/banner.jpg";
        $logoUrl = @file_get_contents($logoUrl) ? $logoUrl : base_url() . $this->session->userdata('logo');
        $logoUrl = base_url() . $this->session->userdata('logo');
        $layout = $this->session->userdata('layout');

        $authUrl = Configuration::getAuthConfirmationURL();
        $qry = http_build_query($this->queryParams);
        $authUrl = "{$authUrl}?{$qry}";
        $this->load->view("oauth/tray/{$view}", [
            'page_title' => 'Configuração de Integração com TRAY',
            'environment' => [
                'name' => $this->model_settings->getValueIfAtiveByName('sellercenter_name') ?? 'Conecta Lá',
                'sellercenter' => $this->model_settings->getValueIfAtiveByName('sellercenter') ?? 'conectala',
                'style' => $layout['value'],
                'logoUrl' => $logoUrl
            ],
            'data' => $this->integration,
            'user' => $this->model_users->getUserById($this->userId) ?? $this->model_users->getUserById($this->integration['user_id']) ?? [],
            'authUrl' => $authUrl,
            'stores' => $stores
        ]);
    }

    public function authConfirm_get()
    {
        if (!$this->validation()) {
            return;
        }
        $this->integration['credentials'] = json_decode($this->integration['credentials'] ?? '{}', true);
        $this->integration['credentials'] = (object)array_merge($this->integration['credentials'], [
            'storeUrl' => $this->queryParams['url'] ?? '',
            'storeId' => $this->queryParams['store'] ?? ''
        ]);
        $this->session->set_userdata('tray_store_config', $this->queryParams['integration_store_id'] ?? $this->storeId ?? $this->integration['store_id']);
        try {
            if (empty($this->integration['id'])) {
                if (empty($this->queryParams['integration_store_id'])) throw new Exception('Loja não encontrada. Realize o processo novamente.');
                if ($this->model_api_integrations->create([
                    'store_id' => $this->queryParams['integration_store_id'] ?? $this->storeId ?? $this->integration['store_id'],
                    'user_id' => $this->integration['user_id'],
                    'status' => 1,
                    'description_integration' => 'Tray',
                    'integration' => 'tray',
                    'credentials' => json_encode($this->integration['credentials'])
                ])) {
                    $this->integration['id'] = $this->model_api_integrations->getInsertId();
                }
            }
            $appController = new AuthApp($this->integration, $this->model_api_integrations);
            redirect($appController->getAuthAppURL(), 'location');
        } catch (Throwable $e) {
            print_r($e->getMessage());
        }
    }

    protected function validation()
    {
        if (!$this->checkAuthRequest()) return false;
        return true;
    }

    public function auth_get()
    {
        if (!$this->validation()) {
            return;
        }
        try {
            $url = rtrim(trim($this->queryParams['api_address'] ?? Configuration::API_URL), '/');
            $response = $this->client->request('POST', "{$url}/auth", [
                'form_params' => [
                    'code' => $this->queryParams['code'] ?? '',
                    'consumer_key' => $this->configuration->getConsumerKey() ?? '',
                    'consumer_secret' => $this->configuration->getConsumerSecret() ?? ''
                ]
            ]);
            $content = json_decode($response->getBody()->getContents());
            if (!isset($content->access_token)) throw new Exception($response->getBody()->getContents());
            $this->authService->saveAuthCredentials(
                $this->integration['id'],
                (object)array_merge($this->queryParams, (array)$content, ['code' => $this->queryParams['code']])
            );
        } catch (Throwable $e) {
            print_r($e->getMessage());
        }

        if ($this->isOnIframe) {
            $this->index_get('iframe_success');
            return;
        }
        redirect(base_url("/stores/integration?store={$this->integration['store_id']}&action=modal-open"));
    }

    protected function fetchUserDataByTrayStoreId()
    {
        $integrations = $this->model_api_integrations->getIntegrationsByCredentialsFieldValue('storeId', $this->trayStoreId);
        if (!empty($integrations)) {
            $integration = current($integrations);
            $this->companyId = $integration['company_id'] ?? null;
            $this->storeId = $integration['store_id'] ?? null;
            $this->userId = $integration['user_id'] ?? null;
        }
    }

    protected function checkAuthRequest(): bool
    {
        if ($this->trayStoreId) {
            $integrations = $this->model_api_integrations->getIntegrationsByCredentialsFieldValue('storeId', $this->trayStoreId);
            if (!empty($integrations)) {
                $integration = current($integrations);
                if (
                    ($this->companyId !== 1 && $integration['company_id'] != $this->companyId) || (
                        $this->storeId > 0 && $integration['store_id'] != $this->storeId
                    )
                ) {
                    $this->response([
                        'data' => [
                            'message' => "A loja Tray de ID {$this->trayStoreId} já está configurada para a empresa {$integration['company_name']} e loja {$integration['store_name']}"
                        ]
                    ], REST_Controller::HTTP_UNAUTHORIZED);
                    return false;
                } else {
                    $this->storeId = $integration['store_id'] ?? $this->storeId;
                }
            }
        }
        if (empty($this->storeId)) {
            if (empty($this->companyId)) {
                $loginUrl = $this->loginUrl;
                die("Para concluir essa ação é necessário estar logado. Realize o <a href='{$loginUrl}'>login</a> e reinicie o processo.");
            } else {
                $store = $this->model_api_integrations->getStoreByCompanyIdAndIntegration($this->companyId, 'tray');
            }
        } else {
            $store = $this->model_stores->getStoreById($this->storeId);
        }
        $integration = $this->model_api_integrations->getIntegrationByStoreId($store['id'] ?? $this->storeId);
        if (empty($integration)) {
            $this->integration = array_merge($store ?? [], [
                'id' => 0,
                'company_id' => $store['company_id'] ?? $this->companyId,
                'store_id' => $store['id'] ?? $this->storeId,
                'store_name' => $store['name'],
                'user_id' => $this->userId ?? $store['user_id'],
                'credentials' => json_encode(['storeUrl' => $this->queryParams['url'] ?? $this->queryParams['store_host'] ?? null])
            ]);
            return true;
        } else {
            if ($integration['integration'] != 'tray') {
                $integration['integration'] = !empty($integration['description_integration']) ? $integration['description_integration'] : strtoupper($integration['integration']);
                $this->response([
                    'data' => [
                        'message' => "A loja {$integration['store_name']} já está configurada com a integração {$integration['integration']}. Solicite a remoção dessa integração para concluir a configuração com a Tray."
                    ]
                ], REST_Controller::HTTP_UNAUTHORIZED);
                return false;
            }
        }
        $integration = array_merge($store ?? [], $integration ?? []);
        if (!Model_api_integrations::isActiveIntegration($integration)) {
            $this->response([
                'data' => [
                    'message' => "A loja {$integration['store_name']} e/ou integração {$integration['description_integration']} não está ativa. Ative-a para concluir o processo."
                ]
            ], REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }

        if (isset($integration['company_id']) && ($this->companyId !== 1 && $integration['company_id'] != $this->companyId)) {
            $this->response([
                'data' => [
                    'message' => "A loja {$integration['store_name']} e/ou integração {$integration['description_integration']} não corresponde com a empresa do usuário logado."
                ]
            ], REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }

        $this->integration = $integration ?? [];
        return true;
    }

}