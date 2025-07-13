<?php 

class Model_ticket_ml extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('ticket_ml', $data);
			return ($insert == true) ? true : false;
		}
	}
	
	public function update($data, $order_code)
	{
		if($data && $id) {
			$this->db->where('order_code', $id);
			$update = $this->db->update('ticket_ml', $data);
			return ($update == true) ? true : false;
		}
	}
	
	public function replace($data)
	{
		if($data) {
			$replace = $this->db->replace('ticket_ml', $data);
			return ($replace == true) ? true : false;
		}
	}

}