<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'days_to_refund_value_tuna')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "days_to_refund_value_tuna",
                'value' => '90',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 3,
                'friendly_name' => 'Dias para devolver valor de pedido Tuna',
                'description' => 'Quando ativo, o parâmetro irá definir até quantos dias a partir de hoje poderão ter valores estornados para o comprador a partir da data de criação do pedido.'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'days_to_refund_value_tuna')->delete('settings');
	}
};