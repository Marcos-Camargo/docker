<?php 
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

*/

/**
 * Class Model_auction
 */
class Model_attributes_products_value extends CI_Model
{
    public function __construct() {
		parent::__construct();
    }

    public function removeByAttributeProductValue(int $attribute, int $product_id, string $value)
    {
        return $this->db->delete('attributes_products_value', array(
            'id_attr_prd'   => $attribute,
            'prd_id'        => $product_id,
            'value'         => $value
        ));
    }

    public function getValuesAttributeToSendMarketplaceInProduct(int $store_id, int $product_id, int $limit = 200): array
    {
        return $this->db->select('
            apv.prd_id, 
            apv.id_attr_prd, 
            apv.value, 
            acm.int_to, 
            acm.id_atributo, 
            acm.tipo, 
            acm.valor, 
            p.store_id
        ')
        ->distinct('
            apv.prd_id, 
            apv.id_attr_prd, 
            apv.value, 
            acm.int_to, 
            acm.id_atributo, 
            acm.tipo, 
            acm.valor, 
            p.store_id
        ')
        ->join('attributes_products ap', 'ap.id = apv.id_attr_prd')
        ->join('atributos_categorias_marketplaces acm', 'acm.name_md5 = ap.name_md5')
        ->join('categorias_marketplaces cm', 'cm.category_marketplace_id = acm.id_categoria')
        ->join('products p', 'p.id = apv.prd_id AND cm.category_id = left(substr(p.category_id,3),length(p.category_id)-4)')
        ->join('produtos_atributos_marketplaces pam use index (produtos_atributos_marketplaces_id_product_IDX)', 'pam.id_product = apv.prd_id and pam.id_atributo = acm.id_atributo and pam.int_to = acm.int_to', 'left')
        ->where('pam.id_product is null', NULL, FALSE)
            ->where(array(
                'p.store_id'    => $store_id,
                'apv.prd_id'    => $product_id
            ))
        ->limit($limit)
        ->get('attributes_products_value apv use index (attributes_products_value_prd_id_IDX) ')
        ->result_array();
    }

    public function getProductsToSendMarketplaceInProduct(int $store_id, int $last_product_id, int $limit = 200): array
    {
        return $this->db->distinct('
            apv.prd_id
        ')
        ->select('
            apv.prd_id
        ')
        ->join('attributes_products ap', 'ap.id = apv.id_attr_prd')
        ->join('atributos_categorias_marketplaces acm', 'acm.name_md5 = ap.name_md5')
        ->join('products p', 'p.id = apv.prd_id')
        ->join('produtos_atributos_marketplaces pam use index (produtos_atributos_marketplaces_id_product_IDX)', 'pam.id_product = apv.prd_id and pam.id_atributo = acm.id_atributo and pam.int_to = acm.int_to', 'left')
        ->where('pam.id_product is null', NULL, FALSE)
        ->where(array(
            'p.store_id'    => $store_id,
            'apv.prd_id >'  => $last_product_id
        ))
        ->order_by('apv.prd_id', 'asc')
        ->limit($limit)
        ->get('attributes_products_value apv use index (attributes_products_value_prd_id_IDX)')
        ->result_array();
    }
}