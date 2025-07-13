<?php
/*

Model de Acesso ao BD para tabela de queues_control

*/

class Model_queues_control extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('queues_control', $data);
			return ($insert == true) ? true : false;
		}
	}

    public function update($data, $process_name)
    {
        if($data && $process_name) {
            $this->db->where('process_name', $process_name);
            $update = $this->db->update('queues_control', $data);
        }
    }
	
	public function replace($data)
    {
        if($data) {
            $update = $this->db->replace('queues_control', $data);
        }
    }
	
	public function getData($process_name)
    {
        $sql = "SELECT * FROM queues_control WHERE process_name = ?";
        $query = $this->db->query($sql, array($process_name));
        return $query->row_array();
    }
	
	public function getQueuesWithProblems()
    {
        $sql = "SELECT * FROM queues_control WHERE date_update < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

}