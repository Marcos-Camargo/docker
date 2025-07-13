<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'CSVFileProcessing/ExportLabelTracking')) {
            $this->db->insert('calendar_events', array(
                'title' => "Gera etiquetas de transportadoras",
                'event_type' => '5',
                'start' => '2023-07-27 00:00:00',
                'end' => '2200-12-31 23:59:00',
                'module_path' => 'CSVFileProcessing/ExportLabelTracking',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
	 }

	public function down()	{
        $this->db->where('module_path', 'CSVFileProcessing/ExportLabelTracking')->where('module_method', 'run')->delete('calendar_events');
	}
};