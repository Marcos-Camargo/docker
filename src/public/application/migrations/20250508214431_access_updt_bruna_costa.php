<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		if ($this->db->get_where('users', ['email' => 'brunacosta@conectala.com.br'])->row()) {
			$this->db->update('users', 	
				[
					'firstname' => 'Bruna',
					'lastname' => 'Costa'
				],
				['email' => 'brunacosta@conectala.com.br']
			);
		}
	}

	public function down()	{
	}
};