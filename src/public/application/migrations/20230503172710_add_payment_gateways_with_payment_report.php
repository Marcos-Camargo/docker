<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'payment_gateways_with_payment_report'))
		{
			$this->db->query("INSERT INTO settings (name, value, status, user_id) VALUES ('payment_gateways_with_payment_report', '2;4', '1', '1');");
		}
	}

	public function down()
	{
		$this->dbforge->drop_column('settings', 'payment_gateways_with_payment_report');
	}
};