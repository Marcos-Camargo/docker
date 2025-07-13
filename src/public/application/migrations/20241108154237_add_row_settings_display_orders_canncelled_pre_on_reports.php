<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'display_pre_canceled_orders_in_financial_reports')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "display_pre_canceled_orders_in_financial_reports",
                'value' => '1',
                'status' => 1,
                'user_id' => 1,
                'setting_category_id' => 3,
                'friendly_name' => 'Exibir pedidos no status cancelado pré nas telas de relatórios',
                'description' => 'Caso desativado, os pedidos com o status cancelado pré não serão exibidos nas telas de relatórios nem planilhas'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'display_pre_canceled_orders_in_financial_reports')->delete('settings');
	}
};