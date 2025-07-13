<?php

/**
 * Class Model_orders_pickup_store
 * @property CI_DB_query_builder $db
 */
class Model_orders_pickup_store extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
		
    }
    
    /* get pickupinfo infromation */
    public function getDataByOrderId($order_id)
    {
        $sql = "SELECT * FROM orders_pickup_store WHERE order_id = ?";
        $query = $this->db->query($sql, array($order_id));
        return $query->row_array();
    }

    /* get pickupinfo infromation */
    public function getDataByOrderMktId($int_to, $marketplace_order_id)
    {
        $sql = "SELECT * FROM orders_pickup_store WHERE marketplace_order_id = ? and int_to = ?";
        $query = $this->db->query($sql, array($marketplace_order_id, $int_to));
        return $query->row_array();
    }
    	
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('orders_pickup_store', $data);
            return ($insert == true) ? true : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('order_id', $id);
            $update = $this->db->update('orders_pickup_store', $data);
            return ($update == true) ? true : false;
        }
    }
    
    public function remove($id)
    {
        if($id) {
            $this->db->where('order_id', $id);
            $delete = $this->db->delete('orders_pickup_store');
            return ($delete == true) ? true : false;
        }
    }
    
}