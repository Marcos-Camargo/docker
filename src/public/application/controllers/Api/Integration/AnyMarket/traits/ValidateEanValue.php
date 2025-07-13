<?php
if (!defined('ValidateEanValue')) {
    define('ValidateEanValue', '');

    trait ValidateEanValue
    {
        protected function validateEAN($EAN, $store_id)
        {
            if (empty($EAN)) {
                return true;
            }
            if ($this->CI->model_products->ean_check($EAN)) {
                $this->printInTerminal("Validado com sucesso do ean vindo da anymarket\n");
            } else {
                throw new ValidationException("O EAN {$EAN} é inválido, verifique e tente novamente.\n");
            }
            $id_product = $this->CI->model_products->VerifyEanUnique($EAN, $store_id);
            if ($id_product) {
                $product = $this->CI->model_products->getProductData(0, $id_product);
                if (!empty($product['product_id_erp']) && $product['product_id_erp'] == $this->product['product_id_erp']) {
                    $this->printInTerminal("Validado que o EAN não existe na base de dados para este logista.\n");
                } else if (!empty($product['product_id_erp'])
                    && $product['product_id_erp'] != $this->product['product_id_erp']
                    && $product['sku'] == $this->product['sku']) {
                    $this->printInTerminal("Sobrescrever vínculo de produto existente.\n");
                } else if (empty($product['product_id_erp']) && $product['sku'] == $this->product['sku']) {
                    $this->printInTerminal("Validado que o EAN existe na base de dados para este logista e o produto tem o mesmo SKU.\n");
                } else {
                    $variants = $this->CI->model_products->getVariants($id_product);
                    $hasSku = false;

                    if (isset($this->variants) && !empty($this->variants)) {
                        $skus = array_column($this->variants, 'sku');
                        foreach ($variants ?? [] as $variant) {
                            if (in_array($variant['sku'], $skus)) {
                                $hasSku = true;
                                continue;
                            }
                            $product['name'] = "{$product['name']} - Variação: {$variant['name']}";
                        }
                    }

                    if(!$hasSku) {
                        throw new ValidationException(
                            "O EAN {$EAN} já está cadastrado para o produto {$product['name']}.\n"
                        );
                    }
                }
            }
            $this->product['ean'] = $EAN;
        }
    }
}