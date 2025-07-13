<?php
/**
 * Class UpdateStatus
 *
 * php index.php BatchC/Integration_v2/Order/UpdateStatus run {ID} {Store}
 *
 */

require APPPATH . "libraries/Integration_v2/Order_v2.php";

use GuzzleHttp\Utils;
use Integration\Integration_v2\Order_v2;

class UpdateStatus extends BatchBackground_Controller
{
    /**
     * @var Order_v2
     */
    private $order_v2;

    /**
     * @var string Nome do estado para geração de logs.
     */
    public $nameStatusUpdated = null;

    /**
     * Instantiate a new CreateProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->order_v2 = new Order_v2();

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->order_v2->setJob(__CLASS__);
    }

    /**
     * Método responsável pelo "start" da aplicação.
     *
     * @param  string|int|null  $id         Código do job (job_schedule.id).
     * @param  int|null         $store      Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params).
     * @param  int|null         $orderId    Código do pedido que irá fazer a ação.
     * @return bool                         Estado da execução.
     */
    public function run($id = null, int $store = null, int $orderId = null): bool
    {
        $log_name = $this->order_v2->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        if (!$this->checkStartRun(
            $log_name,
            $this->router->directory,
            __CLASS__,
            $id,
            $store
        )) {
            echo "[ERRO][LINE:" . __LINE__ . "] Falha na validação em checkStartRun\n";
            return false;
        }

        // realiza algumas validações iniciais antes de iniciar a rotina
        try {
            $date = dateNow();
            echo "Iniciado em ".$date->format('Y/m/d H:i:s')."\n";
            $this->order_v2->startRun($store);
        } catch (InvalidArgumentException $exception) {
            $message = $exception->getMessage();
            echo "[ERRO][LINE:".__LINE__."] $message\n";
            $this->order_v2->log_integration(
                "Erro para executar a integração",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>$message</p>",
                "E"
            );
            $this->gravaFimJob();
            return true;
        }

        // Recupera pedido para atualização
        try {
            $this->order_v2->setToolsOrder();
            // Uma api interna chamará esse metodo para fazer alguma ação isolada
            $this->updateOrders($orderId);
            $this->updateOrdersFromIntegration($orderId);
        } catch (Exception $exception) {
            echo "[ERRO][LINE:".__LINE__."][".__FUNCTION__."] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
            $this->gravaFimJob();
            return false;
        }

        // Grava a última execução
        $this->order_v2->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
            
        return true;
    }

