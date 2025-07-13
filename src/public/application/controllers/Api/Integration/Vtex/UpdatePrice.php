<?php

class UpdatePrice
{
    public $_this;

    public function __construct($_this)
    {
        $this->_this = $_this;
    }

    public function updatePrice($idProduct)
    {
        $this->_this->setJob('WeebHook-updatePrice');

        // Consulta endpoint par obter estoque
        $price = $this->_this->product->getPriceErp($idProduct);

        // Inicia transação
        $this->_this->setUniqueId($idProduct); // define novo unique_id

        $update = $this->_this->product->updatePrice($idProduct, $price);

        if ($update !== false && $update !== null) {
            $this->_this->log_integration("Preço do produto {$idProduct} atualizado", "<h4>O preço do produto {$idProduct} foi atualizado com sucesso.</h4><strong>Preço alterado:</strong> {$price}", "S");
            $this->_this->log_data('api', 'vtex/updatePrice', "preço do produto {$idProduct} da loja {$this->_this->store} alterado para {$price}", 'I');
            return true;
        }

        return null;
    }
}