<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'buy_box')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "buy_box",
                'value' => "buy_box",
                'status' => 2,
                'user_id' => 0,
                'setting_category_id' => 6,
                'friendly_name' => 'Buy Box',
                'description' => 'Este parâmetro ativará o checkbox buy box na tela de Lojas.'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'buy_box')->delete('settings');
	}
};