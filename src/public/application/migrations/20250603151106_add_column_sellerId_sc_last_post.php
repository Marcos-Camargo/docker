<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		if (!$this->dbforge->column_exists("seller_id", "sellercenter_last_post")) {
            $this->db->query("ALTER TABLE `sellercenter_last_post` ADD COLUMN `seller_id` VARCHAR(255);");
        }
	}

	public function down()	{
		if ($this->dbforge->column_exists("seller_id", "sellercenter_last_post")) {
            $this->dbforge->drop_column("sellercenter_last_post", "seller_id");
        }
	}
};