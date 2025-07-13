<?php
/*

Model de Acesso ao BD para tabela de fretes de pedidos

*/

class Model_gsoma_products_creation_control extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('gsoma_products_creation_control', $data);
			return ($insert == true) ? true : false;
		}
	}

    public function update($data, $int_to)
    {
        if($data && $int_to) {
            $this->db->where('int_to', $int_to);
            $update = $this->db->update('gsoma_products_creation_control', $data);
        }
    }
	
	public function replace($data)
    {
        if($data) {
            $update = $this->db->replace('gsoma_products_creation_control', $data);
        }
    }
	
	public function getData($int_to)
    {
        $sql = "SELECT * FROM gsoma_products_creation_control WHERE int_to = ?";
        $query = $this->db->query($sql, array($int_to));
        return $query->row_array();
    }

}