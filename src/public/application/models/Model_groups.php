<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Grupos (controle de acesso)
 
 */

class Model_groups extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getGroupData($groupId = null)
    {
        if($groupId) {
            $sql = "SELECT * FROM `groups` WHERE id = ?";
            $query = $this->db->query($sql, array($groupId));
            return $query->row_array();
        }
        
        if ($this->data['user_group_id'] == 1) {
            $sql = "SELECT * FROM `groups` ORDER BY group_name";
        }elseif ($this->data['only_admin'] == 1) {
            $sql = "SELECT * FROM `groups` where id != 1 ORDER BY group_name";
        }else {
            $sql = "SELECT * FROM `groups` where only_admin = 0 ORDER BY group_name";
        }
        $query = $this->db->query($sql, array($this->data['usercomp']));
        return $query->result_array();
    }
    
    public function create($data = '')
    {
        $create = $this->db->insert('groups', $data);
        return ($create) ? $this->db->insert_id()  : false;
    }
    
    public function edit($data, $id)
    {
        $this->db->where('id', $id);
        $update = $this->db->update('groups', $data);
        return ($update) ?  $id : false;
    }
    
    public function delete($id)
    {
        $this->db->where('id', $id);
        $delete = $this->db->delete('groups');
        return ($delete) ? true : false;
    }
    
    public function existInUserGroup($id)
    {
        $sql = "SELECT * FROM user_group WHERE group_id = ?";
        $query = $this->db->query($sql, array($id));
        return ($query->num_rows() == 1) ? true : false;
    }
    
    public function getUserGroupByUserId($user_id)
    {
        $sql = "SELECT * FROM user_group
		INNER JOIN `groups` ON `groups`.id = user_group.group_id
		WHERE user_group.user_id = ?";
        $query = $this->db->query($sql, array($user_id));
        return $query->row_array();        
    }
	
	public function getGroupDataByName($group_name)
    {
        $sql = "SELECT * FROM `groups` WHERE upper(group_name) = ?";
        $query = $this->db->query($sql, array(strtoupper($group_name)));
        return $query->row_array();
      
    }
}