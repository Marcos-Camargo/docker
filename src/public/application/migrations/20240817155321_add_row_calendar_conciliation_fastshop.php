<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        if ($this->db->where('module_path', 'Marketplace/External/Fastshop')->where('module_method', 'runConciliacao')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Enviar conciliação para a Fastshop",
                'event_type' => '71',
                'start' => '2024-08-17 02:00:00',
                'end' => '2200-12-31 23:59:59',
                'module_path' => 'Marketplace/External/Fastshop',
                'module_method' => 'runConciliacao',
                'params' => 'null'
            ));
        }
    }

    public function down()	{
        $this->db->where('module_path', 'Marketplace/External/Fastshop')->where('module_method', 'runConciliacao')->delete('calendar_events');
    }
};