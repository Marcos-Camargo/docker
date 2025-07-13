<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Session $session
 * @property CI_Config $config
 * @property CI_Input $input
 * @property Firebase\JWT\JWT $jwt
 * @property Client $client
 *
 * @property Model_api_integrations $model_api_integrations
 * @property Model_integration_erps $model_integration_erps
 * @property Model_integrations $model_integrations
 */
class Configure extends Admin_Controller
{
    /**
     * @var Client Cliente of GuzzleHttp
     */
    private $client;

    /**
     * @var string Código da integração
     */
    private $integration = 'bling_v3';

    private $attempt_count = 0;

    /**
     * Instantiate a new UpdateNFe instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->not_logged_in();
        $this->load->library('JWT');
        $this->lang->load('api', 'portuguese_br');
        $this->load->model('model_api_integrations');
        $this->load->model('model_integration_erps');
        $this->load->model('model_integrations');
        $this->setClientGuzzle();
    }

    /**
     * Define a instância Client de GuzzleHttp
     */
    private function setClientGuzzle()
    {
        $this->client = new Client([
            'verify' => false,
            'allow_redirects' => true
        ]);
    }

    /**
     * Configuração da integração
     */
    public function index()
    {
        $data = cleanArray($this->input->get());

        $this->log_data('bling_v3/Configure', 'index', json_encode($data, JSON_UNESCAPED_UNICODE));

        // State não encontrado.
        if (empty($data['state'])) {
            $this->session->set_flashdata('error', $this->lang->line('api_operation_not_accepted'));
            redirect('stores/integration', 'refresh');
            return;
        }

        try {
            $data_store = $this->decodeJwt($data['state']);

            // Dados do jwt não encontrado.
            if (empty($data_store->redirect_uri) && (!is_object($data_store) || !property_exists($data_store, 'cod_store') || !property_exists($data_store, 'cod_company'))) {
                throw new Exception($this->lang->line('api_store_not_found'));
            }
        } catch (Exception $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
            redirect('stores/integration', 'refresh');
            return;
        }

        // Erro na comunicação.
        if (array_key_exists('error_description', $data)) {
            $this->session->set_flashdata('error', $data['error_description']);
            redirect('stores/integration', 'refresh');
            return;
        }

        // Code não encontrado.
        if (empty($data['code'])) {
            $this->session->set_flashdata('error', $this->lang->line('api_operation_not_accepted'));
            redirect('stores/integration', 'refresh');
            return;
        }

        if (!empty($data_store->redirect_uri)) {
            redirect($data_store->redirect_uri.'?'.implode('&', array_map(function(string $k, string $v){return "$k=$v";}, array_keys($data), array_values($data))));
        }

        $store = $data_store->cod_store;

        $code = $data['code'];

        $integration_data = $this->model_api_integrations->getIntegrationByStoreId($store);
        // Loja já tem integração.
        if ($integration_data) {
            $credentials = json_decode($integration_data['credentials']);
            if (!$credentials || empty($credentials->revoke)) {
                $this->session->set_flashdata('error', sprintf($this->lang->line('api_app_store_already_integrated'), $store, $integration_data['store_name'], $integration_data['int_description']));
                redirect("stores/integration/$store", 'refresh');
                return;
            }

        }

        try {
            // Gera os dados de acesso.
            $data_auth = $this->getAccessToken($code);
            $data_auth['created_at'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
            $data_auth['expire_at'] = date(DATETIME_INTERNATIONAL, strtotime($data_auth['created_at']) + $data_auth['expires_in']);
            $data_auth['loja_bling'] = $data_store->loja_bling;
            $data_auth['stock_bling'] = $data_store->stock_bling;
        } catch (Exception $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
            redirect("stores/integration/$store", 'refresh');
            return;
        }

        $credentials = json_encode($data_auth, JSON_UNESCAPED_UNICODE);
        $integration_configuration = $this->model_integration_erps->getByName($this->integration);

        $data_to_create = array(
            'status'                    => 1,
            'store_id'                  => $store,
            'user_id'                   => $this->session->userdata('id'),
            'credentials'               => $credentials,
            'integration'               => $integration_configuration['name'],
            'description_integration'   => $integration_configuration['description'],
            'integration_erp_id'        => $integration_configuration['id']
        );

        $this->model_api_integrations->create($data_to_create, true);
        $this->model_integrations->removeRowsOrderToIntegration($store);

        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_integration_api'));
        redirect('integrations/job_integration', 'refresh');
    }

    /**
     * @param   string      $code
     * @return  array|mixed
     * @throws  Exception
     */
    private function getAccessToken(string $code): array
    {
        $integration_configuration = $this->model_integration_erps->getByName($this->integration);
        // Não encontrado a integração com o bling v3.
        if (!$integration_configuration || empty($integration_configuration['configuration'])) {
            throw new Exception('Configuração para Bling V3 não encontrada.');
        }

        $configuration = json_decode($integration_configuration['configuration'], true);

        // Não encontrado os dados de configuração para o bling v3.
        if (!$configuration || !array_key_exists('client_id', $configuration) || !array_key_exists('client_secret', $configuration)) {
            throw new Exception('Configuração para Bling V3 não configurada.');
        }

        $authorization = base64_encode("$configuration[client_id]:$configuration[client_secret]");

        $options = array(
            'form_params' => array(
                'grant_type'    => 'authorization_code',
                'code'          => $code
            ),
            'headers' => array(
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Accept'        => '1.0',
                'Authorization' => "Basic $authorization"
            )
        );

        try {
            $request  = $this->client->request('POST', 'https://www.bling.com.br/Api/v3/oauth/token', $options);
            $response = json_decode($request->getBody()->getContents(), true);
            $this->attempt_count = 0;
        } catch (GuzzleException | BadResponseException $exception) {
            if ($exception->getCode() == 429) {
                if ($this->attempt_count == 0) {
                    sleep(10);
                    $this->attempt_count++;
                    return $this->getAccessToken($code);
                }
            }
            $this->attempt_count = 0;
            $error_message = $exception->getMessage();
            $message = method_exists($exception, 'getResponse') ? (method_exists($exception->getResponse(), 'getBody') ? $exception->getResponse()->getBody()->getContents() : $error_message) : $error_message;
            $error = json_decode($message, true);
            $this->log_data('bling_v3', 'Configure/getAccessToken', "line=".__LINE__."\nuser_id=".$this->session->userdata('id')."\n".$error_message."\n".json_encode($options, JSON_UNESCAPED_UNICODE), 'E');
            throw new Exception($error['error']['description'] ?? json_encode($message, JSON_UNESCAPED_UNICODE));
        }

        return $response;
    }

    /**
     * Decodifica a jwt
     *
     * @param   string $jwt
     * @return  object
     * @throws  Exception
     */
    private function decodeJwt(string $jwt): object
    {
        $key = $this->config->config['encryption_key']; // Key para decodificação
        $decodeJWT = $this->jwt->decode($jwt, $key, array('HS256'));

        // Verifica se ocorreu algum problema para decodificar a key
        if (is_string($decodeJWT)) {
            throw new Exception($decodeJWT);
        }

        return $decodeJWT;
    }
}