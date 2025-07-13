<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'Script/FixVariationProduct')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Corrige variações duplicadas",
                'event_type' => '71',
                'start' => '2024-11-22 00:30:00',
                'end' => '2024-11-22 23:59:59',
                'module_path' => 'Script/FixVariationProduct',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
    }

	public function down()	{
        $this->db->delete('calendar_events', array('module_path' => 'Script/FixVariationProduct'));
	}
};