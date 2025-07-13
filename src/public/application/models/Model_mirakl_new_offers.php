<?php 
/*

Model de Acesso ao BD para mirakl_new_offers

*/  

class Model_mirakl_new_offers extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function getData($skulocal = null)
	{
		if($skulocal) {
			$sql = "SELECT * FROM mirakl_new_offers WHERE skulocal= ?";
			$query = $this->db->query($sql, array($skulocal));
			return $query->row_array();
		}

		$sql = "SELECT * FROM mirakl_new_offers";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('mirakl_new_offers', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

	public function update($data, $skulocal)
	{
		if($data && $skulocal) {
			$this->db->where('skulocal', $skulocal);
			$update = $this->db->update('mirakl_new_offers', $data);
			return ($update == true) ? $skulocal : false;
		}
	}

	public function remove($skulocal)
	{
		if($skulocal) {
			$this->db->where('skulocal', $skulocal);
			$delete = $this->db->delete('mirakl_new_offers');
			return ($delete == true) ? true : false;
		}
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('mirakl_new_offers', $data);
			return ($insert == true) ? true : false;
		}
	}
	
	public function createIfNotExist($skulocal, $data)
	{

		$sql = "SELECT skulocal FROM mirakl_new_offers WHERE skulocal= ?";
		$query = $this->db->query($sql, array($skulocal));
		$row = $query->row_array();
		if ($row) {
			return $this->update($data,$row['skulocal']); 
		}
		else {
			return $this->create($data);
		}
	}
	
	public function getNewProductsByStore($store_id)
	{
		$sql = "SELECT * FROM mirakl_new_offers WHERE status=0 AND store_id=?";
		$query = $this->db->query($sql, array($store_id));
		return $query->result_array();
	}
	
	public function removeByStatusAndStoreid($status, $store_id)
	{
		$this->db->where('status', $status);
		$this->db->where('store_id', $store_id);
		$delete = $this->db->delete('mirakl_new_offers');
		return ($delete == true) ? true : false;
	}	

	public function updateByStatusAndStoreid($data, $status, $store_id)
	{
		$this->db->where('status', $status);
		$this->db->where('store_id', $store_id);
		$update = $this->db->update('mirakl_new_offers', $data);
		return ($update == true) ? true : false;
	}
	
}