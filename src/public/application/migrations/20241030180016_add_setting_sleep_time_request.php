<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'max_sleep_request')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "max_sleep_request",
                'value' => 15,
                'status' => 1,
                'user_id' => 0,
                'setting_category_id' => 6,
                'friendly_name' => 'Tempo Sleep para Requisições',
                'description' => 'Este parâmetro definirá quantos segundos serão aguardados antes de chamar uma requisição outra vez.'
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'max_sleep_request')->delete('settings');
	}
};