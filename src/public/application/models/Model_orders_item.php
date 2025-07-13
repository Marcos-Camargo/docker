<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Integracoes
 
 */

class Model_orders_item extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    public function getItensByOrderId($order_id){
        return $this->db->from('orders_item')->where('order_id',$order_id)->get()->result_array();
    }

    public function updateById($id, $data)
    {
        if ($id) {
            $this->db->where('id', $id);
            $update = $this->db->update('orders_item', $data);
            return $id;
        }
        return false;
    }


    public function getItemDataBySkuMkt($skumkt)
    {
        if (empty($skumkt))
            return false;
        
        $sql = "select p.id as product_id, pti.int_to from prd_to_integration pti left join products p on p.id = pti.prd_id where pti.skumkt= ?";
        $query = $this->db->query($sql, array($skumkt));
        $result = $query->row_array();

        return ($result) ? $result : false;
    }

    public function getItensByOrderIdWithSkumkt($order_id)
    {
        $this->db->select('c.*');
        $this->db->from('orders_item c');
      //  $this->db->join('prd_to_integration p', 'p.prd_id = c.product_id', 'left');
        $this->db->where('c.order_id', $order_id);

        return $this->db->get()->result_array();
    }


    public function remove(int $orderId): bool
    {
        return (bool)$this->db->where('order_id', $orderId)->delete('orders_item');
    }
    public function getItemsByOrderId($order_id) {
        $this->db->where('order_id', $order_id);
        $query = $this->db->get('orders_item');
        return $query->result_array();
    }
}