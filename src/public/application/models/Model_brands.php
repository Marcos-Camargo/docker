<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Marcas/Fabricantes
 
 */

class Model_brands extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /*get the active brands information*/
    public function getActiveBrands()
    {
        $sql = "SELECT * FROM brands WHERE active = ? ORDER BY name";
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }
    
    /* get the brand data */
    public function getBrandData($id = null)
    {
        if($id) {
            $sql = "SELECT * FROM brands WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
        
        $sql = "SELECT * FROM brands";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getBrandDataByStoreId($storeId)
    {
        $sql = "SELECT DISTINCT b.id, b.name
                FROM brands b
                JOIN products p ON JSON_UNQUOTE(JSON_EXTRACT(p.brand_id, '$[0]')) = CAST(b.id AS CHAR)
                WHERE p.store_id = ?";
        $query = $this->db->query($sql, [$storeId]);
        return $query->result_array();
    }

    public function getBrandDataByStoreIdMarketplace($storeId,$int_to)
    {
        $sql = "SELECT DISTINCT b.id, b.name
                FROM brands b
                JOIN products p ON JSON_UNQUOTE(JSON_EXTRACT(p.brand_id, '$[0]')) = CAST(b.id AS CHAR)
                JOIN brands_marketplaces ON (brands_marketplaces.brand_id = b.id AND brands_marketplaces.int_to = ?)
                WHERE p.store_id = ?";
        $query = $this->db->query($sql, [$int_to,$storeId]);
        return $query->result_array();
    }

    /*get the active brands information*/
    public function getBrandbyName($name)
    {
        $sql = "SELECT * FROM brands WHERE LOWER(name) = LOWER(?)";
        $query = $this->db->query($sql, array($name));
        $row = $query->row_array();
        if ($row) {
            return $row['id'];
        } else {
            return false;
        }
    }
    
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('brands', $data);
            return ($insert) ? $this->db->insert_id() : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            try {
                $update = $this->db->update('brands', $data);
                return ($update) ? true : false;
            }
            catch ( Exception $e ) {
                return false;
            }
        }
    }
    
    public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            try {
                $delete = $this->db->delete('brands');
                return ($delete) ? true : false;
            } catch (\Exception $e) {
                return false; 
            }
        }
    }
    
    public function getBrandsDataView($offset =0, $procura='', $orderby = '', $limit = 200 )
    {
    	if ($offset == '') {$offset=0;}
		if ($limit == '') {$limit=200;}
        $sql = "SELECT * FROM brands ";
		$sql .= $procura.$orderby." LIMIT ".$limit." OFFSET ".$offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }
	
	public function getBrandsDataCount($procura='' )
    {
        $sql = "SELECT count(*) as qtd FROM brands ";
		$sql .= $procura;
        $query = $this->db->query($sql);
		$row = $query->row_array();
		return $row['qtd'];
    }
	
	public function getBrandDatabyName($name)
    {
        $sql = "SELECT * FROM brands WHERE name = ?";
        $query = $this->db->query($sql, array($name));
        return $query->row_array();
    }

}