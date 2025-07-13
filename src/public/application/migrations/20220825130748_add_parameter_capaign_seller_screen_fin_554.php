<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query("INSERT INTO `settings` (`name`, `value`, `status`, `user_id`) VALUES ('criacao_da_tela_de_campanha_para_lojista', 'Ativa/Inativa a nova tela de campanha para sellers', '2', '1')");
	}

	public function down()	{
        $this->db->query('DELETE FROM settings WHERE name = "criacao_da_tela_de_campanha_para_lojista"');
    }
};