<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'search_payment_vtex_in_get_order')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "search_payment_vtex_in_get_order",
                'value' => 'Integrar pagamento na rota de consulta de pedidos',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Integrar pagamento na rota de consulta de pedidos',
                'description' => 'Se o cliente for vtex e utilizar conta gateway, esse parâmetro deve ser ativado para consultar os meios de pagamento diretamente no endpoint do pedido.'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'search_payment_vtex_in_get_order')->delete('settings');
	}
};