<?php
if (!defined('LoadSizeToDescriptionAndNameProduct')) {
    define('LoadSizeToDescriptionAndNameProduct', '');

    trait LoadSizeToDescriptionAndNameProduct
    {
        private function loadSizeToDescriptionAndNameProduct()
        {
            if (!isset($this->model_settings)) {
                echo("model_settings não definido\n");
                return;
            }
            $product_length_name = $this->model_settings->getValueIfAtiveByName('product_length_name');
            if ($product_length_name) {
                $this->product_length_name = $product_length_name;
            } else {
                $this->product_length_name = Model_products::CHARACTER_LIMIT_IN_FIELD_NAME;
            }
            $product_length_description = $this->model_settings->getValueIfAtiveByName('product_length_description');
            if ($product_length_description) {
                $this->product_length_description = $product_length_description;
            } else {
                $this->product_length_description = Model_products::CHARACTER_LIMIT_IN_FIELD_DESCRIPTION;
            }
        }
    }
}