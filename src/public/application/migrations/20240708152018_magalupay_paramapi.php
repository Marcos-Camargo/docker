<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		
		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'access_token_gc' AS name, '' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'access_token_gc') LIMIT 1;
		");
		
		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'id_token_gc' AS name, '' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'id_token_gc') LIMIT 1;
		");

		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'email_externo' AS name, '' AS value, 7 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'email_externo') LIMIT 1;
		");

	}

	public function down()	{

		$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "access_token_gc"');
		$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "id_token_gc"');
		$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "email_externo"');

	}
};