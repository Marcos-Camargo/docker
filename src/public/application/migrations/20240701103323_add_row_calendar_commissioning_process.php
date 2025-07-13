<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {

        if ($this->db->where('module_path', 'Marketplace/CommissioningProcess')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Atualizar dados de comissão dos pedidos",
                'event_type' => '10',
                'start' => '2024-07-01 08:00:00',
                'end' => '2200-12-31 19:59:59',
                'module_path' => 'Marketplace/CommissioningProcess',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
    }

	public function down()	{
        $this->db->delete('calendar_events', array('module_path' => 'Marketplace/CommissioningProcess'));
	}
};