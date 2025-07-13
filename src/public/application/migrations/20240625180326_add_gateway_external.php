<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		if (!$this->dbforge->register_exists('payment_gateways', 'code', 'externo'))
		{
			$this->db->query("INSERT INTO payment_gateways (id, name, code) VALUES (7, 'Externo', 'externo');");
		}
	}

	public function down()
	{
		$this->db->query('DELETE FROM payment_gateways WHERE code like "externo";');
	}
};