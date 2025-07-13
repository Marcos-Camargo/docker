<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'validate_completed_sku_marketplace')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "validate_completed_sku_marketplace",
                'value' => 'Exibe filtro de produtos com sku marketplace preenchido e botão para preenchimento de sku marketplace',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Validar sku marketplace completo',
                'description' => 'Exibe na tela de curadoria o filtro de produtos com sku marketplace preenchido e exibirá o botão para preenchimento de sku marketplace na tela de curadoria'

            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'validate_completed_sku_marketplace')->delete('settings');
	}
};