<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		/************************ API ONBOARDING ***********************************/

		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'url_autenticar_api_onboarding' AS name, 'https://keycloak-staging.luizalabs.com/' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'url_autenticar_api_onboarding') LIMIT 1;
		");

		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'url_access_api_onboarding' AS name, 'https://vai-dar-namoro.mgc-hml.mglu.io/' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'url_access_api_onboarding') LIMIT 1;
		");
	
		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'grant_type' AS name, '' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'grant_type') LIMIT 1;
		");

		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'client_secret' AS name, '' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'client_secret') LIMIT 1;
		");

		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'client_id' AS name, '' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'client_id') LIMIT 1;
		");
		
		/************************ API GESTÃO DE CARTEIRA ***********************************/

		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'url_autenticar_api_gestao_carteira' AS name, 'https://idpa-api-preprod.luizalabs.com/' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'url_autenticar_api_gestao_carteira') LIMIT 1;
		");

		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'url_access_getinfo_api_gestao_carteira' AS name, 'https://tohru-staging.luizalabs.com/' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'url_access_getinfo_api_gestao_carteira') LIMIT 1;
		");

		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'url_access_payment_api_gestao_carteira' AS name, 'https://uncle-chan-api-staging.luizalabs.com/' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'url_access_payment_api_gestao_carteira') LIMIT 1;
		");
		
		$this->db->query("
		  INSERT INTO payment_gateway_settings (name, value, gateway_id)
		  SELECT * FROM (SELECT 'client_id' AS name, '' AS value, 6 AS gateway_id) AS temp
		  WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'client_id') LIMIT 1;
		");
	
		$this->db->query("
			INSERT INTO payment_gateway_settings (name, value, gateway_id)
			SELECT * FROM (SELECT 'scope' AS name, '' AS value, 6 AS gateway_id) AS temp
			WHERE NOT EXISTS ( SELECT name FROM payment_gateway_settings WHERE name = 'scope') LIMIT 1;
		");

		
		}
	
		public function down()	{
			$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "url_autenticar_api_onboarding"');
			$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "url_access_api_onboarding"');
			$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "grant_type"');
			$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "client_secret"');
			$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "client_id"');
			$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "scope"');
			$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "url_autenticar_api_gestao_carteira"');
			$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "url_access_getinfo_api_gestao_carteira"');
			$this->db->query('DELETE FROM payment_gateway_settings WHERE name = "url_access_payment_api_gestao_carteira"');
		}
};