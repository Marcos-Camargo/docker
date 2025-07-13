<?php
if (!defined('LojaIntegradaOrderItensFields')) {
    define('LojaIntegradaOrderItensFields', '');
    trait LojaIntegradaOrderItensFields
    {
        private $orderItensFields = [
            ['field' => ['product_id_erp'], 'require' => true, 'fieldGoal' => ['product_id'], 'type' => 'arraytoarray'],
            ['field' => ['qty'], 'require' => true, 'fieldGoal' => ['quantity'], 'type' => 'arraytoarray'],
            ['field' => ["original_price"], 'require' => true, 'fieldGoal' => ['unit_value'], 'type' => 'arraytoarray'],
            ['field' => ['amount'], 'require' => true, 'fieldGoal' => ['line_value'], 'type' => 'arraytoarray'],
        ];
        private function getOrderItensFields(){
            return $this->orderItensFields;
        }
    }
}
