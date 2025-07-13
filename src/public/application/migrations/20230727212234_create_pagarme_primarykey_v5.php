<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if ($this->db->where('name', 'primary_account_v5')->get('payment_gateway_settings')->num_rows() === 0) {
            $this->db->insert('payment_gateway_settings', array(
                'name' => "primary_account_v5",
                'value' => '',
                'gateway_id' => 2
            ));
        }
	}

	public function down()
    {
        $this->db->where('name', 'primary_account_v5')->delete('payment_gateway_settings');
	}
};