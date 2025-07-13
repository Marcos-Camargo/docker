<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'return_merchant_name_on_simulation_vtex')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "return_merchant_name_on_simulation_vtex",
                'value' => '1',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 5,
                'friendly_name' => 'Retornar seller ID no atributo merchantName na cotação de frete',
                'description' => 'Caso o Marketplace use meios de pagamentos personalizados por seller, utilizar essa opção para que retorne o id do seller no atributo merchantName da cotação de frete utilizada na VTEX'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'return_merchant_name_on_simulation_vtex')->delete('settings');
	}
};