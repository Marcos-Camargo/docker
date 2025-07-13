<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'client_id_magalu')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "client_id_magalu",
                'value' => '',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Client ID Magalu',
                'description' => 'Client ID Magalu'
            ));
        }

        if ($this->db->where('name', 'client_secret_magalu')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "client_secret_magalu",
                'value' => '',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Client Secret Magalu',
                'description' => 'Client Secret Magalu'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'client_id_magalu')->delete('settings');
        $this->db->where('name', 'client_secret_magalu')->delete('settings');
	}
};