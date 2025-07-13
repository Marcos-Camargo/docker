<?php

class Model_suggestions extends CI_Model
{
    const COMPANY_ID_CONECTA = 1;
    private $TABLE = 'suggestions';
    private $active_status = 2;
    public function __construct()
    {
        parent::__construct();
    }
    public function create($data)
    {
        $this->db->insert($this->TABLE, $data);
    }
    public function getAll($title=null,$tags=null,$type=null,$categorie=null)
    {
        $this->db->select('*');
        $this->db->from($this->TABLE);
        $this->db->where('active', $this->active_status);
        if($title!=null){
            $this->db->like('title', $title);
        }
        if($tags!=null){
            $this->db->like('tags', $tags);
        }
        if($type!=null){
            $this->db->where('type', $type);
        }
        if($categorie!=null){
            $this->db->where('categorie', $categorie);
        }
        $this->db->like('tags', $tags);
        $query = $this->db->get();
        return $query->result_array();
    }
    public function getOne($id)
    {
        $this->db->select('*');
        $this->db->from($this->TABLE);
        $this->db->where('id', $id);
        $query = $this->db->get();
        return $query->row_array();
    }
    public function update($id, $data)
    {
        if (isset($data['id'])) {
            unset($data['id']);
        }
        $this->db->where('id', $id);
        $this->db->update($this->TABLE, $data);
    }
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->update($this->TABLE, array('active'=>1));
    }
}
