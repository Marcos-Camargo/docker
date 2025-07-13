<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("DELETE FROM errors_transformation WHERE status = ?", array(2));

	}

	public function down()	{
	}
};