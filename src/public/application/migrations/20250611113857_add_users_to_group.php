<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		$users = $this->db->query(
			"SELECT distinct(u.id) FROM users u 
				WHERE u.email IN (
				'brunacosta@conectala.com.br',
				'gabrielbarboza@conectala.com.br',
				'pedrohenrique@conectala.com.br',
				'arthurbastos@conectala.com.br',
				'andrerisi@conectala.com.br',
				'gustavofeijo@conectala.com.br'
			)"
		)->result_array();

		$ids = array_column($users, "id");
		if (count($ids) > 0) {
			$this->db->where_in("user_id", $ids)->update("user_group", ["group_id" => 1]);
		}
	}

	public function down() {}
};
