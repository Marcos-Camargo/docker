<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if($this->dbforge->register_exists('settings', 'name', 'fin_579_nova_coluna_valor_antecipado') == 0)
        {
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('fin_579_nova_coluna_valor_antecipado', 'Libera a coluna de \"valor antecipado\" na conciliacao', '2', '1');");
        }
	}

	public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'fin_579_nova_coluna_valor_antecipado';");
	}
};