<?php

/**
 * PROCESSAR FILA DE PRODUTOS
 * php index.php BatchC/Integration_v2/Product/tray/Queue/ProductQueueHandler run {ID} {STORE_ID} product
 *
 * PROCESSAR FILA DE PEDIDOS
 * php index.php BatchC/Integration_v2/Product/tray/Queue/OrderQueueHandler run {ID} {STORE_ID} order
 */

require_once APPPATH . "libraries/Integration_v2/tray/Resources/Notification.php";

use \Integration_v2\tray\Resources\Notification;

/**
 * Class BaseQueueHandler
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property Model_webhook_notification_queue $notificationRepo
 */
abstract class BaseQueueHandler extends BatchBackground_Controller
{

    protected $storeId;

    protected $scope;

    /**
     * @var \Integration\Integration_v2
     */
    protected $toolsProvider;

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
    }

    public function run($id = null, int $storeId = null, string $scope = null, int $timestamp = null): bool
    {
        $this->storeId = $storeId;
        $this->scope = $scope;

        $logName = __DIR__ . '/' . get_class($this) . '/' . __FUNCTION__;
        try {
            try {
                if (!$this->checkStartRun(
                    $logName,
                    $this->router->directory,
                    get_class($this),
                    $id,
                    "{$this->storeId} {$this->scope} {$timestamp}"
                )) return false;

                $this->getProvider()->setDateStartJob();
                $this->getProvider()->setUniqueId(time() . ":{$this->storeId}");
                $this->getProvider()->setLastRun();
            } catch (InvalidArgumentException $exception) {
                $this->getProvider()->log_integration(
                    "Erro para executar a integração",
                    "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                    "E"
                );
                throw new InvalidArgumentException($exception->getMessage());
            }
            $this->processQueueRows();
            $this->getProvider()->saveLastRun(true);

        } catch (Throwable $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
            $this->log_data('batch', $logName, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
        }
        $this->log_data('batch', $logName, 'finish');
        $this->gravaFimJob();

        return true;
    }

    protected function processQueueRows()
    {
        echo sprintf("[PROCESS][LINE:%s] - Iniciando consulta na fila...\n", __LINE__);
        $queueRows = $this->fetchQueueNotifications();
        foreach ($queueRows as $queueRow) {
            $this->getProvider()->setUniqueId("{$queueRow['scope_id']}");
            $this->processQueueRow($queueRow);
        }
    }

    protected function fetchQueueNotifications(): array
    {
        return $this->notificationRepo->findAllByCriteria([
            'store_id' => $this->storeId,
            'origin' => Notification::ORIGIN,
            'topic' => Notification::getEnabledTopicsByScope($this->scope),
            'status' => Notification::STATUS_NEW,
        ], ['scope_id'], ['updated_at' => 'ASC']);
    }

    protected function processQueueRow($queueRow)
    {
        $groupedIds = $queueRow['grouped_ids'] ?? $queueRow['id'] ?? '';
        $groupedIds = explode(',', $groupedIds);
        try {
            echo sprintf("[PROCESS][LINE:%s] - Alterando status dos registros %s...\n", __LINE__, implode(',', $groupedIds));
            if (!empty($groupedIds)) $this->notificationRepo->save($groupedIds, [
                'status' => Notification::STATUS_PROCESSING
            ]);
            $jsonData = $queueRow['data'] ?? '{}';
            $queueRow['data'] = json_decode($jsonData) ?? (object)[];
            echo sprintf("[PROCESS][LINE:%s] - Processando registros %s...\n", __LINE__, implode(',', $groupedIds));
            $this->queueDataHandler($queueRow);
            echo sprintf("[PROCESS][LINE:%s] - Processamento concluído.\n", __LINE__, implode(',', $groupedIds));
        } catch (Throwable $e) {
            echo sprintf("[PROCESS][LINE:%s] - Erro inesperado:  %s\n", __LINE__, $e->getMessage());
        }
        echo sprintf("[PROCESS][LINE:%s] - Limpando fila de registros %s...\n", __LINE__, implode(',', $groupedIds));
        if (!empty($groupedIds)) $this->notificationRepo->remove($groupedIds);
        echo sprintf("[PROCESS][LINE:%s] - Limpeza concluída.\n", __LINE__, implode(',', $groupedIds));
    }

    protected abstract function getProvider(): \Integration\Integration_v2;

    protected abstract function queueDataHandler(array $queueNotification);

}