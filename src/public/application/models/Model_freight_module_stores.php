<?php
/*

Model de Acesso ao BD para a tabela de lojas ativas e inativas no módulo de fretes

*/

class Model_freight_module_stores extends CI_Model
{
    const TABLE = 'freight_module_stores';
    public function __construct()
    {
        parent::__construct();
    }

    
    public function insert($store_id)
    {   
        $data = array(
            'store_id'      => $store_id,
            'active_status' => 1,
            'create_date'   => dateNow()->format(DATETIME_INTERNATIONAL)
        );
        $insert = $this->db->insert(self::TABLE, $data);
		return $insert ? $this->db->insert_id() : false;
    }
    
    public function activateFreightModule($store_id)
    {
        $sql = "UPDATE freight_module_stores SET active_status = 1  WHERE store_id = ?";
        $result = $this->db->query($sql, array($store_id));
        return ($result == true) ? true : false;
    }

    public function inactivateFreightModule($store_id)
    {
        $sql = "UPDATE freight_module_stores SET active_status = 0 WHERE store_id = ?";
        $result = $this->db->query($sql, array($store_id));
        return ($result == true) ? true : false; 
    }

    public function validateIfStoreCreated($store_id)
    {
        $sql = "SELECT * FROM freight_module_stores  WHERE store_id = ?";
        $query = $this->db->query($sql, array($store_id));
        return $query->row_array() ? true : false;
    }

    public function verifyActiveStore($store_id)
    {
        $sql = "SELECT active_status FROM freight_module_stores WHERE store_id = ? AND active_status = ?";
        $query = $this->db->query($sql, array($store_id, 1));
        return $query->row_array() ? true : false;
    }

}