<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        if ($this->db->where('name', 'ms_authenticator_client_id')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "ms_authenticator_client_id",
                'value' => 'microservices',
                'status' => 2,
                'user_id' => 1
            ));
        }

        if ($this->db->where('name', 'ms_authenticator_realm')->get('settings')->num_rows() === 0) {
            $sellerCenter = $this->db->get_where('settings', array('name' => 'sellercenter'))->row_array();
            $this->db->insert('settings', array(
                'name' => "ms_authenticator_realm",
                'value' => $sellerCenter['value'],
                'status' => 2,
                'user_id' => 1
            ));
        }

        if ($this->db->where('name', 'ms_authenticator_secret')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "ms_authenticator_secret",
                'value' => '',
                'status' => 2,
                'user_id' => 1
            ));
        }

        if ($this->db->where('name', 'ms_authenticator_url')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "ms_authenticator_url",
                'value' => 'https://keycloakdev.conectala.com.br',
                'status' => 2,
                'user_id' => 1
            ));
        }
    }

    public function down()
    {
        $this->db->where('name', 'ms_authenticator_client_id')->delete('settings');
        $this->db->where('name', 'ms_authenticator_realm')->delete('settings');
        $this->db->where('name', 'ms_authenticator_secret')->delete('settings');
        $this->db->where('name', 'ms_authenticator_url')->delete('settings');
    }
};