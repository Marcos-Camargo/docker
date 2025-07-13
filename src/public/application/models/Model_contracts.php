<?php

class Model_contracts extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }


    public function getDataById($id)
    {
        $sql = "SELECT * FROM `contracts` WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

    public function getAll($query = '1 = 1')
    {
        $sql = "SELECT * FROM `contracts` WHERE ".$query;
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert('contracts', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
        return false;
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('contracts', $data);
            return ($update == true) ? true : false;
        }
        return false;
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('contracts');
            return ($delete == true) ? true : false;
        }
        return false;
    }

    public function addLog($data)
    {
        if ($data) {
            $insert = $this->db->insert('log_contracts', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
        return false;
    }

    public function getCountContracts()
    {
        $sql = "SELECT COUNT(id) as total FROM `contracts`";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function getExpiredContracts()
    {
        $sql = "SELECT id FROM contracts where validity < NOW() AND active = 1 AND block = 0";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function checkContractSignatureStore($store_id, $document_type){
        $sql = "SELECT * FROM contracts c 
        JOIN contract_signatures cs ON cs.contract_id = c.id 
        WHERE cs.store_id = ? AND document_type = ? AND cs.active = ?";
        $query = $this->db->query($sql, array($store_id, $document_type, 1));        
        return $query->num_rows() > 0 ? true : false;
    }

    public function checkNewContractIsAnticipationTransfer($id){
        $sql = "SELECT * FROM contracts c         
        JOIN attribute_value av ON av.id = c.document_type
        WHERE c.id = ? AND av.value LIKE ?";
        return $this->db->query($sql, array($id, "Contrato de Antecipação"))->row();        
    }

}
