<?php
/*
Filas para enviar e receber mensagens 
*/
require_once 'system/libraries/Vendor/autoload.php';

// use Stomp\Client;
// use Stomp\Exception\StompException;
// use Stomp\Stomp;
// use Stomp\StatefulStomp;
// use Stomp\Transport\Message;

// AWS 
// composer require aws/aws-sdk-php  
// use Aws\Sqs\SqsClient; 
// use Aws\Exception\AwsException;

// OCI
// composer require hitrov/oci-api-php-request-sign 
use Hitrov\OCI\Signer;

// necessário para a fila na OCI

class QueueService
{

    private $sellercenter;
    private $service;               // define o tipo de fila OCI, AWS ou STOMP 
    private $sqs_QueueUrl = null;   // usado para o AWS SQS somente
    private $sqs_Client = null;     // usado para o AWS SQS somente
    private $con_stomp;             // usado para ao Stomp
    private $init = false;
    private $oci_queueurl = null;   // usado o OCI somente
    private $oci_compartmentId = null;  // usado o OCI somente
    private $oci_queue = null;      // usado o OCI somente

    public function __construct($service = null)
    {
        $this->instance = &get_instance();
        $this->instance->load->model('model_settings');

        $settingSellerCenter = $this->instance->model_settings->getSettingDatabyName('sellercenter');

        if ($settingSellerCenter) {
            $this->sellercenter = $settingSellerCenter['value'];
        } else {
            $this->log("não achei o sellercenter");
            die;
        }
        if (!is_null($service)) {
            $this->initService($service);
        }
    }

    public function initService($service = 'OCI')
    {
        $this->service = $service;
        switch ($this->service) {
            case 'SQS';
                break;
            case 'STOMP';
                return $this->initSTOMP();
                break;
            case 'OCI';
                $this->initOCI();
                break;
            default;
                $this->log($this->service . " não suportado");
                return false;
                break;
        }
        return true;
    }

    /**
     * @deprecated usar sendQueueMessageQueue
     * @param $queueName
     * @param $message
     * @return array
     */
    public function sendQueueMessage($queueName, $message)
    {

        switch ($this->service) {  // pronto para incluir novos serviços
            case 'SQS';
                $result = $this->sendSQSMessage($queueName, $message);
                break;
            case 'STOMP';
                $result = $this->sendSTOMPMessage($queueName, $message);
                break;
            case 'OCI';
                $result = $this->sendOCIMessage($queueName, $message);
                break;
            default;
                $result = array(
                    'success' => false,
                    'message' => $this->service . " não suportado"
                );
                break;
        }
        return $result;

    }

    public function sendQueueMessageQueue($queueName, $message, $channel = null)
    {

        switch ($this->service) {  // pronto para incluir novos serviços
            case 'SQS';
                $result = $this->sendSQSMessage($queueName, $message);
                break;
            case 'STOMP';
                $result = $this->sendSTOMPMessage($queueName, $message);
                break;
            case 'OCI';
                $result = $this->sendOCIMessageQueue($queueName, $channel, $message);
                break;
            default;
                $result = array(
                    'success' => false,
                    'message' => $this->service . " não suportado"
                );
                break;
        }
        return $result;

    }

    /**
     * @deprecated para oracle, não usar mais este, usar receiveQueueMessageQueue
     * @param $queueName
     * @param $delete
     * @return array
     */
    public function receiveQueueMessage($queueName, $delete = false)
    {

        switch ($this->service) {  // pronto para incluir novos serviços
            case 'SQS';
                $result = $this->receiveSQSMessage($queueName, $delete);
                break;
            case 'STOMP';
                $result = $this->receiveSTOMPMessage($queueName, $delete);
                break;
            case 'OCI';
                $result = $this->receiveOCIMessage($queueName, $delete);
                break;
            default;
                $result = array(
                    'success' => false,
                    'message' => $this->service . " não suportado"
                );
                break;
        }
        return $result;
    }

