<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if(!$this->db->field_exists('moip_return_advanced_payments', 'settings'))
        {
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('moip_return_advanced_payments', 'Devolve valores em cartão na Moip que foram adiantados para o repasse', '2', '1');");
        }
	}

	public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'moip_return_advanced_payments';");
	}
};