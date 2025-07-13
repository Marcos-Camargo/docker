<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'show_marketplace_attributes_only_to_admin')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "show_marketplace_attributes_only_to_admin",
                'value' => '',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 6,
                'friendly_name' => 'Exibir atributos do marketplace apenas para administradores',
                'description' => 'Quando o parâmetro estiver ativo, os atributos configurados serão ocultados dos lojistas e ficarão visíveis apenas para administradores do sistema.'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'show_marketplace_attributes_only_to_admin')->delete('settings');
	}
};