<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'plataform_code_freterapido')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "plataform_code_freterapido",
                'value' => '',
                'status' => 1,
                'user_id' => 1,
                'setting_category_id' => 5,
                'friendly_name' => 'Token Plataform Code Frete Rapido.',
                'description' => 'Plataform Code do Frete Rapido utilizado para fazer cotações.'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'plataform_code_freterapido')->delete('settings');
	}
};