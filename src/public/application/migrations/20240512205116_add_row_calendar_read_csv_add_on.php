<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'Automation/ImportCSVAddOn')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Ler os arquivos enviados para sincronizar o produtos Add-On",
                'event_type' => '10',
                'start' => '2024-05-12 03:00:00',
                'end' => '2023-12-31 23:59:59',
                'module_path' => 'Automation/ImportCSVAddOn',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
    }

	public function down()	{
        $this->db->delete('calendar_events', array('module_path' => 'Automation/ImportCSVAddOn'));
	}
};