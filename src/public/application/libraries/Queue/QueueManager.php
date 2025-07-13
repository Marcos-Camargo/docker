<?php

/**
 * Classe antiga responsável por gerenciar a fila de processamento.
 *
 */
class QueueManager
{

    /**
     * @var QueueService
     */
    public static $queue;
    public static $sellercenter;

    private static $shouldBeConnected = true;

    private static function checkConnection(): void
    {

        if (is_null(self::$queue)) {
            try {

                get_instance()->load->library("queueService", 'OCI', 'queue');
                get_instance()->load->model("model_oci_queues");
                self::loadConfigurations();

                self::$queue = new queueService();
                self::$queue->initService('OCI');

            } catch (Throwable $exception) {
            }

        }

        if (!(!is_null(self::$queue))) {
            throw new Exception("Queue not connected");
        }

    }

    private static function loadConfigurations(): void
    {

        $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');
        if ($sellercenter) {
            self::$sellercenter = $sellercenter;
        }

    }

    public static function getQueuedItens($key, $channelId = null)
    {

        self::checkConnection();

        $result = self::$queue->receiveQueueMessageQueue($key, $channelId);
        if (!$result['success']) {
            error_log($result['message']);
            return [];
        }
        return $result;

    }

    public static function getQueueStatus($key)
    {

        self::checkConnection();

        return self::$queue->getOciStats($key);

    }

    public static function createQueues(array $queues): void
    {

        self::checkConnection();

        //Se chegou com uma lista de filas pra cadastrar aqui, é por que ainda não foi cadastrado
        //e nem está mapeado no banco de dados
        foreach ($queues as $queueName){

            $return = self::$queue->findOCIQueue($queueName, self::$sellercenter);

            if (isset($return['success']) && $return['success']){

                $data = [
                    'oci_queue_id' => $return['queueId'],
                    'display_name' => $queueName,
                    'url' => $return['url'],
                ];

                get_instance()->model_oci_queues->create($data);

            }

        }

    }

}