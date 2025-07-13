<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'required_clifor')) {
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('required_clifor', 'Controla a obrigatoriedade do campo Clifor no cadastro/edição de lojas', '2', '1');");
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'required_clifor';");
    }
};