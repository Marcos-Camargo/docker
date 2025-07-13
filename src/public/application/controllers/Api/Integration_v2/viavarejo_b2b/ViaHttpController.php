<?php

use Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper;

require_once APPPATH . "libraries/REST_Controller.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php";

/**
 * Class BaseHttpController
 * @package Api\Integration_v2\viavarejo_b2b
 * @property \Model_api_integrations $model_api_integrations
 * @property \Model_webhook_notification_queue $notificationRepo
 * @property \Model_job_schedule $jobScheduleRepo
 * @property CI_Loader $load
 * @property Model_settings $model_settings
 */
abstract class ViaHttpController extends \REST_Controller
{
    protected $companyId;
    protected $storeId = null;
    protected $integrationId = null;

    protected $flagId;
    protected $flagName;
    protected $campaignId;

    protected $flagMapper;

    protected $requestHeader;
    protected $requestQuery;
    protected $requestBody;

    protected $rawRequestContent;

    private $queue;
    private $sellercenter;

    public function __construct($config = 'rest')
    {
        ini_set('display_errors', 0);
        header('Content-Type: application/json');
        parent::__construct($config);
        $this->requestHeader = $this->_head_args;
        $this->requestQuery = $this->_query_args;
        $this->requestBody = $this->request->body;
        $this->flagMapper = new FlagMapper();
        $this->load->model('model_api_integrations');
        $this->load->model('model_webhook_notification_queue', 'notificationRepo');
        $this->load->model('model_job_schedule', 'jobScheduleRepo');

        $this->flagName = strtolower($this->requestBody['Bandeira']);
        $this->flagId = FlagMapper::getFlagIdByName($this->flagName);
        if (!$this->authRequest()) {
            die($this->output->final_output);
        }
        if ($this->callAsyncRequest()) {
            $this->setResponse(true);
            die($this->output->final_output);
        }
        $this->rawRequestContent = $this->requestBody['Message']['Content'] ?? '{}';
        $this->rawRequestContent = json_decode($this->rawRequestContent);

        $this->startQueueService();

    }

    private function startQueueService()
    {

        if ($this->queue){
            return;
        }

        $this->load->library("queueService");
        $this->load->model('model_settings');

        $this->queue = new queueService();
        $this->queue->initService('OCI');

        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

        if ($settingSellerCenter) {
            $this->sellercenter = $settingSellerCenter['value'];
        }

    }

    private function authRequest()
    {
        $token = $this->requestHeader['Authorization'] ?? $this->requestHeader['authorization'] ?? '';
        $token = explode(' ', $token);
        $token = $token[1] ?? $token[0];
        $integrations = $this->model_api_integrations->getIntegrationsByCredentialsFieldValue('webhookAuthToken', $token);
        foreach ($integrations ?? [] as $integration) {
            $this->companyId = $integration['company_id'];
            $credentials = json_decode($integration['credentials'], true);
            if (strcasecmp($credentials['flag'], $this->flagName) === 0) {
                $this->storeId = $integration['store_id'];
                $this->integrationId = $integration['id'];
                $this->flagId = FlagMapper::getFlagIdByName($this->flagName);
                $this->campaignId = $credentials['campaign'] ?? null;
            }
        }
        if (!$this->storeId) {
            $this->setResponse(false, \REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }

        try {
            $this->buildToolsClass();
        } catch (Throwable $e) {
            $this->setResponse(false, \REST_Controller::HTTP_UNPROCESSABLE_ENTITY, [$e->getMessage()]);
            return false;
        }
        return true;
    }

    public function index_post()
    {
        try {
            $this->handlePostRequest();
            $this->setResponse(true);
        } catch (Throwable $e) {
            $message = "{$e->getMessage()}:{$e->getLine()}";
            $this->setResponse(false, REST_Controller::HTTP_BAD_REQUEST, [$message]);
        }
    }

    protected abstract function buildToolsClass();

    protected abstract function handlePostRequest();

    protected function setResponse(bool $isValid, $httpCode = \REST_Controller::HTTP_OK, array $messages = [])
    {
        $messages = array_map(function ($message) use ($isValid, $httpCode) {
            return [
                'Code' => $message['code'] ?? $httpCode ?? 0,
                'Content' => $message['content'] ?? $message,
                'Type' => $message['type'] ?? (!$isValid ? 'ERROR' : 'SUCCESS'),
            ];
        }, $messages);
        $this->response(
            [
                'IsValid' => $isValid,
                'StatusCode' => $httpCode,
                'Messages' => $messages
            ], $httpCode);
    }

    protected function callAsyncRequest()
    {

        $this->startQueueService();

        $data = [
            'company_id' => $this->companyId,
            'store_id' => $this->storeId,
            'integration_id' => $this->integrationId,
            'origin' => strtolower("viavarejo_b2b_{$this->flagName}"),
            'topic' => strtolower("viavarejo_b2b_{$this->flagName}." . get_class($this)),
            'scope_id' => 0,
        ];

        $this->rawRequestContent = $this->requestBody['Message']['Content'] ?? '{}';
        $this->rawRequestContent = json_decode($this->rawRequestContent);
        if (empty($this->rawRequestContent)) return true;

        $topic = get_class($this);

        $data['data'] = json_encode([
            'origin' => [
                'companyId' => $this->companyId,
                'storeId' => $this->storeId,
                'integrationId' => $this->integrationId,
                'flagId' => $this->flagId,
                'flagName' => $this->flagName,
                'campaignId' => $this->campaignId,
                'topic' => $topic,
            ],
            'content' => $this->rawRequestContent
        ]);

        $queueName = 'job_via_callbacks_'.ENVIRONMENT.'_'.$this->sellercenter;
        $result = $this->queue->sendOCIMessageQueue($queueName, mb_strtolower($topic).'_'.$this->storeId, $data);

        if (!$result['success']) {
            $this->log_data('batch',__CLASS__.'/'.__FUNCTION__,json_encode(['queue_name' => $queueName, 'in' => $data, 'out' => $result], JSON_UNESCAPED_UNICODE),"W");
            error_log($result['message']);
            return false;
        }
        return true;

    }
}