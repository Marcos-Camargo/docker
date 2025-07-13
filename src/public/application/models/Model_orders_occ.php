<?php   

class Model_orders_occ extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}


	public function getData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM orders_occ WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM orders_occ";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

    public function getDataToProcess($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM orders_occ WHERE id = ? AND status = 0";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM orders_occ WHERE status = 0";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('orders_occ', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('orders_occ', $data);
			return ($update == true) ? $id : false;
		}
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('orders_occ');
			return ($delete == true) ? true : false;
		}
	}

	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('orders_occ', $data);
			return ($insert == true) ? true : false;
		}
	}



}