<?php

/**
 * Class CreateOrder
 *
 * php index.php BatchC/Integration_v2/Order/anymarket/UpdateOrder run {ID} {Store}
 *
 */

require APPPATH . "libraries/Integration_v2/Order_v2.php";

use Integration\Integration_v2\Order_v2;

/**
 * Class UpdateOrder
 * @property CI_Loader $load
 * @property Order_v2 $order_v2
 * @property Model_orders $model_orders
 */
class UpdateOrder extends BatchBackground_Controller
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
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->order_v2->setJob(__CLASS__);

        $this->load->model('model_orders');
    }


    /**
     * Método responsável pelo "start" da aplicação
     *
     * @param string|int|null $id Código do job (job_schedule.id)
     * @param int|null $store Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
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
            return false;
        }
        
        // realiza algumas validações iniciais antes de iniciar a rotina
        try {
            $this->order_v2->startRun($store);
        } catch (InvalidArgumentException $exception) {
            $this->order_v2->log_integration(
                "Erro para executar a integração",
                "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                "E"
            );
            $this->gravaFimJob();
            return true;
        }

        // Recupera os pedidos para criação
        try {
            $this->order_v2->setToolsOrder();
            $this->sendOrders();
        } catch (Exception $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
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
     * @return bool
     * @throws InvalidArgumentException
     */
    public function sendOrders(): bool
    {
        $totalPage = 1;

        for ($page = 1; $page <= $totalPage; $page++) {

            // consulta a lista de produtos
            try {
                $orders = $this->order_v2->getOrderToIntegration([
                    'status' => implode(',', Model_orders::getOpenedOrderStatus())
                ], $page);
            } catch (InvalidArgumentException $exception) {
                echo "[PROCESS][LINE:" . __LINE__ . "] Não encontrou mais resultados na página: $page.\n";
                continue;
            }

            // define o total de páginas a serem lidas
            if ($totalPage === $page && $orders->pages_count != 1) {
                $totalPage = $orders->pages_count;
            }

            foreach ($orders->result as $orderIntegration) {

                $orderId = $orderIntegration->order_code;
                $idIntegration = $this->order_v2->getOrderIdIntegration($orderId);
                if (empty($idIntegration)) {
                    echo "[PROCESS][LINE:" . __LINE__ . "] Pedido ({$orderId}) ainda não integrado\n";
                    continue;
                }
                $this->order_v2->setUniqueId($orderId);

                try {
                    $order = $this->order_v2->getOrder($orderId);
                } catch (InvalidArgumentException $e) {
                    echo "[PROCESS][LINE:" . __LINE__ . "] Não encontrou dados para o pedido: $orderId. Err: {$e->getMessage()}\n";
                    continue;
                }

                try {
                    $orderParsed = $this->order_v2->toolsOrder->parseOrderToIntegration($order);
                    $updatableFields = [
                        'marketPlaceId', 'marketPlaceNumber', 'items', 'payments', 'interestValue', 'discount', 'freight', 'productNet', 'total', 'idAccount'
                    ];
                    $orderParsed = array_intersect_key($orderParsed, array_flip($updatableFields));
                    foreach ($orderParsed['items'] ?? [] as $k => $item) {
                        $updatableItemFields = [
                            'sku', 'amount', 'unit', 'gross', 'total', 'discount', 'marketPlaceId'
                        ];
                        $orderParsed['items'][$k] = array_intersect_key($item, array_flip($updatableItemFields));
                    }
                    foreach ($orderParsed['payments'] ?? [] as $k => $item) {
                        $updatableItemFields = [
                            'value', 'installments', 'method'
                        ];
                        $orderParsed['payments'][$k] = array_intersect_key($item, array_flip($updatableItemFields));
                    }
                    echo "\n---- {$orderId} ----\n";
                    echo json_encode($orderParsed, JSON_PRETTY_PRINT);
                    echo "\n----------------\n";
                    continue;
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:" . __LINE__ . "] Pedido ($orderId) não integrado. {$exception->getMessage()}\n";
                    continue;
                }

                // recupera o ID do pedido integrado
                $idIntegrated = $response['code'] ?? $response['id'];

                $logRequest = '';
                if (isset($response['request'])) {
                    $logRequest = "<div class='col-md-12 d-flex justify-content-center mb-3'><button type='button' class='btn btn-primary text-center' data-toggle='collapse' data-target='#collapseLogRequest' aria-expanded='false' aria-controls='collapseLogRequest'>Visualizar log enviado</button></div><div class='collapse' id='collapseLogRequest'><pre>{$response['request']}</pre></div>";
                }

                $this->order_v2->log_integration("Pedido ($orderId) atualizado", "<h4>Dados do pedido atualizados com sucesso</h4> <ul><li>O pedido $orderId, foi atualizado em {$this->order_v2->integration} com o código ($idIntegrated)</li></ul>$logRequest", "S");


                echo "[SUCCESS][LINE:" . __LINE__ . "] Pedido $orderId atualizado com sucesso com o código $idIntegrated\n";
            }
        }
        return true;
    }
}