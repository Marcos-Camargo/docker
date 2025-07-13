<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query("update users set active = 2 where email = 'daniellycosta@conectala.com.br';");
	}

	public function down()	{
	}
};