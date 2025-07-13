<?php
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Calendario de Jobs

*/  

class Model_Calendar extends CI_Model
{
	public function get_events($start, $end)
	{
	    return $this->db->where("start >=", $start)->where("start <=", $end)->where("start <=", $end)->or_where("end >=", $start)->get("calendar_events");
	}
	
	public function add_event($data)
	{
	    $create = $this->db->insert("calendar_events", $data);
		return ($create) ? $this->db->insert_id() : create;
	}
	
	public function get_event($id)
	{
	    return $this->db->where("ID", $id)->get("calendar_events");
	}

	public function delete_eventByModuleMethod($module_path, $module_method)
	{
		$sql = "DELETE FROM calendar_events WHERE module_path = '".$module_path."' AND module_method = '".$module_method."'";
		return $this->db->query($sql);
	}
	
	public function update_event($id, $data)
	{
	    return $this->db->where("ID", $id)->update("calendar_events", $data);
	}
	
	public function delete_event($id)
	{
	    return $this->db->where("ID", $id)->delete("calendar_events");
	}

	public function get_newevents($start)
	{
	    return $this->db->where("start <=", $start)->where("end >=", $start)->order_by('event_type DESC','DATE_FORMAT(start, "%H%i%s") ASC')->get("calendar_events");
	}

	public function add_job($data)
	{
	    $this->db->insert("job_schedule", $data);
	}

    public function readyToRun($hm): CI_DB_result
    {
        $this->db->where('status', 0)
            ->where('TIMEDIFF(date_start, NOW()) BETWEEN -539 AND 0', null, false)
            ->update('job_schedule', array(
                'status' => 4,
                'error_msg' => $hm
            ));

        $subQuery = $this->db->select('s.id')
            ->where('j.server_id = s.server_id')
            ->where('s.status',4)
            ->where('s.error_msg !=', $hm)
            ->get_compiled_select('job_schedule s');

        $this->db->select('j.*, ce.event_type')
            ->join('calendar_events ce', 'ce.ID = j.server_id', 'left')
            ->where('j.error_msg', $hm)
            ->where("j.id NOT IN ($subQuery)");

        return $this->db->get('job_schedule j');
    }
	
	public function get_jobs($query)
	{
	    return $this->db->where($query)->get("job_schedule");
	}
	public function update_job($id, $data)
	{
	    $this->db->where("ID", $id)->update("job_schedule", $data);
	}
	
	public function getEventOpen($module, $method, $params = null)
	{
	    if ($params == null) {$params = 'null';}

		$sql = "SELECT * FROM job_schedule WHERE module_path = ? AND module_method = ? AND params = ? AND status = 1";
		$query = $this->db->query($sql, array($module, $method, $params));
		return count($query->result_array());
	}

    public function getEventModuleParam($module, $param)
    {
        $sql = "SELECT * FROM calendar_events where module_path = ? AND params = ?";
        $query = $this->db->query($sql, array($module, $param));
        return $query->row_array();
    }

    public function delete_job($server_id)
    {
        $this->db->where("server_id", $server_id)->delete("job_schedule");
    }

    public function deleteEventByParams($params)
    {
        if ($params) {
            $this->db->where("params", $params)->delete("calendar_events");
        }
    }

    public function deleteJobByParams($params)
    {
        if ($params) {
            $this->db->where("params", $params)->delete("job_schedule");
        }
    }
	
	 public function deleteJobsByModuleAndParams($module_path,$params)
    {
        if (($params) && ($module_path )) {
            $this->db->where("module_path", $module_path)->where("params", $params)->delete("job_schedule");
        }
    }

	public function getCalendarDataView($offset = 0, $procura = '', $orderby = '', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }
        if ($procura != '') {
            $procura = ' WHERE '.substr($procura,5);
        }
        $sql = "SELECT *, DATE_FORMAT(start, '%H:%i') as starttime FROM calendar_events ";

        $sql .= $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCalendarDataCount($procura = '')
    {
        if ($procura != '') {
            $procura = ' WHERE '.substr($procura,5);
        }
        $sql = "SELECT count(*) as qtd FROM calendar_events ".$procura;

        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

	public function getCalendarById($id)
	{
		$sql = "SELECT * FROM calendar_events where ID = ? ";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
	}

	public function get_event_array($id)
	{
		$sql = "SELECT * FROM calendar_events WHERE ID = ?";
	    $query = $this->db->query($sql, array($id));
	    return $query->row_array();
	}

	public function resetJobs($hm)
	{
		echo "Reset nos jobs que não executaram\n";

		$sql = "UPDATE job_schedule SET status='7' WHERE status=4 AND error_msg=?";
		$this->db->query($sql,array($hm));
		echo $this->db->last_query(). "\n";
	}

	public function getEventStatus($server_id, $status, $hm)
	{

		$sql = "SELECT * FROM job_schedule WHERE server_id = ? AND status = ? AND error_msg < ?";
		$query = $this->db->query($sql, array($server_id, $status, $hm));
		return count($query->result_array());
	}

}

?>