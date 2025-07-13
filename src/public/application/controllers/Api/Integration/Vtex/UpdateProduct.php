<?php

class UpdateProduct
{
    public $_this;

    public function __construct($_this)
    {
        $this->_this = $_this;
    }

    public function updateProduct($idProduct)
    {
        $this->_this->setJob('WeebHook-updateProduct');

        $dataProduct = $this->_this->product->getDataProductERP($idProduct);

        $productUpdate = $this->_this->product->updateProduct($dataProduct);
        unset($productUpdate->ProductDescription);

        if ($productUpdate['success'] === null)  return null;
        if ($productUpdate['success'] === false) {
            $this->_this->log_data('api', 'vtex/updateProduct', "Erro para atualizar o produto ID={$idProduct} encontrou um erro, get_product=" . json_encode($dataProduct) . " retorno=" . json_encode($productUpdate), "E");
        }

        return true;
    }
}