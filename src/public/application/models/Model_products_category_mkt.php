<?php 
/*

Model de Acesso ao BD para Model_products_category_mkt que é o retorno da omnilogic com a categoria do produto

*/  

class Model_products_category_mkt extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	
	public function getData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM products_category_mkt WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM products_category_mkt";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('products_category_mkt', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('products_category_mkt', $data);
			return ($update == true) ? $id : false;
		}
		return false;
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('products_category_mkt');
			return ($delete == true) ? true : false;
		}
		return false;
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('products_category_mkt', $data);
			return ($insert == true) ? true : false;
		}
		return false;
	}
	
	public function getCategoryEnriched($int_to, $product_id) {
        $sql = "SELECT * FROM products_category_mkt WHERE int_to = ? AND prd_id = ?";
        $query = $this->db->query($sql, array($int_to, $product_id));
        return $query->row_array();
    }
	
}