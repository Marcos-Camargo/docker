<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'retroativo_pedidos_wake')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "retroativo_pedidos_wake",
                'value' => 2,
                'status' => 1,
                'user_id' => 0,
                'setting_category_id' => 6,
                'friendly_name' => 'Data Retroativa Wake',
                'description' => 'Este parâmetro definirá em até quantos dias retroativos sobre a data atual a busca de pedidos irá buscar.'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'retroativo_pedidos_wake')->delete('settings');
	}
};