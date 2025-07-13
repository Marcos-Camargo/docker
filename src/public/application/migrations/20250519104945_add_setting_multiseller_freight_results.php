<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'multiseller_freight_results')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "multiseller_freight_results",
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
        $this->db->where('name', 'multiseller_freight_results')->delete('settings');
	}
};