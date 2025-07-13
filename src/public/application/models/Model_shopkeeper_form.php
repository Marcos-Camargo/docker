<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Formulario lojista
 
 */

class Model_shopkeeper_form extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /*get the active brands information*/
    public function getActiveForms()
    {
        $sql = "SELECT * FROM shopkeeper_form WHERE active = ? ORDER BY name";
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }
    
    /* get the brand data */
    public function getFormDatabySellerCenter($sellercenter)
    {
        $sql = "SELECT * FROM shopkeeper_form WHERE sellercenter = ?";
        $query = $this->db->query($sql, array($sellercenter));

        return $query->result_array();
    }

    /* get the brand data */
    public function getFormDatabyId($id = null, $data = '')
    {
        if($id) {
            if($data == ''){
                $sql = "SELECT * FROM shopkeeper_form WHERE id = ?";
                $query = $this->db->query($sql, array($id));
                return $query->row_array();
            }else{

                $this->db->where('id', $id);
                $update = $this->db->update('shopkeeper_form', $data);

                $sql = "SELECT * FROM shopkeeper_form WHERE id = ?";
                $query = $this->db->query($sql, array($id));
                return $query->row_array();
            }
        }

        $sql = "SELECT * FROM shopkeeper_form";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('shopkeeper_form', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('shopkeeper_form', $data);
            return ($update == true) ? true : false;
        }
    }

    public function updateBySellerCenter($data, $sellercenter)
    {
        if($data && $sellercenter) {
            $this->db->where('sellercenter', $sellercenter);
            $update = $this->db->update('shopkeeper_form', $data);
            return ($update == true) ? true : false;
        }
    }
    
    public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('shopkeeper_form');
            return ($delete == true) ? true : false;
        }
    }

    
    public function reproved($id, $id_reason)
    {
        if($id) {
            $this->db->where('id', $id);
            $update = $this->db->update('shopkeeper_form', array('status' => 3, 'attribute_value_id' => $id_reason));
            return ($update == true) ? true : false;
        }
    }

    public function aproved($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $update = $this->db->update('shopkeeper_form', array('status' => 2));
            return ($update == true) ? true : false;
        }
    }

    /* get the emails data */
    public function lookForEmail(string $email, int $status = 2): array
    {
        if($email){
            $sql = "SELECT responsible_email FROM shopkeeper_form WHERE responsible_email = ? AND status = ?";
            $query = $this->db->query($sql, array($email, $status));
            return $query->result_array();
        }
    }

}