<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->update('calendar_events', array(
            'alert_after' => 120,
            'event_type' => 60
        ), array('module_path' => 'FreteContratar'));
	}

	public function down()	{
	}
};