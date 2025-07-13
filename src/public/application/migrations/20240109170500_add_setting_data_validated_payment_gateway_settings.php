<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'data_validated_pagarme_v4')->get('payment_gateway_settings')->num_rows() === 0) {
            $this->db->insert('payment_gateway_settings', array(
                'name' => "data_validated_pagarme_v4",
                'value' => 0,
                'gateway_id' => 2
            ));
        }
        if ($this->db->where('name', 'data_validated_pagarme_v5')->get('payment_gateway_settings')->num_rows() === 0) {
            $this->db->insert('payment_gateway_settings', array(
                'name' => "data_validated_pagarme_v5",
                'value' => 0,
                'gateway_id' => 2
            ));
        }
        if ($this->db->where('name', 'data_validated_getnet')->get('payment_gateway_settings')->num_rows() === 0) {
            $this->db->insert('payment_gateway_settings', array(
                'name' => "data_validated_getnet",
                'value' => 0,
                'gateway_id' => 1
            ));
        }
        if ($this->db->where('name', 'data_validated_moip')->get('payment_gateway_settings')->num_rows() === 0) {
            $this->db->insert('payment_gateway_settings', array(
                'name' => "data_validated_moip",
                'value' => 0,
                'gateway_id' => 4
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'data_validated_pagarme_v4')->delete('payment_gateway_settings');
        $this->db->where('name', 'data_validated_pagarme_v5')->delete('payment_gateway_settings');
        $this->db->where('name', 'data_validated_getnet')->delete('payment_gateway_settings');
        $this->db->where('name', 'data_validated_moip')->delete('payment_gateway_settings');
	}
};