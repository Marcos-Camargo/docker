<?php 
/*
SW Serviços de Informática 2019 

Model de Acesso ao BD para Clientes

*/  

class Model_clients_test extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function insert($data = null)
	{
		$insert = $this->db->insert('clients_test', $data);
		$client_id = $this->db->insert_id();
		// get_instance()->log_data('Clients','insert',json_encode($data),"I");

		return ($client_id) ? $client_id : false;
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('clients_test');
			// SW - Log update
			get_instance()->log_data('Clients','remove',$id,"I");
			return ($delete == true) ? true : false;
		}
	}

}