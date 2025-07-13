<?php defined('BASEPATH') or exit('No direct script access allowed');
return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'youtube_tutorial_create_permission')) {
            $this->db->query("INSERT INTO settings (name, value, status, user_id, date_updated) VALUES('youtube_tutorial_create_permission', 'Url para embedar vídeo do youtube', 2, 1, CURRENT_TIMESTAMP);");
        }
    }
    public function down()
    {
        $this->db->query('DELETE FROM settings WHERE name like "youtube_tutorial_create_permission";');
    }
};
