<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if(!$this->dbforge->register_exists('settings', 'name', 'orders_precancelled_to_zero'))
        {
            $this->db->query("INSERT INTO `settings` (`name`, `value`, `status`, `user_id`) VALUES ('orders_precancelled_to_zero', 'Zera o Pagamento de pedidos Cancelamento Pré', '1', '1');");
        }
	}

	public function down()	
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'orders_precancelled_to_zero';");
	}
};