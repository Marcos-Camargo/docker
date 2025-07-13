<?php
/*
 
 Model de Acesso ao log de pedidos integrados de marketplaces  para Integracoes
 
 */

class Model_log_integration_order_marketplace extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /* get the brand data */

    
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('log_integration_order_marketplace', $data);
            return ($insert == true) ? true : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('log_integration_order_marketplace', $data);
            return ($update == true) ? true : false;
        }
    }
    
    public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('log_integration_order_marketplace');
            return ($delete == true) ? true : false;
        }
    }
  
 	public function getLogByIntToOrderId($int_to, $order_id, $offset, $limit)
	{
		if ($offset == '') {$offset =0;}
		if ($limit == '') {$limit =200;}
		$sql = "SELECT * FROM log_integration_order_marketplace WHERE int_to= ? AND order_id=? ORDER BY id DESC LIMIT ".$limit." OFFSET ".$offset; 
		$query = $this->db->query($sql, array($int_to, $order_id));
		return $query->result_array();
	}

	public function getLogByOrderId($order_id, $offset, $limit)
	{
		if ($offset == '') {$offset =0;}
		if ($limit == '') {$limit =200;}
		$sql = "SELECT * FROM log_integration_order_marketplace WHERE order_id=? ORDER BY id DESC LIMIT ".$limit." OFFSET ".$offset; 
		$query = $this->db->query($sql, array($order_id));
		return $query->result_array();
	}

	public function getAll()
	{
		$sql = "SELECT * FROM log_integration_order_marketplace";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

}