<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'enable_debug_campaigns')) {
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('enable_debug_campaigns', 'Habilita debug nas telas de Campanhas v2', '2', '1');");
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'enable_debug_campaigns';");
    }
};