<?php

if (!defined('UpdateQtdProductByVariation')) {
    define('UpdateQtdProductByVariation', '');
    /**
     *
     */
    trait UpdateQtdProductByVariation
    {
        public function updateQtdProductByVariation($product_id)
        {
            $variants = $this->model_products->getVariantsByProd_id($product_id);
            $qtd = 0;
            $max_price = 0;
            if (count($variants) == 0) {
                return;
            } else {
                foreach ($variants as $key => $variant) {
                    $qtd += intval($variant['qty']);
                    if ($max_price == 0) {
                        $max_price = floatval($variant['price']);
                    } else {
                        if ($max_price < floatval($variant['price'])) {
                            $max_price = floatval($variant['price']);
                        }
                    }
                }
                $update = $this->model_products->updatePriceAndStock($product_id, $max_price, $qtd);
            }
        }
    }
}