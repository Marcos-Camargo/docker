<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		if (!$this->dbforge->register_exists('billet_status', 'id', '33'))
		{
			$this->db->query("INSERT INTO billet_status (id, nome, ativo, tipo_status) VALUES (33, 'Devolução descontada mas não executada', 1, 'Repasse Seller');");
		}
	}

	public function down()
	{
		$this->db->where('id', 33)->delete('billet_status');
	}
};