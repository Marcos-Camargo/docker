<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        if (!$this->dbforge->register_exists('settings', 'name', 'customization_theme')){
            $this->db->query("INSERT INTO settings (name, value, status, user_id) VALUES ('customization_theme', 'Permite personalizar o tema', 0, 1);");
        }
    }
    public function down()	{
        $this->db->query('DELETE FROM settings WHERE name like "customization_theme";');
    }
};