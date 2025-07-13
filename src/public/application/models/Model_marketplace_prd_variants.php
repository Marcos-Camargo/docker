<?php 

class Model_marketplace_prd_variants extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	
	public function getData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM marketplace_prd_variants WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM marketplace_prd_variants";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('marketplace_prd_variants', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('marketplace_prd_variants', $data);	
			return ($update == true) ? $id : false;
		}
		return false;
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('marketplace_prd_variants');
			return ($delete == true) ? $id : false;
		}
		return false;
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('marketplace_prd_variants', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}
	
	public function getMarketplaceVariantsByFields($int_to, $store_id, $prd_id, $variant) 
	{
		$sql = 'SELECT * FROM marketplace_prd_variants WHERE int_to = ?AND store_id = ? AND prd_id = ? AND variant = ?';
		$query = $this->db->query($sql,array($int_to, $store_id, $prd_id, $variant));
        return $query->row_array();
	}
	
}