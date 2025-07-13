<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        if ($this->dbforge->register_exists('settings', 'name', 'save_slow_queries_campaigns')) {
            $this->db->query("UPDATE settings SET status = 2 WHERE name = 'save_slow_queries_campaigns'");
        }
    }

    public function down()
    {
    }
};