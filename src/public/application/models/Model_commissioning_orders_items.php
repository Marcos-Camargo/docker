<?php

class Model_commissioning_orders_items extends CI_Model
{
    private $tableName = 'commissioning_orders_items';

    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data)
    {
        $insert = $this->db->insert($this->tableName, $data);
        $id = $this->db->insert_id();
        return $insert ? $id : false;
    }

    public function update(array $data, int $id)
    {
        $this->db->where('id', $id);
        $update = $this->db->update($this->tableName, $data);
        return $update ? $id : false;
    }

    public function getCommissionByOrder(int $order_id): array
    {
        return $this->db->get_where($this->tableName, array('order_id' => $order_id))->result_array();
    }

    public function getCommissionByOrderAndItem(int $order_id, int $item_id): ?array
    {
        return $this->db->get_where($this->tableName,
            array('order_id' => $order_id, 'item_id' => $item_id))->row_array();
    }

    public function getCommissionByOrderAndProduct(int $order_id, int $product_id): ?array
    {
        $this->db->select($this->tableName.'.comission', 'orders_item.id');
        $this->db->from($this->tableName);
        $this->db->join('orders_item', 'orders_item.id = ' . $this->tableName . '.item_id', 'inner');
        $this->db->where($this->tableName . '.order_id = ', $order_id);
        $this->db->where('orders_item.product_id = ', $product_id);
        
        return $this->db->get()->row_array();
    }
}