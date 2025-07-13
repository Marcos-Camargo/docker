<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	
	public function up() {
		$this->db->query("ALTER TABLE `attribute_value` ADD commission_charges int(1) DEFAULT 1 NULL;");
		$this->db->query("ALTER TABLE `attribute_value` ADD default_reason int(1) DEFAULT 0 NULL;");
	}

	public function down()	{
	}
};