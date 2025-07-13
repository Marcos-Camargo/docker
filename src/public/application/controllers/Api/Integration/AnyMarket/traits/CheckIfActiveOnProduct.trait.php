<?php
if (!defined('CheckIfActiveOnProduct')) {
    define('CheckIfActiveOnProduct', '');
    /**
     *
     */
    trait CheckIfActiveOnProduct
    {
        public function checkIfActiveOnProduct($product)
        {
            $variants_by_product = $this->model_products->getVariantsByProd_id($product['id']);
            $has_active = false;
            foreach ($variants_by_product as $variant) {
                if ($variant['status'] == 1) {
                    $has_active = true;
                }
            }
            $product_att = [];
            if (!$has_active) {
                $product_att['status'] = 2;
                $this->model_products->update($product_att, $product['id']);
            } else {
                $product_att['status'] = 1;
                $this->model_products->update($product_att, $product['id']);
            }
        }
    }
}
