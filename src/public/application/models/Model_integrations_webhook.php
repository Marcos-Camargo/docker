<?php

class Model_integrations_webhook extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }


    public function getData($id = null, $supplier = false)
	{
    
		if ($id && !$supplier) {
			$sql = "SELECT url, type_webhook, store_id FROM integrations_webhook WHERE store_id = ? AND is_supplier = 0";
			$query = $this->db->query($sql, array($id));
			return $query->result_array();
		}

        if($id && $supplier){
            $sql = "SELECT url, type_webhook FROM integrations_webhook WHERE id_supplier = ? AND is_supplier = 1 GROUP BY url";
			$query = $this->db->query($sql, array($id));
			return $query->result_array();
        }

		$sql = "SELECT * FROM integrations_webhook";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
    

    public function updateOrInsert(array $array)
    {

        foreach ($array as $data) {
            if ($data['is_supplier'] == 1) {
                // Se for um fornecedor, usamos id_supplier para exclusão
                $supplier_id = $data['id_supplier'];
                $this->db->where('id_supplier', $supplier_id);
            } else {
                // Se não for um fornecedor, usamos store_id para exclusão
                $store_id = $data['store_id'];
                $this->db->where('store_id', $store_id);
                $this->db->where('is_supplier', 0);
            }
            $this->db->delete('integrations_webhook');
        }

        foreach ($array as $data) {
                   
            $this->db->insert('integrations_webhook', $data);
            
        }

        return true;
    }


    public function getDataToSend($store_id = null)
	{
    
		if ($store_id) {
			$sql = "SELECT url, type_webhook, store_id FROM integrations_webhook WHERE store_id = ? GROUP BY url";
			$query = $this->db->query($sql, array($store_id));
			return $query->result_array();
		}

        return false;
	}

    public function getDataToSendSupplier($store_id = null)
	{
    
        if ($store_id) {
            $sql = "SELECT e.provider_id FROM integrations_webhook c 
                    INNER JOIN stores e ON e.id = c.store_id 
                    WHERE c.store_id = ? ";
            $query = $this->db->query($sql, array($store_id));
            return $query->result_array();
        }

        return false;
	}


    public function getDataUnique($id = null, $supplier = false, $urlCallback)
	{
    
		if ($id && !$supplier) {
			$sql = "SELECT url, type_webhook, store_id FROM integrations_webhook WHERE store_id = ? AND is_supplier = 0 AND url = ? limit 1";
			$query = $this->db->query($sql, array($id,$urlCallback));
			return $query->row_array();
		}

        if($id && $supplier){
            $sql = "SELECT url, type_webhook FROM integrations_webhook WHERE id_supplier = ? AND is_supplier = 1 AND url = ? GROUP BY url limit 1";
			$query = $this->db->query($sql, array($id,$urlCallback));
			return $query->row_array();
        }

	}

    public function getDataUniqueProvider($id = null, $supplier = false, $urlCallback)
	{
    
        if($id && $supplier){
            $sql = "SELECT url, type_webhook FROM integrations_webhook WHERE id_supplier = ? AND is_supplier = 1 AND url = ? ";
			$query = $this->db->query($sql, array($id,$urlCallback));
			return $query->result_array();
        }

	}

    public function deleteUrlCallback($id = null, $supplier = false, $urlCallback)
	{
    
		if ($id && !$supplier) {
			$sql = "DELETE FROM integrations_webhook WHERE store_id = ? AND is_supplier = 0 AND url = ? limit 1";
			$query = $this->db->query($sql, array($id,$urlCallback));
			return $this->db->affected_rows() > 0;
		}

        if($id && $supplier){
            $sql = "DELETE FROM integrations_webhook WHERE id_supplier = ? AND is_supplier = 1 AND url = ?";
			$query = $this->db->query($sql, array($id,$urlCallback));
			return $this->db->affected_rows() > 0;
        }

	}


    public function storeExists($store_id = null)
    {
        if ($store_id) {
            $sql = "SELECT COUNT(*) as count FROM integrations_webhook WHERE store_id = ?";
            $query = $this->db->query($sql, array($store_id));
            $result = $query->row_array();

            if ($result['count'] > 0) {
                return true;
            }
        }

        return false;
    }

}