    /**
     * Atualização dos pedidos já integrados.
     *
     * @param   int|null    $orderIdCheck   Código do pedido. Só será efetuado a atualização para esse pedido.
     * @throws  Exception
     */
    public function updateOrders(int $orderIdCheck = null)
    {
        $orderAlreadyRead = array();
        $last_queue_id = 0;

        while (true) {
            // consulta a lista de produtos
            try {
                $orders = $this->order_v2->getNewOrderToIntegration(false, $last_queue_id);
            } catch (InvalidArgumentException $exception) {
                echo "[PROCESS][LINE:" . __LINE__ . "] Não encontrou mais resultados a partir do queue_id: $last_queue_id.\n";
                break;
            }

            foreach ($orders->result as $orderIntegration) {
                if (!property_exists($orderIntegration, 'queue_id') || is_null($orderIntegration->queue_id)) {
                    echo "Arquivo desatualizado\n";
                    break 2;
                }
                $last_queue_id = $orderIntegration->queue_id;
                try {
                    $this->order_v2->setNameStatusUpdated();

                    if ($orderIdCheck !== null) {
                        if ($orderIntegration->order_code != $orderIdCheck) {
                            echo "[PROCESS][LINE:" . __LINE__ . "] Pedido ($orderIntegration->order_code) não será lido. Foi enviado para ler apenas o pedido $orderIdCheck.\n";
                            continue;
                        }
                    }

                    $this->order_v2->toolsOrder->orderId  = $orderIntegration->order_code;
                    $this->order_v2->setUniqueId($this->order_v2->toolsOrder->orderId);
                    $paidStatus = $orderIntegration->status->code;

                    $idIntegration = $this->order_v2->getOrderIdIntegration($this->order_v2->toolsOrder->orderId);
                    if ($idIntegration === null) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Pedido ({$this->order_v2->toolsOrder->orderId}) ainda não integrado\n";
                        continue;
                    }

                    $this->order_v2->toolsOrder->orderIdIntegration = $idIntegration;

                    try {
                        $order = $this->order_v2->getOrder($this->order_v2->toolsOrder->orderId);
                    } catch (InvalidArgumentException $exception) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Não encontrou dados para o pedido ({$this->order_v2->toolsOrder->orderId}).\n";
                        continue;
                    }

                    // Faz um de-para com os status
                    $status = $this->order_v2->getStatusIntegration($paidStatus);

                    // verifica se o pedido está correto para continuar
                    $checkOrder = $this->order_v2->checkDataOrderToIntegration($order, false);
                    if ($checkOrder === 'cancel') {
                        $status = 'cancel';
                    }

                    if ($status !== null) {
                        // pedido já lido, será lido novamente na próxima execução da batch
                        // Isso acontece, pois, se o pedido sofreu alteração, precisará enviar esses dados ao marketplace para ler os próximos
                        if (in_array($this->order_v2->toolsOrder->orderId, $orderAlreadyRead)) {
                            continue;
                        }
                        $orderAlreadyRead[] = $this->order_v2->toolsOrder->orderId;
                    }

                    switch ($status) {
                        // Aguardar pagamento. Nesse caso iremmos apenas consultar se foi cancelado.
                        case 'no_paid':
                            $actionStatus = $this->order_v2->getCancelIntegration($order);
                            break;
                        // Aguardar pedido ser faturado na integradora
                        case 'invoice':
                            $actionStatus = $this->order_v2->setInvoice($order);
                            break;
                        // cancelar pedido
                        case 'cancel':
                            $actionStatus = $this->order_v2->setCancelIntegration();
                            break;
                        // Enviar/receber dados de rastreamento
                        case 'tracking':
                            $actionStatus = $this->order_v2->setTracking();
                            break;
                        // Enviar/receber pedido enviado
                        case 'shipped':
                            $actionStatus = $this->order_v2->setShipped();
                            break;
                        // Enviar/receber ocorrências do rastreio
                        case 'in_transit':
                            $actionStatus = $this->order_v2->setOccurrence();
                            break;
                        // Enviar/receber dados de pedido entregue
                        case 'delivered':
                            $actionStatus = $this->order_v2->setDelivered();
                            break;
                        // Ignorar estado do pedido, não está mapeada para realizar alguma ação
                        default:
                            $actionStatus = true;
                            break;
                    }

                    if ($status === null && $actionStatus) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Pedido {$this->order_v2->toolsOrder->orderId} com status ($paidStatus) não mapeado, removido da fila.\n";
                        $this->order_v2->removeOrderQueue($this->order_v2->toolsOrder->orderId);
                        continue;
                        // cancelamento remove todos os status da fila
                    } elseif ($status === 'cancel' && $actionStatus) {
                        $this->order_v2->removeAllOrderIntegration($this->order_v2->toolsOrder->orderId);
                        continue;
                    } elseif ($actionStatus && $status !== null) {
                        $this->order_v2->removeOrderQueue($this->order_v2->toolsOrder->orderId);
                        //continue; 
                    }
                    // Não é gerado log.
                    // Quando for 'invoice', pois é gerado no método que busca a nota fiscal, com os dados da nota
                    // Quando for nulo, pois é apenas um estado não mapeado que foi removido da fila
                    if ($actionStatus && $status !== 'invoice' && $status !== null && $this->nameStatusUpdated) {
                        $this->order_v2->log_integration("Pedido ({$this->order_v2->toolsOrder->orderId}) atualizado", "<h4>Estado do pedido atualizado com sucesso</h4> <ul><li>O estado do pedido {$this->order_v2->toolsOrder->orderId}, foi atualizado para <strong>$this->nameStatusUpdated</strong></li></ul>", "S");
                        echo "[SUCCESS][LINE:" . __LINE__ . "] Pedido {$this->order_v2->toolsOrder->orderId} atualizado com sucesso para $this->nameStatusUpdated\n";
                    }

                } catch (Throwable $e) {
                    echo "[ERRO][LINE:".__LINE__."] Pedido ({$this->order_v2->toolsOrder->orderId}) não atualizado.. {$e->getMessage()}\n";
                    $this->order_v2->log_integration("Pedido ({$this->order_v2->toolsOrder->orderId}) não atualizado.", "<h4>Não foi possível atualizar o pedido {$this->order_v2->toolsOrder->orderId}</h4> <ul><li> {$e->getMessage()}</li></ul>", "E");
                }
            }
        }
    }

    protected function updateOrdersFromIntegration($orderId = null)
    {
        if (method_exists($this->order_v2->toolsOrder, 'getOrdersFromIntegrationNotifications')) {
            $ordersNotifications = $this->order_v2->toolsOrder->getOrdersFromIntegrationNotifications([
                'company_id' => $this->order_v2->company,
                'store_id' => $this->order_v2->store,
                'orderId' => $orderId,
            ]);
            if (method_exists($this->order_v2->toolsOrder, 'updateOrderFromIntegrationNotifications')) {
                foreach ($ordersNotifications ?? [] as $orderNotification) {
                    if (empty($orderNotification['order_id'])) {
                        continue;
                    }
                    $result = $this->order_v2->toolsOrder->updateOrderFromIntegrationNotifications($orderNotification);
                    echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$result['order_id']}:".json_encode($result) . "\n";
                }
            }
        }
    }
}