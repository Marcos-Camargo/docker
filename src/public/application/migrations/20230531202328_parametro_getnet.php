<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->dbforge->register_exists('settings', 'name', 'desativa_parametro_idvtex_getnet'))
		{
			$this->db->query("INSERT INTO settings (name, value, status, user_id) VALUES ('desativa_parametro_idvtex_getnet', 'Se o parâmetro estiver ativo não envia para a Getnet o ID Vtex no Cadastro (Não ativar o parâmetro para sellercenter que seja VTEX)', '2', '0');");
		}

	}

	public function down()	{

		$this->db->query("delete from settings where name = 'desativa_parametro_idvtex_getnet';");

	}
};