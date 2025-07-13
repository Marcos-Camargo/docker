<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

  public function up()
  {
    $this->db->query('DELETE FROM settings WHERE name like "criacao_da_tela_de_campanha_para_lojista";');
  }

  public function down()
  {
    $this->db->query("INSERT INTO `settings` (`name`, `value`, `status`, `user_id`) VALUES ('criacao_da_tela_de_campanha_para_lojista', 'Ativa/Inativa a nova tela de campanha para sellers', '2', '1')");
  }
};
