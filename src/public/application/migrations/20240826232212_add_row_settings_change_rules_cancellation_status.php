<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'change_rules_cancellation_status')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "change_rules_cancellation_status",
                'value' => 'Alterar regra de status de cancelamento',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Alterar regra de status de cancelamento',
                'description' => 'O Status “Cancelado” e “Cancelado Pelo Seller” deve ser interpretado a partir do usuário que cancelou. Se o usuário for Admin → Status “Cancelado” Se o usuário foi Não-Admin → Status “Cancelado Pelo Seller”'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'change_rules_cancellation_status')->delete('settings');
	}
};