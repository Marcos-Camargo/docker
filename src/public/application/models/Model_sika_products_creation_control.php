<?php
/*

Model de Acesso ao BD para tabela de controle de criação de produtos da Sika

*/

class Model_sika_products_creation_control extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('sika_products_creation_control', $data);
			return ($insert == true) ? true : false;
		}
	}

    public function update($data, $id)
    {
        if($data && $int_to) {
            $this->db->where('id', $id);
            $update = $this->db->update('sika_products_creation_control', $data);
        }
    }
	
	public function replace($data)
    {
        if($data) {
            $update = $this->db->replace('sika_products_creation_control', $data);
        }
    }
	
	public function getData($id)
    {
        $sql = "SELECT * FROM sika_products_creation_control WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

}