<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    
	public function up() {
		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'url_endpoint' AS name, '' AS value, 8 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'url_endpoint') LIMIT 1;
		");
		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'tuna_account' AS name, '' AS value, 8 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'tuna_account') LIMIT 1;
		");
		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'tuna_apptoken' AS name, '' AS value, 8 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'tuna_apptoken') LIMIT 1;
		");
	}

	public function down()	{
		$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "url_endpoint"');
		$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "tuna_account"');
		$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "tuna_apptoken"');
	}
};