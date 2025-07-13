<?php

class Model_orders_simulations_anticipations_store extends CI_Model
{

    private $tableName = 'orders_simulations_anticipations_store';

    public function __construct()
    {
        parent::__construct();
    }

    public function create($data)
    {
        $insert = $this->db->insert($this->tableName, $data);

        $id = $this->db->insert_id();

        return $insert ? $id : false;
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update($this->tableName, $data);

            return $update ? $id : false;
        }
    }

    public function getAllBySimulationId(int $simulationId): ?array
    {

        return $this->db->select('*')
            ->from($this->tableName)
            ->where('simulations_anticipations_store_id', $simulationId)
            ->get()
            ->result_array();


    }

    public function findByOrderId(int $orderId): ?array
    {

        return $this->db->select("{$this->tableName}.*, 
        simulations_anticipations_store.store_id,
        simulations_anticipations_store.payment_date,
        simulations_anticipations_store.anticipation_status, 
        simulations_anticipations_store.anticipation_id,
        orders.gross_amount total_order,
        orders.date_time order_date,
        orders.data_mkt_delivered order_delivered_date,
        orders.numero_marketplace,
        (SELECT SUM(valor_repasse) FROM orders_conciliation_installments WHERE order_id = $orderId) as valor_repasse,
        (SELECT email FROM users WHERE id = simulations_anticipations_store.user_id) AS user_name")
            ->from($this->tableName)
            ->join('simulations_anticipations_store', 'simulations_anticipations_store.id = ' . $this->tableName . '.simulations_anticipations_store_id')
            ->join('orders', 'orders.id = ' . $this->tableName . '.order_id')
            ->where('order_id', $orderId)
            ->order_by('id', 'DESC')
            ->get()
            ->row_array();

    }

}