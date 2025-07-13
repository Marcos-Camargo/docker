<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Marcas/Fabricantes
 
 */

class Model_products_catalog_associated extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function create(array $data)
    {
        $insert = $this->db->insert('products_catalog_associated', $data);
        return ($insert) ? $this->db->insert_id() : false;
    }
    
    public function update(array $data, int $id): bool
    {
        try {
            return $this->db->where('id', $id)->update('products_catalog_associated', $data);
        }
        catch ( Exception $e ) {
            return false;
        }
    }
    
    public function remove(int $id)
    {
        try {
            return $this->db->where('id', $id)->delete('products_catalog_associated');
        } catch (Exception $e) {
            return false;
        }
    }

    public function removeByProductId(int $product_id)
    {
        try {
            return $this->db->where('product_id', $product_id)->delete('products_catalog_associated');
        } catch (Exception $e) {
            return false;
        }
    }

    public function getByProductId(int $product_id): array
    {
        try {
            return $this->db
                ->select('pca.*, pc.price')
                ->join('products_catalog pc', 'pc.id = pca.catalog_product_id')
                ->where(array('pca.product_id' => $product_id))
                ->get('products_catalog_associated AS pca')
                ->result_array();
        } catch (Exception $e) {
            return array();
        }
    }

    public function checkIfCatalogExistProduct(int $catalog_id): bool
    {
        try {
            return $this->db->get_where('products_catalog_associated', array('catalog_id' => $catalog_id))->num_rows() > 0;
        } catch (Exception $e) {
            return true;
        }
    }

    public function getByCatalogProductIdAndCatalogId(int $product_id, int $catalog_id): ?array
    {
        try {
            return $this->db->get_where('products_catalog_associated', array('catalog_product_id' => $product_id, 'catalog_id' => $catalog_id))->row_array();
        } catch (Exception $e) {
            return null;
        }
    }

    public function getProductAssociateByIntTo(string $int_to): array
    {
        try {
            $sub_query = $this->db->select('id')->where('int_to', $int_to)->get_compiled_select('catalogs');
            return $this->db
                ->select('c.int_to, c.name, 1 as is_assciated')
                ->join('catalogs c', 'c.id = ca.catalog_id_to')
                ->where("ca.catalog_id_from = ($sub_query)")
                ->get('catalogs_associated ca')
                ->result_array();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getProductsChangedToIntegrate(string $int_to, $offset = 0, $limit = 20): array
    {
        try {
            $sub_query = $this->db->select('id')->where('int_to', $int_to)->get_compiled_select('catalogs');
            return $this->db
                ->select('
                    p.*, 
                    pi.id AS pi_id, 
                    pi.skumkt AS pi_skumkt, 
                    pi.status as pi_status, 
                    pi.status_int as pi_status_int, 
                    pca.status,
                    pca.catalog_product_id,
                    pc.price
                ')
                ->join('prd_to_integration pi', "p.id = pi.prd_id")
                ->join('products_catalog_associated pca', 'pca.product_id = p.id')
                ->join('products_catalog pc', 'pc.id = pca.catalog_product_id')
                ->where('p.date_update > pi.date_last_int', null, false)
                ->where("pca.catalog_id = ($sub_query)")
                ->where(
                    array(
                        "pi.approved" => 1,
                        "pi.int_to" => $int_to,
                        "p.status !=" => Model_products::DELETED_PRODUCT,
                    )
                )
                ->limit($limit, $offset)
                ->get('products p')
                ->result_array();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getProductsChangedToIntegrateNew(string $int_to, int $catalog_id, $offset = 0, $limit = 20): array
    {
        try {
            $sub_query_prd_to_integration = $this->db
                ->select('prd_id')
                ->where('int_to', $int_to)
                ->get_compiled_select('prd_to_integration');

            $sub_query_catalogs_products_catalog = $this->db
                ->select('product_catalog_id')
                ->where('c.catalog_id', $catalog_id)
                ->where('c.product_catalog_id = p.product_catalog_id', null, false)
                ->get_compiled_select('catalogs_products_catalog c');

            return $this->db
                ->select('
                    p.*,
                    pca.status,
                    pca.catalog_product_id,
                    pc.price
                ')
                ->join('products_catalog_associated pca', 'pca.product_id = p.id')
                ->join('products_catalog pc', 'pc.id = pca.catalog_product_id')
                ->where("p.id NOT IN ($sub_query_prd_to_integration)")
                ->where("p.product_catalog_id IN ($sub_query_catalogs_products_catalog)")
                ->where(
                    array(
                        "p.qty >" => 0,
                        "p.has_variants" => '',
                        "pca.status" => 1,
                        "p.situacao" => 2,
                    )
                )
                ->limit($limit, $offset)
                ->get('products p')
                ->result_array();
        } catch (Exception $e) {
            return [];
        }
    }
}