<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('module_path', 'Automation/ImportCSVGroupSimpleSku')->where('module_method', 'run')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Processar os arquivo de agrupamento de sku",
                'event_type' => '5',
                'start' => '2024-06-20 08:00:00',
                'end' => '2200-12-31 19:59:59',
                'module_path' => 'Automation/ImportCSVGroupSimpleSku',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
	}

	public function down()	{
        $this->db->where('module_path', 'Automation/ImportCSVGroupSimpleSku')->delete('calendar_events');
	}
};