    public function receiveQueueMessageQueue($queueName, $channelId = null, $delete = false)
    {

        switch ($this->service) {  // pronto para incluir novos serviços
            case 'SQS';
                $result = $this->receiveSQSMessage($queueName, $delete);
                break;
            case 'STOMP';
                $result = $this->receiveSTOMPMessage($queueName, $delete);
                break;
            case 'OCI';
                $result = $this->receiveOCIMessageQueue($queueName, $channelId, $delete);
                break;
            default;
                $result = array(
                    'success' => false,
                    'message' => $this->service . " não suportado"
                );
                break;
        }
        return $result;
    }

    /**
     * @deprecated usar deleteQueueMessageQueue
     * @param $queueName
     * @param $messageId
     * @return array
     */
    public function deleteQueueMessage($queueName, $messageId)
    {

        switch ($this->service) {  // pronto para incluir novos serviços
            case 'SQS';
                $result = $this->deleteSQSMessage($queueName, $messageId);
                break;
            case 'STOMP';
                $result = $this->deleteSTOMPMessage($queueName, $messageId);
                break;
            case 'OCI';
                $result = $this->deleteOCIMessage($queueName, $messageId);
                break;
            default;
                $result = array(
                    'success' => false,
                    'message' => $this->service . " não suportado"
                );
                break;
        }
        return $result;
    }

    public function deleteQueueMessageQueue($queueName, $messageId)
    {

        switch ($this->service) {  // pronto para incluir novos serviços
            case 'SQS';
                $result = $this->deleteSQSMessage($queueName, $messageId);
                break;
            case 'STOMP';
                $result = $this->deleteSTOMPMessage($queueName, $messageId);
                break;
            case 'OCI';
                $result = $this->deleteOCIMessageQueue($queueName, $messageId);
                break;
            default;
                $result = array(
                    'success' => false,
                    'message' => $this->service . " não suportado"
                );
                break;
        }
        return $result;
    }

    private function initOCI()
    {

        $this->instance = &get_instance();
        $this->oci_userId = $this->instance->config->item('oci_user_id');
        $this->oci_fingerprint = $this->instance->config->item('oci_fingerprint');
        $this->oci_tenancyId = $this->instance->config->item('oci_tenancy_id');
        $this->oci_region = $this->instance->config->item('oci_region');
        $this->oci_key_file = $this->instance->config->item('oci_key_file');
        $this->oci_compartmentId = $this->instance->config->item('oci_compartment_id');

        $this->oci_queueurl = 'https://messaging.' . $this->oci_region . '.oci.oraclecloud.com/20210201/queues';

    }

    public function findOCIQueue($queueName, $sellerCenter, $createQueue = true, $strictName = true)
    {
        if (!is_null($this->oci_queue)) {
            if ($queueName == $this->oci_queue['queueName']) {
                return $this->oci_queue;
            }
        }

        $url = $this->oci_queueurl . '?compartmentId=' . $this->oci_compartmentId;
        $result = $this->OCIhttp($url, 'GET');

        $queuesNotStrict = [];
        if ($result['httpcode'] == 200) {
            $queues = json_decode($result['result'], true);
            foreach ($queues['items'] as $queue) {

                if ($strictName) {
                    if (($queue['displayName'] == $queueName) && ($queue['lifecycleState'] == "ACTIVE")) {
                        $this->oci_queue = array(
                            'queueName' => $queueName,
                            'success' => true,
                            'message' => "Fila encontrada",
                            'queueId' => $queue['id'],
                            'url' => $queue['messagesEndpoint']
                        );
                        return $this->oci_queue;
                    }
                } else {

                    if (strstr($queue['displayName'], $queueName) && ($queue['lifecycleState'] == "ACTIVE")) {
                        $queuesNotStrict[] = array(
                            'queueName' => $queue['displayName'],
                            'success' => true,
                            'message' => "Fila encontrada",
                            'queueId' => $queue['id'],
                            'url' => $queue['messagesEndpoint']
                        );
                    }

                }

            }
            if (!$strictName && $queuesNotStrict) {
                return $queuesNotStrict;
            }
            if ($createQueue) {
                $return = $this->createAndReturnOCIQueue($queueName, $sellerCenter);
            } else {
                $return = array(
                    'queueName' => null,
                    'success' => false,
                    'message' => "Não foi possível encontrar a fila " . $queueName,
                    'queueId' => null,
                    'url' => null
                );
            }
        } else {
            $return = array(
                'queueName' => null,
                'success' => false,
                'message' => "Erro ao tentar acessar a fila " . $result['httpcode'],
                'queueId' => null,
                'url' => null
            );
        }
        return $return;
    }

