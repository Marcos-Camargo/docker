<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query("ALTER TABLE `pickup_points` CHANGE COLUMN `complement` `complement` VARCHAR(255) NULL;");
	}

	public function down()	{
        $this->db->query("ALTER TABLE `pickup_points` CHANGE COLUMN `complement` `complement` VARCHAR(255) NOT NULL;");
	}
};
