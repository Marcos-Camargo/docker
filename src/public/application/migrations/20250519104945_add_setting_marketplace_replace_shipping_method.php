<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'marketplace_replace_shipping_method')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "marketplace_replace_shipping_method",
                'value' => '{"lowest_price":"Normal","lowest_deadline":"Expressa"}',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Nomes de método de enetrega para marketplaces',
                'description' => 'Nomes dos marketplaces para alterar o método de entrega'
            ));
        }
    }

	public function down()	{
        $this->db->where('name', 'marketplace_replace_shipping_method')->delete('settings');
	}
};