    public function createOCIQueue($queueName, $sellerCenter)
    {
        $queue = array(
            'displayName' => $queueName,
            'compartmentId' => $this->oci_compartmentId,
            'freeformTags' => array(
                'sellercenter' => $sellerCenter,
            ),
            'retentionInSeconds' => 7 * 24 * 60 * 60, // 7 dias
            'timeoutInSeconds' => 30,
            'visibilityInSeconds' => 5 * 60 // 5 minutos
        );

        return $this->OCIhttp($this->oci_queueurl, 'POST', json_encode($queue));

    }

    public function createAndReturnOCIQueue($queueName, $sellerCenter, $sleep = 60)
    {

        $result = $this->createOCIQueue($queueName, $sellerCenter);

        sleep($sleep); // necesário para a criação da fila na OCI

        if (($result['httpcode'] >= 200) && ($result['httpcode'] <= 299)) {
            return $this->findOCIQueue($queueName, $sellerCenter, false);
        } else {
            return array(
                'queueName' => $queueName,
                'success' => false,
                'message' => "Erro ao tentar criar a fila " . $result['httpcode'],
                'queueId' => null,
                'url' => null
            );
        }
    }

    /**
     * @param $queueName
     * @param $message
     * @return array
     * @deprecated não utilizar mais este método, pois ele pode duplicar filas na OCI e é muito lento!!
     * utilizar o método abaixo (sendOCIMessageQueue), pois ele busca no banco de dados a id da fila já salva e usa ela, caso não tem,
     * não vai conseguir inserir na fila
     */
    private function sendOCIMessage($queueName, $message)
    {

        $queue = $this->findOCIQueue($queueName, $this->sellercenter, true);
        if (!$queue['success']) {
            return array(
                'success' => false,
                'message' => "Não foi possível pegar o id da fila " . $queueName . ' Mensagem:' . $queue['message']
            );
        }

        if (is_array($message)) {
            $message = json_encode($message);
        }

        $params = array(
            'messages' => array(
                array(
                    'content' => $message
                )
            )
        );
        $result = $this->OCIhttp($queue['url'] . '/20210201/queues/' . $queue['queueId'] . '/messages', 'POST', json_encode($params));
        if (($result['httpcode'] >= 200) && ($result['httpcode'] <= 299)) {
            $resp = json_decode($result['result'], true);
            return array(
                'success' => true,
                'message' => "Mensagem enviada",
                'id' => $resp['messages'][0]['id']
            );
        } else {
            return array(
                'success' => false,
                'message' => "Não foi possível enviar a mensagem na fila " . $queueName . ' - ' . json_encode($result)
            );
        }

    }

