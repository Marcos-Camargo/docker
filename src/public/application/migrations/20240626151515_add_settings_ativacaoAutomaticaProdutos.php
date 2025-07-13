<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'ativacao_automatica_produtos')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "ativacao_automatica_produtos",
                'value' => "ativacao_automatica_produtos",
                'status' => 2,
                'user_id' => 0,
                'setting_category_id' => 6,
                'friendly_name' => 'Ativacao Automatica Produtos',
                'description' => 'Este parâmetro ativará o checkbox Ativacao Automatica Produtos na tela de Lojas.'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'ativacao_automatica_produtos')->delete('settings');
	}
};