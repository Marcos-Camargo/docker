<?php

class Model_brands_vtex extends CI_Model
{
	const TABLE = 'brands_vtex';
	public function __construct()
	{
		parent::__construct();
	}

	public function getDataByBrandId($brand_id)
	{
		$sql = "SELECT * FROM brands_vtex WHERE id = ?";
		$query = $this->db->query($sql, array($brand_id));
		return $query->result_array();
	}
	public function findByBrandIdAndIntTo($brandId, $intTo)
	{
		return $this->db->select()->from(self::TABLE)->where(['int_to' => $intTo, 'id' => $brandId])->get()->row_array();
	}

	public function getAllBransByMarketplace($int_to)
	{
		$sql = "SELECT * FROM brands_vtex WHERE int_to = ? ORDER BY name";
		$query = $this->db->query($sql, array($int_to));
		return $query->result_array();
	}

	public function getBrandMktplace($int_to, $brand_id)
	{
		$sql = "SELECT * FROM brands_vtex WHERE int_to = ? AND id = ?";
		$query = $this->db->query($sql, array($int_to, $brand_id));
		return $query->row_array();
	}

	public function getBrandMktplaceByName($int_to, $name)
	{
		$sql = "SELECT * FROM brands_vtex WHERE int_to = ? AND name = ?";
		$query = $this->db->query($sql, array($int_to, $name));
		return $query->row_array();
	}

	public function create($data)
	{
		if ($data) {
			$insert = $this->db->insert('brands_vtex', $data);
			return ($insert == true) ? true : false;
		}
		return false;
	}

	public function update($data, $int_to, $brand_id)
	{
		if ($data && $int_to && $brand_id) {
			$this->db->where('int_to', $int_to);
			$this->db->where('id', $brand_id);
			$update = $this->db->update('brands_vtex', $data);
			return ($update == true) ? true : false;
		}
		return false;
	}

	public function remove($int_to, $brand_id)
	{
		if ($int_to && $brand_id) {
			$this->db->where('int_to', $int_to);
			$this->db->where('id', $brand_id);
			$delete = $this->db->delete('brands_vtex');
			return ($delete == true) ? true : false;
		}
		return false;
	}


}