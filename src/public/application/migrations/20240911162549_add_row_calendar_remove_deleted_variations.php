<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'Automation/Fix/RemoveDeletedVariations')->where('module_method', 'run')->get('calendar_events')->num_rows() === 0) {
            $this->db->insert('calendar_events', array(
                'title' => "Corrige variações excluídas na publicação",
                'event_type' => '71',
                'start' => '2024-09-11 03:00:00',
                'end' => '2024-09-30 23:59:59',
                'module_path' => 'Automation/Fix/RemoveDeletedVariations',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
    }

	public function down()	{
        $this->db->delete('calendar_events', array('module_path' => 'Automation/Fix/RemoveDeletedVariations'));
	}
};