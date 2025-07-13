<?php

class Model_monitor_events extends CI_Model
{   
    var $monitor_db;

    public function __construct()
    {
        parent::__construct();
        $this->monitor_db = $this->load->database('monitor', TRUE) ;
    }
    
    public function getEvents()
    {
        $sql = "SELECT * FROM events ";
        $query = $this->monitor_db->query($sql);
        return $query->result_array();
    }
    
    public function getEventsid($id)
    {
        
        $sql = "SELECT * FROM events WHERE id = ?";
        $query = $this->monitor_db->query($sql, array($id));
        return $query->row_array();

    }
    
    public function getEvent($sellercenter, $environment, $subject, $event_name, $validity)
    {

        $sql = "SELECT * FROM events WHERE sellercenter = ? AND environment =? AND subject=? AND event_name = ? AND validity =?";
        $query = $this->monitor_db->query($sql, array($sellercenter,$environment, $subject, $event_name, $validity));
        return $query->row_array();

    }
    
    public function create($data)
    {
        if($data) {
            $insert = $this->monitor_db->insert('events', $data);
            return ($insert) ? $this->monitor_db->insert_id() : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->monitor_db->where('id', $id);
            try {
                $update = $this->monitor_db->update('events', $data);
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
                $delete = $this->monitor_db->delete('events');
                return ($delete) ? true : false;
            } catch (\Exception $e) {
                return false; 
            }
        }
    }

    public function getEventsBySellercenterValidity($sellercenter, $environment, $validity)
    {

        $sql = "SELECT * FROM events WHERE sellercenter = ? AND environment =? AND validity =? ORDER BY subject, event_name";
        $query = $this->monitor_db->query($sql, array($sellercenter,$environment, $validity));
        return $query->result_array();

    }

    public function getEventsByValidity($validity)
    {

        $sql = "SELECT * FROM events WHERE validity =? ORDER BY sellercenter, subject, event_name";
        $query = $this->monitor_db->query($sql, array($validity));
        return $query->result_array();

    }

}

