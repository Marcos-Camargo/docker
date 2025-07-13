<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query("ALTER TABLE canceled_orders ADD commission_charges_attribute_value INT NULL;");
	}

	public function down()	{
	}
};