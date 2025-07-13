<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'max_attemp_request')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "max_attemp_request",
                'value' => 3,
                'status' => 1,
                'user_id' => 0,
                'setting_category_id' => 6,
                'friendly_name' => 'Maximo de Requisições',
                'description' => 'Este parâmetro definirá o numero maximo de requisições de uma chamada.'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'max_attemp_request')->delete('settings');
	}
};