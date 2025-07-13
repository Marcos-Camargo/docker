<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ERPIntegration
{
    protected $CI;
    var $instance;
	var $readonlydb;
    

    public function __construct()
    {
        $this->_CI = &get_instance();
        $this->_CI->load->model('model_products');
        $this->instance = &get_instance();
        $this->readonlydb = $this->instance->load->database('readonly', TRUE);
        
    }

    public function setAttributeProduct(int $productId, array $attributes)
    {
        
        foreach($attributes as $nameAttribute => $attribute)
        {
        //buscar categoria do produto
            $prod = $this->_CI->model_products->getProductData(0 , $productId);            

            $sql = 'SELECT * FROM categorias_marketplaces  where category_id = ?';
            
            $category_prd = json_decode($prod['category_id'])[0] ?? 0;

            $querycategories = $this->readonlydb->query($sql, array($category_prd));
            $categories_marketplace = $querycategories->result_array();


            foreach($categories_marketplace as $category) {

                $sql = 'SELECT * FROM atributos_categorias_marketplaces  where id_categoria = ?  and int_to = ? and nome = ?';
                echo $category["category_marketplace_id"]."  ".$category["int_to"]."  ".$attribute->label;
                $queryattributes_cat = $this->readonlydb->query($sql, array($category["category_marketplace_id"], $category["int_to"], $attribute->label));

                // nao encontrou atributo
                if(!$queryattributes_cat)
                    //continue;
                    die;
                
                $atributos_cat_marketplace = $queryattributes_cat->result_array();
                
                if($atributos_cat_marketplace[0]['id_atributo'])
                {
                    $sql = 'SELECT * FROM produtos_atributos_marketplaces  where id_atributo = ? and id_product = ? and int_to = ?';

                    $filterProductAttribute = array($atributos_cat_marketplace[0]['id_atributo'], $productId, $category["int_to"]);
                    $queryattributes_prod = $this->readonlydb->query($sql, $filterProductAttribute)->row_array();

                    if($queryattributes_prod)
                    {
                        if($queryattributes_prod['valor'] != $attribute->value->code)
                            $prod_attr = $this->instance->db->where($filterProductAttribute)->update('produtos_attributos_marketplace', array("valor" => $attribute->value->code));

                    }else
                    {
                        $data    = array('id_atributo' => $atributos_cat_marketplace[0]['id_atributo'], 'id_product' => $productId, 'valor' => $attribute->value->code, 'int_to' => $category["int_to"]);
                        $prod_attr = $this->instance->db->insert('produtos_atributos_marketplaces', $data);    
                    }
                }
            }
        }
    }
}
