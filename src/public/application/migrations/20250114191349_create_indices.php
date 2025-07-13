<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query('ALTER TABLE products ADD INDEX(`store_id`)');
	}

	public function down()	{
	}
};