<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

  public function up()
  {
    $this->db->query('DELETE FROM settings WHERE name like "fin_574_nova_coluna_valor_antecipado";');
    $this->db->query('DELETE FROM settings WHERE name like "fin_579_nova_coluna_valor_antecipado";');
  }

  public function down()
  {
    if($this->dbforge->register_exists('settings', 'name', 'fin_579_nova_coluna_valor_antecipado') == 0)
    {
        $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('fin_579_nova_coluna_valor_antecipado', 'Libera a coluna de \"valor antecipado\" na conciliacao', '2', '1');");
    }
    if($this->dbforge->register_exists('settings', 'name', 'fin_574_nova_coluna_valor_antecipado') == 0)
    {
        $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('fin_574_nova_coluna_valor_antecipado', 'Libera a coluna de \"valor antecipado\" na extrato', '2', '1');");
    }
  }
};
