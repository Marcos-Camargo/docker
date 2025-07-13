<?php
require APPPATH . "libraries/Traits/RemoveAccentsAndCedilla.trait.php";
if (!defined('LengthValidationProduct')) {
    define('LengthValidationProduct', '');
    trait LengthValidationProduct
    {
        use RemoveAccentsAndCedilla;
        private $product_length_name;
        private $product_length_description;
        private $product_length_sku;
        /**
         * function with load config to validate fields description sku and name.
         * @param void
         * @return void
         */
        private function loadLengthSettings(): void
        {
            // Para algumas integrações que passam a si mesma no processo.
            if (!isset($this->CI) && is_subclass_of($this, 'Integration')) {
                $this->CI = $this;
            }
            // Para o controler da tela.
            if (!isset($this->CI) && is_subclass_of($this, 'CI_Controller')) {
                $this->CI = $this;
            }
            // Para integrações que não são do CI mas que tem o parametro _this
            if (!isset($this->CI) && is_subclass_of($this->_this, 'Integration')) {
                $this->CI = $this->_this;
            }
            if (!isset($this->CI->model_settings)) {
                $this->CI->load->model('model_settings');
            }
            $product_length_name = $this->CI->model_settings->getValueIfAtiveByName('product_length_name');
            if ($product_length_name) {
                $this->product_length_name = $product_length_name;
            } else {
                $this->product_length_name = Model_products::CHARACTER_LIMIT_IN_FIELD_NAME;
            }
            $product_length_description = $this->CI->model_settings->getValueIfAtiveByName('product_length_description');
            if ($product_length_description) {
                $this->product_length_description = $product_length_description;
            } else {
                $this->product_length_description = Model_products::CHARACTER_LIMIT_IN_FIELD_DESCRIPTION;
            }
            $product_length_sku = $this->CI->model_settings->getValueIfAtiveByName('product_length_sku');
            if ($product_length_sku) {
                $this->product_length_sku = $product_length_sku;
            } else {
                $this->product_length_sku = Model_products::CHARACTER_LIMIT_IN_FIELD_SKU;
            }
        }
        /**
         * Return true if the sku has am size valid.
         * @param string name
         * @return boolean if is valid
         */
        public function validateLengthSku($sku)
        {
            if (isset($this->form_validation)) {
            	 $this->form_validation->set_message('validateLengthSku', sprintf("Nem SKU do produto nem da variação pode exceder %s caracteres.", $this->product_length_sku));
               // $this->form_validation->set_message('validateLengthSku', sprintf($this->CI->lang->line('messages_sku_exced_limit_caracter'), $this->product_length_sku));
            }
            return strlen($this->removeAccentsAndCedilla($sku)) <= $this->product_length_sku;
        }
        /**
         * Return true if the name has am size valid.
         * @param string name
         * @return boolean if is valid
         */
        public function validateLengthName($name)
        {
            return strlen($this->removeAccentsAndCedilla($name)) <= $this->product_length_name;
        }
        /**
         * Return true if the description has am size valid.
         * @param string decription
         * @return boolean if is valid
         */
        public function validateLengthDescription($description)
        {
            return strlen($this->removeAccentsAndCedilla($description)) <= $this->product_length_description;
        }
        /**
         * Return mensage to description with size invalid.
         * @param void
         * @return string message
         */
        public function getMessageLenghtDescriptionInvalid()
        {
            return "A descrição do produto não pode ter mais de " . $this->product_length_description . " caracteres.";
        }
        /**
         * Return mensage to name with size invalid.
         * @param void
         * @return string message
         */
        public function getMessageLenghtNameInvalid()
        {
            return "O nome do produto não pode ter mais de " . $this->product_length_name . " caracteres.";
        }
        /**
         * Return mensage to sku with size invalid.
         * @param void
         * @return string message
         */
        public function getMessageLenghtSkuInvalid($type = 'produto')
        {
            return "O SKU do {$type} não pode ter mais do que {$this->product_length_sku} caracteres.";
        }
        /**
         * @deprecated
         */
        public function getMessageLenghtSkuInvalidToVariation()
        {
            return "O SKU da variação não pode ter mais do que {$this->product_length_sku} caracteres.";
        }
    }
}
