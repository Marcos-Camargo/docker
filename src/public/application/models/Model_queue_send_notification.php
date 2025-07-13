<?php 

class Model_queue_send_notification extends CI_Model
{
	public const  WAITING_SEND = 0;
	public const SENT = 1;
	public const IGNORED = 2;

	public function __construct()
	{
		parent::__construct();
	}

	
	public function list()
	{
		$sql = "select qsen.id, qsen.store_id, qsen.user_id,
				tent.status as status_rule,
				te.status as status_templatem,
				nt.status as status_trigger,
				te.subject, te.description,
				nt.identifier
			from queue_send_email_notification qsen
				join template_email_notification_trigger tent on tent.id = qsen.template_email_notification_trigger_id 
				join template_email te on te.id = tent.template_email_id 
				join notification_trigger nt on nt.id = tent.notification_trigger_id 
			where qsen.status = ? and qsen.date_send is null";
		$query = $this->db->query($sql, array(self::WAITING_SEND));
		return $query->result_array();
	}
	
	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('queue_send_email_notification', $data);
			return ($insert == true) ? $this->db->insert_id() : false;
		}
		return false;
	}

	public function updateStatus($id, $status)
	{
		if($status && $id) {
			$this->db->where('id', $id);
			$data = array();
			$data['status'] = $status;
			$update = $this->db->update('queue_send_email_notification', $data);	
			return ($update == true) ? $id : false;
		}
		return false;
	}
}
