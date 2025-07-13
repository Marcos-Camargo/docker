<?php
/*
 Model Order Mediation
 
 */

class Model_orders_mediation extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /* get order mediation */
    public function getOrderMediationData($order_id)
    {
        if($order_id) {
            $sql = "SELECT * FROM orders_mediation WHERE order_id = ?";
            $query = $this->db->query($sql, array($order_id));
            return $query->row_array();
        }
        
        $sql = "SELECT * FROM orders_mediation";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    /* insert order mediation */
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('orders_mediation', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }
    
    /* update order mediation */
    public function update($data, $order_id)
    {
        if($data && $order_id) {
            $this->db->where('order_id', $order_id);
            $update = $this->db->update('orders_mediation', $data);
            return ($update == true) ? true : false;
        }
    }
}