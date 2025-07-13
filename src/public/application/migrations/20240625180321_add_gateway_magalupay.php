<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		if (!$this->dbforge->register_exists('payment_gateways', 'code', 'magalupay'))
		{
			$this->db->query("INSERT INTO payment_gateways (id, name, code) VALUES (6, 'MagaluPay', 'magalupay');");
		}
	}

	public function down()
	{
		$this->db->query('DELETE FROM payment_gateways WHERE code like "magalupay";');
	}
};