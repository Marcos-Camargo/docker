<?php

class Model_contract_signatures extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }


    public function getDataById($id)
    {
        return $query = $this->db
            ->select(
                array(
                    'contract_signatures.*',
                    'contracts.contract_title',
                    'contracts.document_type',
                    'contracts.attachment',
                    'contracts.block',
                    'contracts.validity',
                    'stores.name as storeName',
                    'stores.CNPJ as storeCnpj'
                )
            )
            ->from('contract_signatures')
            ->join('contracts', 'contract_signatures.contract_id = contracts.id')
            ->join('stores', 'contract_signatures.store_id = stores.id')
            ->where(
                array(
                    'contract_signatures.id' => $id,
                )
            )
            ->get()
            ->row_array();
    }

    public function getAll($query = '1 = 1')
    {
        return     $query = $this->db
            ->select(
                array(
                    'contract_signatures.*',
                    'contracts.contract_title',
                    'contracts.document_type',
                    'contracts.attachment',
                    'contracts.block',
                    'stores.name as storeName',
                    'stores.CNPJ as storeCnpj'
                )
            )
            ->from('contract_signatures')
            ->join('contracts', 'contract_signatures.contract_id = contracts.id')
            ->join('stores', 'contract_signatures.store_id = stores.id')
            ->where($query)
            ->order_by('contract_signatures.id')
            ->get()
            ->result_array();
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert('contract_signatures', $data);
            return ($insert == true) ? true : false;
        }
        return false;
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('contract_signatures', $data);
            return ($update == true) ? true : false;
        }
        return false;
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('contract_signatures');
            return ($delete == true) ? true : false;
        }
        return false;
    }

    public function activeContracts($contractId)
    {
        if ($contractId) {
            $this->db->where('contract_id', $contractId);
            $update = $this->db->update('contract_signatures', array('active' => 1));
            return ($update == true) ? true : false;
        }
        return false;
    }

    public function inactiveContracts($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $update = $this->db->update('contract_signatures', array('active' => 0));
            return ($update == true) ? true : false;
        }
        return false;       
    }

    public function getAllCompanyContracts($store_ids)
    {
        $sql = "SELECT cs.id, cs.active, cs.store_id, c.block FROM `contract_signatures` AS cs JOIN `contracts` AS c ON cs.contract_id = c.id 
        WHERE cs.store_id IN ? AND cs.active = 1 AND cs.signature_date is NULL ORDER BY c.block DESC LIMIT 1";
        $query = $this->db->query($sql, array($store_ids));
        return $query->row_array();
    }

    public function getCountContracts($store_ids)
    {
        if($store_ids){
            $sql = "SELECT COUNT(id) as total FROM `contract_signatures` WHERE contract_signatures.store_id IN (". $store_ids.")";
            $query = $this->db->query($sql);
            return $query->row_array();
        }
        return array('total' => 0);

    }

    public function checkContractTypeIsAnticipationTransfer($id){
        $sql = "SELECT *, cs.store_id AS loja_id FROM contract_signatures cs 
        JOIN contracts c ON c.id = cs.contract_id 
        JOIN attribute_value av ON av.id = c.document_type
        WHERE cs.id = ? AND av.value LIKE ?";
        return $this->db->query($sql, array($id, "Contrato de Antecipação"))->row();        
    }

}
