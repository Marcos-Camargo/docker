<?php 

class Model_queue_products_notify_omnilogic extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	
	public function getData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM queue_products_notify_omnilogic WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM queue_products_notify_omnilogic";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getDataNext()
	{
		$sql = "SELECT * FROM queue_products_notify_omnilogic WHERE status=0 ORDER BY date_update ASC ";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getDataAllDelayed()
	{
		$sql = "SELECT * FROM queue_products_notify_omnilogic WHERE status=1 AND date_update < DATE_SUB(NOW(), INTERVAL 1 MINUTE) ORDER BY date_update ASC ";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('queue_products_notify_omnilogic', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('queue_products_notify_omnilogic', $data);	
			return ($update == true) ? $id : false;
		}
		return false;
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('queue_products_notify_omnilogic');
			return ($delete == true) ? $id : false;
		}
		return false;
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('queue_products_notify_omnilogic', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}
	
	public function existProcessing($prd_id, $int_to, $status)
	{
		$sql = "SELECT * FROM queue_products_notify_omnilogic WHERE prd_id = ? AND int_to = ? AND status=? LIMIT 1";
		$query = $this->db->query($sql, array($prd_id, $int_to, $status));
		return $query->row_array();

	}
	
	
}