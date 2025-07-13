<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        if ($this->db->where('module_path', 'Marketplace/External/Fastshop')->where('module_method', 'runDownloadNfse')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Baixar NFSe do integrador externo Fastshop",
                'event_type' => '71',
                'start' => '2024-09-01 03:00:00',
                'end' => '2200-12-31 23:59:59',
                'module_path' => 'Marketplace/External/Fastshop',
                'module_method' => 'runDownloadNfse',
                'params' => 'null'
            ));
        }
    }

    public function down()	{
        $this->db->where('module_path', 'Marketplace/External/Fastshop')->where('module_method', 'runDownloadNfse')->delete('calendar_events');
    }
};