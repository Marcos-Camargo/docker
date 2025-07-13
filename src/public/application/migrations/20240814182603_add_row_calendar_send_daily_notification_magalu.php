<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'Marketplace/External/Magalu')->where('module_method', 'run')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Envia notificação de pedidos cancelados diariamente",
                'event_type' => '71',
                'start' => '2024-08-14 01:00:00',
                'end' => '2200-12-31 23:59:59',
                'module_path' => 'Marketplace/External/Magalu',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
    }

	public function down()	{
        $this->db->delete('calendar_events', array('module_path' => 'Marketplace/External/Magalu'));
	}
};