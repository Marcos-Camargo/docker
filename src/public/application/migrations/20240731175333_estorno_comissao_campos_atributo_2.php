<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query("ALTER TABLE `attribute_value` ADD active int(1) DEFAULT 1;");
	}

	public function down()	{
	}
};