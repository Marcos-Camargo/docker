<?php

require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Controllers/ImportLoadFileController.php";

use Integration_v2\viavarejo_b2b\Resources\Mappers\FileNameMapper;
use Integration_v2\viavarejo_b2b\Controllers\ImportLoadFileController;

/**
 * Class ScheduleJobsFromQueuedFiles
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_job_schedule $model_job_schedule
 * @property ImportLoadFileController $importLoadFileController
 * @property Model_stores $model_stores
 */
class ScheduleJobsFromQueuedFiles extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = [
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => true,
        ];
        $this->session->set_userdata($logged_in_sess);
        $this->load->model('model_csv_to_verifications');
        $this->load->model('model_job_schedule');
        $this->load->model('model_stores');

        $this->importLoadFileController = new ImportLoadFileController(
            $this->model_csv_to_verifications,
            $this->model_job_schedule
        );
    }

    // php index.php BatchC/Integration_v2/Product/viavarejo_b2b/ScheduleJobsFromQueuedFiles run null null
    public function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            get_instance()->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }
        get_instance()->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

        $queuedFiles = $this->model_csv_to_verifications->getAllByCriteria([
            'checked' => 0,
            'final_situation' => ['wait', 'processing'],
            'module' => array_values(FileNameMapper::MAP_QUEUE_MODULE)
        ]);

        $scheduledJobsByCompany = [];
        $options = [];
        foreach ($queuedFiles as $queuedFile) {
            $store = $this->model_stores->getStoreById($queuedFile['store_id']);
            $options['company_id'] = $store['company_id'] ?? 0;
            if ($queuedFile['final_situation'] == 'processing') {
                $createdAtTime = strtotime($queuedFile['created_at']);
                if ($createdAtTime <= strtotime(date('Y-m-d H:i:s', strtotime('-36 hours')))) {
                    $this->model_csv_to_verifications->update(['final_situation' => 'error', 'checked' => 1], $queuedFile['id']);
                    continue;
                }
                $updatedAtTime = strtotime($queuedFile['update_at']);
                if ($updatedAtTime <= strtotime(date('Y-m-d H:i:s', strtotime('-12 hours')))) {
                    $this->model_csv_to_verifications->update(['final_situation' => 'wait'], $queuedFile['id']);
                    continue;
                }
                if (strtotime($updatedAtTime) >= strtotime(date('Y-m-d H:i:s', strtotime('-6 hours')))) {
                    continue;
                }
            }
            if (!isset($scheduledJobsByCompany[$options['company_id']])) {
                $scheduledJobsByCompany[$options['company_id']]['options'] = $options;
                $scheduledJobsByCompany[$options['company_id']]['queuedFiles'] = [];
            }
            array_push($scheduledJobsByCompany[$options['company_id']]['queuedFiles'], $queuedFile);
        }

        foreach ($scheduledJobsByCompany as $scheduledJob) {
            $this->importLoadFileController->createScheduleJobsFromQueuedFiles($scheduledJob['queuedFiles'], $scheduledJob['options']);
        }

        print_r($scheduledJobsByCompany);
        get_instance()->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }


}