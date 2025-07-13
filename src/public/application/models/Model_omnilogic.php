<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Integracoes
 
 */

class Model_omnilogic extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /* get the brand data */
    public function get($offer_id)
    {
        if($id) {
            $sql = "SELECT * FROM omnilogic where offer_id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
    }

    public function getList($channel) {
        if($channel) {
            $sql = "select o.* from products p 
                join omnilogic o on o.seller_offer_id = p.id
            where channel = ? and checked = 0 ";
            $query = $this->db->query($sql, array($channel));
            return $query->result_array();
        }
    }

    public function getToSend($int_to) {
        if($int_to) {
            $sql = "select c.id, c.prd_id, pti.id as id_pti 
            from omnilogic_offer_complete c 
                join prd_to_integration pti on pti.prd_id = c.prd_id 
                join products p on p.id = c.prd_id
            where 
                sent = 0 and 
                pti.int_to = ? and 
                p.status = 1 and 
                p.situacao = 2 and 
                pti.status = 1 and 
                pti.approved in (1, 3) and
                pti.skumkt is null ";
            // $sql = "select c.id, c.prd_id, pti.id as id_pti 
            // from products_category_mkt  c 
            //     join prd_to_integration pti on pti.prd_id = c.prd_id 
            //     join products p on p.id = c.prd_id
            // where 
            //     pti.int_to = 'MLC' and 
            //     p.status = 1 and 
            //     p.situacao = 2 and 
            //     pti.status = 1 and 
            //     pti.approved in (1, 3) and
            //     pti.skumkt is null 
            // group by c.id, c.prd_id, pti.id";
            $query = $this->db->query($sql, array($int_to));
            return $query->result_array();
        }   
    }

    public function markSentMkt($id) {
        if($id) {
			$sql = "UPDATE omnilogic_offer_complete SET sent = 1 WHERE id = ?";
            $result = $this->db->query($sql, array($id));
			return $id; 
		}
		return false;
    }

    public function markApproved($id) {
        if($id) {
			$sql = "UPDATE prd_to_integration SET approved = 1 WHERE id = ?";
            $result = $this->db->query($sql, array($id));
			return $id; 
		}
		return false;
    }

    public function sendOfferCategoryProblem($data) {
        if ($data) {
            $insert = $this->db->insert('omnilogic_offer_category_problem', $data);
            return ($insert == true) ? true : false;
        }
        return false;
    }

    public function setChecked($offer_id) {
        if($offer_id) {
			$sql = "UPDATE omnilogic SET checked = 1 WHERE id = ?";
            $result = $this->db->query($sql, array($offer_id));
			return $offer_id; 
		}
		return false;
    }
    
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('omnilogic', $data);
            return ($insert == true) ? true : false;
        }
    }

    public function insertAtributeNotFound($data) {
        if($data) {
            $insert = $this->db->insert('products_attribute_omni_not_found', $data);
            return ($insert == true) ? true : false;
        }
    }
    
    public function sent($prd_id) 
	{
		if($prd_id) {
			$sql = "UPDATE products SET omnilogic_status = 'SENT', omnilogic_date_sent = now() WHERE id = ?";
            $result = $this->db->query($sql, array($prd_id));
			return $prd_id; 
		}
		return false;
	}

    public function resent($prd_id) 
	{
		if($prd_id) {
			$sql = "UPDATE products SET omnilogic_status = 'RESENT', omnilogic_date_sent = now() WHERE id = ?";
            $result = $this->db->query($sql, array($prd_id));
			return $prd_id; 
		}
		return false;
	}

    public function received($prd_id) 
	{
		if($prd_id) {
			$sql = "UPDATE products SET omnilogic_status = 'RECEIVED', omnilogic_date_received = now() WHERE id = ?";
            $result = $this->db->query($sql, array($prd_id));
			return $prd_id; 
		}
		return false;
    }	
    
    public function unenriched($prd_id) 
	{
		if($prd_id) {
			$sql = "UPDATE products SET omnilogic_status = 'UNENRICHED' WHERE id = ?";
            $result = $this->db->query($sql, array($prd_id));
			return $prd_id; 
		}
		return false;
    }	
    
    public function enriched($prd_id) 
	{
		if($prd_id) {
			$sql = "UPDATE products SET omnilogic_status = 'ENRICHED' WHERE id = ?";
            $result = $this->db->query($sql, array($prd_id));
			return $prd_id; 
		}
		return false;
    }	

    public function save_log($data) {
        $insert = $this->db->insert('log_omnilogic', $data);
        return ($insert == true) ? true : false;
    }

    
}