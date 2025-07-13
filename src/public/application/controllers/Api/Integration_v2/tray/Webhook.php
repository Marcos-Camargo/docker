<?php

use \Integration_v2\tray\Controllers\WebhookNotification;

require_once APPPATH . "libraries/REST_Controller.php";
require_once APPPATH . "libraries/Integration_v2/tray/Controllers/WebhookNotification.php";

/**
 * Class Webhook
 * @property CI_Loader $load
 * @property Model_webhook_notification_queue $model_webhook_notification_queue
 * @property Model_job_schedule $model_job_schedule
 * @property Model_api_integrations $model_api_integrations
 * @property WebhookNotification $webhookNotification
 */
class Webhook extends REST_Controller
{

    public function __construct($config = 'rest')
    {
        parent::__construct($config);
        ini_set('display_errors', 0);
        $this->load->model([
            'model_webhook_notification_queue',
            'model_job_schedule',
            'model_api_integrations'
        ]);
        $this->webhookNotification = new WebhookNotification(
            $this->model_webhook_notification_queue,
            $this->model_job_schedule,
            $this->model_api_integrations
        );
    }

    public function index_post()
    {
        try {
            $form = $this->_post_args ?? [];
            if (!empty($form)) {
                $this->webhookNotification->saveNotification((object)$form);
            }
        } catch (Throwable $e) {
            $this->response(null, self::HTTP_BAD_REQUEST);
            return;
        }
        $this->response(null, self::HTTP_OK);
    }
}