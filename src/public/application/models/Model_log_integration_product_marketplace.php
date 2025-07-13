<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Integracoes
 
 */

class Model_log_integration_product_marketplace extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
    }
    
    /* get the brand data */

    
    public function create($data)
    {
        
        if ($this->model_settings->getStatusbyName('enable_log_integration_product_marketplace') != 1) {
            return true; 
        }

        if($data) {
            $insert = $this->db->insert('log_integration_product_marketplace', $data);
            return ($insert == true) ? true : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('log_integration_product_marketplace', $data);
            return ($update == true) ? true : false;
        }
    }
    
    public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('log_integration_product_marketplace');
            return ($delete == true) ? true : false;
        }
    }
  
 	public function getLogByIntToPrdId($int_to, $prd_id, $offset, $limit)
	{
		if ($offset == '') {$offset =0;}
		if ($limit == '') {$limit =200;}
		$sql = "SELECT * FROM log_integration_product_marketplace WHERE int_to= ? AND prd_id=? ORDER BY id DESC LIMIT ".$limit." OFFSET ".$offset; 
		$query = $this->db->query($sql, array($int_to, $prd_id));
		return $query->result_array();
	}
    
}