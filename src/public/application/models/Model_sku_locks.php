<?php

class Model_sku_locks extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function create($data)
	{

		if ($data) {
			$insert = $this->db->insert('sku_locks', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}

	public function get($data)
	{
		if ($data) {
			return $this->db->where($data)
				->get("sku_locks")
				->result_array();
		}
		return false;
	}

	public function getFirst($data)
	{
		if ($data) {
			return $this->db->where($data)
				->get("sku_locks")
				->row_array();
		}
		return false;
	}
	public function remove($id)
	{

		if ($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('sku_locks');
			return ($delete == true) ? true : false;
		}
		return false;
	}
}
