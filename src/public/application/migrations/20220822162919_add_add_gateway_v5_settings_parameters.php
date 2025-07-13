<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

    $this->db->query("
      INSERT INTO payment_gateway_settings (name, value, gateway_id)
      SELECT * FROM (SELECT 'url_api_v5' AS name, 'https://api.pagar.me/core/v5/' AS value, 2 AS gateway_id) AS temp
      WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'url_api_v5') LIMIT 1;
    ");

    $this->db->query("
      INSERT INTO payment_gateway_settings (name, value, gateway_id)
      SELECT * FROM (SELECT 'app_key_v5' AS name, '' AS value, 2 AS gateway_id) AS temp
      WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'app_key_v5') LIMIT 1;
    ");

    $this->db->query("
      INSERT INTO payment_gateway_settings (name, value, gateway_id)
      SELECT * FROM (SELECT 'external_id_prefix_v5' AS name, 'MKTP' AS value, 2 AS gateway_id) AS temp
      WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'external_id_prefix_v5') LIMIT 1;
    ");

    $this->db->query("
      INSERT INTO payment_gateway_settings (name, value, gateway_id)
      SELECT * FROM (SELECT 'pagarme_subaccounts_api_version' AS name, '' AS value, 2 AS gateway_id) AS temp
      WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'pagarme_subaccounts_api_version') LIMIT 1;
    ");
    
	}

	public function down()	{
        $this->db->query('DELETE FROM payment_gateway_settings WHERE name = "url_api_v5"');
        $this->db->query('DELETE FROM payment_gateway_settings WHERE name = "app_key_v5"');
        $this->db->query('DELETE FROM payment_gateway_settings WHERE name = "external_id_prefix_v5"');
        $this->db->query('DELETE FROM payment_gateway_settings WHERE name = "pagarme_subaccounts_api_version"');
    }
};