<?php

class Model_monitor_logistic_summary extends CI_Model
{   
    var $monitor_db;

    public function __construct()
    {
        parent::__construct();
        $this->monitor_db = $this->load->database('monitor', TRUE) ;
    }
    
    public function getLogisticSummaryControls()
    {
        $sql = "SELECT * FROM logistic_summary_control ";
        $query = $this->monitor_db->query($sql);
        return $query->result_array();
    }
    
    public function getLogisticSummaryControlId($id)
    {
        
        $sql = "SELECT * FROM logistic_summary_control WHERE id = ?";
        $query = $this->monitor_db->query($sql, array($id));
        return $query->row_array();

    }
    
    public function getLogisticSummaryControl($sellercenter, $environment)
    {

        $sql = "SELECT * FROM logistic_summary_control WHERE sellercenter = ? AND environment =? ";
        $query = $this->monitor_db->query($sql, array($sellercenter,$environment));
        return $query->row_array();

    }
    
    public function createLogisticSummaryControl($data)
    {
        if($data) {
            $insert = $this->monitor_db->insert('logistic_summary_control', $data);
            return ($insert) ? $this->monitor_db->insert_id() : false;
        }
    }
    
    public function updateLogisticSummaryControl($data, $id)
    {
        if($data && $id) {
            $this->monitor_db->where('id', $id);
            try {
                $update = $this->monitor_db->update('logistic_summary_control', $data);
                return ($update) ? true : false;
            }
            catch ( Exception $e ) {
                return false;
            }
        }
    }
    
    public function removeLogisticSummaryControl($id)
    {
        if($id) {
            $this->monitor_db->where('id', $id);
            try {
                $delete = $this->monitor_db->delete('logistic_summary_control');
                return ($delete) ? true : false;
            } catch (\Exception $e) {
                return false; 
            }
        }
    }


    public function getLogisticSummaries()
    {
        $sql = "SELECT * FROM logistic_summary ";
        $query = $this->monitor_db->query($sql);
        return $query->result_array();
    }
    
    public function getLogisticSummaryId($id)
    {
        
        $sql = "SELECT * FROM logistic_summary WHERE id = ?";
        $query = $this->monitor_db->query($sql, array($id));
        return $query->row_array();

    }
    
    public function getLogisticSummary($sellercenter, $environment, $marketplace, $integration, $date)
    {

        $sql = "SELECT * FROM logistic_summary WHERE sellercenter = ? AND environment = ? AND marketplace = ? AND integration = ?  AND date = ? ";
        $query = $this->monitor_db->query($sql, array($sellercenter, $environment, $marketplace, $integration, $date));
        return $query->row_array();

    }
    
    public function createLogisticSummary($data)
    {
        if ($data) {
            $insert = $this->monitor_db->insert('logistic_summary', $data);
            return ($insert) ? $this->monitor_db->insert_id() : false;
        }
    }
    
    public function updateLogisticSummary($data, $id)
    {
        if($data && $id) {
            $this->monitor_db->where('id', $id);
            try {
                $update = $this->monitor_db->update('logistic_summary', $data);
                return ($update) ? true : false;
            }
            catch ( Exception $e ) {
                return false;
            }
        }
    }
    
    public function removeLogisticSummary($id)
    {
        if($id) {
            $this->monitor_db->where('id', $id);
            try {
                $delete = $this->monitor_db->delete('logistic_summary');
                return ($delete) ? true : false;
            } catch (\Exception $e) {
                return false; 
            }
        }
    }

}