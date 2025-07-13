<?php

/**
 * @property CI_DB_driver $db
 */

class Model_collections extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getActiveCollections()
    {
        $sql = "SELECT * FROM collections WHERE active = ? ORDER BY name";
        $query = $this->db->query($sql, array(1));
        return $query->result_array();
    }
    
    public function getCollectionData($id = null)
    {
        if($id) {
            $sql = "SELECT * FROM collections WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }
        
        $sql = "SELECT * FROM collections where active = 1 ORDER BY path ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCollectionByMktpId($id = null)
    {
        $sql = "SELECT * FROM collections WHERE mktp_id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();

    }
        
    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('collections', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }
    
    public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('collections', $data);
            return ($update == true) ? true : false;
        }
    }
    
    public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('collections');
            return ($delete == true) ? true : false;
        }
    }
    	
	public function getCollectionDatabyName($name)
    {
        $sql = "SELECT * FROM collections WHERE name = ?";
        $query = $this->db->query($sql, array($name));
        return $query->row_array();
    }

    public function getCollectionActiveDatabyPath($path)
    {
        $sql = "SELECT * FROM collections WHERE mktp_id = ? AND active = 1";
        $query = $this->db->query($sql, array($path));
        return $query->row_array();
    }

    public function getProductCollectionByProductId($id = null)
    {

        $sql = "SELECT collection_id FROM products_collections WHERE product_id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->result_array();      
    
    }

    public function createProductCollection($data)
    {
        if($data) {
            $insert = $this->db->insert('products_collections', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
    }

    public function removeProductCollections($id)
    {
        if($id) {
            $this->db->where('product_id', $id);
            $delete = $this->db->delete('products_collections');
            return ($delete == true) ? true : false;
        }
    }

    public function getProductCollectionIdByProductId($id = null)
    {

        $sql = "SELECT mktp_collection_id FROM products_collections WHERE product_id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->result_array();      
    
    }

    public function getActiveCollectionsByFilter($filters = array())
    {
        $this->db->where('active', true);
        foreach ($filters as $type => $filter) {
            foreach ($filter as $key => $value) {
                $this->db->$type($key, $value);
            }
        }
        return $this->db->order_by('name', 'ASC')->get('collections')->result_array();
    }
    
    public function getProductCollectionIdByProductIdAndCollectionId($idProd = null, $idCollection = null)
    {
        $sql = "SELECT * FROM products_collections WHERE product_id = ? AND mktp_collection_id = ?";
        $query = $this->db->query($sql, array($idProd, $idCollection));
        return $query->result_array();      
    
    }

}