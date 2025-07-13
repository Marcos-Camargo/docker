<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'email' AS name, '' AS value, 7 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'email') LIMIT 1;
		");
	}

	public function down()	{
		$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "email"');
	}
};