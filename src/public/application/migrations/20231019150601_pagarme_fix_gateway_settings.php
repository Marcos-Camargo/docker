<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
        $this->db->query("UPDATE payment_gateway_settings set value = 'MKTP' WHERE name = 'external_id_prefix_v5'");
        $this->db->query("DELETE from payment_gateway_settings WHERE name = 'api_version'");

	}

	public function down()	{
	}
};