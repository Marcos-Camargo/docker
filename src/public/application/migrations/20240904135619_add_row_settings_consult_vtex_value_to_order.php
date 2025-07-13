<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'consult_vtex_value_to_order')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "consult_vtex_value_to_order",
                'value' => 'Consultar preço do sku ao integrar o pedido',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Consultar preço do sku ao integrar o pedido',
                'description' => 'Consultar preço do sku ao integrar o pedido, assim realizando devidamente os descontos ou acréscimos'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'consult_vtex_value_to_order')->delete('settings');
	}
};