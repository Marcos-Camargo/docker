<?php 

class Model_ticket_b2w extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('ticket_b2w', $data);
			return ($insert == true) ? true : false;
		}
	}
	
	public function update($data, $order_code)
	{
		if($data && $id) {
			$this->db->where('order_code', $id);
			$update = $this->db->update('ticket_b2w', $data);
			return ($update == true) ? true : false;
		}
	}
	
	public function replace($data)
	{
		if($data) {
			$replace = $this->db->replace('ticket_b2w', $data);
			return ($replace == true) ? true : false;
		}
	}

	public function remove($order_code)
	{
		if($order_code) {
			$this->db->where('order_code', $order_code);
			$delete = $this->db->delete('ticket_b2w');
			return ($delete == true) ? true : false;
		}
	}
}