<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'day_to_send_conciliation_to_external_marketplace_integration')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "day_to_send_conciliation_to_external_marketplace_integration",
                'value' => '0',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Dia para envio da conciliação',
                'description' => 'Dia para envio da conciliação para o integrador de marketplace externo'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'day_to_send_conciliation_to_external_marketplace_integration')->delete('settings');
	}
};