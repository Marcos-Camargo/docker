<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'max_percentual_auto_approve_products_campaign')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "max_percentual_auto_approve_products_campaign",
                'value' => '',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 3,
                'friendly_name' => 'Percentual máximo para auto aprovação dos produtos em campanhas',
                'description' => 'O valor configurado no parâmetro levará em consideração o valor máximo aceito de participação do marketplace no desconto em campanhas e irá aprovar automaticamente caso for inferior ou igual ao valor aqui informado.'
            ));
        }
        if ($this->db->where('name', 'min_percentual_auto_repprove_products_campaign')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "min_percentual_auto_repprove_products_campaign",
                'value' => '',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 3,
                'friendly_name' => 'Percentual mínimo para auto reprovação dos produtos em campanhas',
                'description' => 'O valor configurado no parâmetro levará em consideração o valor máximo aceito de participação do marketplace no desconto em campanhas e caso o valor exceda o configurado, o produto será automaticamente rejeitado.'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'max_percentual_auto_approve_products_campaign')->delete('settings');
        $this->db->where('name', 'min_percentual_auto_repprove_products_campaign')->delete('settings');
	}
};