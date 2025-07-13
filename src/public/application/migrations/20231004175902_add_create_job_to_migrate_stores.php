<?php

defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {

        $job = $this->db->where('module_path', 'Migrate/MigrateSeller')->get('calendar_events')->num_rows();

        if ($job == 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Inicia a migração de seller das lojas ",
                'event_type' => '60',
                'start' => '2022-04-10 03:00:00',
                'end' => '2200-12-31 23:59:00',
                'module_path' => 'Migrate/MigrateSeller',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
    }

    public function down()
    {
        $this->db->where('module_path', 'Migrate/MigrateSeller')->delete('calendar_events');
    }
};