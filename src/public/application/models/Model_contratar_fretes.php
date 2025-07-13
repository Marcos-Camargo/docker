<?php 
/*

Model de Acesso ao BD para tabela de fretes que precisam ser contratados

*/  

class Model_contratar_fretes extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/* get the fretes a contratar pelo status */
	public function getContratarFreteByStatus($status)
	{
		$sql = "SELECT * FROM contratar_fretes WHERE status = ?";
		$query = $this->db->query($sql, array($status));
		return $query->result_array();	
	}
	
	/* get the contratar_frete table data */
	public function getContratarFreteData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM contratar_fretes WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM contratar_fretes";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('contratar_fretes', $data);
			return ($insert == true) ? true : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('contratar_fretes', $data);
			return ($update == true) ? true : false;
		}
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('contratar_fretes');
			return ($delete == true) ? true : false;
		}
	}

}