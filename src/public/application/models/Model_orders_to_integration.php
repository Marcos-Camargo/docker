<?php
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Integracoes

 */

class Model_orders_to_integration extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Recupera se o pedido precisa ser cancelado
     *
     * @param   int   $orderId  Código do pedido (orders.id)
     * @param   int   $store    Código do pedido (stores.id)
     * @return  bool            Retorna se existe cancelamento
     */
    public function isCanceled(int $orderId, int $store): bool
    {
        $orderCancel = $this->db
            ->get_where(
                'orders_to_integration',
                array(
                    'order_id' => $orderId,
                    'store_id' => $store,
                    'paid_status' => 96,
                )
            )->row_array();

        if (!$orderCancel) {
            return false;
        }

        return true;
    }

    /**
     * Remove todos os pedidos da fila de integração.
     *
     * @param   int             $orderId      Código do pedido (orders.id).
     * @param   int|array|null  $store        Código do pedido (stores.id).
     * @param   bool            $queueMaster  Irá processar na tabela orders_to_integration_master, caso false consultará orders_to_integration.
     * @return  bool                          Retornar o status da exclusão.
     */
    public function removeAllOrderIntegration(int $orderId, $store, bool $queueMaster = false): bool
    {
        $table = $queueMaster ? 'orders_to_integration_master' : 'orders_to_integration';

        $filter = ['order_id' => $orderId];
        if (is_numeric($store) && $store > 0) {
            $filter['store_id'] = $store;
        }

        if (is_array($store)) {
            return (bool)$this->db->where_in('store_id', $store)->delete($table);
        }

        return (bool)$this->db->delete(
            $table,
            $filter
        );
    }
    /**
     * Remove o pedido da fila de integração.
     *
     * @param   int   $order_id     Código do pedido (orders.id).
     * @param   int   $store        Código do pedido (stores.id).
     * @param   bool  $queueMaster  Irá processar na tabela orders_to_integration_master, caso false consultará orders_to_integration.
     * @return  bool                Retornar o status da exclusão.
     */
    public function removeOrderIntegration(int $order_id, int $store, bool $queueMaster = false): bool
    {
        $table = $queueMaster ? 'orders_to_integration_master' : 'orders_to_integration';

        return (bool)$this->db->delete(
            $table,
            array(
                'store_id' => $store,
                'order_id' => $order_id,
                'new_order' => 1,
            ),
            1
        );
    }
    /**
     * Verifica se existe um status como pago, aguardando faturamento ou cancelado para criar o pedido
     *
     * @param   int  $orderId  Código do pedido (orders.id)
     * @param   int  $store    Código do pedido (stores.id)
     * @return  bool           Retorna o status para criação
     */
    public function getOrderOtherThanUnpaid(int $orderId, int $store): bool
    {
        $query = $this->db
            ->from('orders_to_integration')
            ->where(array(
                'store_id' => $store,
                'order_id' => $orderId,
            ))
            ->where_in('paid_status', array(3))
            ->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        // Remover da fila do não pago
        $this->db->delete(
            'orders_to_integration',
            array(
                'store_id' => $store,
                'order_id' => $orderId,
                'paid_status' => 1,
            ),
            1
        );

        // coloca o próximo status como new_order = 1
        $orderUpdated = $this->db
            ->from('orders_to_integration')
            ->where(
                array(
                    'store_id' => $store,
                    'order_id' => $orderId,
                    'new_order' => 0,
                )
            )
            ->where_in('paid_status', array(3))
            ->order_by('id', 'asc')
            ->get()
            ->row_array();

        return (bool)$this->db->where('id', $orderUpdated['id'])->update('orders_to_integration', array('new_order' => 1));
    }

    /**
     * @param   string          $limit                  Limite query Ex.(LIMIT 5 OFFSET 10)
     * @param   bool            $queueMaster            Irá pesquisar na tabela orders_to_integration_master, caso false consultará orders_to_integration
     * @param   int|array|null  $store_id               Código da loja (stores.id)
     * @param   bool            $check_credit_card_fee  Valida se tem a taxa do cartão de crédito (orders_paymment.taxa_cartao_credito)
     * @return  mixed                                   Retorna a consulta (para ver o resultado deve ser chamado o método result_array, num_rows, first_row, ...)
     */
    public function getDataOrdersOnlyNewOrder($store_id, bool $queueMaster, string $limit = "", bool $check_credit_card_fee = false, $start_queue_id = 0)
    {
        $table = $queueMaster ? 'orders_to_integration_master' : 'orders_to_integration';
        $otiStoreFilter = '1=1';
        $oti_StoreFilter = '1=1';

        if (is_numeric($store_id) && $store_id > 0) {
            $otiStoreFilter = "oti.store_id = $store_id";
            $oti_StoreFilter = "oti_.store_id = $store_id";
        } else if (is_array($store_id)) {
            $otiStoreFilter = "oti.store_id IN (" . implode(',', $store_id) . ")";
            $oti_StoreFilter = "oti_.store_id IN (" . implode(',', $store_id) . ")";
        }

        $sql = "SELECT oti.* FROM $table AS oti";

        if ($check_credit_card_fee) {
            $sql .= " JOIN orders_payment op ON op.order_id = oti.order_id";
        }

        $sql .= " WHERE $otiStoreFilter AND (SELECT oti_.id FROM $table AS oti_ WHERE $oti_StoreFilter AND oti_.new_order = 1 AND oti_.order_id = oti.order_id GROUP BY oti_.order_id LIMIT 1)";

        if ($check_credit_card_fee) {
            $sql .= " AND op.taxa_cartao_credito IS NOT NULL";
        }

        if ($start_queue_id) {
            $sql .= " AND oti.id > $start_queue_id";
        }

        return $this->db->query("$sql GROUP BY oti.order_id ORDER BY oti.updated_at $limit");
    }

    /**
     * @param   int|array|null  $store                  Código da loja (stores.id)
     * @param   bool            $queueMaster            Irá pesquisar na tabela orders_to_integration_master, caso false consultará orders_to_integration
     * @param   int|null        $code                   Código do pedido, se necessário (orders.id)
     * @param   string          $filters                Filtros, se necessário Ex.( AND paid_status=3 AND new_order=0 )
     * @param   string          $limit                  Limite query Ex.(LIMIT 5 OFFSET 10)
     * @param   bool            $check_credit_card_fee  Valida se tem a taxa do cartão de crédito (orders_paymment.taxa_cartao_credito)
     * @return  mixed                                   Retorna a consulta (para ver o resultado deve ser chamado o método result_array, num_rows, first_row, ... )
     */
    public function getDataOrdersInteg($store, bool $queueMaster, int $code = null, string $filters = "", string $limit = "", bool $check_credit_card_fee = false, int $start_queue_id = 0)
    {
        $where = $code ? "AND oti.order_id = '$code'" : "";
        $table = $queueMaster ? 'orders_to_integration_master oti' : 'orders_to_integration oti';

        $storeFilter = '1=1';
        if (is_numeric($store) && $store > 0) {
            $storeFilter = "oti.store_id = $store";
        } else if (is_array($store)) {
            $storeFilter = "oti.store_id IN (" . implode(',', $store) . ")";
        }

        $sql = "SELECT oti.* FROM $table";

        if ($check_credit_card_fee) {
            $sql .= " JOIN orders_payment op ON op.order_id = oti.order_id";
        }

        $sql .= " WHERE $storeFilter $where $filters";

        if ($check_credit_card_fee) {
            $sql .= " AND op.taxa_cartao_credito IS NOT NULL";
        }

        if ($start_queue_id) {
            $sql .= " AND oti.id > $start_queue_id";
        }
        
        return $this->db->query("$sql ORDER BY oti.updated_at ASC $limit");
    }

    public function getOrdersByStoreToSend($store)
    {
        return $this->db->select('*')->from('orders_to_integration')->where(['store_id' => $store])->get()->result_array();
    }

    /**
     * Atualiza dados da tabela de fila de pedidos
     *
     * @param   int     $id          Código do registro na fila (orders_to_integration.id | orders_to_integration_master.id)
     * @param   array   $data        Dados que serão atualizados
     * @param   bool    $queueMaster Irá atualizar na tabela orders_to_integration_master, caso false atualizará orders_to_integration
     * @return  bool                 Retorna o status da atualização
     */
    public function update(int $id, array $data, bool $queueMaster = false): bool
    {
        $table = $queueMaster ? 'orders_to_integration_master' : 'orders_to_integration';
        return (bool)$this->db->update($table, $data, "id = {$id}");
    }

    public function getOrderToIntegrationByStore_idAndId(int $store_id, int $id)
    {
        return $this->db->select()->from('orders_to_integration')->where(['id' => $id, 'store_id' => $store_id])->get()->row_array();
    }
    public function create($data){
        $this->db->insert('orders_to_integration',$data);
        $this->db->insert('orders_to_integration_master',$data);
    }

    /**
     * @param   int   $id           Código do registro na fila (orders_to_integration.id | orders_to_integration_master.id)
     * @param   bool  $queueMaster  Irá excluir na tabela orders_to_integration_master, caso false excluirá orders_to_integration
     * @return  bool                Retorna o status da exclusão
     */
    public function delete(int $id, bool $queueMaster = false): bool
    {
        $table = $queueMaster ? 'orders_to_integration_master' : 'orders_to_integration';
        return (bool)$this->db->delete($table, "id = {$id}");
    }

    /**
     * @param   int  $store_id      Código da loja (stores.id)
     * @param   int  $order_id      Código do pedido (orders.id)
     * @param   bool $queueMaster   Irá consultar na tabela orders_to_integration_master, caso false consultará orders_to_integration
     * @return  bool                Retorna se o pedido ainda não foi consumido e criado na plataforma
     */
    public function getHaveNewOrder(int $store_id, int $order_id, bool $queueMaster = false): bool
    {
        $table = $queueMaster ? 'orders_to_integration_master' : 'orders_to_integration';
        $query = $this->db->query("SELECT * FROM {$table} WHERE store_id = {$store_id} AND new_order = 1 AND order_id = {$order_id} LIMIT 1");

        return !($query->num_rows() == 0);
    }

    /**
     * @param   int|array|null  $store_id       Código da loja (stores.id)
     * @param   int|null        $company_id     Código da empresa (company.id)
     * @param   array           $filter_search  Filtros na consulta
     * @return  array
     */
    public function getListOrders($store_id, ?int $company_id, array $filter_search): array
    {
        $this->db->select('o.*');

        if (is_numeric($store_id) && $store_id > 0) {
            $this->db->where([
                'o.company_id' => $company_id,
                'o.store_id' => $store_id
            ]);
        } else if (is_array($store_id)) {
            $this->db->where_in('o.store_id', $store_id);
        }

        $page                   = $filter_search['page'] ?? null;
        $per_page               = $filter_search['per_page'] ?? null;
        $start_date             = $filter_search['start_date'] ?? null;
        $end_date               = $filter_search['end_date'] ?? null;
        $status                 = $filter_search['status'] ?? null;
        $marketplace_number     = $filter_search['marketplace_number'] ?? null;
        $check_credit_card_fee  = $filter_search['check_credit_card_fee'] ?? false;
        $order_created_before  = $filter_search['order_created_before'] ?? null;
        $order_created_after   = $filter_search['order_created_after'] ?? null;

        if ($start_date) { // existe data inicial, fazer o filtro
            $this->db->where("DATE_FORMAT(o.date_time,'%Y-%m-%d %H:%i:%s') >= ",$start_date.' 00:00:00');
        }
        if ($end_date) {// existe data final, fazer o filtro
            $this->db->where("DATE_FORMAT(o.date_time,'%Y-%m-%d %H:%i:%s') <= ",$end_date.' 23:59:59');
        }

        if ($status) { // existe status, fazer o filtro
            $status = explode(',', $status);
            $this->db->where_in('o.paid_status', $status);
        }
        if ($marketplace_number) {// existe filtro pelo código do pedido no marketplace
            $this->db->where('o.numero_marketplace', $marketplace_number);
        }

        if ($check_credit_card_fee) {
            $this->db->join('orders_payment AS op', 'op.order_id = o.id');
            $this->db->where('op.taxa_cartao_credito IS NOT NULL', NULL, FALSE);
        }

        // Ou pode usar : o.date_time
        if ($order_created_before) {
            $this->db->where('DATE_FORMAT(o.date_updated, "%Y-%m-%d") <=', $order_created_before);
        }

        // Ou pode usar : o.date_time
        if ($order_created_after) {
            $this->db->where('DATE_FORMAT(o.date_updated, "%Y-%m-%d") >=', $order_created_after);
        }

        $this->db->order_by('o.id', 'DESC'); // ordenar pelo ID

        $page--;
        $this->db->limit($per_page, $page * $per_page); // paginação dos resultados
        if ($page < 0) {
            $this->db->reset_query();
            return array();
        }
        return $this->db->get('orders AS o USE INDEX (ix_orders_01)')->result_array();

    }

    public function getOrdersQeueuByOrder(int $order)
    {
        return $this->db->select('paid_status, updated_at')
                        ->from('orders_to_integration')
                        ->where(['order_id' => $order])
                        ->get()
                        ->result_array();
    }

    /**
     * Recupera os pedidos para integração, deverá ser enviados apenas pedidos já pagos
     *
     * @param   int     Código da loja (stores.id)
     * @return  array   Retorno os pedidos na fila para integrar
     */
    public function getOrdersToSend($store): array
    {
        return $this->db
            ->from('orders_to_integration')
            ->where(array(
                'store_id'  => $store,
                'new_order' => 1
            ))
            ->where_in('paid_status', array(1, 2, 3))
            ->get()
            ->result_array();
    }

    /**
     * Cria um novo registro de integração caso o status for 3, aguardar para faturar
     *
     * @param   array   $data   Dados da integração
     * @return  bool            Retorna o status da criação
     */
    public function controlRegisterIntegration($data): bool
    {
        $response = false;

        if ($data['paid_status'] == 3) {
            $idIntegration = $data['id'];

            $arrUpdate = array(
                'new_order' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            );

            $update = $this->db->where(
                array(
                    'id'        => $idIntegration,
                    'store_id'  => $this->store,
                )
            )->update('orders_to_integration', $arrUpdate);

            $response = (bool)$update;
        }

        return $response;
    }

    /**
     * Remove todos os pedidos da fila de integração
     *
     * @param   int   $orderId Código do pedido (orders.id)
     * @param   int   $store   Código do pedido (stores.id)
     * @param   array $status  Código do status do pedido (orders.paid_status)
     * @return  bool           Retornar o status da exclusão
     */
    public function removeOrderToIntegrationByStatus(int $orderId, int $store, array $status): bool
    {
        return $this->db
            ->where(
                array(
                    'store_id' => $store,
                    'order_id' => $orderId,
                )
            )
            ->where_in('paid_status', $status)
            ->delete('orders_to_integration');
    }

    /**
     * Recupera se o pedido precisa ser cancelado
     *
     * @param   int     $order  Código do pedido
     * @param   int     $store  Código da loja
     * @return  bool            Retorna se existe cancelamento
     */
    public function getOrderCancel(int $order, int $store): bool
    {
        $orderCancel = $this->db
            ->from('orders_to_integration')
            ->where(array(
                'order_id'      => $order,
                'store_id'      => $store
            ))
            ->where_in('paid_status', array(95, 97))
            ->get()->row_array();

        if (!$orderCancel) {return false;}

        return true;
    }

    public function getOrderIdIntegration(int $order, int $store)
    {
        $order = $this->db->select('order_id_integration')->from('orders')->where(array('id' => $order, 'store_id' => $store))->get()->row_array();
        return $order['order_id_integration'] ?? null;
    }


    public function cleanOrderQueueByStore($store): bool
    {
        if(!empty($store)) {
            return $this->db->delete('orders_to_integration', array('store_id' => $store));
        }

        return false;
    }
}
