<?php 
/*

Model de Acesso ao BD para carrefour_new_products

*/  

class Model_carrefour_new_products extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function getData($product_sku = null)
	{
		if($product_sku) {
			$sql = "SELECT * FROM carrefour_new_products WHERE product_sku= ?";
			$query = $this->db->query($sql, array($product_sku));
			return $query->row_array();
		}

		$sql = "SELECT * FROM carrefour_new_products";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('carrefour_new_products', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

	public function update($data, $product_sku)
	{
		if($data && $product_sku) {
			$this->db->where('product_sku', $product_sku);
			$update = $this->db->update('carrefour_new_products', $data);
			return ($update == true) ? $product_sku : false;
		}
	}

	public function remove($product_sku)
	{
		if($product_sku) {
			$this->db->where('product_sku', $product_sku);
			$delete = $this->db->delete('carrefour_new_products');
			return ($delete == true) ? true : false;
		}
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('carrefour_new_products', $data);
			return ($insert == true) ? true : false;
		}
	}
	
	public function createIfNotExist($product_sku, $data)
	{

		$sql = "SELECT product_sku FROM carrefour_new_products WHERE product_sku= ?";
		$query = $this->db->query($sql, array($product_sku));
		$row = $query->row_array();
		if ($row) {
			return $this->update($data,$row['product_sku']); 
		}
		else {
			return $this->create($data);
		}
	}
	
	public function getNewProductsByStore($store_id)
	{
		$sql = "SELECT * FROM carrefour_new_products WHERE status=0 AND store_id=?";
		$query = $this->db->query($sql, array($store_id));
		return $query->result_array();
	}
	
	public function removeByStatusAndStoreid($status, $store_id)
	{
		$this->db->where('status', $status);
		$this->db->where('store_id', $store_id);
		$delete = $this->db->delete('carrefour_new_products');
		return ($delete == true) ? true : false;
	}	

	public function updateByStatusAndStoreid($data, $status, $store_id)
	{
		$this->db->where('status', $status);
		$this->db->where('store_id', $store_id);
		$update = $this->db->update('carrefour_new_products', $data);
		return ($update == true) ? true : false;
	}
	
}