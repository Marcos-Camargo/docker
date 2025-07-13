<?php
if (!defined('ValidationSkuSpace')) {
    define('ValidationSkuSpace', '');
    trait ValidationSkuSpace
    {
        /**
         * This function valid if the sku have space, especial caracter and caracter with accent
         * @param string sku
         * @return boolean true if is valid.
         */
        public function validateSkuSpace($sku)
        {
            if (preg_match("/\\s/", $sku)) {
                return false;
            }
            $pattern = '/[\'\/~`\!#\$%\^&\*\(\)\+=\{\}\[\]\|;:"\<\>,\?\\\]/';
            if (preg_match($pattern, $sku)) {
                return false;
            }
            $pattern = '/[(á|à|ã|â|ä)|(Á|À|Ã|Â|Ä)|(é|è|ê|ë)|(É|È|Ê|Ë)|(í|ì|î|ï)|(Í|Ì|Î|Ï)|(ó|ò|õ|ô|ö)|(Ó|Ò|Õ|Ô|Ö)|(ú|ù|û|ü)|(Ú|Ù|Û|Ü)|(ñ)|(Ñ)|(ç)|(Ç)\"]/';
            if (preg_match($pattern, $sku)) {
                return false;
            }
            return true;
        }
        /**
         * Return mensage to sku bad formated.
         * @param string $type|void
         * @return string message
         */
        public function getMessagemSkuFormatInvalid($type='produto')
        {
            return "O sku do(a) {$type} possui caracteres especiais ou espaço em branco";
        }
        /**
         * @deprecated
         */
        public function getMessagemSkuFormatInvalidToVariation()
        {
            // return "The variation sku does not have special characters or white space.";
            return $this->getMessagemSkuFormatInvalid('variação');
        }
    }
}
