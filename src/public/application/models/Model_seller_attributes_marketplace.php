<?php

/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

*/

class Model_seller_attributes_marketplace extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param   int     $store_id
     * @param   int     $category_id
     * @param   string  $int_to
     * @param   bool    $show_attribute_name
     * @return  array
     */
    public function getAttributesStoreCategoryMarketplace(int $store_id, int $category_id, string $int_to, bool $show_attribute_name = false): array
    {
        $this->db->where(
            array(
                'sam.store_id' => $store_id,
                'sam.category_id' => $category_id,
                'sam.int_to' => $int_to
            )
        )->join('atributos_categorias_marketplaces acm', 'acm.id_categoria = sam.category_marketplace_id and acm.int_to = sam.int_to and acm.id_atributo = sam.attribute_marketplace_id');

        if ($show_attribute_name) {
            $this->db->select('sam.store_id, sam.company_id, sam.int_to, sam.category_id, sam.category_marketplace_id, sam.attribute_marketplace_id, sam.attribute_seller_value, acm.nome')
            ->where(
                array(
                    'acm.tipo' => 'list'
                )
            );
        } else {
            $this->db->select('sam.store_id, sam.company_id, sam.int_to, sam.category_id, sam.category_marketplace_id, sam.attribute_marketplace_id, sam.attribute_seller_value, acm.tipo');
        }

        return $this->db->get('seller_attributes_marketplace sam')->result_array();
    }

    /**
     * @param   int     $store_id
     * @param   int     $category_id
     * @param   string  $int_to
     * @return  bool
     */
    public function removeAllAttributesByStoreCategoryMarketplace(int $store_id, int $category_id, string $int_to): bool
    {
        return (bool)$this->db->where(
            array(
                'store_id' => $store_id,
                'category_id' => $category_id,
                'int_to' => $int_to
            )
        )->delete('seller_attributes_marketplace');
    }

    /**
     * @param   array   $data
     * @return  bool
     */
    public function create(array $data): bool
    {
        return $this->db->insert('seller_attributes_marketplace', $data);
    }

    public function getValuesToChangeInProduct(int $store_id, int $last_id = 0, $limit = 200): array
    {
        return $this->db->distinct('
            sam.int_to, 
            sam.attribute_marketplace_id , 
            savm.attribute_value_marketplace_id, 
            apv.prd_id, 
            apv.value, 
            ap.name,
            ap.id
        ')->select('
            sam.id as last_id, 
            sam.int_to, 
            sam.attribute_marketplace_id , 
            savm.attribute_value_marketplace_id, 
            apv.prd_id, 
            apv.value as attribute_value_custom_seller, 
            ap.name as attribute_value_name_custom_seller,
            ap.id as attribute_id_custom_seller
        ')
        ->join('seller_attribute_values_marketplace savm', 'savm.category_marketplace_id = sam.category_marketplace_id and savm.int_to = sam.int_to and savm.attribute_marketplace_id = sam.attribute_marketplace_id', 'left')
        ->join('attributes_products ap', 'ap.name = sam.attribute_seller_value')
        ->join('attributes_products_value apv', 'apv.id_attr_prd = ap.id and (apv.value = savm.attribute_value_seller_name or savm.attribute_value_seller_name is null)')
        ->join('produtos_atributos_marketplaces pam', 'pam.id_atributo = sam.attribute_marketplace_id and pam.int_to = sam.int_to and pam.id_product = apv.prd_id', 'left')
        ->where('pam.id_product is null', NULL, FALSE)
        ->where(array(
            'sam.store_id' => $store_id,
            'sam.id >' => $last_id
        ))
        ->order_by('sam.id', 'asc')
        ->limit($limit)
        ->get('seller_attributes_marketplace sam')
        ->result_array();
    }
}