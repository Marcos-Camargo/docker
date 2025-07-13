<?php

class UpdateStock
{
    public $_this;

    public function __construct($_this)
    {
        $this->_this = $_this;
    }

    public function updateStock($idProduct)
    {
        $this->_this->setJob('WeebHook-updateStock');

        // Consulta endpoint par obter estoque
        $qty = $this->_this->product->getStock($idProduct);
        $qty = $qty === false ? 0 : $qty;

        // Inicia transação
        $this->_this->setUniqueId($idProduct); // define novo unique_id

        $update = $this->_this->product->updateStock($idProduct, $qty);

        if ($update !== false) {
            $this->_this->log_integration("Estoque do produto {$idProduct} atualizado", "<h4>O estoque do produto {$idProduct} foi atualizado com sucesso.</h4><strong>Estoque alterado:</strong> {$qty}", "S");
            $this->_this->log_data('api', 'vtex/updateStock', "Estoque do produto {$idProduct} da loja {$this->_this->store} alterado para {$qty}", 'I');
            return true;
        }

        return null;
    }
}