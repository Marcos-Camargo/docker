<?php 

class Model_queue_products_marketplace extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	
	public function getData($id = null)
	{
		if($id) {
			$sql = "SELECT * FROM queue_products_marketplace WHERE id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "SELECT * FROM queue_products_marketplace";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getDataNext($limit = 1, $problem = null)
	{
		if (is_null($problem)) {
			$sql = "SELECT * FROM queue_products_marketplace WHERE status=0 ORDER BY date_update ASC LIMIT ".$limit;
			$query = $this->db->query($sql);
		}
		else {
			$sql = "SELECT * FROM queue_products_marketplace WHERE status=0 AND date_create > ? ORDER BY date_update ASC LIMIT ".$limit;
			$query = $this->db->query($sql,array($problem));
		}
		
		return $query->result_array();
	}

	public function getByPrdIntTo($prd_id, $int_to)
	{
		$sql = "SELECT * FROM queue_products_marketplace WHERE prd_id = ? AND int_to = ?";
		$query = $this->db->query($sql, [$prd_id, $int_to]);
		return $query->result_array();
	}

	public function getDataNextNew($limit = 1, $problem = null, $int_to = null)
	{
		$where_int_to = '';
		if (!is_null($int_to)) {
			$where_int_to = ' AND int_to = "'.$int_to.'" ';
		}

		if (is_null($problem)) {
			$sql = "SELECT * FROM queue_products_marketplace WHERE status=0 ".$where_int_to." ORDER BY id ASC LIMIT ".$limit;
			$query = $this->db->query($sql);
		}
		else {
			$sql = "SELECT * FROM queue_products_marketplace WHERE status=0 AND id > ? ".$where_int_to." ORDER BY id ASC LIMIT ".$limit;
			$query = $this->db->query($sql,array($problem));
		}
		
		return $query->result_array();
	}

	public function getWithoutIntTo($limit = 1){
		$sql = "SELECT * FROM queue_products_marketplace WHERE int_to IS NULL LIMIT ?";
		$query = $this->db->query($sql,[$limit]);
		return $query->result_array();
	}
	
	public function getDataAllDelayed()
	{
		$sql = "SELECT * FROM queue_products_marketplace WHERE status=1 AND date_update < DATE_SUB(NOW(), INTERVAL 5 MINUTE) ORDER BY date_update ASC ";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function updateAllDelayed()
	{
		$sql = "UPDATE queue_products_marketplace SET status=0 WHERE status=1 AND date_update < DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
		$query = $this->db->query($sql);
		return $query;
	}

	public function create($data, bool $batch = false)
	{
		if ($data) {
            // Insert in batch.
            if ($batch) {
                $insert = $this->db->insert_batch('queue_products_marketplace', $data);
                return $insert ? $this->db->insert_id() : false;
            }

			$insert = $this->db->insert('queue_products_marketplace', $data);
			return $insert ? $this->db->insert_id() : false;
		}
		return false;
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('queue_products_marketplace', $data);	
			return ($update == true) ? $id : false;
		}
		return false;
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('queue_products_marketplace');
			return ($delete == true) ? $id : false;
		}
		return false;
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('queue_products_marketplace', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}
	
	public function existProcessing($prd_id, $int_to, $status)
	{
		$sql = "SELECT * FROM queue_products_marketplace WHERE prd_id = ? AND int_to = ? AND status=? LIMIT 1";
		$query = $this->db->query($sql, array($prd_id, $int_to, $status));
		return $query->row_array();

	}
	
	public function removePrdIdNull($prd_id)
	{
		$sql = "DELETE FROM queue_products_marketplace WHERE prd_id = ? AND int_to is null";
		return $this->db->query($sql, array($prd_id));
	}
	
	public function countQueue()
	{
		$sql = "SELECT count(*) as qtd FROM queue_products_marketplace";
		$query = $this->db->query($sql);
		return $query->row_array();
	}
	
	public function countOldRecords($date_old)
	{
		$sql = "SELECT count(*) as qtd FROM queue_products_marketplace WHERE date_create < ?";
		$query = $this->db->query($sql,array($date_old));
		return $query->row_array();
	}

	public function countQueueRunning()
	{
		$sql = "SELECT count(*) as qtd FROM queue_products_marketplace WHERE status=1";
		$query = $this->db->query($sql);
		return $query->row_array();
	}

	public function deleteOthers($id, $int_to, $prd_id)
	{
		$sql = "DELETE FROM queue_products_marketplace WHERE id != ? AND int_to = ? AND prd_id = ? ";
		return $this->db->query($sql, array($id, $int_to, $prd_id));
	}

	public function deleteByIntTo(string $int_to)
	{
        return $this->db->delete('queue_products_marketplace', array('int_to' => $int_to));
	}

	public function updateAllDelayedIntTo($int_to)
	{
		$sql = "UPDATE queue_products_marketplace SET status=0 WHERE status=1 AND int_to = ? AND date_update < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
		$query = $this->db->query($sql,array($int_to));
		return $query;
	}

	public function resetStaleQueueItems($int_to, $minutes)
	{
		$sql = "UPDATE queue_products_marketplace SET status=0 WHERE status=1 AND int_to = ? AND date_update < DATE_SUB(NOW(), INTERVAL ? MINUTE)";
		$query = $this->db->query($sql, [$int_to, $minutes]);
		return $query;
	}
}