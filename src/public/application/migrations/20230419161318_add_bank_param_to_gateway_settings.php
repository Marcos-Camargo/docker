<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		if (!$this->dbforge->register_exists('payment_gateway_settings', 'name', 'banks_with_zero_fee'))
		{
			$this->db->query("INSERT INTO payment_gateway_settings (name, value, gateway_id) VALUES ('banks_with_zero_fee', 'Bradesco;', '2');");
		}
	}

	public function down()
	{
		$this->dbforge->drop_column('payment_gateway_settings', 'banks_with_zero_fee');
	}
};