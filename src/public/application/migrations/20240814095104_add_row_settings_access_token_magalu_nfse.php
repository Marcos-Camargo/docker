<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'access_token_magalu_nfse')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "access_token_magalu_nfse",
                'value' => '',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Access token Magalu NFSe',
                'description' => 'Access token Magalu NFSe'
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'access_token_magalu_nfse')->delete('settings');
	}
};