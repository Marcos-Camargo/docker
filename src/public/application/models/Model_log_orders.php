<?php   

class Model_log_orders extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}


	public function getData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM log_orders WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM log_orders";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('log_orders', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
	}

}