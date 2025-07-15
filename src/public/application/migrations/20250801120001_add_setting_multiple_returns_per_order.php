<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        if ($this->db->where('name', 'enable_multiple_returns_per_order')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => 'enable_multiple_returns_per_order',
                'value' => 'Permitir mais de uma devolucao por pedido',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Permitir multiplas devolucoes por pedido',
                'description' => 'Quando habilitado e possivel criar mais de uma devolucao para o mesmo pedido'
            ));
        }
    }

    public function down()  {
        $this->db->where('name', 'enable_multiple_returns_per_order')->delete('settings');
    }
};
