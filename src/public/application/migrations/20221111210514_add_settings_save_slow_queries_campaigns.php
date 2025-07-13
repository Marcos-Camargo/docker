<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'save_slow_queries_campaigns')) {
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('save_slow_queries_campaigns', 'Habilita salvamento de queries lentas nas campanhas', '2', '1');");
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'save_slow_queries_campaigns';");
    }
};