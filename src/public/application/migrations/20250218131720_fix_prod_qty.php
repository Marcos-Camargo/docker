<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('module_path', 'Automation/Fix/FixPrdQty')->get('calendar_events')->num_rows() === 0) {
			$new_date = new Datetime('tomorrow');
            $this->db->insert('calendar_events', array(
                'title' => "Corrige produtos com estoque incorreto.",
                'event_type' => '74',
                'start' => $new_date->format('Y-m-d H:i:s'),
                'end' => '2200-12-31 23:59:59',
                'module_path' => 'Automation/Fix/FixPrdQty',
                'module_method' => 'run',
                'params' => 'null'
            ));
        }
    }

	public function down()	{
        $this->db->delete('calendar_events', array('module_path' => 'Automation/Fix/FixPrdQty'));
	}
};