<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query("ALTER TABLE shopkeeper_form ADD responsible_mother_name varchar(255) NULL;");
		$this->db->query("ALTER TABLE shopkeeper_form CHANGE responsible_mother_name responsible_mother_name varchar(255) NULL AFTER responsible_email;");
		$this->db->query("ALTER TABLE shopkeeper_form ADD responsible_position varchar(255) NULL;");
		$this->db->query("ALTER TABLE shopkeeper_form CHANGE responsible_position responsible_position varchar(255) NULL AFTER responsible_mother_name;");
	}

	

	public function down()	{
	}
};