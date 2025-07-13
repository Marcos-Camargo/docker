<?php 
/*

Model de Acesso ao BD para vs_products -> Json pronto dos produtos para ser enviado para o Hub Vertem - Vertem Shop. 

*/  

class Model_vs_products extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	
	public function getData($id = null, $offset = 0, $limit = null)
	{
		if($id) {
			$sql = "SELECT * FROM vs_products WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}
		
		$next = '';
		if (!is_null($limit)) {
			$next = ' LIMIT '.$limit.' OFFSET '.$offset;
		}
		$sql = "SELECT * FROM vs_products ORDER BY id ".$next;
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getDataSeller($seller_id, $offset = 0, $limit = null)
	{
		$next = '';
		if (!is_null($limit)) {
			$next = ' LIMIT '.$limit.' OFFSET '.$offset;
		}
		$sql = "SELECT * FROM vs_products WHERE seller_id = ? ORDER BY id ".$next;
		$query = $this->db->query($sql, array($seller_id));
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('vs_products', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('vs_products', $data);
			return ($update == true) ? $id : false;
		}
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('vs_products');
			return ($delete == true) ? true : false;
		}
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('vs_products', $data);
			return ($insert == true) ? true : false;
		}
	}
	
	public function createIfNotExist($prd_id, $data)
	{

		$sql = "SELECT id FROM vs_products WHERE prd_id = ? ";
		$query = $this->db->query($sql, array($prd_id));

		$row = $query->row_array();
		if ($row) {
			return $this->update($data,$row['id']); 
		}
		else {
			return $this->create($data);
		}
	}
	
}