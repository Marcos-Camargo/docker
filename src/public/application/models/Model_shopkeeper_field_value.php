<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Formulario lojista
 */

class Model_shopkeeper_field_value extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /*get the active brands information*/
    public function getActiveForms()
    {
        $sql = "SELECT * FROM shopkeeper_field_value WHERE active = ? ORDER BY name";
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }
    
    /* get the brand data */
    public function getBrandData($id = null)
    {
        if($id) {
            $sql = "SELECT * FROM shopkeeper_field_value WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
        
        $sql = "SELECT * FROM shopkeeper_field_value";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    public function getFieldValueByFormIdandType($id = null, $type)
    {
        if($id) {
            $fields = "";
            if($type == 1 || $type == 2 )
            {
                $fields = "FF.required, FF.type, ";
            }
            $sql = "SELECT {$fields} FV.field_value , FF.label FROM shopkeeper_field_value FV inner join shopkeeper_field_form FF 
            ON FV.field_form_id = FF.id WHERE FV.form_id = ? AND FF.type = ?";

            $query = $this->db->query($sql, array($id, $type));
            return $query->result_array();
        }
        
        $sql = "SELECT * FROM shopkeeper_field_value";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('shopkeeper_field_value', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('shopkeeper_field_value', $data);
            return ($update == true) ? true : false;
        }
    }
    
    public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('shopkeeper_field_value');
            return ($delete == true) ? true : false;
        }
    }
    
	
	public function getFieldDatabyId($name)
    {
        $sql = "SELECT * FROM shopkeeper_field_value WHERE field_form_id = ?";
        $query = $this->db->query($sql, array($name));
        return $query->row_array();
    }

    public function removeByFieldValueAndFieldFormIdAndFormId($field_value, $field_form_id, $form_id)
    {
        return $this->db->where(array(
            'field_value' => $field_value,
            'field_form_id' => $field_form_id,
            'form_id' => $form_id
        ))->delete('shopkeeper_field_value');
    }


}