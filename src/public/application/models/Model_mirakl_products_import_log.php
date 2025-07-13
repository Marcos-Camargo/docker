<?php 
/*

Model de Acesso ao BD para mirakl_products_import_log

*/  

class Model_mirakl_products_import_log extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function getData($id = null)
	{
		if($product_sku) {
			$sql = "SELECT * FROM mirakl_products_import_log WHERE id= ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM mirakl_products_import_log";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('mirakl_products_import_log', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('mirakl_products_import_log', $data);
			return ($update == true) ? $id : false;
		}
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('mirakl_products_import_log');
			return ($delete == true) ? true : false;
		}
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('mirakl_products_import_log', $data);
			return ($insert == true) ? true : false;
		}
	}
	
	public function createIfNotExist($id, $data)
	{

		$sql = "SELECT product_sku FROM mirakl_products_import_log WHERE id= ?";
		$query = $this->db->query($sql, array($id));
		$row = $query->row_array();
		if ($row) {
			return $this->update($data,$row['id']); 
		}
		else {
			return $this->create($data);
		}
	}
	
	public function getOpenImportsByStore($int_to, $store_id)
	{
		$sql = "SELECT * FROM mirakl_products_import_log WHERE int_to = ? AND store_id = ? AND status=0 ORDER BY store_id, date_created";
		$query = $this->db->query($sql, array($int_to, $store_id));
		return $query->result_array();
	}
	
	public function getOldImports($int_to, $store_id,$from_date,$to_date)
	{
		$sql = "SELECT * FROM mirakl_products_import_log WHERE int_to = ? AND store_id = ? AND date_created > ? AND date_created < ? ORDER BY store_id, date_created";
		$query = $this->db->query($sql,array($int_to, $store_id,$from_date,$to_date));
		return $query->result_array();
	}
}