<?php

class Model_jobs_logs extends CI_Model
{   
    var $monitor_db;

    public function __construct() 
    {
        parent::__construct();
        $this->monitor_db = $this->load->database('monitor', TRUE) ;
    }
    
    public function getEvents()
    {
        $sql = "SELECT * FROM jobs_logs ";
        $query = $this->monitor_db->query($sql);
        return $query->result_array();
    }
    
    public function getEventsid($id)
    {
        
        $sql = "SELECT * FROM jobs_logs WHERE id = ?";
        $query = $this->monitor_db->query($sql, array($id));
        return $query->row_array();

    }
        
    public function create($data)
    {
        if($data) {
            $insert = $this->monitor_db->insert('jobs_logs', $data);
            return ($insert) ? $this->monitor_db->insert_id() : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->monitor_db->where('id', $id);
            try {
                $update = $this->monitor_db->update('jobs_logs', $data);
                return ($update) ? true : false;
            }
            catch ( Exception $e ) {
                return false;
            }
        }
    }
    
    public function remove($id)
    {
        if($id) {
            $this->monitor_db->where('id', $id);
            try {
                $delete = $this->monitor_db->delete('jobs_logs');
                return ($delete) ? true : false;
            } catch (\Exception $e) {
                return false; 
            }
        }
    }

    public function RemoveAllSellercenter($sellercenter, $environment) {
        $sql = "DELETE FROM jobs_logs WHERE sellercenter =? AND environment = ?";
        $this->monitor_db->query($sql, array($sellercenter, $environment ));
    }

    public function getJob($sellercenter, $job_id)
    {

        $sql = "SELECT * FROM jobs_logs WHERE sellercenter=? AND job_id=? AND date_reference = ? AND environment = ?";
        $query = $this->monitor_db->query($sql,array($sellercenter, $job_id, date("Ymd"), ENVIRONMENT));
        return $query->row_array();
    }

}