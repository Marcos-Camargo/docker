<?php

/**
 *
 */
if (!defined('UpdatePrazoOperacional')) {
    define('UpdatePrazoOperacional', '');
    trait UpdatePrazoOperacional
    {
        private function updatePrazoOperacional($product, $product_temp_data)
        {
            $updateData = ['prazo_operacional_extra' => max(intval($product['prazo_operacional_extra']), intval($product_temp_data['prazo_operacional_extra']))];
            if ($updateData['prazo_operacional_extra'] != intval($product['prazo_operacional_extra'])) {
                return $this->model_products->update($updateData, $product['id'], "Atualizando para maior prazo operacional.");
            }
            return false;
        }
    }
}
