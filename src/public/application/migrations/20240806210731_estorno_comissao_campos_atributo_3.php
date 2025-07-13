<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'maximum_days_to_refund_comission')) {
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`, friendly_name, `description`, `setting_category_id`) 
			VALUES ('maximum_days_to_refund_comission', '90', '1', '1','Quantidade de dias máximos para reembolso de comissão','Parâmetro utilizado para calcular a quantidade de dias que o marketplace tem para aceitar um estorno de comissão em um pedido cancelado.',3);");
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'maximum_days_to_refund_comission';");
    }
};