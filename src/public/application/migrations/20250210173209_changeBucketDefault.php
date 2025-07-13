<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		$this->db->query("ALTER TABLE `products` MODIFY `is_on_bucket` TINYINT(1) DEFAULT 1");
	}

	public function down()
	{
		$this->db->query("ALTER TABLE `products` MODIFY `is_on_bucket` TINYINT(1) DEFAULT 0");
	}
};
