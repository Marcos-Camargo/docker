<?php

class Model_suggestions_likes extends CI_Model
{
    const COMPANY_ID_CONECTA = 1;
    private $TABLE = 'suggestions_likes';
    public function __construct()
    {
        parent::__construct();
    }
    public function create($data)
    {
        $this->db->insert($this->TABLE, $data);
    }
    public function getAll()
    {
        $this->db->select('*');
        $this->db->from($this->TABLE);
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
        $this->db->delete($this->TABLE, array('id' => $id));
    }
    public function getByUserAndSuggestions($user_id,$suggestion_id){
        $this->db->select('*');
        $this->db->from($this->TABLE);
        $this->db->where('user_id', $user_id);
        $this->db->where('suggestion_id', $suggestion_id);
        $query = $this->db->get();
        return $query->row_array();
    }
    public function countBySuggestion($suggestion_id){
        $this->db->select('*');
        $this->db->from($this->TABLE);
        $this->db->where('suggestion_id', $suggestion_id);
        $query = $this->db->count_all_results();
        return $query;
    }
}