<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'enable_multiseller_operation')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "enable_multiseller_operation",
                'value' => 'Quando ativo, será possível mais que uma loja compartilhar o mesmo pedido',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Mais que uma loja compartilha o mesmo pedido',
                'description' => 'Quando ativo, será possível mais que uma loja compartilhar o mesmo pedido'
            ));
        }
    }

	public function down()	{
        $this->db->where('name', 'enable_multiseller_operation')->delete('settings');
	}
};