<?php

/**
 * php index.php BatchC/Integration_v2/Product/viavarejo_b2b/Queue/QueueNotifications run {ID} {STORE_ID} {QUEUE_ID}
 */

require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/ToolsProduct.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php";

use Integration\Integration_v2\viavarejo_b2b\ToolsProduct;
use Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper;

/**
 * Class QueueNotifications
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property Model_webhook_notification_queue $notificationRepo
 * @property ToolsProduct $toolsProduct
 * @property \Integration\Integration_v2\Order_v2 $order_v2
 * @property \Integration\viavarejo_b2b\ToolsOrder $toolsOrder
 */
abstract class QueueNotifications extends BatchBackground_Controller
{
    protected const JOBS_RUNNING_PARALLEL = 3;

    protected $queueId = null;
    protected $storeId = null;
    protected $topic = null;
    protected $topicName = null;

    public $sellercenter;
    public $queue_name = null;

    private $queue;

    public function __construct()
    {
        parent::__construct();
        $this->session->set_userdata([
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => true
        ]);
        $this->load->model('model_webhook_notification_queue', 'notificationRepo');
        $this->toolsProduct = new ToolsProduct();
        $this->toolsProduct->setJob(get_class($this));
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

    public function run($id = null, $storeId = null, $queueId = null, $topic = null): bool
    {
        $this->storeId = $storeId == 'null' ? null : $storeId;
        $this->queueId = $queueId == 'null' ? null : $queueId;
        $this->topic = $this->topic ?? ($topic == 'null' ? null : $topic);
        $params = implode(' ', array_slice(func_get_args(), 1));
        try {
            $logName = __DIR__ . '/' . get_class($this) . '/' . __FUNCTION__;
            if (!$this->checkStartRun(
                $logName,
                $this->router->directory,
                get_class($this),
                $id,
                "{$params}"
            )) return false;
            $this->toolsProduct->startRun($this->storeId);
            if (!empty($this->topic)) {
                $this->topicName = $this->topic;
                $this->topic = implode('.', [
                    FlagMapper::getIntegrationNameFromFlag($this->toolsProduct->credentials->flag),
                    $this->topic
                ]);
            }
            $this->toolsProduct->setDateStartJob();

            $this->startQueueService();

            $this->processQueueRows();


        } catch (Throwable $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
        }
        $this->toolsProduct->setJob(get_class($this));
        $this->toolsProduct->saveLastRun();
        $this->gravaFimJob();
        return true;
    }

    protected function processQueueRows()
    {
        echo sprintf("[PROCESS][LINE:%s] - Iniciando consulta na fila...\n", __LINE__);
        do{

            $queueRow = $this->fetchQueueNotifications();
            if ($queueRow['id']){
                $this->processQueueRow($queueRow);
            }

        }while($queueRow && isset($queueRow['id']) && $queueRow['id']);

    }

    protected function fetchQueueNotifications(): array
    {

        $this->queue_name = mb_strtolower('job_via_callbacks_'.ENVIRONMENT.'_'.$this->sellercenter);

        $result = $this->queue->receiveQueueMessageQueue($this->queue_name, $this->topicName.'_'.$this->storeId);
        if (!$result['success']) {
            error_log($result['message']);
            return [];
        }
        return $result; // retornará a mensagem ou null se não tiver nenhuma mensagem nova

    }

    protected function processQueueRow($queueRow)
    {

        $message_id = $queueRow['id'];
        $message = json_decode($queueRow['message'], true);

        try {
            $jsonData = $message['data'] ?? '{}';
            $queueRow['data'] = json_decode($jsonData) ?? (object)[];
            echo sprintf("[PROCESS][LINE:%s] - Processando registro id %s, job: %s\n", __LINE__, $message_id, json_encode($queueRow));
            $this->queueDataHandler($queueRow);
            echo sprintf("[PROCESS][LINE:%s] - Processamento concluído id %s.\n", __LINE__, $message_id);
        } catch (Throwable $e) {
            echo sprintf("[PROCESS][LINE:%s] - Erro inesperado:  %s\n", __LINE__, $e->getMessage());
        }
        echo sprintf("[PROCESS][LINE:%s] - Limpando fila de registros %s...\n", __LINE__, $message_id);
        $result = $this->queue->deleteQueueMessageQueue($this->queue_name, $message_id);
        if ($result['success']){
            echo sprintf("[PROCESS][LINE:%s] - Limpeza concluída.\n", __LINE__);
        }else{
            echo sprintf("[PROCESS][LINE:%s] - Falha na limpeza da id %s, erro: %s\n", __LINE__, $message_id, json_encode($result));
        }
    }

    protected abstract function buildServiceProvider($params);

    protected abstract function queueDataHandler(array $queueNotification);
}