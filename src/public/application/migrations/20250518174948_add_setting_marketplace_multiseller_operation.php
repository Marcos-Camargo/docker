<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'marketplace_multiseller_operation')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "marketplace_multiseller_operation",
                'value' => 'Nomes dos marketplaces separado por vírgula (int_to)',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Marketplaces para compartilhar o mesmo pedido',
                'description' => 'Marketplaces que serão possível mais que uma loja compartilhar o mesmo pedido'
            ));
        }
    }

	public function down()	{
        $this->db->where('name', 'marketplace_multiseller_operation')->delete('settings');
	}
};