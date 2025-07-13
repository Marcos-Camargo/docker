<?php
/*
  
 Model de Acesso ao BD shopify_new_stores
 
 */

class Model_shopify_new_stores extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('shopify_new_stores', $data);
            return ($insert == true) ? true : false;
        }
    }

    public function update_creation_status($data)
    {

        if($data) {
			$sql = "UPDATE shopify_new_stores SET creation_status = ? WHERE creation_status = ? AND company_id = ? ORDER BY store_creation_date DESC LIMIT ?";
			$query = $this->db->query($sql,[1,0,$data['company_id'],1]);
			}  
    }

    public function getShopifyStoresDataView($offset = 0, $procura = '', $orderby = '', $limit = 200)
	{
		if ($offset == '') {
			$offset = 0;
		}
		if ($limit == '') {
			$limit = 200;
		}
		
        $sql = "SELECT * FROM shopify_new_stores s";
        $sql .= $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;

		$query = $this->db->query($sql);

		return $query->result_array();
	}
 	
    public function getShopifyStoresDataCount($procura = '')
	{
		if ($procura == '') {
			$sql = "SELECT count(*) as qtd FROM shopify_new_stores ";
		} else {
			$sql = "SELECT count(*) as qtd FROM shopify_new_stores s";
			$sql .= $procura;
		}

		$query = $this->db->query($sql, array());
		$row = $query->row_array();

		return $row['qtd'];
	}

	public function getCreatedStores()
	{
		
		$sql = "SELECT * FROM shopify_new_stores WHERE creation_status = ?";
		$query = $this->db->query($sql,1);

		if($query == []){
			return false;
			echo "nao encontrou dados na tabela do banco de dados" ;
		}

		return $query->result_array();

	}

}