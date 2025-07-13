<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'ignore_integration_inactive_store')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "ignore_integration_inactive_store",
                'value' => 'Ignora se a loja está inativa para integração com a loja',
                'status' => 1,
                'user_id' => 1,
                'setting_category_id' => 6,
                'friendly_name' => 'Ignora se a loja está inativa para integração com a loja',
                'description' => 'Ignora se a loja está inativa para integração com a loja'
            ));
        }
    }

	public function down()	{
        $this->db->where('name', 'ignore_integration_inactive_store')->delete('settings');
	}
};