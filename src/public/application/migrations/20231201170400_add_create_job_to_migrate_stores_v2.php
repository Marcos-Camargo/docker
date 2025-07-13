<?php

defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $this->db->where('module_path', 'Migrate/MigrateSeller')->update('calendar_events', array(
            'module_path' => 'MigrateSeller/MigrateSeller'
        ));
    }

    public function down()
    {
        $this->db->where('module_path', 'MigrateSeller/MigrateSeller')->update('calendar_events', array(
            'module_path' => 'Migrate/MigrateSeller'
        ));
    }
};