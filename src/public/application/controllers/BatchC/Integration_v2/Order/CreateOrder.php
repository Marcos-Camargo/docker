<?php

/**
 * Class CreateOrder
 *
 * php index.php BatchC/Integration_v2/Order/CreateOrder run {ID} {Store}
 *
 */

require APPPATH . "libraries/Integration_v2/Order_v2.php";

use Integration\Integration_v2\Order_v2;

class CreateOrder extends BatchBackground_Controller
{
    /**
     * @var Order_v2
     */
    private $order_v2;

    /**
     * Instantiate a new CreateProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->order_v2 = new Order_v2();;

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
     * Método responsável pelo "start" da aplicação
     *
     * @param  string|int|null  $id     Código do job (job_schedule.id)
     * @param  int|null         $store  Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
     * @return bool                     Estado da execução
     */
    public function run($id = null, int $store = null): bool
    {
        $log_name = $this->order_v2->integration . '/' . __CLASS__ . '/' . __FUNCTION__;

        if (!$this->checkStartRun(
            $log_name,
            $this->router->directory,
            __CLASS__,
            $id,
            $store
        )) {
            echo "[ERRO][LINE:".__LINE__."] Falha na validação em checkStartRun\n";
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
            return false;
        }

        // Recupera os pedidos para criação
        try {
            $this->order_v2->setToolsOrder();
            $this->sendOrders();
        } catch (Exception $exception) {
            echo "[ERRO][LINE:".__LINE__."] {$exception->getMessage()}\n";
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
     * Recupera os produtos para cadastro
     *
     * @throws InvalidArgumentException
     */
    public function sendOrders()
    {
        $last_queue_id = 0;

        while (true) {
            // consulta a lista de produtos
            try {
                $orders = $this->order_v2->getNewOrderToIntegration(true, $last_queue_id);
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
                    $orderId = $orderIntegration->order_code;
                    $this->order_v2->setUniqueId($orderId);

                    try {
                        $order = $this->order_v2->getOrder($orderId);
                    } catch (InvalidArgumentException $exception) {
                        echo "[PROCESS][LINE:" . __LINE__ . "] Não encontrou dados para o pedido: $orderId.\n";
                        continue;
                    }

                    $checkOrder = $this->order_v2->checkDataOrderToIntegration($order);

                    if ($checkOrder !== true) {
                        switch ($checkOrder) {
                            case 'cancel':
                                echo "[SUCCESS][LINE:".__LINE__."] Pedido ($orderId) cancelado, não precisa ser integrado\n";
                                break;
                            case 'client':
                                echo "[ERRO][LINE:".__LINE__."] Pedido ($orderId) sem dados de cliente, não deve ser integrado\n";
                                break;
                            case 'items':
                                echo "[ERRO][LINE:".__LINE__."] Pedido ($orderId) sem itens, não deve ser integrado\n";
                                break;
                            case 'payments':
                                echo "[ERRO][LINE:".__LINE__."] Pedido ($orderId) sem pagamento, não deve ser integrado\n";
                                break;
                        }
                        continue;
                    }

                    // Pedido incompleto.
                    if ($order->is_incomplete && !$this->order_v2->can_integrate_incomplete_order) {
                        echo "[SUCCESS][LINE:".__LINE__."] Pedido ($orderId) está incompleto, ainda não deve ser integrado.\n";
                        continue;
                    }

                    try {
                        // Adiciona uma flag para a Tiny.
                        $order->shipping->is_correios = $order->shipping->shipping_carrier == "CORREIOS";
                        
                        // Troca o nome da transportadora de "Transportadora"/"Correios" para o nome real.
                        $order->shipping->shipping_carrier = $order->shipping->shipping_carrierName?:$order->shipping->shipping_carrier;
                        
                        $response = $this->order_v2->toolsOrder->sendOrderIntegration($order);
                    } catch (InvalidArgumentException $exception) {
                        echo "[ERRO][LINE:".__LINE__."] Pedido ($orderId) não integrado. {$exception->getMessage()}\n";
                        continue;
                    }

                    // recupera o ID do pedido integrado
                    $idIntegrated = $response['code'] ?? $response['id'];

                    $logRequest = '';
                    if (isset($response['request'])) {
                        $logRequest = "<div class='col-md-12 d-flex justify-content-center mb-3'><button type='button' class='btn btn-primary text-center' data-toggle='collapse' data-target='#collapseLogRequest' aria-expanded='false' aria-controls='collapseLogRequest'>Visualizar log enviado</button></div><div class='collapse' id='collapseLogRequest'><pre>{$response['request']}</pre></div>";
                    }

                    $this->order_v2->log_integration("Pedido ($orderId) integrado", "<h4>Novo pedido integrado com sucesso</h4> <ul><li>O pedido $orderId, foi criado em {$this->order_v2->integration} com o código ($idIntegrated)</li></ul>$logRequest", "S");

                    // salva código do pedido gerado pela integradora
                    $this->order_v2->saveOrderIdIntegration($orderId, $response['id']);
                    // remove da fila de integração se o pedido
                    if (isset($orderId)) {
                        if ($order->status->code == 1) {
                            $this->order_v2->changeStatusNewOrder($orderId);
                        } else {
                            $this->order_v2->removeOrderQueue($orderId);
                        }
                    }
                    // confirmar pagamento
                    if (method_exists($this->order_v2->toolsOrder, 'confirmOrder')) {
                        if (!in_array($order->status->code, [1, 2, 96])) {
                            try {
                                $this->order_v2->toolsOrder->confirmOrder($order, $response['id']);
                            } catch (InvalidArgumentException $exception) {
                                throw new InvalidArgumentException($exception->getMessage());
                            }
                        }
                    }
                    echo "[SUCCESS][LINE:".__LINE__."] Pedido $orderId integrado com sucesso com o código $idIntegrated\n";

                } catch (Throwable $e) {
                    echo "[ERRO][LINE:".__LINE__."] Pedido ($orderId) não integrado. {$e->getMessage()}\n";
                    $this->order_v2->log_integration("Pedido ({$orderId}) não integrado.", "<h4>Não foi possível integrar o pedido {$orderId}</h4> <ul><li> {$e->getMessage()}</li></ul>", "E");
                }
            }

        }
    }
}
