<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'external_authentication_openid_connect')->get('settings')->num_rows() === 0) {

            // Se for conectala, entra como ativo.

            $this->db->insert('settings', array(
                'name' => "external_authentication_openid_connect",
                'value' => 'OpenIP Connect como external login type',
                'status' => 2,
                'user_id' => 1
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'external_authentication_openid_connect')->delete('settings');
	}
};