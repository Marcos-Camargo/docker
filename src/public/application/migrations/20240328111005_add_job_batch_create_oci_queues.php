<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        $data = array(
            'title'         => 'Criar filas na OCI',
            'event_type'    => 10,
            'module_path'   => 'CreateOciQueues',
            'module_method' => 'run',
            'params'        => 'null',
            'start'         => '2022-11-28 23:59:00',
            'end'           => '2200-12-31 23:59:00',
            'alert_after'   => null,
        );

        $this->db->insert('calendar_events', $data);

    }

    public function down()	{
    }
};