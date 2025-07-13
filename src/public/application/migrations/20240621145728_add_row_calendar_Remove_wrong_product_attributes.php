<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'Automation/Fix/RemoveWrongProductAttributes')->where('module_method', 'run')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Corrige atributos que não deveriam existir nos produtos",
                'event_type' => '71',
                'start' => '2024-06-21 03:00:00',
                'end' => '2024-06-28 23:59:59',
                'module_path' => 'Automation/Fix/RemoveWrongProductAttributes',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
    }

	public function down()	{
        $this->db->delete('calendar_events', array('module_path' => 'Automation/Fix/RemoveWrongProductAttributes'));
	}
};