<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if ($this->db->where('module_path', 'Automation/ImportCSVSyncSkusellerSkumkt')->where('module_method', 'run')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Processar os arquivo do de para de sku do seller x sku do marketplace",
                'event_type' => '5',
                'start' => '2024-06-09 08:00:00',
                'end' => '2200-12-31 19:59:59',
                'module_path' => 'Automation/ImportCSVSyncSkusellerSkumkt',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
	}

	public function down()	{
        $this->db->where('module_path', 'Automation/ImportCSVSyncSkusellerSkumkt')->delete('calendar_events');

	}
};