<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('name', 'unique_clifor_store')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "unique_clifor_store",
                'value' => 'Clifor deve ser único no cadastro da loja',
                'status' => 1,
                'user_id' => 1,
                'setting_category_id' => 6,
                'friendly_name' => 'Clifor único por loja',
                'description' => 'Clifor deve ser único no cadastro da loja'

            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'unique_clifor_store')->delete('settings');
	}
};