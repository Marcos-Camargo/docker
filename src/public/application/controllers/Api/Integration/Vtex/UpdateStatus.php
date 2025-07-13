<?php

class UpdateStatus
{
    public $_this;

    public function __construct($_this)
    {
        $this->_this = $_this;
    }

    public function updateStatus($idProduct, $status)
    {
        $this->_this->setJob('WeebHook-updateStatus');

        $updateStatus = $this->_this->product->updateProductForSku($idProduct, array('status' => $status));
        $statusName = $status == 1 ? 'Ativo' : 'Inativo';

        if ($updateStatus) {
            $this->_this->log_data('api', 'vtex/updateProduct', "SKU={$idProduct} atualizou status para {$status}", "I");
            $this->_this->log_integration("Status do produto {$idProduct} atualizado", "<h4>O status do produto {$idProduct} foi atualizado com sucesso.</h4><strong>Status alterado:</strong> {$statusName}", "S");
            return true;
        }

        return null;
    }
}