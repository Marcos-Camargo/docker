<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        if ($this->db->where('module_path', 'Marketplace/External/General')->where('module_method', 'runResendOrders')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Reenviar pedidos não integrados no integrador externo",
                'event_type' => '10',
                'start' => '2024-08-17 01:00:00',
                'end' => '2200-12-31 23:59:59',
                'module_path' => 'Marketplace/External/General',
                'module_method' => 'runResendOrders',
                'params' => 'null'
            ));
        }
        if ($this->db->where('module_path', 'Marketplace/External/General')->where('module_method', 'runResendWithError')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Reenviar pedidos com error no integrador externo",
                'event_type' => '71',
                'start' => '2024-08-17 02:00:00',
                'end' => '2200-12-31 23:59:59',
                'module_path' => 'Marketplace/External/General',
                'module_method' => 'runResendWithError',
                'params' => 'null'
            ));
        }
    }

    public function down()	{
        $this->db->where('module_path', 'Marketplace/External/General')->where('module_method', 'runResendOrders')->delete('calendar_events');
        $this->db->where('module_path', 'Marketplace/External/General')->where('module_method', 'runResendWithError')->delete('calendar_events');
    }
};