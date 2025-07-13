<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'create_seller_mosaico')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "create_seller_mosaico",
                'value' => 'Adiciona campos adicionais para criação de lojas Mosaico.',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 6,
                'friendly_name' => 'Adiciona campos adicionais para criação de lojas Mosaico.',
                'description' => 'Adiciona campos adicionais para criação de lojas Mosaico.'
            ));
        }
    }

	public function down()	{
        $this->db->where('name', 'create_seller_mosaico')->delete('settings');
	}
};;