<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'allow_payment_antecipation_by_store')){
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`, `setting_category_id`, `friendly_name`, `description`) VALUES (
                'allow_payment_antecipation_by_store', 
                'Antecipação de Pagamento por Loja', 
                '2', 
                '1',
                '3',
                'Antecipação de Pagamento por Loja',     
                'Habilita configuração de antecipação de pagamento por loja.'
                )"
            );
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings where name = 'allow_payment_antecipation_by_store'");
    }
};