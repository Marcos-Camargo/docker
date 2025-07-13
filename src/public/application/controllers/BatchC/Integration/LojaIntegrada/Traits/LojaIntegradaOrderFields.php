<?php
if (!defined('LojaIntegradaOrderFields')) {
    define('LojaIntegradaOrderFields', '');
    trait LojaIntegradaOrderFields
    {
        private $orderFields = [
            ['field' => ['customer', 'name'], 'require' => true, 'fieldGoal' => ['buyer', 'name'], 'type' => 'arraytoarray'],
            ['field' => ['customer', 'email'], 'require' => true, 'fieldGoal' => ['buyer', 'email'], 'type' => 'arraytoarray'],
            ['field' => ['customer', 'cpf_cnpj'], 'require' => true, 'fieldGoal' => ['buyer', 'document'], 'type' => 'arraytoarray'],
            ['field' => ['customer', 'type'], 'require' => true, 'fieldGoal' => ['buyer', 'type'], 'type' => 'arraytoarray'],
            ['field' => ['customer', 'id'], 'require' => true, 'fieldGoal' => ['buyer', 'external_id'], 'type' => 'arraytoarray'],
            ['field' => ['customer', "phones", 0], 'require' => true, 'fieldGoal' => ['buyer', 'phone'], 'type' => 'arraytoarray'],
            ['field' => ['customer', 'phones', 1], 'require' => true, 'fieldGoal' => ['buyer', 'cellPhone'], 'type' => 'arraytoarray'],
            // // 
            ['field' => ['customer', 'name'], 'require' => true, 'fieldGoal' => ['shipping', 'address', 'name'], 'type' => 'arraytoarray'],
            ['field' => ["shipping", "shipping_address", "street"], 'require' => true, 'fieldGoal' => ['shipping', 'address', 'address'], 'type' => 'arraytoarray'],
            ['field' => ["shipping", "shipping_address", "country"], 'require' => true, 'fieldGoal' => ['shipping', 'address', 'country'], 'type' => 'arraytoarray'],
            ['field' => ["shipping", "shipping_address", "complement"], 'require' => true, 'fieldGoal' => ['shipping', 'address', 'complement'], 'type' => 'arraytoarray'],
            ['field' => ["shipping", "shipping_address", "street"], 'require' => true, 'fieldGoal' => ['shipping', 'address', 'street'], 'type' => 'arraytoarray'],
            ['field' => ["shipping", "shipping_address", "region"], 'require' => true, 'fieldGoal' => ['shipping', 'address', 'state'], 'type' => 'arraytoarray'],
            ['field' => ["shipping", "shipping_address", "city"],    'require' => true, 'fieldGoal' => ['shipping', 'address', "city"], 'type' => 'arraytoarray'],
            ['field' => ["shipping", "shipping_address", "number"], 'require' => true, 'fieldGoal' => ['shipping', 'address', 'number'], 'type' => 'arraytoarray'],
            ['field' => ["shipping", "shipping_address", "postcode"], 'require' => true, 'fieldGoal' => ['shipping', 'address', 'zipcode'], 'type' => 'arraytoarray'],
            ['field' => ["shipping", "shipping_address", "neighborhood"], 'require' => true, 'fieldGoal' => ['shipping','address','district'], 'type' => 'arraytoarray'],
            ['field' => ['ship_option'], 'require' => true, 'fieldGoal' => ['shipping','option'], 'type' => 'arraytoarray'],
            // // 
            ['field' => ["payments","discount"], 'require' => true, 'fieldGoal' => ['amount','discount'], 'type' => 'arraytoarray'],
            ['field' => ["shipping","seller_shipping_cost"], 'require' => true, 'fieldGoal' => ['amount','freight'], 'type' => 'arraytoarray'],
            ['field' => ["payments","service_charge"], 'require' => true, 'fieldGoal' => ['amount','fees'], 'type' => 'arraytoarray'],
            ['field' => ["payments","gross_amount"], 'require' => true, 'fieldGoal' => ['amount','total'], 'type' => 'arraytoarray'],
            ['field' => ["payments","gross_amount"], 'require' => true, 'fieldGoal' => ['amount','gross'], 'type' => 'arraytoarray'],
            // // 
            ['field' => ['paid_status'], 'require' => true, 'fieldGoal' => ['info','status'], 'type' => 'arraytoarray'],
            ['field' => ["system_marketplace_code"], 'require' => true, 'fieldGoal' => ['info','marketPlaceId'], 'type' => 'arraytoarray'],
            ['field' => ['reference'], 'require' => true, 'fieldGoal' => ['info','reference'], 'type' => 'arraytoarray'],
        ];
        private function getOrderFields()
        {
            return $this->orderFields;
        }
    }
}
