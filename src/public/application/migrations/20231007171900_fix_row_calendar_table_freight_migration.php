<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->update('calendar_events', array(
            'end' => '2023-10-08 04:00:00',
            'event_type' => 71
        ), array('module_path' => 'Logistic/TableFreightMigration'));
	 }

	public function down()	{
        $this->db->update('calendar_events', array(
            'end' => '2023-10-07 04:00:00',
            'event_type' => 74
        ), array('module_path' => 'Logistic/TableFreightMigration'));
	}
};