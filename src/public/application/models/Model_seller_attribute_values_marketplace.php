<?php

/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

*/

class Model_seller_attribute_values_marketplace extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data): bool
    {
        return $this->db->insert('seller_attribute_values_marketplace', $data);
    }

    /**
     * @param   int     $category_id
     * @param   string  $int_to
     * @param   string  $attribute
     * @return  array
     */
    public function getValuesAttributeSellerCategoryMarketplaceAttribute(int $category_id, string $int_to, string $attribute): array
    {
        return $this->db
            ->where(
                array(
                    'category_id' => $category_id,
                    'int_to' => $int_to,
                    'attribute_marketplace_id' => $attribute,
                )
            )
            ->get('seller_attribute_values_marketplace')
            ->result_array();
    }

    /**
     * @param   int     $store_id
     * @param   int     $category_id
     * @param   string  $int_to
     * @param   string  $attribute
     * @return  bool
     */
    public function removeAllValuesAttributeByStoreCategoryMarketplaceAttribute(int $store_id, int $category_id, string $int_to, string $attribute): bool
    {
        return (bool)$this->db->where(
            array(
                'store_id' => $store_id,
                'category_id' => $category_id,
                'int_to' => $int_to,
                'attribute_marketplace_id' => $attribute
            )
        )->delete('seller_attribute_values_marketplace');
    }

    /**
     * @param   int     $store_id
     * @param   int     $category_id
     * @param   string  $int_to
     * @param   array   $not_in
     * @return  bool
     */
    public function removeAllValuesAttributeByStoreCategoryMarketplace(int $store_id, int $category_id, string $int_to, array $not_in): bool
    {
        return (bool)$this->db->where(
            array(
                'store_id' => $store_id,
                'category_id' => $category_id,
                'int_to' => $int_to
            )
        )
        ->where_not_in('attribute_marketplace_id', $not_in)
        ->delete('seller_attribute_values_marketplace');
    }
}