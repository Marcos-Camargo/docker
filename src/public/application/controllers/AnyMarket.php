<?php
/*
SW Serviços de Informática 2019

 */
require APPPATH . "controllers/BatchC/Integration/AnyMarket/Order/CreateOrder.php";
require_once APPPATH . "libraries/Attributes/Application/ApplicationAttributeService.php";
require_once APPPATH . "libraries/Attributes/Custom/CustomApplicationAttributeService.php";
require_once APPPATH . 'libraries/Integration_v2/anymarket/AnyMarketConfiguration.php';

use Firebase\JWT\JWT;

defined('BASEPATH') or exit('No direct script access allowed');

use Integration\Integration_v2\anymarket\AnyMarketConfiguration;
use \libraries\Attributes\Custom\CustomApplicationAttributeService;
use \libraries\Attributes\Application\ApplicationAttributeService;

/**
 * Class AnyMarket
 * @property Model_attributes $model_attributes
 * @property Model_api_integrations $model_api_integrations
 * @property Model_settings $model_settings
 * @property Model_users $model_users
 * @property Model_stores $model_stores
 * @property ApplicationAttributeService $applicationAttrService
 * @property CustomApplicationAttributeService $customAttrService
 *
 * @property CI_Loader $load
 * @property CI_Session $session
 */
class AnyMarket extends Admin_Controller
{

    private $configAnymarket;
    private $urlApiAnymarket;
    private $appidAnymarket;

    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: *");
        header('Origin: *');
        header("Access-Control-Allow-Methods: GET, OPTIONS, POST, GET, PUT");
        parent::__construct();

        $this->load->model('model_api_integrations');
        $this->load->model('model_settings');
        $this->load->model('model_job_schedule');
        $this->load->model('model_stores');
        $this->load->library('rest_request');
        $this->configAnymarket = new AnyMarketConfiguration();
        $this->urlApiAnymarket = $this->configAnymarket->getHost();
        if (empty($this->urlApiAnymarket)) {
            throw new Exception("\'url_anymarket\' não está definido no sistema");
        }
        $this->appidAnymarket = $this->configAnymarket->getAppId();
        if (!$this->appidAnymarket) {
            die("appID não definida para este usuario, por favor solicite a devida configuração no site da anymarket.");
        }
        $this->load->model('model_attributes');
        $this->load->model('model_users');

