<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {

        if (!$this->dbforge->register_exists('payment_gateway_settings', 'name', 'allow_transfer_between_accounts')){
            $this->db->insert('payment_gateway_settings', array(
                'name' => "allow_transfer_between_accounts",
                'value' => '0',
                'gateway_id' => 2
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'allow_transfer_between_accounts')->delete('payment_gateway_settings');
	}
};