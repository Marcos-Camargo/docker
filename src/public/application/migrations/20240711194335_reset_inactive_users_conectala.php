<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("
		update users 
					set active = 2
		where email in ('vanessafilon@conectala.com.br','mariafernandaribeiro@conectala.com.br','jessicapillar@conectala.com.br','jorgeramos@conectala.com.br');
		");

	} 

	public function down()	{
	}
};