    /**
     *
     * @param $queueName
     * @param $message
     * @return void
     */
    public function sendOCIMessageQueue($queueName, $channelId, $message)
    {

        //Antes de buscar na oracle, vamos verificar se já temos salvo no banco de dados
        get_instance()->load->model('model_oci_queues');
        $queue = get_instance()->model_oci_queues->findByName($queueName);

        if (!$queue) {
            return array(
                'success' => false,
                'message' => "Fila ainda não cadastrada na OCI",
            );
        }

        if (is_array($message)) {
            $message = json_encode($message);
        }

        $params = [
            'messages' => [
                [
                    'content' => $message,
                ],
            ],
        ];

        if ($channelId) {
            $params['messages'][0]['metadata'] = [
                'channelId' => (string)$channelId,
            ];
        }

        $result = $this->OCIhttp($queue['url'] . '/20210201/queues/' . $queue['oci_queue_id'] . '/messages',
            'POST',
            json_encode($params)
        );

        if (($result['httpcode'] >= 200) && ($result['httpcode'] <= 299)) {
            $resp = json_decode($result['result'], true);
            return array(
                'success' => true,
                'message' => "Mensagem enviada",
                'id' => $resp['messages'][0]['id']
            );
        } else {
            return array(
                'success' => false,
                'message' => "Não foi possível enviar a mensagem na fila " . $queueName . ' - ' . json_encode($result)
            );
        }

    }

    /**
     * @deprecated não usar mais este, pois este pode permitir que duplique as filas na OCI
     * Usar: receiveOCIMessageQueue
     * @param $queueName
     * @param $delete
     * @return array
     */
    private function receiveOCIMessage($queueName, $delete = false)
    {

        $queue = $this->findOCIQueue($queueName, $this->sellercenter, true);
        if (!$queue['success']) {
            return array(
                'success' => false,
                'message' => "Não foi possível pegar o id da fila " . $queueName . ' Mensagem:' . $queue['message'],
                'id' => null,
            );
        }

        $result = $this->OCIhttp($queue['url'] . '/20210201/queues/' . $queue['queueId'] . '/messages', 'GET');
        if (($result['httpcode'] >= 200) && ($result['httpcode'] <= 299)) {
            $resp = json_decode($result['result'], true);
            if (empty($resp['messages'])) { // fila vazia
                $return = array(
                    'success' => true,
                    'message' => null,
                    'id' => null,
                );
            } else {
                $return = array(
                    'success' => true,
                    'message' => $resp['messages'][0]['content'],
                    'id' => $resp['messages'][0]['receipt'],
                );
                if ($delete) {
                    $result = $this->deleteOCIMessage($queueName, $return['id']);
                    if (!$result['success']) {
                        $return = $result;
                    }
                    $return['id'] = null; // deletou, o ID poder ser null
                }
            }
        } else {
            $return = array(
                'success' => false,
                'message' => "Não foi possível receber a mensagem na fila " . $queueName . ' httpcode: ' . $result['httpcode'] . ' response:' . $result['result'],
                'id' => null,
            );
        }
        return $return;

    }

    private function receiveOCIMessageQueue($queueName, $channelId = null, $delete = false)
    {

        //Antes de buscar na oracle, vamos verificar se já temos salvo no banco de dados
        get_instance()->load->model('model_oci_queues');
        $queue = get_instance()->model_oci_queues->findByName($queueName);

        if (!$queue) {
            return array(
                'success' => false,
                'message' => "Fila ainda não cadastrada na OCI",
            );
        }

        $url = $queue['url'] . '/20210201/queues/' . $queue['oci_queue_id'] . '/messages';
        if ($channelId){
            $url.= '?channelFilter='.$channelId;
        }

        $result = $this->OCIhttp($url, 'GET');
        if (($result['httpcode'] >= 200) && ($result['httpcode'] <= 299)) {
            $resp = json_decode($result['result'], true);
            if (empty($resp['messages'])) { // fila vazia
                $return = array(
                    'success' => true,
                    'message' => null,
                    'id' => null,
                );
            } else {
                $return = array(
                    'success' => true,
                    'message' => $resp['messages'][0]['content'],
                    'id' => $resp['messages'][0]['receipt'],
                );
                if ($delete) {
                    $result = $this->deleteOCIMessageQueue($queueName, $return['id']);
                    if (!$result['success']) {
                        $return = $result;
                    }
                    $return['id'] = null; // deletou, o ID poder ser null
                }
            }
        } else {
            $return = array(
                'success' => false,
                'message' => "Não foi possível receber a mensagem na fila " . $queueName . ' httpcode: ' . $result['httpcode'] . ' response:' . $result['result'],
                'id' => null,
            );
        }
        return $return;

    }

