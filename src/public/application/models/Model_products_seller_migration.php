<?php

class Model_products_seller_migration extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }


    /* get the product data */
    public function getProductData($offset = 0, $procura = '', $orderby = '', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }
        $sql = "SELECT psm.* FROM products_seller_migration psm WHERE psm.internal_id IS NULL  AND psm.date_disapproved IS NULL AND psm.date_approved IS NULL";
        $sql .= $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert('products_seller_migration', $data);
            return ($insert) ? $this->db->insert_id() : false;
        }
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            try {
                $update = $this->db->update('products_seller_migration', $data);
                return ($update) ? true : false;
            } catch (Exception $e) {
                return false;
            }
        }
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            try {
                $delete = $this->db->delete('products_seller_migration');
                return ($delete) ? true : false;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    public function getProductsDataCount($procura = '')
    {
        $sql = "SELECT count(*) as qtd FROM products_seller_migration WHERE internal_id IS NULL AND date_disapproved IS NULL AND date_approved IS NULL";
        $sql .= $procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }
    public function getUnmigratedProducts($procura = '')
    {
        $sql = "SELECT count(*) as qtd FROM products_seller_migration WHERE internal_id IS NULL AND date_disapproved IS NULL AND date_approved IS NULL";
        $sql .= $procura;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }
    public function getUnmigratedProductsByStore(int $store_id)
    {
        $sql = "SELECT * FROM products_seller_migration WHERE internal_id IS NULL AND date_disapproved IS NULL AND date_approved IS NULL AND store_id = ?";
        $query = $this->db->query($sql, array($store_id));
        return $query->result_array();
    }
    public function getProductDatabyName($name)
    {
        $sql = "SELECT * FROM products_seller_migration WHERE product_name = ?";
        $query = $this->db->query($sql, array($name));
        return $query->row_array();
    }

    public function getProductDatabySkuId($skuId, $store_id)
    {
        $sql = "SELECT * FROM products_seller_migration WHERE id_sku = ? and store_id = ? ";
        $query = $this->db->query($sql, array($skuId, $store_id));
        return $query->row_array();
    }
    public function getProductDatabyStore($storeId)
    {
        $sql = "SELECT * FROM products_seller_migration WHERE store_id = ?";
        $query = $this->db->query($sql, array($storeId));
        return $query->result_array();
    }   
     public function getProductDataById($id)
    {
        $sql = "SELECT * FROM products_seller_migration WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }
    public function getMigration($storeId)
    {
        $sql = "SELECT * FROM seller_migration_register WHERE store_id = ? AND `status` = 0";
        $query = $this->db->query($sql, array($storeId));
        return $query->result_array();
    }
    public function getIntTo($storeId)
    {
        $sql = "SELECT int_to FROM seller_migration_register WHERE store_id = ?";
        $query = $this->db->query($sql, array($storeId));
        return $query->row_array();
    }
    /**Recuperar Categoria*/
    public function getProductCategory($category_marketplace_id, $int_to)
    {
        $sql = "SELECT category_id FROM categorias_marketplaces cm2 WHERE category_marketplace_id = ? AND int_to = ? ;";
        $query = $this->db->query($sql, array($category_marketplace_id, $int_to));
        return $query->row_array();
    }
    /**Recuperar Marca*/
    public function getProductBrand($brand_marketplace_id, $int_to)
    {
        $sql = "SELECT * FROM brands_marketplaces bm WHERE id_marketplace = ? AND int_to = ? ;";
        $query = $this->db->query($sql, array($brand_marketplace_id, $int_to));
        return $query->row_array();
    }
   
}
