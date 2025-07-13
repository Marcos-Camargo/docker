<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'allow_change_cancel_reason_penalty_to')) {
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`, friendly_name, `description`, `setting_category_id`) 
			VALUES ('allow_change_cancel_reason_penalty_to', 'Habilita/Desabilita o botão de editar cancelamento', '2', '1','Habilita/Desabilita o botão de editar cancelamento','Caso esteja ativado ele vai bloquear a edição do cancelamento, se estiver desativado em vai permitir a edição. Parâmetro criado para controlar o estorno de comissão.',3);");
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'allow_change_cancel_reason_penalty_to';");
    }
};