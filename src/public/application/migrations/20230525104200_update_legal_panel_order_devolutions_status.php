<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $this->db->where(['status' => 'Devolução de Pedido'])->update("legal_panel", array(
            'status' => 'Chamado Aberto'
        ));
        $this->db->where(['active' => '0'])->update("legal_panel", array(
            'status' => 'Chamado Fechado'
        ));
    }

    public function down() {
    }
};