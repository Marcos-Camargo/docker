<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'flag_liberacao_repasse_conciliacao')) {
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('flag_liberacao_repasse_conciliacao', 'Habilita o botão para marcar uma conciliacao como paga', '2', '1');");
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'flag_liberacao_repasse_conciliacao';");
    }
};