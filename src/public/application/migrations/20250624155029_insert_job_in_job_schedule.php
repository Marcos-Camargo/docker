<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $this->db->insert('job_schedule', [
            'module_path'             => 'Vtex/FixVtexProductSyncJob',
            'module_method'           => 'run',
            'params'                  => 'null',
            'status'                  => 0,
            'finished'                => 0,
            'date_start'              => date('Y-m-d H:i:s', strtotime('+3 minutes')),
            'date_end'                => null,
            'server_id'               => 0,
        ]);
    }

    public function down()
    {
        $this->db
            ->where('module_path', 'Vtex/FixVtexProductSyncJob')
            ->where('module_method', 'run')
            ->delete('job_schedule');
    }
};