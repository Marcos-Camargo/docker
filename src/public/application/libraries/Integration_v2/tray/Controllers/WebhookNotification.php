<?php

namespace Integration_v2\tray\Controllers;

require_once APPPATH . "libraries/Integration_v2/tray/Resources/Notification.php";

use Integration_v2\tray\Resources\Notification;

/**
 * Class WebhookNotification
 * @package Integration_v2\tray\Controllers
 * @property \Model_webhook_notification_queue $notificationRepo
 * @property \Model_job_schedule $jobScheduleRepo
 * @property \Model_api_integrations $apiIntegrationRepo
 */
class WebhookNotification
{
    public function __construct(
        \Model_webhook_notification_queue $notificationRepo,
        \Model_job_schedule $jobScheduleRepo,
        \Model_api_integrations $apiIntegrationRepo
    )
    {
        $this->notificationRepo = $notificationRepo;
        $this->jobScheduleRepo = $jobScheduleRepo;
        $this->apiIntegrationRepo = $apiIntegrationRepo;
    }

    public function saveNotification(object $formData)
    {
        if (!Notification::isEnabledScopeAction($formData->scope_name ?? '', $formData->act ?? '')) return;

        $storeData = $this->apiIntegrationRepo->getIntegrationsByCredentialsFieldValue('storeId', $formData->seller_id ?? 0)[0] ?? [];
        if (!\Model_api_integrations::isActiveIntegration($storeData)) return;

        $data = [
            'company_id' => $storeData['company_id'],
            'store_id' => $storeData['store_id'],
            'integration_id' => $storeData['integration_id'],
            'origin' => Notification::ORIGIN,
            'topic' => Notification::retrieveQueueTopic($formData->scope_name, $formData->act),
            'scope_id' => "{$formData->scope_id}",
        ];

        $hasNotification = $this->notificationRepo->find($data);
        $data['data'] = json_encode($formData);

        if ($this->notificationRepo->save($hasNotification['id'] ?? 0, $data)) {
            $this->createUpdateJobSchedule(array_merge($data, ['scope' => $formData->scope_name]));
        }
    }

    public function createUpdateJobSchedule($params)
    {
        $jobConfiguration = Notification::getJobConfiguration($params['scope'] ?? null);

        $roundUnMinute = ((int)date('i')) % 10 >= 5 ? 10 : 5;
        $roundDecMinute = (int)(((int)date('i')) / 10);
        $mStart = $roundUnMinute == 5 ? "{$roundDecMinute}0" : "{$roundDecMinute}5";
        $groupDate = strtotime(date("Y-m-d H:{$mStart}:00"));

        $data = [
            'module_path' => $jobConfiguration['class'],
            'module_method' => 'run',
            'params' => "{$params['store_id']} {$jobConfiguration['scope']} {$groupDate}",
            'status' => 0,
            'finished' => 0,
            'date_start' => date('Y-m-d H:i:s', strtotime("+{$jobConfiguration['interval']} minutes")),
            'date_end' => null,
            'server_id' => rand(1000000000, 9999999999)
        ];
        $checkSchedule = $this->jobScheduleRepo->find([
            'module_path' => $data['module_path'],
            'params' => $data['params'],
            'finished' => $data['finished'],
        ]);
        $id = $checkSchedule['id'] ?? 0;
        if (!empty($id)) return;
        $this->jobScheduleRepo->create($data);
    }

}