    public function getOciStats($queueName)
    {

        $queueNames = $this->findOCIQueue($queueName, $this->sellercenter, false, false);
        if (!$queueNames || (isset($queueNames['success']) && !$queueNames['success'])) {
            return $queueNames;
        }

        $return = [];
        foreach ($queueNames as $queue) {
            $result = $this->OCIhttp($queue['url'] . '/20210201/queues/' . $queue['queueId'] . '/stats', 'GET');
            $return[] = [
                'queue' => $queue,
                'status' => json_decode($result['result'], true),
            ];
        }

        return $return;

    }

    /**
     * @deprecated não usar este, usar o novo abaixo, pois este pode permitir que duplique filas
     * @param $queueName
     * @param $receipt
     * @return array
     */
    private function deleteOCIMessage($queueName, $receipt)
    {
        $queue = $this->findOCIQueue($queueName, $this->sellercenter, true);
        if (!$queue['success']) {
            return array(
                'success' => false,
                'message' => "Não foi possível pegar o id da fila " . $queueName . ' Mensagem:' . $queue['message']
            );
        }
        $result = $this->OCIhttp($queue['url'] . '/20210201/queues/' . $queue['queueId'] . '/messages/' . $receipt, 'DELETE');
        if (($result['httpcode'] >= 200) && ($result['httpcode'] <= 299)) {
            return array(
                'success' => true,
                'message' => $result
            );
        } else {
            return array(
                'success' => false,
                'message' => $result
            );
        }
    }

    private function deleteOCIMessageQueue($queueName, $receipt)
    {

        //Antes de buscar na oracle, vamos verificar se já temos salvo no banco de dados
        get_instance()->load->model('model_oci_queues');
        $queue = get_instance()->model_oci_queues->findByName($queueName);

        if (!$queue) {
            return array(
                'success' => false,
                'message' => "Fila ainda não cadastrada na OCI",
            );
        }

        $result = $this->OCIhttp($queue['url'] . '/20210201/queues/' . $queue['oci_queue_id'] . '/messages/' . $receipt, 'DELETE');
        if (($result['httpcode'] >= 200) && ($result['httpcode'] <= 299)) {
            return array(
                'success' => true,
                'message' => $result
            );
        } else {
            return array(
                'success' => false,
                'message' => $result
            );
        }
    }


    private function initSTOMP()
    {

        $con = new Client('ssl://cell-1.queue.messaging.us-ashburn-1.oci.oraclecloud.com:61613');
        $con->setLogin('service_queue_hmlg', base64_encode('#wbhD53{8+>5X+4FFTId'));

        try {
            $this->con_stomp = new StatefulStomp($con);
        } catch (StompException $e) {
            $this->log($e->getMessage());
            return false;
        }
        return true;
    }

    private function sendSTOMPMessage($queueName, $message)
    {
        try {
            $a = $this->con_stomp->send('/queue/' . $queueName, new Message($message));
        } catch (StompException $e) {
            return array(
                'success' => false,
                'message' => "Não foi possível enviar mensagem para " . $queueName . " Mensagem: " . $e->getMessage()
            );
        }
        return array(
            'success' => true,
            'message' => null
        );
    }

