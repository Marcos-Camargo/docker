<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->update('settings', array('status' => 2), array('name' => 'ignore_integration_inactive_store'));
    }

	public function down()	{
        $this->db->update('settings', array('status' => 1), array('name' => 'ignore_integration_inactive_store'));
	}
};