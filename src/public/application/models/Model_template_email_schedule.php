<?php
/* 
 Model de Acesso ao BD para Templates de Email Schedule
 
 */

class Model_template_email_schedule extends CI_Model
{
    protected $results;
    const TABLE = 'template_email_notification_trigger';

    public function __construct()
    {
        parent::__construct();
    }

    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('template_email_notification_trigger', $data);
            return ($update == true) ? true : false;
        }
    }

    public function create($data = '')
    {
        $create = $this->db->insert('template_email_notification_trigger', $data);
        return ($create == true) ? $this->db->insert_id() : false;
    }
	
	public function getTemplatesScheduleDataCount($procura = '')
	{
        $sql = "SELECT count(1) FROM template_email_notification_trigger";
        if ($procura != '') {
            $sql .= ' where 1 = 1 '. $procura;
        }
        $query = $this->db->query($sql);
        return $query->row_array();
	}

    public function getTemplateNotificationData($id = null)
    {
        $sql = "SELECT * FROM template_email_notification_trigger";
        if ($id) {
            $sql = "SELECT * FROM template_email_notification_trigger WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
        
        $query = $this->db->query($sql, array($this->data['usercomp']));
        return $query->result_array();
    }

    public function getTemplatesScheduleView($offset = 0, $procura = '', $orderby = '', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }
        if (($this->data['user_group_id'] == 1) || $this->data['only_admin'] == 1) {
            $sql = "SELECT  tnt.id, nt.name,te.title,  te.subject, tnt.status FROM template_email_notification_trigger as tnt 
                        JOIN template_email te ON tnt.template_email_id = te.id
                        JOIN notification_trigger nt ON tnt.notification_trigger_id = nt.id";
        } else {
            $sql = "SELECT  tnt.id, nt.name,te.title,  te.subject, tnt.status FROM template_email_notification_trigger as tnt 
                        JOIN template_email te ON tnt.template_email_id = te.id
                        JOIN notification_trigger nt ON tnt.notification_trigger_id = nt.id";
        }
        $sql .= $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getTemplatesScheduleData()
	{
        $sql = "SELECT  tnt.id, nt.name,te.title,  te.subject, tnt.status FROM template_email_notification_trigger as tnt 
        JOIN template_email te ON tnt.template_email_id = te.id
        JOIN notification_trigger nt ON tnt.notification_trigger_id = nt.id";
		$query = $this->db->query($sql);
        $query = $query->result();
        $template_schedule = [];
        foreach ($query as $key => $value) {
           $template_schedule[$key] = $value;
        }
    
		return $template_schedule;
	}
    public function getNotificationTriggerSchedule()
    {
        return $this->db->select('notification_trigger_id, status')->from(self::TABLE)->get()->result_array();
    }
}