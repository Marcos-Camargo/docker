<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$user = $this->db->get_where('users', ['email' => 'carlosdubaj@conectala.com.br'])->row();
		if ($user) {
			$this->db->update(
				'users', 
				['active' => 2,],
				['email' => 'carlosdubaj@conectala.com.br']
			);
		}
	}

	public function down()	{
	}
};