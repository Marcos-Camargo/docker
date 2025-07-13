<?php 

class Model_queue_payments_orders_marketplace extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	
	public function getData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM queue_payments_orders_marketplace WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM queue_payments_orders_marketplace";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getDataOrderId($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM queue_payments_orders_marketplace WHERE order_id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}
	}
	
	public function getDataNext($limit = 1, $problem = null)
	{
		if (is_null($problem)) {
			$sql = "SELECT * FROM queue_payments_orders_marketplace WHERE status=0 ORDER BY date_updated ASC LIMIT ".$limit;
			$query = $this->db->query($sql);
		}
		else {
			$sql = "SELECT * FROM queue_payments_orders_marketplace WHERE status=0 AND date_created > ? ORDER BY date_updated ASC LIMIT ".$limit;
			$query = $this->db->query($sql,array($problem));
		}
		
		return $query->result_array();
	}

	public function getDataNextNew($limit = 1, $problem = null)
	{
		if (is_null($problem)) {
			$sql = "SELECT * FROM queue_payments_orders_marketplace WHERE status=0 ORDER BY id ASC LIMIT ".$limit;
			$query = $this->db->query($sql);
		}
		else {
			$sql = "SELECT * FROM queue_payments_orders_marketplace WHERE status=0 AND id > ? ORDER BY id ASC LIMIT ".$limit;
			$query = $this->db->query($sql,array($problem));
		}
		
		return $query->result_array();
	}
	
	public function getDataAllDelayed()
	{
		$sql = "SELECT * FROM queue_payments_orders_marketplace WHERE status=1 AND date_updated < DATE_SUB(NOW(), INTERVAL 5 MINUTE) ORDER BY date_updated ASC ";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function updateAllDelayed()
	{
		$sql = "UPDATE queue_payments_orders_marketplace SET status=0 WHERE status=1 AND date_updated < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
		$query = $this->db->query($sql);
		return $query;
	}

	public function create($data, bool $batch = false)
	{
		if ($data) {
            // Insert in batch.
            if ($batch) {
                $insert = $this->db->insert_batch('queue_payments_orders_marketplace', $data);
                return $insert ? $this->db->insert_id() : false;
            }

			$insert = $this->db->insert('queue_payments_orders_marketplace', $data);
			return $insert ? $this->db->insert_id() : false;
		}
		return false;
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('queue_payments_orders_marketplace', $data);	
			return ($update == true) ? $id : false;
		}
		return false;
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('queue_payments_orders_marketplace');
			return ($delete == true) ? $id : false;
		}
		return false;
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('queue_payments_orders_marketplace', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}
	
	public function existProcessing($order_id, $status)
	{
		$sql = "SELECT * FROM queue_payments_orders_marketplace WHERE order_id = ? AND status=? LIMIT 1";
		$query = $this->db->query($sql, array($order_id, $status));
		return $query->row_array();

	}
	
	public function countQueue()
	{
		$sql = "SELECT count(*) as qtd FROM queue_payments_orders_marketplace";
		$query = $this->db->query($sql);
		return $query->row_array();
	}
	
	public function countOldRecords($date_old)
	{
		$sql = "SELECT count(*) as qtd FROM queue_payments_orders_marketplace WHERE date_created < ?";
		$query = $this->db->query($sql,array($date_old));
		return $query->row_array();
	}

	public function countQueueRunning()
	{
		$sql = "SELECT count(*) as qtd FROM queue_payments_orders_marketplace WHERE status=1";
		$query = $this->db->query($sql);
		return $query->row_array();
	}

	public function deleteOthers($id, $order_id)
	{
		$sql = "DELETE FROM queue_payments_orders_marketplace WHERE id != ? AND order_id = ? ";
		return $this->db->query($sql, array($id, $order_id));
	}

}