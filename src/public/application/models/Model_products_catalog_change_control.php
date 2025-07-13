<?php
/*

Model de Acesso ao BD para tabela de fretes de pedidos

*/

class Model_products_catalog_change_control extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('products_catalog_change_control', $data);
			return ($insert == true) ? true : false;
		}
	}

    public function update($data, $id)
    {
        if($data && $int_to) {
            $this->db->where('id', $id);
            $update = $this->db->update('products_catalog_change_control', $data);
        }
    }
	
	public function replace($data)
    {
        if($data) {
            $update = $this->db->replace('products_catalog_change_control', $data);
        }
    }
	
	public function getData($id)
    {
        $sql = "SELECT * FROM products_catalog_change_control WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

}