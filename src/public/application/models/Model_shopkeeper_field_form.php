<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Formulario lojista
 
 */

class Model_shopkeeper_field_form extends CI_Model
{

    public function __construct() 

    {
        parent::__construct();
    }
    
    public function getFieldsbySellerCenter($id, $type = null)
    {
        if($id) {
            $sql = "SELECT * FROM shopkeeper_field_form WHERE sellercenter = ? AND visible = 1";
            if($type != null)
            {
                $sql .= " AND type in (?)";
                $query = $this->db->query($sql, array($id,$type));
            }else
            {
                $query = $this->db->query($sql, array($id));
            }
            return $query->result_array();
        }
        
        $sql = "SELECT * FROM shopkeeper_field_form";
        $query = $this->db->query($sql);
        return $query->result_array();
    }


    
    public function createField($data)
    {
        if($data) {
            $insert = $this->db->insert('shopkeeper_field_form', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }
    
    public function updateField($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('shopkeeper_field_form', $data);
            return ($update == true) ? true : false;
        }
    }
    
    public function removeField($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('shopkeeper_field_form');
            return ($delete == true) ? true : false;
        }
    }
    
	
	public function getFieldDatabyId($id)
    {
        $sql = "SELECT * FROM shopkeeper_field_form WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

}