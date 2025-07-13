<?php
/*
 
 Model de link de treinamento (youtube)
 
 */

class Model_user_link_training extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getUserLinkTrainingByLink($link = '')
    {
        if($link) {
            $sql = "SELECT * FROM user_link_training WHERE link = ?";
            $query = $this->db->query($sql, array($link));
            return $query->row_array();
        }
    }
    
    public function create($data = '')
    {
        $create = $this->db->insert('user_link_training', $data);
        return ($create == true) ? true : false;
    }
    
    public function edit($data, $id)
    {
        $this->db->where('id', $id);
        $update = $this->db->update('user_link_training', $data);
        return ($update == true) ? true : false;    
    }

    public function editByModuleClass($data)
    {
        $this->db->where('module', $data['module']);
        $this->db->where('class', $data['class']);
        $update = $this->db->update('user_link_training', $data);
        return $data['link'];    
    }
    
    public function delete($id)
    {
        $this->db->where('id', $id);
        $delete = $this->db->delete('user_link_training');
        return ($delete == true) ? true : false;
    }
    
    public function getLinkTrainingVideo($method, $class)
	{
		$sql="SELECT u.* FROM user_link_training u WHERE u.module = ? and u.class = ?";
		$result=$this->db->query($sql, array($method , $class));
        $resultLink = $result->row_array();

		if($resultLink != null){
			return $resultLink['link'];
		}else{
			return false;
		}
	}
}