<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {

        if (!$this->dbforge->register_exists('settings', 'name', 'fixed_ip_api_url')){
            $this->db->insert('settings', array(
                'name'      => 'fixed_ip_api_url',
                'value'     => 'http://conectala-via.conectala.tec.br',
                'status'    => 1,
                'user_id'   => 1
            ));
        }
        if (!$this->dbforge->register_exists('settings', 'name', 'fixed_ip_token_url')){
            $this->db->insert('settings', array(
                'name'      => 'fixed_ip_token_url',
                'value'     => 'cdf26213a150dc3ecb610f18f6b38b46',
                'status'    => 1,
                'user_id'   => 1
            ));
        }
	}

	public function down()	{
        $this->db->where('name', 'fixed_ip_token_url')->delete('settings');
        $this->db->where('name', 'fixed_ip_api_url')->delete('settings');
	}
};