    private function receiveSTOMPMessage($queueName, $delete = false)
    {
        try {
            $this->con_stomp->subscribe('/queue/' . $queueName, null, 'client');
            // receive a message from the queue
            $msg = $this->con_stomp->read();

            // do what you want with the message
            if ($msg != null) {
                $return = array(
                    'success' => true,
                    'message' => $msg->body,
                    'msg_id' => $msg
                );
            } else {
                return array(
                    'success' => false,
                    'message' => "Mensagem NULL",
                    'msg_id' => null,
                );
            }
            $this->con_stomp->unsubscribe();
        } catch (StompException $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'msg_id' => null,
            );
        }
        if ($delete) {
            $this->con_stomp->deleteSTOMPMessage($msg);
        }
        return $return;

    }

    private function deleteSTOMPMessage($queueName, $msg_id)
    {

        try {
            $this->con_stomp->subscribe('/queue/' . $queueName, null, 'client-individual');
            $this->con_stom->ack($msg_id);
            $this->con_stomp->unsubscribe();
        } catch (StompException $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
        return array(
            'success' => true,
            'message' => null
        );

    }

    private function receiveSQSMessage($queueName, $delete = false)
    {

        $queueUrl = $this->listSQSQueueUrl($queueName);
        if (!$queueUrl) {
            return array(
                'success' => false,
                'message' => "Não foi possível pegar a URL da fila SQS " . $queueName,
                'id' => null
            );
        }
        $client = $this->getSQSQueueClient();
        if (!$client) {
            return array(
                'success' => false,
                'message' => "Não foi possível conecta na fila SQS " . $queueName,
                'id' => null
            );
        }

        try {
            $result = $client->receiveMessage(array(
                'AttributeNames' => ['All'],
                'MaxNumberOfMessages' => 1,
                'MessageAttributeNames' => ['All'],
                'QueueUrl' => $queueUrl,
                'WaitTimeSeconds' => 20,  // long polling, vai esperar por 20 segundos por 1 mensagem
            ));
            if (!empty($result->get('Messages'))) {
                if ($delete) {
                    $this->deleteSQSMessage($queueName, $result->get('Messages')[0]['ReceiptHandle']);
                }
                $return = array(
                    'success' => true,
                    'message' => $result->get('Messages')[0],
                    'id' => $result->get('Messages')[0]['ReceiptHandle'],
                );
            } else {
                // fila vazia 
                $return = array(
                    'success' => true,
                    'message' => null,
                    'id' => null
                );
            }
        } catch (AwsException $e) {
            // output error message if fails
            // error_log($e->getMessage());
            $return = array(
                'success' => false,
                'message' => $e->getAwsErrorMessage(),
                'id' => null
            );
        }

        return $return;
    }

    private function deleteSQSMessage($queueName, $message_id)
    {
        $queueUrl = $this->listSQSQueueUrl($queueName);
        if (!$queueUrl) {
            return array(
                'success' => false,
                'message' => "Não foi possível pegar a URL da fila " . $queueName
            );
        }
        $client = $this->getSQSQueueClient();
        if (!$client) {
            return array(
                'success' => false,
                'message' => "Não foi possível conectar na fila SQS " . $queueName
            );
        }
        try {
            $result = $client->deleteMessage([
                'QueueUrl' => $queueUrl,
                'ReceiptHandle' => $message_id
            ]);
            return array(
                'success' => true,
                'message' => $result
            );
        } catch (AwsException $e) {
            return array(
                'success' => false,
                'message' => $e->getAwsErrorMessage()
            );
        }

    }

    private function sendSQSMessage($queueName, $message)
    {

        $queueUrl = $this->listSQSQueueUrl($queueName);
        if (!$queueUrl) {
            return array(
                'success' => false,
                'message' => "Não foi possível pegar a URL da fila " . $queueName
            );
        }
        $client = $this->getSQSQueueClient();
        if (!$client) {
            return array(
                'success' => false,
                'message' => "Não foi possível conecta na fila SQS " . $queueName
            );
        }

        if (is_array($message)) {
            $message = json_encode($message);
        }
        $params = [
            'DelaySeconds' => 0,
            'MessageBody' => $message,
            'QueueUrl' => $queueUrl
        ];
        try {
            $result = $client->sendMessage($params);
            return array(
                'success' => true,
                'message' => $result
            );
        } catch (AwsException $e) {
            return array(
                'success' => false,
                'message' => $e->getAwsErrorMessage()
            );
        }
    }

    private function getSQSQueueClient()
    {

        if (!is_null($this->sqs_Client)) {
            return $this->sqs_Client;
        }
        $this->instance = &get_instance();
        $access_key_id = $this->instance->config->item('sqs_access_key_id');
        $access_key_secret = $this->instance->config->item('sqs_access_key_secret');

        $credentials = new Aws\Credentials\Credentials($access_key_id, $access_key_secret);
        $this->sqs_Client = new SqsClient([
            'profile' => 'default',
            'region' => 'us-east-1',
            'version' => '2012-11-05',
            //	'credentials'   => $credentials
        ]);
        return $this->sqs_Client;
    }

    private function listSQSQueueUrl($queueName)
    {
        if (!is_null($this->sqs_QueueUrl)) {
            return $this->sqs_QueueUrl;
        }
        $client = $this->getSQSQueueClient();
        if (!$client) {
            return false;
        }
        try {
            $result = $client->getQueueUrl([
                'QueueName' => $queueName
            ]);
            $this->sqs_QueueUrl = $result['QueueUrl'];
            $resposta = $this->sqs_QueueUrl;
        } catch (AwsException $e) {
            // output error message if fails
            if ($e->getAwsErrorMessage() == 'The specified queue does not exist for this wsdl version.') {
                $this->log("A fila $queueName não existe");
                $resposta = $this->createSQSQueue($queueName, $this->sellercenter);
            } else {
                error_log($e->getMessage());
                $resposta = false;
            }
        }
        return $resposta;
    }

    private function createSQSQueue($queueName, $sellerCenter)
    {

        $client = $this->getSQSQueueClient();
        if (!$client) {
            return false;
        }
        try {
            $result = $client->createQueue(array(
                'QueueName' => $queueName,
                'Attributes' => array(
                    'DelaySeconds' => 0,
                ),
                'tags' => array(
                    'Sellercenter' => $sellerCenter,
                )
            ));
            return $result['QueueUrl'];
        } catch (AwsException $e) {
            // output error message if fails
            error_log($e->getMessage());
            return false;
        }
    }

    private function initSigner()
    {
        // SENTRY ID: 416
        // Ensure OCI properties are initialized before creating the Signer object.
        $this->initOCI();
        return new Signer(
            $this->oci_tenancyId,
            $this->oci_userId,
            $this->oci_fingerprint,
            $this->oci_key_file
        );

    }

    private function log($msg)
    {
        echo $msg . "\n";
    }

    private function OCIhttp($url, $method = 'GET', $data = null, $cnt429 = 0)
    {

        $signer = $this->initSigner(); // infelizmente 
        $header = $signer->getHeaders($url, $method, $data, 'application/json');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $result = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($errno = curl_errno($ch)) {
            $error_message = curl_strerror($errno);
            $this->log("cURL error ({$errno}):\n {$error_message}");
        }
        curl_close($ch);

        if ($responseCode == 429) {
            $this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em 10 segundos.");
            sleep(10);
            if ($cnt429 >= 2) {
                $this->log("3 requisições já enviadas httpcode=429.Desistindo e mantendo na fila.");
                die;
            }
            $cnt429++;
            return $this->OCIhttp($url, $method, $data, $cnt429);
        }
        if ($responseCode == 504) {
            $this->log("Deu Timeout httpcode=504. Nova tentativa em 60 segundos.");
            sleep(60);
            return $this->OCIhttp($url, $method, $data, 0);
        }
        if ($responseCode == 503) {
            $this->log("OCI com problemas httpcode=503. Nova tentativa em 60 segundos.");
            sleep(60);
            return $this->OCIhttp($url, $method, $data, 0);
        }

        return array(
            'result' => $result,
            'httpcode' => $responseCode,
            'url' => $url,
            'method' => $method,
            'data' => $data,
            'header' => $header,
            'cnt429' => $cnt429
        );
    }

}

?>