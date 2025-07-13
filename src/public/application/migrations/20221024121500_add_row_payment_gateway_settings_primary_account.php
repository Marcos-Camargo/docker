<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {

        if (!$this->dbforge->register_exists('payment_gateway_settings', 'name', 'primary_account')){
            $this->db->insert('payment_gateway_settings', array(
                'name' => "primary_account",
                'value' => '',
                'gateway_id' => 2
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'primary_account')->delete('payment_gateway_settings');
	}
};