<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'external_marketplace_integration')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "external_marketplace_integration",
                'value' => '',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Integrador de marketplace externo',
                'description' => 'Integrador de marketplace externo para ser utilizado para enviar notificações'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'external_marketplace_integration')->delete('settings');
	}
};