<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		
		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'tenant_id' AS name, '' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'tenant_id') LIMIT 1;
		");

	}

	public function down()	{

		$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "tenant_id"');

	}
};