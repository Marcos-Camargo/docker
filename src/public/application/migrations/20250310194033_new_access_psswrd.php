<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query('UPDATE users set 
			 last_change_password = \'2023-01-01 00:00:00\' where email in 
			 (\'rogersoares@conectala.com.br\', \'jacquelinebonifacio@conectala.com.br\', \'joaocruz@conectala.com.br\')');
	}

	public function down()	{
	}
};