        $this->applicationAttrService = new ApplicationAttributeService($this->model_attributes, $this->model_settings);
        $this->customAttrService = new CustomApplicationAttributeService();
    }
	
    public function __destruct()
    {
      // deleta o cookie
      unlink($this->rest_request->cookiejar);
    }
	
    public function config()
    {
        $input = cleanArray($this->input->get());
        // dd($this->checkToken($input));
        if (!$this->checkToken($input)) {
            $this->load->view('any_market/unauthorized');
            return;
        }
        $viewData = [];
        $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        if(!$sellercenter_name){
            $sellercenter_name="Conecta Lá";
        }
        $viewData['sellercenter_name'] = $sellercenter_name;
        if (isset($input['token'])) {
            $anymarket_token = $input['token'];
            $payload = explode('.', $anymarket_token)[1];
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
            $integration = $this->model_api_integrations->getUserByOI($payload->oi);
            if (!empty($integration)) {
                $integration = $integration[0];
                $credentials = json_decode($integration['credentials'], true);
            }
            if (empty($integration)) {
                $viewData['oi'] = $payload->oi;
                $viewData['token'] = $input['token'];
                $this->load->view('any_market/login', $viewData);
                return;
            } else {
                $store = $this->model_stores->getStoreById($integration['store_id']);
                $storeEmail = !empty($store['responsible_email'] ?? '') ? $store['responsible_email'] : null;
                $user = $this->model_users->getUserData($integration['user_id']);
                $userEmail = $storeEmail ?? $this->model_users->fetchStoreManagerUser($integration['store_id'], $store['company_id'])['email'] ?? null;
                $viewData['oi'] = $payload->oi;
                $viewData['token'] = $input['token'];
                $viewData['email'] = isset($credentials['login']) ? $credentials['login'] : ($userEmail ?? $store['email']);
                $viewData['token_api'] = isset($credentials['token']) ? $credentials['token'] : $store['token_api'];
                $viewData['store'] = isset($credentials['store']) ? $credentials['store'] : $store['name'];
                $viewData['token_anymerket'] = isset($credentials['token_anymarket']) ? $credentials['token_anymarket'] : '';
                $viewData['priceFactor'] = isset($credentials['priceFactor']) ? $credentials['priceFactor'] : '1';
                $viewData['defaultDiscountValue'] = isset($credentials['defaultDiscountValue']) ? $credentials['defaultDiscountValue'] : '0';
                $viewData['defaultDiscountType'] = isset($credentials['defaultDiscountType']) ? $credentials['defaultDiscountType'] : 'PERCENT';
                $viewData['inicial_date_order'] = isset($credentials['inicial_date_order']) ? $credentials['inicial_date_order'] : date('Y-m-d\TH:i') ;
                $viewData['updateProductPriceStock'] = isset($credentials['updateProductPriceStock']) ? $credentials['updateProductPriceStock'] : true;
                $viewData['updateProductCrossdocking'] = isset($credentials['updateProductCrossdocking']) ? $credentials['updateProductCrossdocking'] : true;
                $viewData['appId'] = isset($credentials['appId']) ? $credentials['appId'] : '';
                $this->load->view('any_market/config', $viewData);
            }
            // dd($integration);

        } else {
            echo 'unauthorized access.<token>';
            return;
        }
    }
    public function configsave()
    {
        $dados = $this->postClean();

        try {
            $this->checkCredentialsLogin($dados);
        } catch (Exception $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
            $this->session->set_flashdata('data', $dados);
            redirect(base_url('AnyMarket/config')."?lang=pt_BR&token=$dados[token]");
            return;
        }

        $integrationStore = $this->model_api_integrations->getDataByStore($dados['store']);
        if ($integrationStore) {
            $integrationStore = $integrationStore[0];
            if ($integrationStore['integration'] != 'anymarket') {
                $this->session->set_flashdata('error', 'Loja já está integrada com outra plataforma');
                redirect('/anyMarket/config?lang=pt_BR&token=' . $dados['token'], 'refresh');
            }
        }

        if (!$this->checkToken($dados)) {
            $this->load->view('any_market/unauthorized');
            return;
        }
        $viewData = [];
        $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        if(!$sellercenter_name){
            $sellercenter_name="Conecta Lá";
        }
        $viewData['sellercenter_name'] = $sellercenter_name;

        $dados = $this->postClean(NULL,TRUE);
        $integration = $this->model_api_integrations->getUserByOI($dados['oi']);
        $integration = $integration[0];
        $dados['idAccount'] = $integration['user_id'] ?? $dados['idAccount'] ?? '';
        $dados['updateProductPriceStock'] = boolval($integration['updateProductPriceStock'] ?? $dados['updateProductPriceStock'] ?? true);
        $dados['updateProductCrossdocking'] = boolval($integration['updateProductCrossdocking'] ?? $dados['updateProductCrossdocking'] ?? true);
        if (!$integration) {
            $data = $this->model_api_integrations->getDataOnIntegrationAnyMarket($dados);
            if (!$data['user'] || !$data['store']) {
                $viewData['info'] = ['msg' => 'Dados de validação invalidos', 'type' => 'err'];
                $viewData['oi'] = $dados['oi'];
                $viewData['token'] = $dados['token'];
                $this->load->view('any_market/login', $viewData);
            } else {
                $newIntegration = [
                    'store_id' => $data['store']['id'],
                    'user_id' => $data['user']['id'],
                    'description_integration' => 'Integração Anymarket',
                    'credentials' => json_encode($dados),
                    'status' => '1',
                    'integration' => 'anymarket',
                    'id_anymarket_oi' => $dados['oi']
                ];
                $integration = $this->model_api_integrations->createIntegrationAnymarket($newIntegration);

                try{
                    $attributes = $this->applicationAttrService->createUpdateApplicationAttributes(
                        $this->applicationAttrService->getAttributesApplicationByModule('products_variation')
                    );
                    $customAttributes = $this->customAttrService->createUpdateAccountAttributes($attributes, [
                        'store_id' => $newIntegration['store_id'],
                        'company_id' => $data['store']['company_id']
                    ]);
                }catch (Throwable $e) {

                }
                redirect('/AnyMarket/config?token=' . $dados['token'], 'refresh');
            }
        } else {
            $credentials = json_decode($this->integration['credentials'], true);
            $dados['idAccount'] = isset($credentials['idAccount']) ? $credentials['idAccount'] : $dados['idAccount'];
            $dados['lastTimeMarkup'] = $credentials['lastTimeMarkup'] ?? date('Y-m-d H:i:s', strtotime('-7 hours'));
            $dados['updateProductPriceStock'] = isset($credentials['updateProductPriceStock']) ? $credentials['updateProductPriceStock'] : $dados['updateProductPriceStock'];
            $dados['updateProductCrossdocking'] = isset($credentials['updateProductCrossdocking']) ? $credentials['updateProductCrossdocking'] : $dados['updateProductCrossdocking'];
            $dado = ['credentials' => json_encode($dados)];
            $integration = $this->model_api_integrations->update($integration['id'], $dado);
            redirect('/anyMarket/config?token=' . $dados['token'], 'refresh');
        }
    }

    public function configsaverest()
    {
        $dados = $this->postClean(NULL,TRUE);
        $input = [];
        $input['token'] = $dados['token2'];
        if (!$this->checkToken($input)) {
            $result = [
                "sucess" => "false",
                "Message" => "Não autorizado",
            ];
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            return;
        }
        $viewData = [];
        $dados = $this->postClean(NULL,TRUE);
        $integration = $this->model_api_integrations->getUserByOI($dados['oi']);
        $integration = $integration[0];
        $dados['idAccount'] = $integration['user_id'] ?? $dados['idAccount'] ?? '';
        $dados['updateProductPriceStock'] = boolval($integration['updateProductPriceStock'] ?? $dados['updateProductPriceStock'] ?? true);
        $dados['updateProductCrossdocking'] = boolval($integration['updateProductCrossdocking'] ?? $dados['updateProductCrossdocking'] ?? true);
        if (!$integration) {
            $data = $this->model_api_integrations->getDataOnIntegrationAnyMarket($dados);
            if (!$data['user'] || !$data['store']) {
                $viewData['info'] = ['msg' => 'Dados de validação invalidos', 'type' => 'err'];
                $viewData['oi'] = $dados['oi'];
                $viewData['token'] = $dados['token'];
                $result = [
                    "sucess" => "false",
                    "Message" => "Não autorizado",
                ];
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
            } else {
                // if ($this->storeIsOfUser($data['user'], $data['store'])) {
                if (true) {
                    $newIntegration = [
                        'store_id' => $data['store']['id'],
                        'user_id' => $data['user']['id'],
                        'description_integration' => 'Integração Anymarket',
                        'credentials' => json_encode($dados),
                        'status' => '1',
                        'integration' => 'anymarket',
                        'id_anymarket_oi' => $dados['oi']
                    ];
                    $integration = $this->model_api_integrations->createIntegrationAnymarket($newIntegration);
                    try{
                        $attributes = $this->applicationAttrService->createUpdateApplicationAttributes(
                            $this->applicationAttrService->getAttributesApplicationByModule('products_variation')
                        );
                        $customAttributes = $this->customAttrService->createUpdateAccountAttributes($attributes, [
                            'store_id' => $newIntegration['store_id'],
                            'company_id' => $data['store']['company_id']
                        ]);
                    }catch (Throwable $e) {

                    }
                } else {
                    $viewData['info'] = ['msg' => 'Dados de validação invalidos', 'type' => 'err'];
                    $viewData['oi'] = $dados['oi'];
                    $viewData['token'] = $dados['token'];
                    $result = [
                        "sucess" => "false",
                        "Message" => "Não autorizado",
                    ];
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                }
            }
        } else {
            $dados['idAccount'] = isset($credentials['idAccount']) ? $credentials['idAccount'] : $dados['idAccount'];
            $dados['lastTimeMarkup'] = $credentials['lastTimeMarkup'] ?? date('Y-m-d H:i:s', strtotime('-7 hours'));
            $dados['updateProductPriceStock'] = isset($credentials['updateProductPriceStock']) ? $credentials['updateProductPriceStock'] : $dados['updateProductPriceStock'];
            $dados['updateProductCrossdocking'] = isset($credentials['updateProductCrossdocking']) ? $credentials['updateProductCrossdocking'] : $dados['updateProductCrossdocking'];
            $dado = ['credentials' => json_encode($dados)];
            $integration = $this->model_api_integrations->update($integration['id'], $dado);
            // redirect('/anyMarket/config?token=' . $dados['token'], 'refresh');
            $result = [
                "sucess" => "true",
                "Message" => "Atualização realizada com sucesso",
                // "sucess"=>"true",
            ];

            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
    }
    public function storeIsOfUser($user, $store)
    {
        return $user['company_id'] == $store['company_id'] && ($user['store_id'] == $store['id'] || $user['store_id'] == 0);
    }
    public function checkToken($input)
    {
        $anymarket_token = $input['token'];
        if (empty($anymarket_token)) {
            return false;
        }
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        if (!isset($payload->oi)) {
            return false;
        }
        return true;
    }
    public function configpricerefresh()
    {
        $dados = $this->postClean(NULL,TRUE);
        $dados['token'] = $dados['token2'];
        if (!$this->checkToken($dados)) {
            $this->load->view('any_market/unauthorized');
            return;
        }
        $anymarket_token = $dados['token'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0];
        $credentials = json_decode($integration['credentials'], true);
        $headers = array(
            "Content-Type: application/json",
            "appId: {$this->appidAnymarket}",
            "token: {$credentials['token_anymarket']}",
        );
        $url_confirm = $this->urlApiAnymarket . "delegates/forceSyncMarkup";
        $res = $this->rest_request->sendREST($url_confirm, [], 'PUT', $headers);
        if ($res['httpcode'] != 200 && (strpos($res['content'], '422') !== false)) {
            $lastTimeMarkup = new DateTime($credentials['lastTimeMarkup'] ?? date('Y-m-d H:i:s'));
            $currentTime = new DateTime('now');
            $interval = $lastTimeMarkup->diff($currentTime);
            $details = $interval->format("É necessário aguardar %H hora(s) e %i minuto(s) para iniciar uma nova execução.");
            $content = json_decode($res['content'], true);
            $content['error'] = $details;
            $res['content'] = json_encode($content);
        } else if ($res['httpcode'] == 200) {
            $credentials['lastTimeMarkup'] = date('Y-m-d H:i:s');
            $integration = $this->model_api_integrations->update($integration['id'], [
                'credentials' => json_encode($credentials)
            ]);
        }
        echo json_encode($res);
        // $this->response->setStatusCode(404)->setBody($res['content']);
    }

    public function forcesyncorders()
    {
        $dados = $this->postClean(NULL, TRUE);
        $dados['token'] = $dados['token2'];
        if (!$this->checkToken($dados)) {
            $this->load->view('any_market/unauthorized');
            return;
        }
        $anymarket_token = $dados['token'];
        $payload = explode('.', $anymarket_token)[1];
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));
        $integration = $this->model_api_integrations->getUserByOI($payload->oi);
        $integration = $integration[0] ?? [];

        $storeId = $integration['store_id'] ?? null;
        try {
            $chunkCommand = [
                "php index.php BatchC/Integration_v2/Order/CreateOrder run null {$storeId}",
                "php index.php BatchC/Integration_v2/Order/UpdateStatus run null {$storeId}",
            ];
            $shellCommands = implode(' && ', array_merge([sprintf("cd %s", FCPATH)], $chunkCommand));
            $shellCommands = sprintf("%s %s", $shellCommands, '&');
            exec($shellCommands);
            $res = ['message' => 'Iniciada a atualização de pedidos.', 'sucess' => true, 'commands' => $shellCommands];
        } catch (Throwable $e) {
            $res = ['message' => 'Não é possivel iniciar um novo envio antes de ser concluido o anterior.', 'sucess' => $created];
        }

        echo json_encode($res);
    }

    /**
     * @throws Exception
     */
    private function checkCredentialsLogin($data)
    {
        if (
            empty($data['token_in']) ||
            empty($data['store']) ||
            empty($data['login']) ||
            !$this->model_stores->checkCredentialApiStore($data['token_in'], (int)$data['store'], $data['login'])
        ) {
            throw new Exception("As credenciais informadas não conferem.");
        }
    }
}
