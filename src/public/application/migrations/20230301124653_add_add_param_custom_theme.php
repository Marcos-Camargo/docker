<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $this->db->where('name', 'customization_theme')->update('settings', ['status' => 2]);
    }
    public function down()
    {
        $this->db->where('name', 'customization_theme')->update('settings', ['status' => 1]);
    }
};