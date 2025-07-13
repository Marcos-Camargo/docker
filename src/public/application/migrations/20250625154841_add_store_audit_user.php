<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		if (!$this->dbforge->column_exists("audit_user", "stores")) {
            $this->db->query("ALTER TABLE `stores` ADD COLUMN `audit_user` INT;");
        }
	}

	public function down()	{
		if ($this->dbforge->column_exists("audit_user", "stores")) {
            $this->dbforge->drop_column("stores", "audit_user");
        }
	}
};